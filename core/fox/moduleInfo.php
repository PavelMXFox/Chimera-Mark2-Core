<?php
namespace fox;


/**
 *
 * Class fox\moduleInfo
 *
 * @copyright MX STAR LLC 2021
 * @version 4.0.0
 * @author Pavel Dmitriev
 * @license GPLv3
 *
 */


class moduleInfo extends baseClass implements externalCallable
{

    protected $id;

    public string $name = "";

    public string $namespace = "";

    public string $instanceOf = "";

    public string $title = "";

    public array $features = [];

    public bool $enabled = true;

    public string $modVersion = "1.0.0";

    public string $modPriority = "9999";

    public time $installDate;

    public time $updateDate;

    public bool $singleInstanceOnly = true;

    public bool $isTemplate = false;

    public bool $authRequired = true;
    
    protected $__template=null;
    
    public array $ACLRules = [];

    public array $menuItem = [];

    public $globalAccessKey = "isRoot";

    public array $languages = [];
    public array $configKeys=[];

    public static $sqlTable = "tblModules";

    public static $sqlIdx = "id";

    public static $allowDeleteFromDB = true;
    
    public static $sqlColumns = [
        "enabled" => [
            "type" => "INT",
            "index" => "INDEX"
        ],
        "singleInstanceOnly" => [
            "type" => "SKIP"
        ],
        "isTemplate" => [
            "type" => "SKIP"
        ],
        "authRequired" => [
            "type" => "SKIP"
        ],
        "ACLRules" => [
            "type" => "SKIP"
        ],
        "menuItem" => [
            "type" => "SKIP"
        ],
        "globalAccessKey" => [
            "type" => "SKIP"
        ],
        "authRequired" => [
            "type" => "SKIP"
        ],
        "namespace" => [
            "type" => "SKIP"
        ],
        "languages" => [
            "type" => "SKIP"
        ],
        "template" => [
            "type" => "SKIP"
        ],
        "configKeys" => [
            "type" => "SKIP"
        ]
    ];

    
    protected static $excludeProps=[];
    
    protected function fillFromRow($row)
    {

        $rv=parent::fillFromRow($row);
        
        if (!$this->isTemplate && $this->template) {
            $this->ACLRules = (array) $this->template->ACLRules;
            $this->menuItem = (array) $this->template->menuItem;
            $this->globalAccessKey = $this->template->globalAccessKey;
            $this->authRequired = $this->template->authRequired;
            $this->namespace = $this->template->namespace;
            $this->languages = $this->template->languages;
            $this->configKeys= $this->template->configKeys;
        }
        return $rv;
    }
    
    
    
    public function __get($key) {

        switch ($key) {
            case "template":
                if (empty($this->__template)) {
                    $allMods=modules::list();
                    if (array_key_exists($this->instanceOf, $allMods)) {
                        $this->__template=$allMods[$this->instanceOf];
                    }
                }
                return $this->__template;
            default:
                return parent::__get($key);
        }
    }
    
    public function __set($key, $val) {
        switch ($key) {
            case "template":
                if ($val instanceof moduleInfo) {
                    $this->__template=$val;
                } else {
                    throw new foxException("Invalid type");
                }
                break;
            default:
                return parent::__set($key, $val);
        }
    }
    
    public function __xConstruct()
    {
        $this->installDate = new time();
        $this->updateDate = new time();
    }

    public function save()
    {
        if ($this->installDate->isNull()) {
            $this->installDate = new time(time());
        }
        if ($this->updateDate->isNull()) {
            $this->updateDate = new time(time());
        }
        if ($this->isTemplate && empty($this->instanceOf)) {
            $this->instanceOf = $this->name;
        }

        parent::save();
        $this->flushCache();
    }

    public function delete()
    {
        parent::delete();
        $this->flushCache();
    }

    protected function flushCache()
    {
        $cache = new cache();
        $cache->set("modules", null);
    }

    public static function getAll()
    {
        $cache = new cache();
        $mods = $cache->get("modules");
        if ($mods !== null) {
            $rv = [];
            foreach ($mods as $mod) {
                $rv[$mod->name] = new self((array) $mod);
            }
            return $rv;
        }

        $mods = modules::list();
        $m = new static();
        $sql = $m->getSql();
        $res = $sql->quickExec($m->__sqlSelectTemplate." order by `modPriority`");
        $rv = [];
        while ($row = mysqli_fetch_assoc($res)) {
            if (array_key_exists($row["instanceOf"], $mods)) {
                $x = new static($row);
              
                $rv[$row["name"]] = $x;
                
            }
        }
        $cache->set("modules", $rv);
        return $rv;
    }

