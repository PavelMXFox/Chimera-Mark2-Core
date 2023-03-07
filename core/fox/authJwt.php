<?php
namespace fox;

use Firebase\JWT\JWT;

class authJwt {
    public static function issueByAuthToken(authToken $token) {
        $rabbitMqInstance=config::get("RABBIT_INSTANCE")?config::get("RABBIT_INSTANCE"):"rabbitmq";
        $rabbitMqVhost=config::get("RABBIT_VHOST")?config::get("RABBIT_VHOST"):"/";
        $rabbitMqVhostURLe=strtolower(urlencode($rabbitMqVhost));
        $jwtKey=config::get("JWT_KEY")?config::get("JWT_KEY"):getenv("HOSTNAME");
        $payload = ([
            "sub"=>$token->user->invCode,
            "iss"=>"ChimeraCore",
            "iat"=>time(),
            "exp"=>$token->renewStamp,
            "aud"=>[$rabbitMqInstance,"fox"],
            "foxsid"=>$token->sessionId,
            "foxacls"=>$token->user->getAccessRules(),
            "scope"=> [
                $rabbitMqInstance.".configure:".$rabbitMqVhostURLe."/foxuid.".$token->user->invCode,
                $rabbitMqInstance.".read:".$rabbitMqVhostURLe."/foxuid.".$token->user->invCode,
                $rabbitMqInstance.".write:".$rabbitMqVhostURLe."/foxuid.".$token->user->invCode,
                $rabbitMqInstance.".configure:".$rabbitMqVhostURLe."/foxsid.".$token->sessionId,
                $rabbitMqInstance.".read:".$rabbitMqVhostURLe."/foxsid.".$token->sessionId,
                $rabbitMqInstance.".write:".$rabbitMqVhostURLe."/foxsid.".$token->sessionId,
                $rabbitMqInstance.".read:".$rabbitMqVhostURLe."/fox.barcode",
                $rabbitMqInstance.".read:".$rabbitMqVhostURLe."/fox.messages/".$token->user->invCode,
                $rabbitMqInstance.".read:".$rabbitMqVhostURLe."/fox.notify/".$token->user->invCode,
                $rabbitMqInstance.".read:".$rabbitMqVhostURLe."/fox.service/".$token->user->invCode,
                $rabbitMqInstance.".read:".$rabbitMqVhostURLe."/fox.service/".$token->sessionId,
                $rabbitMqInstance.".write:".$rabbitMqVhostURLe."/fox.ping/".$token->sessionId,
                $rabbitMqInstance.".read:".$rabbitMqVhostURLe."/fox.ping/".$token->sessionId,
            ]
        ]);

        return JWT::encode($payload,$jwtKey, "HS256");
    }
}
?>