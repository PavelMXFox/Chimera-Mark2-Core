<?php
namespace fox\auth;

use fox\externalCallable;
use fox\foxException;
use fox\request;
use fox\authJwt;

class renew implements externalCallable {

    public static function APICall(request $request) {
        if (!$request->authOK) {
            throw new foxException("Unauthorized", 401);
        }

        if ($request->token->renewed) {
            trigger_error("Token already renewed by automatic");
        } else {
            $request->token->renew();
        }
        
        return [
            "token" => $request->token->token,
            "expire" => $request->token->expireStamp->isNull() ? "Never" : $request->token->expireStamp,
            "jwt"=>authJwt::issueByAuthToken($request->token)
        ];

    }
}