    public static function getByInstance(string $modInstanceName) : moduleInfo {
        $modsInstalled = moduleInfo::getAll();
        if (! array_key_exists($modInstanceName, $modsInstalled)) {
            throw new foxException("Module not installed", 404);
        }
        
        return $modsInstalled[$modInstanceName];
    }
    
    public function getInstances()
    {
        if (! $this->isTemplate) {
            return null;
        }

        $this->checkSql();
        $res = $this->sql->quickExec($this->__sqlSelectTemplate . " where `i`.`instanceOf` = '" . $this->name . "'");

        $rv = [];
        while ($row = mysqli_fetch_assoc($res)) {
            $rv[] = new self($row);
        }
        return $rv;
    }

    public static function load(string $modName)
    {}

    public function export() {
        $rv=parent::export();
        if ($this->isTemplate) {
            $rv["instances"]=$this->getInstances();
            $rv["instancesCount"]=count($rv["instances"]);
        }
        
        return $rv;
    }
    
    public static function APICall(request $request) {
        if (! $request->user->checkAccess("adminModulesInstall", "core")) {
            throw new foxException("Forbidden", 403);
        }
        
        switch ($request->method) {
            case "GET":
                if (! $request->user->checkAccess("adminModulesInstall", "core")) {
                    throw new foxException("Forbidden", 403);
                }
                
                $modInstanceName = common::clearInput($request->function, "0-9a-zA-Z._-");
                $modsInstalled = static::getAll();
                if (! array_key_exists($modInstanceName, $modsInstalled)) {
                    throw new foxException("Module not installed", 404);
                }
                
                $mod=$modsInstalled[$modInstanceName];
                if (empty($request->parameters[0])) {
                    return $mod;
                }
                
                switch ($request->parameters[0]) {
                    case "features":
                        $rv=[];
                        foreach($mod->template->features as $fx) {
                            $rv[$fx]=(array_search($fx, $mod->features)===false)?false:true;
                        }
                        return $rv;
                        break;
                        
                    case "config":
                        $rv=[
                        "values"=>config::getAll($mod->name),
                        "keys"=>$mod->configKeys,
                        ];
                        return $rv;
                        break;
                    default:
                        throw new foxException("Method not allowed",405);
                        
                }
                break;
                
            case "DELETE":
                if (! $request->user->checkAccess("adminModulesInstall", "core")) {
                    throw new foxException("Forbidden", 403);
                }
                
                $modInstanceName = common::clearInput($request->function, "0-9a-zA-Z._-");
                $modsInstalled = static::getAll();
                if (! array_key_exists($modInstanceName, $modsInstalled)) {
                    throw new foxException("Module not installed", 404);
                }
                
                $mod = $modsInstalled[$modInstanceName];
                
                if (empty($request->parameters[0])) {
                    $mod->delete();
                    foxRequestResult::throw(200, "Deleted");
                }
                
                switch ($request->parameters[0]) {
                    case "features":
                        $idx = array_search(common::clearInput($request->requestBody->feature),$mod->features);
                        if ($idx !==false) {
                            unset($mod->features[$idx]);
                            $mod->features=array_values($mod->features);
                            $mod->save();
                            foxRequestResult::throw(200, "Deleted");
                        }
                        break;
                        
                    case "config":
                        config::del(common::clearInput($request->requestBody->key), $mod->name);
                        foxRequestResult::throw(200, "Deleted");
                        break;
                    default:
                        throw new foxException("Method not allowed",405);
                        
                }
                
                break;
                
            case "PUT":
                if (! $request->user->checkAccess("adminModulesInstall", "core")) {
                    throw new foxException("Forbidden", 403);
                }
                
                $modInstanceName = common::clearInput($request->function, "0-9a-zA-Z._-");
                $modsInstalled = static::getAll();
                if (! array_key_exists($modInstanceName, $modsInstalled)) {
                    throw new foxException("Module not installed", 404);
                }
                
                $mod = $modsInstalled[$modInstanceName];
                
                switch ($request->parameters[0]) {
                    case "features":
                        $idx = array_search(common::clearInput($request->requestBody->feature),$mod->features);
                        if ($idx ===false && array_search(common::clearInput($request->requestBody->feature),$mod->template->features) !== false) {
                            $mod->features[]=common::clearInput($request->requestBody->feature);
                            $mod->features=array_values($mod->features);
                            $mod->save();
                            foxRequestResult::throw(201, "Created");
                        }
                        foxRequestResult::throw(201, "Created");
                        break;
                        
                    case "config":
                        config::set(common::clearInput($request->requestBody->key), $request->requestBody->value, $mod->name);
                        foxRequestResult::throw(201, "Created");
                        break;
                    default:
                        throw new foxException("Method not allowed",405);
                        
                }
                
                break;
                
            default:
                throw new foxException("Method not allowed",405);
        }
    }
}
?>