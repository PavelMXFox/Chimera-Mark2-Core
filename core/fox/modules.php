<?php
namespace fox;

/**
 *
 * Class fox\modules
 *
 * @copyright MX STAR LLC 2021
 * @version 4.0.0
 * @author Pavel Dmitriev
 * @license GPLv3
 *        
 */
class modules implements externalCallable
{

    // not implemented yet
    public const pseudoModules = [
        "core" => [
            "title" => "Core module",
            "modVersion" => "4.0.0",
            "name" => "core",
            "namespace" => "fox",
            "features" => [
                "page",
                "menu"
            ],
            "isTemplate" => true,
            "singleInstanceOnly" => true,
            "authRequired" => true,
            "languages" => [
                "ru"
            ],
            "ACLRules" => [
                "isRoot" => "Superadmin user",
                "adminViewModules"=>"Manage modules",
                "adminModulesInstall"=>"Install modules",
                "adminUsers"=>"Manage users",
                "adminUserGroups"=>"Manage userGroups",
            ],
            "configKeys"=> [
                "converterURL"=>"FoxConverter URL prefix",
            ],
            "menuItem" => [
                "admin" => [
                    "title" => [
                        "ru" => "Админка",
                        "en" => "Admin area"
                    ],
                    "function" => null,
                    "pageKey" => "admin",
                    "accessRule" => "adminBasicRO",
                    "items" => [
                        [
                            "title" => [
                                "ru" => "Модули",
                                "en" => "Modules"
                            ],
                            "function" => "modules",
                            "pageKey" => "adminModules"
                        ],
                        [
                            "title" => [
                                "ru" => "Пользователи",
                                "en" => "Users"
                            ],
                            "function" => "users",
                            "pageKey" => "adminUsers",
                            "accessRule" => "adminUsersRO"
                        ],
                        [
                            "title" => [
                                "ru" => "Группы",
                                "en" => "Groups"
                            ],
                            "titleLangIdx" => "adminGroups",
                            "function" => "groups",
                            "pageKey" => "adminGrous"
                        ]
                    ]
                ]
            ],
            "globalAccessKey" => "allUsers"
        ],
        "auth" => [
            "title" => "Auth pseudo module",
            "modVersion" => "4.0.0",
            "name" => "auth",
            "namespace" => "fox\\auth",
            "features" => [
                "auth"
            ],
            "isTemplate" => true,
            "singleInstanceOnly" => true,
            "authRequired" => false,
            "ACLRules" => [],
            "menuItem" => [],
            "globalAccessKey" => "isRoot"
        ],
        "meta" => [
            "title" => "Metadada pseudo module",
            "modVersion" => "4.0.0",
            "name" => "meta",
            "namespace" => "fox\\meta",
            "features" => "",
            "isTemplate" => true,
            "singleInstanceOnly" => true,
            "authRequired" => false,
            "ACLRules" => [],
            "menuItem" => [],
            "globalAccessKey" => "isRoot"
        ]
    ];

    public static function list()
    {
        $rv = [];

        foreach (static::scan() as $modName) {
            if (array_key_exists($modName, static::pseudoModules)) {
                $modInfo = new moduleInfo(static::pseudoModules[$modName]);
            } else {
                $modClass = $modName . "\module";
                $modInfo = (new $modClass())::getModInfo();
            }
            $rv[$modInfo->name] = $modInfo;
        }

        return $rv;
    }

    public static function listInstalled()
    {
        return moduleInfo::getAll();
    }

    public static function scan()
    {
        $rv = [];
        foreach (static::pseudoModules as $key => $val) {
            $rv[] = $key;
        }
        foreach (scandir(__DIR__ . "/../../modules/") as $dir) {
            if (preg_match("/^[.]/", $dir)) {
                continue;
            }
            if (! is_dir(__DIR__ . "/../../modules/" . $dir)) {
                continue;
            }
            if (! file_exists(__DIR__ . "/../../modules/" . $dir . "/module.php") && ! file_exists(__DIR__ . "/../../modules/" . $dir . "/Autoloader.php")) {
                continue;
            }
            $rv[] = $dir;
        }
        return $rv;
    }
    
    // REST API CALLS IMPLEMENTATION
    public static function API_PUT_installed(request $request)
    {
        /**
         * Request:
         * PUT core/modules/installed
         *
         * Payload:
         * module (string) - name of module
         * name (string) (optional) - name of module instance.
         * priority (int) (optional) - module priority
         *
         * Reply:
         * 201: Created
         * object: Installed module
         * 404: Not found - module not found
         * 409: Already installed
         * 409: Multi-instance not allowed
         */
        if (! $request->user->checkAccess("adminModulesInstall", "core")) {
            throw new foxException("Forbidden", 403);
        }
        
        $modName = common::clearInput($request->requestBody->module, "0-9a-zA-Z._-");
        $modInstanceName = common::clearInput($request->requestBody->name, "0-9a-zA-Z._-");
        $modPriority= common::clearInput($request->requestBody->priority, "0-9");
        if (empty($modInstanceName)) {
            $modInstanceName = $modName;
        }
        
        $modules = static::list();
        if (! array_key_exists($modName, $modules)) {
            throw new foxException("Module " . $modName . " not present", 404);
        }
        
        $mod = $modules[$modName];
        
        $modsInstalled = moduleInfo::getAll();
        if (array_key_exists($modInstanceName, $modsInstalled)) {
            foxException::throw("ERR","Already installed",409,"ALI");
        }
        
        if ($mod->singleInstanceOnly && count($mod->getInstances()) > 0) {
            foxException::throw("ERR","Multi-instances not allowed", 409,"MIN");
            
        }
        
        $mod->instanceOf = $mod->name;
        $mod->name = $modInstanceName;
        if (!empty($modPriority)) {
            $mod->modPriority=$modPriority;
        }
        $mod->save();
        
        foxRequestResult::throw(201, "Created", $mod);
    }
    
    public static function API_GET_list(request $request)
    {

        if (! $request->user->checkAccess("adminViewModules", "core")) {
            throw new foxException("Forbidden", 403);
        }
        return static::list();
    }

    public static function API_POST_instances(request $request)
    {

        if (! $request->user->checkAccess("adminViewModules", "core")) {
            throw new foxException("Forbidden", 403);
        }

        $modName = common::clearInput($request->requestBody->module, "0-9a-zA-Z._-");
        $modules = static::list();
        if (! array_key_exists($modName, $modules)) {
            throw new foxException("Module " . $modName . " not present", 404);
        }

        return $modules[$modName]->getInstances();
    }

    public static function API_GET_installed(request $request)
    {
        if (! $request->user->checkAccess("adminViewModules", "core")) {
            throw new foxException("Forbidden", 403);
        }
        return static::listInstalled();
    }
   
}
?>