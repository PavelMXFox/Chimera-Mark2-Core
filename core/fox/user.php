<?php
namespace fox;

/**
 *
 *
 * Class fox\user
 *
 * @copyright MX STAR LLC 2021
 * @version 4.0.0
 * @author Pavel Dmitriev
 * @license GPLv3
 *        
 */
class user extends baseClass implements externalCallable
{

    protected $id;
    public $login;
    protected UID $invCode;
    protected $__secret;
    public $authType;
    public $authRefId;
    public int $offlineAuthCtr = 0;
    public $fullName;
    protected bool $active = true;
    protected bool $deleted = false;
    public $companyId;
    public $eMail;
    public bool $eMailConfirmed = false;
    protected array $config = [];
    protected ?company $__company = null;
  
    protected static $excludeProps = ["authRefId"];
    public static $sqlTable = "tblUsers";
    public static $deletedFieldName = "deleted";

    public static $sqlColumns = [
        "login" => [
            "type" => "VARCHAR(255)",
            "index" => "UNIQUE",
            "nullable" => false,
            "search"=>"like"
        ],
        "secret" => [
            "type" => "VARCHAR(255)",
            "index" => "INDEX",
            "nullable" => true
        ],
        "invCode" => [
            "type" => "INT",
            "index" => "UNIQUE",
            "nullable" => false,
            "search"=>"invCode"
        ],
        "authType" => [
            "type" => "VARCHAR(255)",
            "index" => "INDEX",
            "nullable" => false
        ],
        "authRefId" => [
            "type" => "TEXT"
        ],
        "fullName" => [
            "type" => "VARCHAR(255)",
            "nullable" => false,
            "search"=>"like"
        ],
        "companyId" => [
            "type" => "INT",
            "index" => "INDEX"
        ],
        "eMail" => [
            "type" => "VARCHAR(255)",
            "index" => "INDEX",
            "search"=>"like"
        ]
    ];

    public function setPassword($passwd)
    {
        $this->__secret = xcrypt::hash($passwd);
    }

    public function checkPassword($passwd)
    {
        if (empty($this->__secret)) {
            return false;
        }
        return ($this->__secret == xcrypt::hash($passwd));
    }

    protected function validateSave()
    {
        if ($this->invCode->isNull()) {
            $this->invCode->issue("core", get_class($this));
        }
        
        if (empty($this->login)) {
            $this->login=(string)$this->invCode;
        }
        return true;
    }

    public function __xConstruct()
    {
        $this->invCode = new UID();
    }

    public function getAccessRules()
    {
        $cache = new cache();
        $ACLS = $cache->get("UserACL." . $this->id, true);
        if ($ACLS !== null) {
            return $ACLS;
        }

        $rv = [];
        // merge all ACLs
        foreach (userGroup::getForUser($this, false, $this->sql) as $val) {
            $rv = array_merge_recursive($rv, $val->accessRules);
        }

        // deduplicate
        foreach ($rv as &$val) {
            $val = array_unique($val);
        }
        $cache->set("UserACL." . $this->id, $rv);
        return $rv;
    }

    public function checkAccess(string $rule, string $modInstance)
    {
        $ACLS = $this->getAccessRules();
        
        if ($rule=="allUsers") {
            return true;
        }
        
        
        if (array_key_exists($modInstance, $ACLS) && (array_search($rule, $ACLS[$modInstance]) !== false)) {
            return true;
        }

        if (array_key_exists("<all>", $ACLS) && (array_search($rule, $ACLS["<all>"]) !== false)) {
            return true;
        }

        if (array_key_exists("<all>", $ACLS) && (array_search("isRoot", $ACLS["<all>"]) !== false)) {
            return true;
        }

        return false;
    }

    public function flushACRCache()
    {
        $cache = new cache();
        $cache->set("UserACL." . $this->id, null);
    }

    public function __get($key)
    {
        switch ($key) {
            case "active":
                return $this->active && ! $this->deleted;

            default:
                return parent::__get($key);
        }
    }
    
    public static function getByRefID($authMethod,$userRefId) {
        $ref=new static();
        $sql = $ref->getSql();
        $res = $sql->quickExec1Line($ref->sqlSelectTemplate. " where `authType`='".$authMethod."' and `authRefId`='".common::clearInput($userRefId)."'");
        if ($res) {
            return new static($res);
        } else {
            return null;
        }
    }
    
    public static function getByEmail($eMail) {
        $ref=new static();
        $sql = $ref->getSql();
        $res = $sql->quickExec1Line($ref->sqlSelectTemplate. " where `eMail`='".common::clearInput($eMail,"@0-9A-Za-z._-")."'");
        if ($res) {
            return new static($res);
        } else {
            return null;
        }
    }
    
    public static function getByLogin($login) {
        $ref=new static();
        $sql = $ref->getSql();
        $res = $sql->quickExec1Line($ref->sqlSelectTemplate. " where `login`='".common::clearInput($login)."'");
        if ($res) {
            return new static($res);
        } else {
            return null;
        }
    }

    protected function prepareCodeForConfirmation($operation) {
        $confCode=new confirmCode();
        $confCode->class=__CLASS__;
        $confCode->instance="core";
        $confCode->reference=$this->id;
        $confCode->operation=$operation;
        $confCode->payload=["eMailAddress"=>$this->eMail];
        return $confCode;
    }
    
