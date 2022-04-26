<?php
namespace fox;

/**
 * 
 * Class fox/mailAccount
 * @property-read mixed $id
 * @property mixed $address
 * @property mixed $rxServer
 * @property mixed $rxProto
 * @property mixed $rxSSL
 * @property mixed $rxPort
 * @property mixed $rxFolder
 * @property mixed $rxArchiveFolder     #folder, where moved processed messages. If empty = no move, if 'trash' - message will be deleted
 * @property mixed $txServer
 * @property mixed $txProto
 * @property mixed $txSSL
 * @property mixed $txPort
 * @property mixed $login
 * @property mixed $password
 * @property mixed $rxLogin
 * @property mixed $rxPassword
 * @property mixed $rxLogin
 * @property mixed $rxPassword
 * 
 **/

class mailAccount extends baseClass {
    protected $id;
    public $address;
    public $rxServer;
    public $rxProto='imap';
    public bool $rxSSL=true;
    public int $rxPort=993;
    public $txServer;
    public $txProto='smtp';
    public bool $txSSL=true;
    public $txPort=465;
    public $login;
    protected $__password;
    
    public $module;
    public $rxFolder;
    public $rxArchiveFolder;
    public bool $default=false;
    public bool $deleted=false;
    
    public static $deletedFieldName = "deleted";
    
    public static $sqlTable = 'tblMailAccounts';
    
    protected static $sqlColumns = [
        "address"=>["type"=>"VARCHAR(255)","nullable"=>false],
        "rxServer"=>["type"=>"VARCHAR(255)", "nullable"=>true],
        "rxProto"=>["type"=>"VARCHAR(255)", "nullable"=>true],
        "txServer"=>["type"=>"VARCHAR(255)", "nullable"=>true],
        "txProto"=>["type"=>"VARCHAR(255)", "nullable"=>true],
        "login"=>["type"=>"VARCHAR(255)", "nullable"=>false],
        "password"=>["type"=>"TEXT", "nullable"=>true],
        "module"=>["type"=>"VARCHAR(255)", "nullable"=>true],
        "rxFolder"=>["type"=>"VARCHAR(255)", "nullable"=>true],
        "rxArchiveFolder"=>["type"=>"VARCHAR(255)", "nullable"=>true],
        
    ];
    
    public function __get($key) {
        switch ($key) {
            case "password": return xcrypt::decrypt($this->__password);
            case "rxLogin": return $this->login;
            case "rxPassword": return xcrypt::decrypt($this->__password);
            case "rxLogin": return $this->login;
            case "rxPassword": return xcrypt::decrypt($this->__password);
            default: return parent::__get($key);
        }
    }
    
    public function __set($key, $val) {
        switch ($key) {
            case "password": $this->__password = xcrypt::encrypt($val); break;
            default: parent::__set($key, $val);
        }
    }
    
    public function connect() {
        return new mailClient($this);
    }
    
    protected function validateSave() {
        if (empty($this->login) || empty($this->__password) || empty($this->address)) { return false;}
        
        return true;
    }
  
       
    public static function getDefaultAccount(&$sql=null) {
        $ref = new static();
        $sql = $ref->getSql();
        $rv = $sql->quickExec1Line("select * from `tblMailAccounts` where `default` = 1 limit 1");
        if ($rv) {
            return new static($rv);
        } else {return null;}
    }
    
}