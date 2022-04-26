<?php namespace fox;

class userInvitation extends baseClass  implements externalCallable {
    protected $id;
    protected $regCode;
    public $eMail;
    public time $expireStamp;
    public bool $allowMultiUse=false;
    public array $joinGroupsId=[];
    
    public static $sqlTable="tblUserInvitation";
    public static $allowDeleteFromDB=true;
    
    public static $sqlColumns = [
        "regCode" => [
            "type" => "CHAR(16)",
            "nullable" => false,
            "index"=>"UNIQUE"
        ],
        "eMail" => [
            "type" => "VARCHAR(255)",
            "nullable" => true
        ],
        "expireStamp"=>[
            "type"=>"DATETIME"
        ]
    ];
    
    protected function __xConstruct() {
        $this->expireStamp=new time();
    }
    
    protected function validateSave() {
        if (empty($this->regCode)) {
            while (true) {
                $nc=(common::genPasswd(16,[0,1,2,3,4,5,6,7,8,9]));
                if($nc[0] !=0) {
                    break;
                }
            }
            $this->regCode=$nc;
        }
        return true;
    }

    public function getCodePrint() {
        return substr($this->regCode, 0,4)."-".substr($this->regCode, 4,4)."-".substr($this->regCode, 8,4)."-".substr($this->regCode, 12,4);
    }
    
    public function sendEmail() {
        if (common::validateEMail($this->eMail)) {
            $m=new mailMessage();
            $m->addRecipient($this->eMail);
            $m->subject=langPack::getAndReplace("core.eMailInviteMessageTitle");
            $m->bodyHTML=langPack::getAndReplace("core.eMailInviteMessage",["regCodePrint"=>$this->getCodePrint()]);
            $m->send();
        }
    }
    
    public static function getByCode($code) {
        $code = common::clearInput($code,"0-9");
        if (strlen($code) != 16) {
            return false;
        }
        
        $ref=new static();
        $sql = $ref->getSql();
        $res=$sql->quickExec1Line($ref->sqlSelectTemplate." where `regCode`='".$code."'");
        if ($res) {
            return new static($res);
        } else {
            return false;
        }
    }
    
    public static function getByEMail($eMail) {
        $eMail = common::clearInput($eMail,"@0-9A-Za-z._-");
        if (!common::validateEMail($eMail)) {
            return false;
        }
        
        $ref=new static();
        $sql = $ref->getSql();
        $res=$sql->quickExec1Line($ref->sqlSelectTemplate." where `eMail`='".$eMail."'");
        if ($res) {
            return new static($res);
        } else {
            return false;
        }
    }
    
    public static function API_PUT(request $request) {
        if (! $request->user->checkAccess("adminUsers", "core")) {
            throw new foxException("Forbidden", 403);
        }
        $eMail=common::clearInput($request->requestBody->eMail,"0-9A-Za-z@_.-");
        
        if ($inv=static::getByEMail($eMail)) {
            return $inv;
        } elseif (user::getByEmail($eMail)) {
            foxException::throw("ERR", "User already registered", 409,'UAX');
        }
        
        $inv = new static();
        $inv->eMail=$eMail;
        $inv->expireStamp=new time($request->requestBody->expireStamp);
        $inv->allowMultiUse=$request->requestBody->allowMultiUse===true || $request->requestBody->allowMultiUse=="true";
        if (!empty($inv->eMail) && !common::validateEMail($inv->eMail)) { foxException::throw("WARN", "Invalid eMail format", 400,"WREML"); }
        $inv->save();
        try {
            $inv->sendEmail();
        } catch (\Exception $e) {
            trigger_error($e->getMessage());
        }
        
        return $inv;
        
    }
    
    public static function API_GET_list(request $request) {
        return static::search();
    }
        
    public static function APIX_GET_reSend(request $request) {
        if (! $request->user->checkAccess("adminUsers", "core")) {
            throw new foxException("Forbidden", 403);
        }
        $inv=new static(common::clearInput($request->function));
        $inv->sendEmail();
    }
    
    public static function API_DELETE(request $request) {
        if (! $request->user->checkAccess("adminUsers", "core")) {
            throw new foxException("Forbidden", 403);
        }
        $inv=new static(common::clearInput($request->function));
        $inv->delete();
    }
}

?>