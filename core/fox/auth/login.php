<?php
namespace fox\auth;

/**
 *
 * Class fox\auth\login
 *
 * @copyright MX STAR LLC 2018-2022
 * @version 4.0.0
 * @author Pavel Dmitriev
 * @license GPLv3
 *
 **/

use fox\externalCallable;
use fox\foxException;
use fox\auth;
use fox\authJwt;
use fox\authToken;
use fox\request;
use fox\foxRequestResult;

class login implements externalCallable
{

    public static function APICall(request $request)
    {
        switch ($request->method) {
            case "POST":
                if ($request->authOK) {
                    throw new foxRequestResult("OK",200);
                }
                if (! (gettype($request->requestBody) == "object" && property_exists($request->requestBody, "login") && property_exists($request->requestBody, "password"))) {
                    throw new foxException("Bad request", 400);
                }

                $type = "API";
                if (property_exists($request->requestBody, "type")) {
                    $type = $request->requestBody->type;
                }

                if ($u = auth::doAuth($request->requestBody->login, $request->requestBody->password)) {
                    $t = authToken::issue($u, $type);
                    return [
                        "token" => $t->token,
                        "expire" => $t->expireStamp->isNull() ? "Never" : $t->expireStamp,
                        "jwt"=>authJwt::issueByAuthToken($t)
                    ];
                } else {
                    throw new foxException("Authorization failed", 401);
                }
                break;
            default:
                throw new foxException("Bad request", 400);
        }
    }
}

?>