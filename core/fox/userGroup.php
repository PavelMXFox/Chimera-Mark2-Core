<?php
namespace fox;

/**
 *
 * Class fox\userGroup
 *
 * @copyright MX STAR LLC 2021
 * @version 4.0.0
 * @author Pavel Dmitriev
 * @license GPLv3
 *        
 */
class userGroup extends baseClass implements externalCallable
{

    protected $id;

    public $name;

    public $companyId;

    protected $__company;

    public bool $isList = false;


    #
    # AccessRules
    # [moduleInstance::<all>][ruleName]
    # ex:
    # ["core"]["isRoot","ACLx"]
    # [<all>]["isRoot"]
    #
    protected array $accessRules = [];

    public static $sqlTable = "tblUserGroups";
    public static $allowDeleteFromDB = true;

    public static $sqlColumns = [
        "name" => [
            "type" => "VARCHAR(255)",
            "index" => "INDEX"
        ],
        "companyId" => [
            "type" => "INT",
            "index" => "INDEX"
        ]
    ];

    public function getMembers()
    {
        $this->checkSql();
        $rv = [];
        foreach (userGroupMembership::getUsersInGroup($this, $this->sql) as $ugm) {
            $rv[$ugm->user->id] = $ugm->user;
        }
        return $rv;
    }

    public static function getForUser(user $user, $isList = false, ?sql $sql = null)
    {
        if (empty($sql)) {
            $sql = new sql();
        }
        $ugms = userGroupMembership::getGroupsForUser($user, $sql);

        $rv = [];

        foreach ($ugms as $ugm) {
            if ($isList === null || ($ugm->group->isList === $isList)) {
                $rv[] = $ugm->group;
            }
        }

        return $rv;
    }

    /*
     * options:
     *  isListOnly - { only - search isList only, no - search with isList = 0, both - search any}, default=no
     *  accessRule - передается в формате access_rule_name@module.
         * !!! Внимание !!! в отличие от проверки прав доступа - здесь поиск по isRoot не осуществляется!
         * Если module отсутствует - то считается, что module ='all'
     *
     *  
     *  
     */
    protected static function xSearch($where, $pattern, ?array $options, sql $sql) {
        $accessRule=(empty($options["accessRule"])?null:$options["accessRule"]);
        $isList=(array_key_exists("isList", $options)?$options["isList"]:false);
        $ruleJoin=null;
        $ruleWhere=null;
        
        if ($isList !== false) {
            $ruleWhere .= " and `i`.`isList` = " . ($isList == true ? 1 : 0);
        }
        
        if (empty($ruleWhere)) {
            $xWhere=$where;
        } else {
            $xWhere=(empty($where)?$ruleWhere:"(".$where.") AND ".$ruleWhere);
        }
        
        return ["where"=>$xWhere, "join"=>$ruleJoin];
    }
    
    public function join(user $user)
    {
        if (empty($this->id)) {
            throw new \Exception("Group not saved! Save it first.");
        }
        if (array_key_exists($user->id, $this->getMembers())) {
            return true;
        }

        $umm = new userGroupMembership();
        $umm->userId = $user->id;
        $umm->groupId = $this->id;
        $umm->save();
        $user->flushACRCache();
        return true;
    }

    public function left(user $user)
    {
        if (empty($this->id)) {
            throw new \Exception("Group not saved! Save it first.");
        }
        foreach (userGroupMembership::getUsersInGroup($this, $this->sql) as $ugm) {
            if ($ugm->user->id == $user->id) {
                $ugm->delete();
            }
        }
        $user->flushACRCache();
        return true;
    }
    
    public function isMember(user $user) {
        foreach (userGroupMembership::getUsersInGroup($this, $this->sql) as $ugm) {
            if ($ugm->user->id == $user->id) {
                return true;
            }
        }
        return false;
    }

