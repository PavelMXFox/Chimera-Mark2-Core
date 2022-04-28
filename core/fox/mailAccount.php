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
 * @property mixed $rxURL
 * @property mixed $txURL
 * 
 **/

class mailAccount extends baseClass implements externalCallable {
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
    
    const urlSchemas=[
      "rx"=>[
          "imap"=>["ssl"=>false,"port"=>143,"proto"=>"imap"],
          "imaps"=>["ssl"=>true,"port"=>993,"proto"=>"imap"],
      ],
      "tx"=>[
          "smtp"=>["ssl"=>false,"port"=>587,"proto"=>"smtp"],
          "smtps"=>["ssl"=>true,"port"=>465,"proto"=>"smtp"],
      ]
    ];
    
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
            case "txURL":
                $this->parceURL($val, "tx");
                break;
            case "rxURL":
                $this->parceURL($val, "rx");
                break;
            case "password": $this->__password = xcrypt::encrypt($val); break;
            default: parent::__set($key, $val);
        }
    }
    
    protected function parceURL($val, $dst) {
        if (!array_key_exists($dst, static::urlSchemas)) {
            foxException::throw("ERR","Invalid dst scheme",400,"UDSCH");
        }
        
        $schemaRef=static::urlSchemas[$dst];
        $ref=parse_url($val);
        if (!array_key_exists($ref["scheme"], $schemaRef)) {
            foxException::throw("ERR","Invalid URL scheme",400,"UUSCH");
        }
        $this->{$dst."SSL"}=$schemaRef[$ref["scheme"]]["ssl"];
        $this->{$dst."Port"}=empty($ref["port"])?$schemaRef[$ref["scheme"]]["port"]:$ref["port"];
        $this->{$dst."Server"}=$ref["host"];
        $this->{$dst."Proto"}=$schemaRef[$ref["scheme"]]["proto"];
        
    }
    
    public function connect() {
        return new mailClient($this);
    }
    
    protected function validateSave() {
        if (empty($this->login) || empty($this->__password) || empty($this->address)) {
            throw new foxException("Validation failed",406);
        }
        
        return true;
    }
  
    protected function validateDelete()
    {
        if ($this->default) {
            foxException::throw("ERR","Default account deletion prohibited",406,"DDAX");
        }
        return true;
    }
    
    public function setDefault() {
        if ($this->default) { return;}
        if ($this->deleted) { throw new foxException("Unacceptable",406); }

        $this->checkSql();
        $this->sql->quickExec("UPDATE `".static::$sqlTable."` set `default`=0");
        $this->default=true;
        $this->save();
    }
       
    public static function getDefaultAccount(&$sql=null) {
        $ref = new static();
        $sql = $ref->getSql();
        $rv = $sql->quickExec1Line("select * from `tblMailAccounts` where `default` = 1 limit 1");
        if ($rv) {
            return new static($rv);
        } else {return null;}
    }
    
    public static function API_GET_list(request $request) {
        if (! $request->user->checkAccess("adminMailAccounts", "core")) {
            throw new foxException("Forbidden", 403);
        }
        
        return static::search();
    }
    
    public static function API_DELETE(request $request) {
        if (! $request->user->checkAccess("adminMailAccounts", "core")) {
            throw new foxException("Forbidden", 403);
        }
        $m=new static(common::clearInput($request->function,"0-9"));
        $m->delete();
        static::log($request->instance,__FUNCTION__, "Mail account ".$m->address." deleted",$request->user,"mailAccount",$m->id);
    }

    public static function API_GET(request $request) {
        if (! $request->user->checkAccess("adminMailAccounts", "core")) {
            throw new foxException("Forbidden", 403);
        }
        $m=new static(common::clearInput($request->function,"0-9"));
        return $m;
    }
    
    public static function APIX_GET_setDefault(request $request) {
        if (! $request->user->checkAccess("adminMailAccounts", "core")) {
            throw new foxException("Forbidden", 403);
        }
        $m=new static(common::clearInput($request->function,"0-9"));
        $m->setDefault();        
        static::log($request->instance,__FUNCTION__, "Default mail account changed to ".$m->address,$request->user,"mailAccount",$m->id);
    }
    
    public static function API_PUT(request $request) {
        if (! $request->user->checkAccess("adminMailAccounts", "core")) {
            throw new foxException("Forbidden", 403);
        }
        $m=new static();
        $m->address=common::clearInput($request->requestBody->address,"0-9A-Za-z._@-");
        $m->rxURL=common::clearInput($request->requestBody->rxURL,"0-9A-Za-z._:/-");
        $m->txURL=common::clearInput($request->requestBody->txURL,"0-9A-Za-z._:/-");
        $m->login=common::clearInput($request->requestBody->login,"0-9A-Za-z._@-");
        $m->password=$request->requestBody->password;
        $m->rxFolder=common::clearInput($request->requestBody->rxFolder,"0-9A-Za-z._-");
        $m->rxArchiveFolder=common::clearInput($request->requestBody->rxArchiveFolder,"0-9A-Za-z._-");$m->save();
        static::log($request->instance,__FUNCTION__, "Mail account ".$m->address." created",$request->user,"mailAccount",$m->id,null,logEntry::sevInfo);
        return $m;
    }

    public static function API_PATCH(request $request) {
        if (! $request->user->checkAccess("adminMailAccounts", "core")) {
            throw new foxException("Forbidden", 403);
        }
        $m=new static(common::clearInput($request->function,"0-9"));
        $m->address=common::clearInput($request->requestBody->address,"0-9A-Za-z._@-");
        $m->rxURL=common::clearInput($request->requestBody->rxURL,"0-9A-Za-z._:/-");
        trigger_error(common::clearInput($request->requestBody->rxURL,"0-9A-Za-z._:/-"));
        $m->txURL=common::clearInput($request->requestBody->txURL,"0-9A-Za-z._:/-");
        $m->login=common::clearInput($request->requestBody->login,"0-9A-Za-z._@-");
        if (!empty($request->requestBody->password)) { $m->password=$request->requestBody->password; }
        $m->rxFolder=common::clearInput($request->requestBody->rxFolder,"0-9A-Za-z._-");
        $m->rxArchiveFolder=common::clearInput($request->requestBody->rxArchiveFolder,"0-9A-Za-z._-");
        $m->save();
        static::log($request->instance,__FUNCTION__, "Mail account ".$m->address." updated",$request->user,"mailAccount",$m->id,null,logEntry::sevInfo,["changelog"=>"$m->changelog"]);
        return $m;
    }
    
}