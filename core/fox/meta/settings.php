<?php
namespace fox\meta;

/**
 *
 * Class fox\meta\settings
 *
 * @copyright MX STAR LLC 2018-2022
 * @version 4.0.0
 * @author Pavel Dmitriev
 * @license GPLv3
 *
 **/

use fox\cache;
use fox\externalCallable;
use fox\request;
use fox\config;
use fox\moduleInfo;
use fox\time;
use fox\modules;
use fox\oAuthProfile;

class settings implements externalCallable
{
    public static function APICall(request $request)
    {
        $c=new cache();

        $oauth=$c->get("coreSettingsOauthProfiles");
        if ($oauth==null) {
            $profiles = oAuthProfile::search()->result;
            $oauth=[];
            foreach ($profiles as $p) {
                if ($p->enabled) {
                    $oauth[] = [
                        "name"=>$p->name,
                        "id"=>$p->id,
                        "icon"=>$p->getClient(null)->getAuthIcon(),
                    ];
                }
            }
            $c->set("coreSettingsOauthProfiles", $oauth);
        }


        $themes=$c->get("coreSettingsThemes");
        if ($themes==null) {
            $themes=[];
            foreach (moduleInfo::getByFeature("theme") as $mod) {
                if ($mod->themes) {
                    $themes=array_merge($themes, $mod->themes);        
                } else {
                    $themes[$mod->name]=$mod->title;
                }
            }
            $c->set("coreSettingsThemes",$themes);
        }
                
        $coreLangs=$c->get("coreSettingsLanguages");
        if ($coreLangs==null) {
            $coreLangs=modules::list()["core"]->languages;
            $c->set("coreSettingsLanguages",$coreLangs);
        }
        
         
        return [
            "title" => config::get("TITLE"),
            "sitePrefix" => config::get("SITEPREFIX"),
            "theme" => config::get("DEFAULT_THEME") === null ? "chimera" : config::get("DEFAULT_THEME"),
            "buildVersion" => "undefined",
            "buildDate" => time::current()->dayStart,
            "pageSize" => config::get("DEFAULT_PAGESIZE") === null ? "30" : config::get("DEFAULT_PAGESIZE"),
            "language" => config::get("DEFAULT_LANGUAGE") === null ? "ru" : config::get("DEFAULT_LANGUAGE"),
            "defaultModule" => config::get("DEFAULT_MODULE") === null ? "core" : config::get("DEFAULT_MODULE"),
            "sessionRenewInterval" => config::get("SESSION_RENEW_SEC") === null ? "3600" : config::get("SESSION_RENEW_SEC"),
            "coreLanguages" => $coreLangs,
            "oauthProfiles"=>$oauth,
            "themes"=>$themes,
            "rabbitMqEnabled"=>strtolower(config::get("RABBITMQ_ENABLED"))==="true",
            "rabbitMqWS"=>config::get("RABBITMQ_WS_URL")===null?"/rabbitmq":config::get("RABBITMQ_WS_URL"),
            "rabbitMqVHost"=>config::get("RABBITMQ_VHOST")===null?"/":config::get("RABBITMQ_VHOST")
        ];
    }
}
?>