<?php namespace fox;

class logEntry extends baseClass implements externalCallable {
    protected $id;
    protected time $entryStamp;
    public $severity;
    public $module;
    public $class;
    public $method;
    public $userId;
    public $msgCode;
    public $message;
    public $refType;
    public $refId;
    public $payload=[];
    
    public static $sqlTable="tblLog";
    
    protected static $sqlColumns = [
        "severity"=>["type"=>"CHAR(10)","index"=>"INDEX"],
        "module"=>["type"=>"VARCHAR(255)","index"=>"INDEX"],
        "class"=>["type"=>"VARCHAR(255)"],
        "method"=>["type"=>"VARCHAR(255)"],
        "userId"=>["type"=>"INT","index"=>"INDEX"],
        "msgCode"=>["type"=>"CHAR(16)"],
        "message"=>["type"=>"TEXT"],
        "refType"=>["type"=>"VARCHAR(255)","index"=>"INDEX"],
        "refId"=>["type"=>"VARCHAR(255)","index"=>"INDEX"],
    ];
    
    const sevDebug="DEBUG";
    const sevInfo="INFO";
    const sevWarning="WARN";
    const sevAlert="ALERT";
    
    protected function __xConstruct() {
        $this->entryStamp=new time();
    }
    
    protected function validateSave() {
        if ($this->entryStamp->isNull()) { $this->entryStamp=time::current(); }
        return true;
    }
    
    public static function add(string $instance, string $class, string $method, ?string $msgCode=null, string $message, ?string $severity="INFO", ?user $user=null,  ?string $refType=null, ?string $refId=null, $payload=null) {
        $l = new static();
        $l->module = $instance;
        $l->class=$class;
        $l->method=$method;
        $l->userId = empty($user)?null:$user->id;
        $l->msgCode=$msgCode;
        $l->message=$message;
        $l->refType=$refType;
        $l->refId=$refId;
        $l->payload=empty($payload)?[]:$payload;
        $l->severity=$severity;
        $l->save();
        return $l;
    }
}
?>