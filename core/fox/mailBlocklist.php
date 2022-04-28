<?php namespace fox;

class mailBlocklist extends baseClass implements externalCallable {
    protected $id;
    public $address;
    public time $entryStamp;
    
    public static $sqlTable="tblMailBlocklist";
    public static $allowDeleteFromDB = true;
    
    public static $sqlColumns = [
        "address" => [
            "type" => "VARCHAR(255)",
            "nullable" => true
        ],
        "entryStamp"=>[
            "type"=>"DATETIME"
        ]
    ];
    
    public static function getByAddress($eMail) {
        $ref=new static();
        $sql=$ref->getSql();
        if($res=$sql->quickExec1Line($ref->sqlSelectTemplate." WHERE `address`='".$eMail."'")) {
            return new static($res);
        } else {
            return false;
        }
    }
    
    protected function __xConstruct() {
        $this->entryStamp=new time();
    }
    
    protected function validateSave()
    {
        if ($this->entryStamp->isNull()) {
            $this->entryStamp=time::current();
        }
        return true;
    }
    
    public static function API_PUT(request $request) {
        if (! $request->user->checkAccess("adminMailBlocklist", "core")) {
            throw new foxException("Forbidden", 403);
        }
        
        $eMail=common::clearInput($request->requestBody->address,"0-9A-Za-z_@.-");
        
        if (!common::validateEMail($eMail)) {
            foxException::throw("ERR", "Invalid address format", 400,"IAF");
        }
        
        if ($bl = static::getByAddress($eMail)) {
            return;
        }
        
        $bl=new static();
        $bl->address=$eMail;
        static::log($request->instance,__FUNCTION__, "Address ".$eMail." added to blockList",$request->user,"eMailAddress",$eMail,null,logEntry::sevInfo);
        $bl->save();
    }
    
    public static function API_DELETE(request $request) {
        if (! $request->user->checkAccess("adminMailBlocklist", "core")) {
            throw new foxException("Forbidden", 403);
        }
        
        $eMail=common::clearInput($request->requestBody->address,"0-9A-Za-z_@.-");
        
        if (!common::validateEMail($eMail)) {
            foxException::throw("ERR", "Invalid address format", 400,"IAF");
        }
        
        if ($bl = static::getByAddress($eMail)) {
            $bl->delete();
            static::log($request->instance,__FUNCTION__, "Address ".$eMail." removed from blockList",$request->user,"eMailAddress",$eMail,null,logEntry::sevInfo);
            foxRequestResult::throw("200", "Deleted");
        } else {
            foxException::throw("WARN", "Not found", 404,"ANF");
        }
    }
}
?>