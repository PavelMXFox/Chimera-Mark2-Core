<?php namespace fox;

use fox\meta\settings;
use stdClass;

/**
 *
 * Class fox\file
 *
 * @copyright MX STAR LLC 2018-2022
 * @version 4.0.0
 * @author Pavel Dmitriev
 * @license GPLv3
 *        
 **/

class file extends baseClass implements externalCallable {
    protected $id;
    public $uuid;
    public $fileName;
    public $module;
    public $class;
    public $refId;
    public $public=false;
    public $private=false;
    public $ownerId=null;
    public time $uploadStamp;
    public time $expireStamp;

    protected $__data;
    
    public static $sqlTable="tblFiles";

    # file access token expires in 30 seconds
    const fileTokenTTL=30; 
    const defaultBucket="files";
    
    protected function __xConstruct() {
        $this->expireStamp=new time();
        $this->uploadStamp=time::current();
    }
    
    public static $sqlColumns = [
        "uuid" => [
            "type" => "CHAR(36)",
            "index"=>"UNIQUE",
        ],
        "fileName" => [
            "type" => "VARCHAR(255)",
        ],
        "module" => [
            "type" => "VARCHAR(255)",
            "index" => "INDEX"
        ],
        "class" => [
            "type" => "VARCHAR(255)",
            "index" => "INDEX"
        ],
        "refId" => [
            "type" => "VARCHAR(255)",
        ],

        "ownerId" => [
            "type" => "INT",
            "index" => "INDEX",
            "nullable"=>true
        ],
        "expireStamp" => [
            "type" => "DATETIME",
            "index" => "INDEX",
            "nullable"=>true
        ],
        "uploadStamp" => [
            "type" => "DATETIME",
            "index" => "INDEX",
            "nullable"=>true,
        ],
    ];


    protected function validateSave()
    {
        if (empty($this->uuid)) { $this->uuid = common::getUUID(); }
        return true;
    }

    protected static function xSearch($where, $pattern, ?array $options, sql $sql) {
        
        $xWhere="";
        if (!empty($options["uuid"])) {
            $xWhere .= ($xWhere?" AND ":"")." `uuid` = '".common::clearInput($options["uuid"],"0-9a-z-")."'";
        }

        if (!empty($options["instance"])) {
            $xWhere .= ($xWhere?" AND ":"")." `module` = '".common::clearInput($options["instance"])."'";
        }

        if (!empty($options["class"])) {
            $xWhere .= ($xWhere?" AND ":"")." `class` = '".common::clearInput($options["class"],'0-9A-Za-z_\.\-\\\/')."'";
        }

        if (!empty($options["refId"])) {
            $xWhere .= ($xWhere?" AND ":"")." `refId` = '".common::clearInput($options["refId"])."'";
        }


        if ($xWhere) {
            if ($where) {
                $where = "(".$where.") AND (".$xWhere.")";
            } else {
                $where = $xWhere;
            }
        }
        return ["where"=>$where, "join"=>null];
    }

    protected static function prepareFileUpload($data, $fileName, object $ref, string $instance=null, user $owner=null, $ttl=null) {
        $f=new static();
        $f->fileName=$fileName;
        $f->module=$instance;
        if ($owner) { $f->ownerId=$owner->id; }
        if ($ttl) { $f->expireStamp=time::current()->addSec($ttl); }
        $f->class = $ref::class;
        $f->uuid=common::getUUID();
        return $f;
    }

    public static function directUpload($data, $fileName, object $ref, string $instance=null, user $owner=null, $ttl=null) {
        $f=static::prepareFileUpload($data, $fileName, $ref, $instance, $owner, $ttl);
        $s3=new s3client();
        if (!$s3->headBucket(static::defaultBucket)) {
            $s3->createBucket(static::defaultBucket);
        }
        $s3->putObject(static::defaultBucket,$f->uuid, $data);
        $f->save();
        return $f;
    }

    public static function getByUUID(string $uuid) : file {
        $files = file::search(options: ["uuid"=>$uuid]);
        if ($files->result) {
            return array_shift($files->result);
        } else {
            throw new foxException("File not found", 404);
        }
    }

    public function getContent() {
        if ($this->uuid) {
            $s3=new s3client();
            return $s3->getObject(static::defaultBucket, $this->uuid);
        }
    }

    public function getDownloadToken() {
        if (empty($this->id)) {
            throw new foxException("Empty fileId not allowed here");
        };
        $token=common::genPasswd(32);

        $c=new cache();
        $c->set('fileDnldToken-'.$token,$this,static::fileTokenTTL);
        return $token;
    }

    public static function getByToken($token) {
        $c=new cache();
        $ftile=$c->get('fileDnldToken-'.$token,true);
        if ($ftile) {
            $file = new static($ftile);
            $c->del('fileDnldToken-'.$token);
            return $file;
        } else {
            throw new foxException("Invalid token",404);
        }
    }

    public static function getUploadToken($data, $fileName, object $ref, string $instance=null, user $owner=null) {
        $f=static::prepareFileUpload($data, $fileName, $ref, $instance, $owner);
        $token=common::genPasswd(32);
        $c=new cache();
        $c->set('fileUpldToken-'.$token,$f,static::fileTokenTTL);
        return $token;
    }

    #### REST API

    public static function API_UnAuth_GET(request $request) {
        if (!empty($request->parameters))  throw new foxException("Invalid request", 400);
        $file2=file::getByToken($request->function);
        header_remove('Content-Type');
        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename='.$file2->fileName);
        print($file2->getContent());
        exit;
    }

}



?>