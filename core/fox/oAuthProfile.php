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
    protected $hash;
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
}
?>