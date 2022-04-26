<?php
namespace fox\auth;

/**
 *
 * Class fox\auth\session
 *
 * @copyright MX STAR LLC 2018-2022
 * @version 4.0.0
 * @author Pavel Dmitriev
 * @license GPLv3
 *
 **/

use fox\externalCallable;
use fox\foxException;
use fox\request;
use fox\modules;

class session implements externalCallable
{

    public static function APICall(request $request)
    {
        switch ($request->method) {
            case "DELETE":
                if (! ($request->authOK)) {
                    throw new foxException("Bad request", 501);
                }

                $request->token->delete();
                return;
            case "GET":
                if ($request->authOK) {
                    $modules=[];
                    $i = 0;
                    foreach (modules::listInstalled() as $mod) {
                        if (array_search("menu", $mod->features) !== false && $request->user->checkAccess($mod->globalAccessKey, $mod->name) && ! empty($mod->menuItem)) {
                            $i ++;
                            $modules[($mod->modPriority * 100) + $i] = [
                                "name" => $mod->name,
                                "instanceOf" => $mod->instanceOf,
                                "menu" => $mod->menuItem,
                                "globalAccesKey" => $mod->globalAccessKey,
                                "languages" => $mod->languages
                            ];
                        }
                    }

                    return [
                        "updated" => time(),
                        "user" => $request->token->user,
                        "acls" => $request->user->getAccessRules(),
                        "modules" => $modules
                    ];
                }
                ;
                throw new foxException("Unauthorized", 401);
                break;
        }
    }
}

?>