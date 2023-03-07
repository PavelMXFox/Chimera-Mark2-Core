<?php namespace fox\auth;

use fox\externalCallable;
use fox\request;
use fox\common;
use fox\userInvitation;
use fox\foxException;
use fox\user;
use fox\foxRequestResult;
use fox\oAuthProfile;
use fox\config;
use fox\userGroup;
use fox\authToken;
use fox\logEntry;

class register implements externalCallable {
    
    const minPasswordLength=6;
    
    public static function API_POST_preCheck(request $request) {
        if (static::validate($request)) {
            throw new foxRequestResult("Check passed","200");
        } else {
            throw new foxException("Unknown validation failure","599");
        }
    }
    
    public static function API_POST_register(request $request) {
        if (!static::validate($request)) { throw new foxException("Unknown validation failure","599"); }
        $eMail = common::clearInput($request->requestBody->email,"0-9A-Za-z_.@-");
        $regCode = common::clearInput($request->requestBody->regCode,"0-9");
        
        $authType = explode("_", $request->requestBody->authType)[0];
        $ic=null;
        if (!empty($regCode)) {
            $ic=userInvitation::getByCode($regCode);
        } else {
            $ic =userInvitation::getByEMail($eMail);
        }
        
        if ($authType=="oauth") {
            $profile = oAuthProfile::getByHash(common::clearInput($request->requestBody->oAuthHash));
            $oac = $profile->getClient(config::get("SITEPREFIX")."/auth/oauth");
            $oac->getTokenByCode(common::clearInput($request->requestBody->oAuthCode));
            $userInfo=$oac->getUserInfo();
            $userRefId=$profile->id.":".$userInfo->sub;
            $u=user::getByRefID("oauth",$userRefId);
        
            if (!$u) { 
                
                $u = new user();
                $u->eMail=$eMail;
                $u->authType="oauth";
                $u->authRefId=$userRefId;
                $u->fullName=$userInfo->name;
                $u->save();
            }
            
        } elseif ($authType=="password") {
            $login = common::clearInput($request->requestBody->login,"0-9A-Za-z_.-");
            $passwd = $request->requestBody->password;
            if (user::getByLogin($login)) {
                foxException::throw("ERR","Login already registered","409","LAR");
            }
            
            if (preg_match("/^[0-9]/",$login)) {
                foxException::throw("ERR","Invalid login format num","406","ILF");
            }
            
            if (strlen($login) < 5) {
                foxException::throw("ERR","Invalid login format len","406","ILF");
            }

            if (strlen($passwd) < static::minPasswordLength) {
                foxException::throw("ERR","Invalid password format len","406","IPF");
            }
            
            $u=new user();
            $u->login=$login;
            $u->fullName=common::clearInput($request->requestBody->fullName,"0-9A-Za-zА-Яа-я ._-");
            $u->authType="internal";
            $u->eMail=$eMail;
            $u->setPassword($passwd);
            $u->save();
        }

        if ($ic) {
            foreach ($ic->joinGroupsId as $grid) {
                $group = new userGroup($grid);
                $group->join($u);
            }
            if (!$ic->allowMultiUse) { $ic->delete(); }
        }
        
        try {
            $u->sendEMailConfirmation();
        } catch (\Exception $e) {
            trigger_error($e->getMessage());    
        }
        logEntry::add($request->instance, static::class, __FUNCTION__, null, "User ".$u->login." registered ", "INFO", $u,  "user", $u->id);
        $t = authToken::issue($u, "WEB");
        return [
            "token" => $t->token,
            "expire" => $t->expireStamp->isNull() ? "Never" : $t->expireStamp,
            "jwt"=>\fox\authJwt::issueByAuthToken($t)
        ];
    }

    public static function API_POST_recovery(request $request) {
        $eMail = common::clearInput($request->requestBody->email,"0-9A-Za-z_.@-");
        if (!common::validateEMail($eMail)) { foxException::throw("ERR","Invalid eMail format",406,"IMF");}
        $u=user::getByEmail($eMail);
        if (!$u || $u->authType!=='internal' || !$u->eMailConfirmed) { foxException::throw("ERR","Not found",404,"URNF");}
        $u->sendPasswordRecovery();
    }
    
    public static function API_POST_validateRecovery(request $request) {
        $code = common::clearInput($request->requestBody->code,"0-9");
        $eMail = common::clearInput($request->requestBody->email,"0-9A-Za-z_.@-");
        if (!common::validateEMail($eMail)) { foxException::throw("ERR","Invalid eMail format",406,"IMF");}
        $u=user::getByEmail($eMail);
        if (!$u || $u->authType!=='internal' || !$u->eMailConfirmed) { foxException::throw("ERR","Not found",404,"URNF");}
        if ($u->validateRecoveryCode($code)) {
            return;
        } else {
            foxException::throw("ERR", "Validation failed", 400,"IVCC");
        }
    }
    
    public static function API_POST_setNewPassword(request $request) {
        $code = common::clearInput($request->requestBody->code,"0-9");
        $eMail = common::clearInput($request->requestBody->email,"0-9A-Za-z_.@-");
        $passwd=$request->requestBody->newPasswd;
        if (strlen($passwd) < static::minPasswordLength) {
            foxException::throw("ERR","Invalid password format len","406","IPF");
        }
        if (!common::validateEMail($eMail)) { foxException::throw("ERR","Invalid eMail format",406,"IMF");}
        $u=user::getByEmail($eMail);
        if (!$u || $u->authType!=='internal' || !$u->eMailConfirmed) { foxException::throw("ERR","Not found",404,"URNF");}
        if ($u->validateRecoveryCode($code,true)) {

            $u->setPassword($passwd);
            $u->save();
            
            
            $t = authToken::issue($u, "WEB");
            
            logEntry::add($request->instance, static::class, __FUNCTION__, null, "Password recovered for user ".$u->login, "INFO", $u,  "user", $u->id);
            return [
                "token" => $t->token,
                "expire" => $t->expireStamp->isNull() ? "Never" : $t->expireStamp,
                "jwt"=>\fox\authJwt::issueByAuthToken($t)
            ];
        } else {
            foxException::throw("ERR", "Validation failed", 400,"IVCC");
        }
    }
    
    protected static function validate(request $request) {
        $eMail = common::clearInput($request->requestBody->email,"0-9A-Za-z_.@-");
        $code = common::clearInput($request->requestBody->regCode,"0-9");
        $ic=null;
        if (!empty($code)) {
            $ic=userInvitation::getByCode($code);

            if (!$ic || (!$ic->expireStamp->isNull() && $ic->expireStamp->stamp<time())) {
                foxException::throw("ERR", "Invalid or expired RegCode", 403,"IRC");
            }
            
        } else {
            $ic =userInvitation::getByEMail($eMail);
        }

        if (!config::get("ALLOW_REGISTER") && !$ic) {
            foxException::throw("ERR", "Registration not allowed", 403,"RNE");
        }
        
        
        if (!common::validateEMail($eMail)) {
            foxException::throw("ERR", "Invalid eMail format", 406,"IMF");
        }
        
        if (user::getByEmail($eMail)) {
            foxException::throw("ERR", "EMail already registered", 403,"EAR");
        }
        return true;
    }
}
?>