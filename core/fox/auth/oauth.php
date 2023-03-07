<?php
namespace fox\auth;

use fox\authJwt;
use fox\externalCallable;
use fox\request;
use fox\oAuthProfile;
use fox\config;
use fox\common;
use fox\foxException;
use fox\user;
use fox\authToken;

/**
 *
 * Class fox\auth\oauth
 *
 * @copyright MX STAR LLC 2018-2022
 * @version 4.0.0
 * @author Pavel Dmitriev
 * @license GPLv3
 *
 **/

class oauth implements externalCallable
{
    public static function APICall(request $request) {
        switch ($request->method) {
            case "GET":
                try {
                    $profile = new oAuthProfile(common::clearInput($request->function,"0-9"));
                } catch (\Exception $e) {
                    if ($e->getCode()==691) {
                        throw new foxException("Not found",404);
                    } else {
                        throw $e;
                    }
                }
                if ($profile->deleted || !$profile->enabled) {
                    throw new foxException("Not found",404);
                }
                return [
                    "id"=>$profile->id,
                    "name"=>$profile->name,
                    "url"=>$profile->getClient(config::get("SITEPREFIX")."/auth/oauth")->getAuthURL(),
                    "icon"=>$profile->getClient(config::get("SITEPREFIX"))->getAuthIcon(),
                ];
                break;
                
            case "POST":
                $profile = oAuthProfile::getByHash(common::clearInput($request->requestBody->hash));
                $oac = $profile->getClient(config::get("SITEPREFIX")."/auth/oauth");
                $oac->getTokenByCode(common::clearInput($request->requestBody->code));
                $userInfo=$oac->getUserInfo();
                $userRefId=$profile->id.":".$userInfo->sub;
                $u=user::getByRefID("oauth",$userRefId);
                
                if (!$u) {
                    foxException::throw("ERR", "User not registered",401,"UNR");
                }
                
                $t = authToken::issue($u, "WEB");
                return [
                    "token" => $t->token,
                    "expire" => $t->expireStamp->isNull() ? "Never" : $t->expireStamp,
                    "jwt"=>authJwt::issueByAuthToken($t)
                ];
                
                break;
            default:
                throw new foxException("Method not implemented",405);
        }
    }
}