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
            $this->mcd->set($this->prefix . "." . $key, xcrypt::encrypt(json_encode($val)), $TTL);
        } else {
            $this->mcd->set($this->prefix . "." . $key, json_encode($val), $TTL);
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
        if ($xval==null || $xval=="null") { return null; }
        $rv= json_decode($this->mcd->get($this->prefix . "." . $key), $array);
        if ($rv !== null) { return $rv; }
        return json_decode(xcrypt::decrypt($this->mcd->get($this->prefix . "." . $key)), $array);
    }
    
    public function del($key) {
        try {
            $this->pConnCheck();
        } catch (\Exception $e) {
            trigger_error($e->getMessage());
            return false;
        }

        $this->mcd->delete($this->prefix . "." . $key);
            
    }
}

?>