<?php namespace fox;

/**
 *
 * Class fox\oAuthProfile
 *
 * @copyright MX STAR LLC 2018-2022
 * @version 4.0.0
 * @author Pavel Dmitriev
 * @license GPLv3
 *
 **/

class oAuthProfile extends baseClass implements externalCallable {
    protected $id;
    public $name;
    public $hash;
    public $url;
    public $clientId;
    protected $__clientKey;
    public $config;
    public bool $enabled=true;
    public bool $deleted=false;
    
    public static $deletedFieldName = "deleted";
    
    public static $sqlTable="tblOAuthProfiles";
    
    protected static $sqlColumns = [
        "name"=>["type"=>"VARCHAR(255)","nullable"=>false],
        "url"=>["type"=>"VARCHAR(255)", "nullable"=>true],
        "clientId"=>["type"=>"TEXT","nullable"=>false],
        "clientKey"=>["type"=>"TEXT","nullable"=>false],
        "config"=>["type"=>"TEXT","nullable"=>true],
        "enabled"=>["type"=>"INT", "default"=>1],
        "deleted"=>["type"=>"INT", "default"=>0],
        "hash"=>["type"=>"VARCHAR(255)"],
    ];
    
    protected function validateSave()
    {
        if (empty($this->hash)) {
            $this->hash=xcrypt::hash(json_encode($this));
        }
        return true;
    }
    
    public function __set($key, $val) {
        switch ($key) {
            case "clientKey":
                $this->__clientKey=$val;
                break;
            default:
                parent::__set($key, $val);
                break;
        }
    }
    
    public function getClient($redirectUrl, $scope=null) : oAuthClient {
        return new oAuthClient($this->url, $this->clientId, $this->__clientKey, $redirectUrl."/".$this->hash,$scope,$this->config);
    }
    
    public static function getByHash($hash) {
        $ref = new static();
        $sql = $ref->getSql();
        $res = $sql->quickExec1Line($ref->sqlSelectTemplate." where `i`.`hash` = '".common::clearInput($hash,"0-9a-z")."'");
        if ($res) {
            return new static($res);
        } else {
            throw new foxException("Invalid hash");
        }
    }
    
    public static function API_GET_list(request $request) {
        if (! $request->user->checkAccess("adminAuthMethods", "core")) {
            throw new foxException("Forbidden", 403);
        }
        return static::search()->result;
    }

    public static function APIX_GET_disable(request $request) {
        if (! $request->user->checkAccess("adminAuthMethods", "core")) {
            throw new foxException("Forbidden", 403);
        }
        $m=new static(common::clearInput($request->function,"0-9"));
        $m->enabled=false;
        $m->save();
        static::log($request->instance,__FUNCTION__, "OAuth profile ".$m->name." disabled.",$request->user,"oAuthProfile",$m->id,null,logEntry::sevInfo);
        
    }

    public static function APIX_GET_enable(request $request) {
        if (! $request->user->checkAccess("adminAuthMethods", "core")) {
            throw new foxException("Forbidden", 403);
        }
        $m=new static(common::clearInput($request->function,"0-9"));
        $m->enabled=true;
        $m->save();
        static::log($request->instance,__FUNCTION__, "OAuth profile ".$m->name." enabled.",$request->user,"oAuthProfile",$m->id,null,logEntry::sevInfo);
    }
    
    public static function API_GET(request $request) {
        if (! $request->user->checkAccess("adminAuthMethods", "core")) {
            throw new foxException("Forbidden", 403);
        }
        $m=new static(common::clearInput($request->function,"0-9"));
        return $m;
    }
    
    public static function API_DELETE(request $request) {
        if (! $request->user->checkAccess("adminAuthMethods", "core")) {
            throw new foxException("Forbidden", 403);
        }
        $m=new static(common::clearInput($request->function,"0-9"));
        $m->delete();
        static::log($request->instance,__FUNCTION__, "OAuth profile ".$m->name." deleted.",$request->user,"oAuthProfile",$m->id,null,logEntry::sevInfo);
    }
    
    public static function API_PUT(request $request) {
        if (! $request->user->checkAccess("adminAuthMethods", "core")) {
            throw new foxException("Forbidden", 403);
        }
        $m=new static();
        $m->name=common::clearInput($request->requestBody->name,"0-9A-Za-z._@-");
        $m->url=common::clearInput($request->requestBody->url,"0-9A-Za-z._:/-");
        $m->clientId=common::clearInput($request->requestBody->clientId,"0-9A-Za-z._:/-");
        $m->clientKey=common::clearInput($request->requestBody->clientKey,"0-9A-Za-z._@-");
        $m->config=$request->requestBody->config;
        $m->hash=common::clearInput($request->requestBody->hash,"0-9A-Za-z._@-");
        $m->save();
        static::log($request->instance,__FUNCTION__, "OAuth profile ".$m->name." created.",$request->user,"oAuthProfile",$m->id,null,logEntry::sevInfo);
        return $m;
    }
    
    public static function API_PATCH(request $request) {
        if (! $request->user->checkAccess("adminAuthMethods", "core")) {
            throw new foxException("Forbidden", 403);
        }
        $m=new static(common::clearInput($request->function,"0-9"));
        $m->name=common::clearInput($request->requestBody->name,"0-9A-Za-z._@-");
        $m->url=common::clearInput($request->requestBody->url,"0-9A-Za-z._:/-");
        if (!empty($request->requestBody->clientId)) { $m->clientId=common::clearInput($request->requestBody->clientId,"0-9A-Za-z._:/-"); }
        if (!empty($request->requestBody->clientKey)) { $m->clientKey=common::clearInput($request->requestBody->clientKey,"0-9A-Za-z._@-"); }
        $m->config=$request->requestBody->config;
        $m->hash=common::clearInput($request->requestBody->hash,"0-9A-Za-z._@-");
        $m->save();
        static::log($request->instance,__FUNCTION__, "OAuth profile ".$m->name." updated.",$request->user,"oAuthProfile",$m->id,null,logEntry::sevInfo,["changelog"=>$m->changelog]);
    }
}
?>