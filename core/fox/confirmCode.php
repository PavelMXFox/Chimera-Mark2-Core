<?php namespace fox;

class confirmCode extends baseClass {
    protected $id;
    public $code;
    public time $issueStamp;
    public time $expireStamp;
    public $instance;
    public $class;
    public $operation;
    public $reference;
    public $payload=[];
    public $hash;  
    const defaultTTL=3600;
    
    public static $sqlTable="tblConfirmationCodes";
    
    public static $allowDeleteFromDB = true;

    public static $sqlColumns = [
        "code" => [
            "type" => "CHAR(4)",
            "nullable" => false,
            "index"=>"INDEX"
        ],
        "instance" => [
            "type" => "VARCHAR(255)",
            "nullable" => false,
        ],
        "class" => [
            "type" => "VARCHAR(255)",
            "nullable" => false,
        ],
        "operation" => [
            "type" => "VARCHAR(255)",
            "nullable" => false,
        ],
        "reference" => [
            "type" => "VARCHAR(255)",
            "nullable" => false,
        ],
        "hash" => [
            "type" => "VARCHAR(255)",
            "nullable" => false,
        ],
    ];
    
    protected function __xConstruct()
    {
        $this->issueStamp=new time();
        $this->expireStamp=new time();
    }
    
    protected function getHash() {
        return xcrypt::hash(json_encode([
            $this->instance,
            $this->class,
            $this->operation,
            $this->reference,
            $this->payload,
        ]));        
    }
    
    public function fillByHash() {
        if (empty($this->hash)) { $this->hash=$this->getHash();}
        $this->checkSql();
        if ($row=$this->sql->quickExec1Line($this->__sqlSelectTemplate." WHERE `hash`='".$this->hash."'")) {
            $this->fillFromRow($row);
            return true;
        } else {
            return false;
        }
    }
    
    protected function validateSave()
    {
        if ($this->issueStamp->isNull()) {$this->issueStamp=time::current();}
        if ($this->expireStamp->isNull()) {$this->expireStamp=time::current()->addSec(static::defaultTTL);}
        if ($this->code==null) { $this->code=(common::genPasswd(4,[0,1,2,3,4,5,6,7,8,9]));}
        if (empty($this->instance) || empty($this->class) || empty($this->operation)  || empty($this->reference)) {
            throw new foxException("Empty refences not allowed here","400");
        }
        if (empty($this->hash)) {$this->hash = $this->getHash(); }
        
        return !$this->fillByHash();
    }
}

?>