    public function addAccessRule(string $rule, string $modInstance = "<all>")
    {
        if (array_key_exists($modInstance, $this->accessRules) && (array_search($rule, $this->accessRules[$modInstance]) !== false)) {
            return true;
        }

        $this->accessRules[$modInstance][] = $rule;
        return true;
    }

    
    public function dropAccessRule(string $rule, string $modInstance = "<all>")
    {
        if (array_key_exists($modInstance, $this->accessRules) && (($rid = array_search($rule, $this->accessRules[$modInstance])) !== false)) {
            unset($this->accessRules[$modInstance][$rid]);
            if (empty($this->accessRules[$modInstance])) {
                unset($this->accessRules[$modInstance]);
            }
            ;
        }
    }
    
    public static function API_GET_list(request $request)
    {
        if (! $request->user->checkAccess("adminUserGroups", "core")) {
            throw new foxException("Forbidden", 403);
        }
        return static::search()->result;
    }
    
    public static function API_POST_members(request $request)
    {
        if (! $request->user->checkAccess("adminUserGroups", "core")) {
            throw new foxException("Forbidden", 403);
        }
        return static::getMembers();
    }

    public static function API_DELETE_acl(request $request)
    {
        if (! $request->user->checkAccess("adminUserGroups", "core")) {
            throw new foxException("Forbidden", 403);
        }
        $group = new static(common::clearInput($request->requestBody->groupId,"0-9"));
        $group->dropAccessRule(common::clearInput($request->requestBody->rule), common::clearInput($request->requestBody->module));
        $group->save();
        static::log($request->instance,__FUNCTION__, "Rule ".common::clearInput($request->requestBody->rule)."@".common::clearInput($request->requestBody->module)." removed from group ".$group->name,$request->user,"userGroup",$group->id,null,logEntry::sevInfo);
        foxRequestResult::throw(200, "Deleted");
    
    }

    public static function API_PUT_acl(request $request)
    {
        if (! $request->user->checkAccess("adminUserGroups", "core")) {
            throw new foxException("Forbidden", 403);
        }
        $group = new static(common::clearInput($request->requestBody->groupId,"0-9"));
        $group->addAccessRule(common::clearInput($request->requestBody->rule), ($request->requestBody->forAll=="1")?"<all>":common::clearInput($request->requestBody->module));
        static::log($request->instance,__FUNCTION__, "Rule ".common::clearInput($request->requestBody->rule)."@".common::clearInput($request->requestBody->module)." added for group ".$group->name,$request->user,"userGroup",$group->id,null,logEntry::sevInfo);
        $group->save();
        
    }
    
    
    public static function APICall(request $request) {

        if (! $request->user->checkAccess("adminUserGroups", "core")) {
            throw new foxException("Forbidden", 403);
        }
        
        switch ($request->method) {
            case "GET":
                if (empty($request->parameters[0])) {
                    return new static(common::clearInput($request->function));
                } else {
                    switch ($request->parameters[0]) {
                        case "acls":
                            return (new static(common::clearInput($request->function)))->accessRules;
                            break;
                        default:
                            throw new foxException("Method not allowed",405);
                    }
                }
                break;
                
            case "PUT":
                $grName=common::clearInput($request->requestBody->name);
                $groups = userGroup::search($grName)->result;
                foreach ($groups as $group) {
                    if (trim(strtolower($group->name))==trim(strtolower($grName))) {
                        foxException::throw("ERR", "Already exists", 409, "GAX");
                    }
                }
                
                $group=new userGroup();
                $group->name=$grName;
                $group->isList=$request->requestBody->isList==1;
                $group->save();
                static::log($request->instance,__FUNCTION__, "UserGroup ".$group->name." created.",$request->user,"userGroup",$group->id,null,logEntry::sevInfo);
                foxRequestResult::throw(201, "Created",$group);
                break;
            case "DELETE":
                $group = new static(common::clearInput($request->function));
                if ($group->getMembers()) {
                    foxException::throw("ERR","Group not empty", 400,"NEG");
                }
                
                $group->delete();
                static::log($request->instance,__FUNCTION__, "UserGroup ".$group->name." deleted.",$request->user,"userGroup",$group->id,null,logEntry::sevInfo);
                
                throw new foxRequestResult("Deleted",200);
                break;
            default:
                throw new foxException("Method not allowed",405);
        }
     
    }
}

?>