    public function sendEMailConfirmation() {
        if ($this->eMailConfirmed) {
            foxException::throw("WARN", "Already confirmed", 406,"ARCF");
        }
        
        if (!common::validateEMail($this->eMail)) {
            foxException::throw("ERR", "Invalid address format", 406,"WREML");
        }
        
        $confCode=$this->prepareCodeForConfirmation("eMailConfirmation");
        $confCode->save();
        
        $m=new mailMessage();
        $m->addRecipient($this->eMail);
        $m->subject=langPack::getAndReplace("core.eMailConfirmMessageTitle");
        $m->bodyHTML=langPack::getAndReplace("core.eMailConfirmMessage",["confCodePrint"=>$confCode->code]);
        $m->send();
    }
    
    public function validateEMailConfirmation($code) {
        $confCode=$this->prepareCodeForConfirmation("eMailConfirmation");
        if (!$confCode->fillByHash()) {
            foxException::throw("ERR","Invalid code",406,"IVCC");
        }
        if ($confCode->code != $code) {
            return false;
        }
        
        if ($confCode->expireStamp->stamp<time()) {
            return false;
        }
        
        $this->eMailConfirmed=true;
        $this->save();
        $confCode->delete();
        return true;
    }
    
    public function sendPasswordRecovery() {
        if ($this->authType!=='internal' || !$this->eMailConfirmed) { throw new foxException("Not acceptable",406);}
        $confCode=$this->prepareCodeForConfirmation("passwordRecovery");
        $confCode->save();
        
        $m=new mailMessage();
        $m->addRecipient($this);
        $m->subject=langPack::getAndReplace("core.accessRecoverMessageTitle");
        $m->bodyHTML=langPack::getAndReplace("core.accessRecoverMessage",["confCodePrint"=>$confCode->code, "eMailEncoded"=>$this->eMail]);
        $m->send();
    }
    
    public function validateRecoveryCode($code,$delete=false) {
        if ($this->authType!=='internal' || !$this->eMailConfirmed) { throw new foxException("Not acceptable",406);}
        $confCode=$this->prepareCodeForConfirmation("passwordRecovery");
        $confCode->fillByHash();
        if ($confCode->code != $code) {
            return false;
        }
        
        if ($confCode->expireStamp->stamp<time()) {
            return false;
        }
        
        if ($delete) { $confCode->delete();}
        return true;
    }
        
    public function export() {
        $rv=parent::export();
        $rv["config"]=(object)$this->config;
        return $rv;
    }
    /**
     * @param array $options - ["groups" - array of userGroup, if set - search will performed only in it]
     */
    protected static function xSearch($where, $pattern, ?array $options, sql $sql) {
        $ruleJoin=null;
                
        if ($options["groups"]) {
            $groups="";
            foreach ($options["groups"] as $group) {
                $groups .= (empty($groups)?"":",")."\"".$group->id."\"";
            }
            $ruleJoin = " INNER JOIN `tblUserGroupLink` as `l` on `l`.`userId`=`i`.`id` AND `l`.`groupId` in ($groups)";
        }
        
        return ["where"=>$where, "join"=>$ruleJoin, "group"=>"`i`.`id`"];
    }
    
    ### REST API
    public static function API_GET_list(request $request)
    {
        if (! $request->user->checkAccess("adminUsers", "core")) {
            throw new foxException("Forbidden", 403);
        }
        return static::search()->result;
    }
    
    public static function API_POST_search(request $request) {
        
        $opts=[];
        if ($request->checkAccess("viewAllUsers") || $request->checkAccess("adminUsers")) {
            $opts=[];
        } else if ($request->checkAccess("viewOwnListsUsers")) {
            $opts = [
                "groups"=>userGroup::getForUser($request->user,true),
            ];
        } else {
            $rv=new searchResult();
            $rv->push($request->user);
            return $rv;
            
        }
        
        return static::search(
            $request->getRequestBodyItem("pattern"),
            $request->getRequestBodyItem("pageSize"),
            $request->getRequestBodyItem("page"),
            $opts
            );
     
    }
    
    public static function APIX_GET_sendEMailConfirmation(request $request) {
        if (! $request->user->checkAccess("adminUsers", "core")) {
            throw new foxException("Forbidden", 403);
        }
        $user = new static(common::clearInput($request->function,"0-9"));
        $user->sendEMailConfirmation();
        static::log($request->instance,__FUNCTION__, "mailConfirmation sent for user ".$user->login,$request->user,"user",$user->id,null,logEntry::sevInfo);
    }

    public static function API_GET_sendEMailConfirmation(request $request) {
        $request->user->sendEMailConfirmation();
        static::log($request->instance,__FUNCTION__, "mailConfirmation sent for user ".$request->user->login,$request->user,"user",$request->user->id,null,logEntry::sevInfo);
    }
    
    public static function API_POST_validateEMailCode(request $request) {
        if ($request->user->validateEMailConfirmation(common::clearInput($request->requestBody->code,"0-9"))) {
            static::log($request->instance,__FUNCTION__, "Mail address confirmed for user ".$request->user->login,$request->user,"user",$request->user->id,null,logEntry::sevInfo);
            return;
        } else {
            foxException::throw("ERR", "Validation failed", 400);
        }
    }
}

?>