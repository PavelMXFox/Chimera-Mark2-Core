<?php
namespace fox;

/**
 *
 * Class fox\cache
 *
 * @copyright MX STAR LLC 2021
 * @version 4.0.0
 * @author Pavel Dmitriev
 * @license GPLv3
 *        
 */
class cache
{

    protected const chunkSize=1024000;
    
    public ?\Memcached $mcd = null;

    protected $prefix = null;

    public static function connect($host = null, $port = 11211)
    {
        $mcd = new self($host, $port);
        if ($mcd->connCheck()) {
            return $mcd;
        } else {
            return false;
        }
    }

    public function __construct($host = null, $port = null)
    {
        if (! class_exists("Memcached")) {
            $this->mcd = null;
            return;
        }

        if (empty($host) && ! empty(config::get("cacheHost"))) {
            $this->mcd = new \Memcached();
            $host = config::get("cacheHost");
            if (empty($port)) {
                $port = config::get("cachePort");
            }
            
            if (empty($port)) {
                $port = 11211;
            }
        }

        if (gettype($host) == 'array') {
            $this->mcd = new \Memcached();
            $this->mcd->addServers($host);
        } elseif (gettype($host) == "string") {
            $this->mcd = new \Memcached();
            $this->mcd->addServer($host, $port);
        } else {
            $this->mcd = null;
            return;
        }
        $this->prefix = str_pad(strtolower(dechex(crc32(config::get("sitePrefix")))), 8, "0", STR_PAD_LEFT);
    }

    protected function pConnCheck()
    {
        if (! $this->connCheck()) {
            throw new \Exception("MEMCACHED Not connected!");
        }
    }

    public function connCheck()
    {
        if (empty($this->mcd)) {
            return false;
        }
        return $this->mcd->getVersion() !== false;
    }

    public function set($key, $val, $TTL = 300,$encrypt=false)
    {
        try {
            $this->pConnCheck();
        } catch (\Exception $e) {
            trigger_error($e->getMessage());
            return false;
        }
        if ($encrypt) {
            $str=xcrypt::encrypt(json_encode($val));
        } else {
            $str=json_encode($val);
        }
        
        $this->del($key);
        
        $len=strlen($str);
        if ($len <= static::chunkSize) {
            $this->mcd->set($this->prefix . "." . $key, $str, $TTL);
        } else {
            # multipart
            $md5=md5($str);
            $chunks=ceil($len/static::chunkSize);
            
            $this->mcd->set($this->prefix . "." . $key.".MPX00", json_encode(["len"=>$len, "md5"=>$md5,"chunks"=>$chunks]), $TTL);
            for ($i = 0; $i<$chunks; $i++) {
                $xzval=substr($str,$i*static::chunkSize,static::chunkSize);
                $this->mcd->set($this->prefix . "." . $key.".MPX0".($i+1), $xzval, $TTL);                
            }
        }
    }

    public function get($key, $array = false)
    {
        try {
            $this->pConnCheck();
        } catch (\Exception $e) {
            trigger_error($e->getMessage());
            return null;
        }
        
        
        $xval=$this->mcd->get($this->prefix . "." . $key);
        if ($xval==null) {
            $idx=$this->mcd->get($this->prefix . "." . $key.".MPX00");
            if ($idx) { 
                $idx=json_decode($idx);
                if ($idx) {
                    $xval="";
                    for ($i=1; $i <=$idx->chunks; $i++) {
                        $xzval=$this->mcd->get($this->prefix . "." . $key.".MPX0".$i);
                        $xval .= $xzval;
                    }
                    $xlen=strlen($xval);
                    $xmd5=md5($xval);
                    
                    if ($xlen!=$idx->len || $xmd5!=$idx->md5) {
                        $this->del($key);
                        $xval=null;
                    }
                }
            }
        }
        if ($xval==null || $xval=="null") { return null; }
        
        $rv= json_decode($xval, $array);
        if ($rv !== null) { return $rv; }
        return json_decode(xcrypt::decrypt($xval), $array);
    }
    
    public function del($key) {
        try {
            $this->pConnCheck();
        } catch (\Exception $e) {
            trigger_error($e->getMessage());
            return false;
        }

        $this->mcd->delete($this->prefix . "." . $key);
        
        $idx=$this->mcd->get($this->prefix . "." . $key.".MPX00");
        if ($idx) {
            $idx=json_decode($idx);
            if ($idx) {
                $this->mcd->delete($this->prefix . "." . $key.".MPX00");
                for ($i=1; $i <=$idx->chunks; $i++) {
                    $this->mcd->delete($this->prefix . "." . $key.".MPX0".$i);
                }
            }
        }
            
    }
}

?>