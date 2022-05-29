<?php
namespace fox;

/**
 *
 * Class fox\userGroupMembership
 *
 * @copyright MX STAR LLC 2021
 * @version 4.0.0
 * @author Pavel Dmitriev
 * @license GPLv3
 *        
 */
class userGroupMembership extends baseClass implements externalCallable
{

    protected $id;

    public $groupId;

    public $userId;

    protected user $__user;

    protected userGroup $__group;

    public static $sqlTable = "tblUserGroupLink";

    public static $allowDeleteFromDB = true;

    public static $sqlColumns = [
        "groupId" => [
            "type" => "INT",
            "index" => "INDEX",
            "nullable" => false
        ],
        "userId" => [
            "type" => "INT",
            "index" => "INDEX",
            "nullable" => false
        ]
    ];

    public static function getUsersInGroup(?userGroup $group = null, ?sql $sql = null)
    {
        if (empty($sql)) {
            $sql = new sql();
        }

        $res = $sql->quickExec("select * from `" . self::$sqlTable . "`" . ($group === null ? "" : " where `groupId` = '" . $group->id . "'"));
        $rv = [];
        while ($row = mysqli_fetch_assoc($res)) {
            $item = new self($row);
            if ($item->user !== null) {
                $rv[] = $item;
            }
        }

        return $rv;
    }

    public function loadUser()
    {
        if (empty($this->__user) && ! empty($this->userId)) {
            try {
                $this->__user = new user($this->userId);
            } catch (\Exception $e) {
                return null;
            }
        }
        return $this->__user;
    }

    public function loadGroup()
    {
        if (empty($this->__group) && ! empty($this->groupId)) {
            $this->__group = new userGroup($this->groupId);
        }
        return $this->__group;
    }

    public static function getGroupsForUser(?user $user = null, ?sql $sql = null)
    {
        // if user===null -> get all items, else - get only items for user
        if (empty($sql)) {
            $sql = new sql();
        }

        $res = $sql->quickExec("select * from `" . self::$sqlTable . "`" . ($user === null ? "" : " where `userId` = '" . $user->id . "'"));
        $rv = [];
        while ($row = mysqli_fetch_assoc($res)) {
            $item = new self($row);
            if ($item->group !== null) {
                $rv[] = $item;
            }
        }

        return $rv;
    }


    public function __get($key)
    {
        switch ($key) {
            case "user":
                return $this->loadUser();

            case "group":
                return $this->loadGroup();

            default:
                return parent::__get($key);
        }
    }

    public function export()
    {
        $rv = parent::export();
        if (! empty($this->__user)) {
            $rv["user"] = $this->__user;
        }
        if (! empty($this->__group)) {
            $rv["group"] = $this->__group;
        }
        return $rv;
    }
    
    public static function API_POST_search(request $request)
    {
        if (! $request->user->checkAccess("adminUserGroups", "core")) {
            throw new foxException("Forbidden", 403);
        }
        
        @$page=common::clearInput($request->requestBody->page);
        if (empty($page) || !(is_numeric($page))) {$page=0;}

        @$pageSize=common::clearInput($request->requestBody->pageSize);
        if (empty($pageSize) || !(is_numeric($pageSize))) {$pageSize=$request->user->config["pageSize"];}
        
        if (!empty($request->requestBody->userId)) {
            $user = new user($userId=common::clearInput($request->requestBody->userId,"0-9"));    
        } else {
            $user=null;
            $userId=null;
        }
        
        if (!empty($request->requestBody->groupId)) {
            $group=new userGroup($groupId=common::clearInput($request->requestBody->groupId,"0-9"));    
        } else {
            $group=null;
            $groupId=null;
        }
        
        if (!$user && !$group) {
            throw new foxException("Invalid request",400);
        }
        
        $res=static::search(null,$pageSize,$page,[
            "user"=>$user,
            "group"=>$group,
        ])->result;
        
        $rv=[];
        foreach ($res as $ugm) {
            $rv[]=[
                "ugmId"=>$ugm->id,
                "user"=>($userId==$ugm->userId)?$user:$ugm->user,
                "group"=>($groupId=$ugm->groupId)?$group:$ugm->group,
            ];
        }
        
        return $rv;
    }
    
    protected static function xSearch($where, $pattern, ?array $options, sql $sql) {
        
        if (!empty($options["user"])) {
            $xWhere="`i`.`userId`='".$options["user"]->id."'";
        }

        if (!empty($options["group"])) {
            $xWhere.=(empty($xWhere)?"":" AND ")."`i`.`groupId`='".$options["group"]->id."'";
        }
        
        return ["where"=>empty($where)?$xWhere:"(".$where.") AND ".$xWhere, "join"=>null];
    }
    
    public static function APICall(request $request) {
        if (! $request->user->checkAccess("adminUserGroups", "core")) {
            throw new foxException("Forbidden", 403);
        }
        
        $user=new user(common::clearInput($request->requestBody->userId,"0-9"));
        $userGroup=new userGroup(common::clearInput($request->requestBody->groupId,"0-9"));
        $userGroupMembership=null;
        
        foreach (static::getGroupsForUser($user) as $ugm) {
            if ($ugm->groupId==$userGroup->id) {
                $userGroupMembership=$ugm;
                break;
            }
        }
        
        switch ($request->method) {
            case "PUT":
                if ($userGroupMembership!==null) {
                    foxException::throw("ERR", "Already exists", 409, "UGX");
                }
                
                $userGroupMembership=new userGroupMembership();
                $userGroupMembership->userId=$user->id;
                $userGroupMembership->groupId=$userGroup->id;
                $userGroupMembership->save();
                $user->flushACRCache();
                static::log($request->instance,__FUNCTION__, "User ".$user->login ." join group ".$userGroup->name,$request->user,"user",$user->id,null,logEntry::sevInfo);
                foxRequestResult::throw("201", "Created");
                break;
                
            case "DELETE":
                if ($userGroupMembership==null) {
                    foxException::throw("ERR", "Not found", 404, "UGN");
                }
                static::log($request->instance,__FUNCTION__, "User ".$userGroupMembership->user->login ." left group ".$userGroupMembership->group->name,$request->user,"user",$userGroupMembership->user->id,null,logEntry::sevInfo);
                
                $userGroupMembership->delete();
                $user->flushACRCache();
                break;
            
            default:
                throw new foxException("Method not allowed",405);
                break;
        }
    }
}
?>