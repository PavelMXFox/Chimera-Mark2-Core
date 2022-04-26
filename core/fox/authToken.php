<?php


namespace fox;

/**
 *
 * Class authToken
 *
 * @copyright MX STAR LLC 2018-2022
 * @version 4.0.0
 * @author Pavel Dmitriev
 * @license GPLv3
 * 
 * @property-read $id
 * @property-read $token
 * @property-read $user
 *
 **/

class authToken extends baseClass
{

    protected $id;

    protected $token1;

    protected $token2;

    protected $token2b;

    public $userId;

    public time $issueStamp;

    // Issue stamp of token
    public time $renewStamp;

    // renew expiration stamp
    public time $expireStamp;

    // token expiration stamp
    protected ?user $__user = null;

    /*
     * Token type - defines usage area
     * "API" = REST API
     * "WEB" = WEB UI
     * "APP" = Mobile Application
     *
     * defaultTTL = days,
     * renewTTL = minutes,
     * after renewTTL and before defaultTTL token may be renewed. if allowRenew then default TTL restarted at any renew request.
     */
    public const tokenTypes = [
        "API" => [
            "name" => "REST API",
            "defaultTTL" => 10,
            "allowLogin" => false,
            "allowRenew" => true,
            "renewTTL" => 30
        ],
        "WEB" => [
            "name" => "WWW",
            "defaultTTL" => 32,
            "allowLogin" => true,
            "allowRenew" => true,
            "renewTTL" => 30
        ],
        "APP" => [
            "name" => "Mobile APP",
            "defaultTTL" => 10,
            "allowLogin" => true,
            "allowRenew" => true,
            "renewTTL" => 30
        ]
    ];

    public string $type = "API";

    public static $allowDeleteFromDB = true;

    public static $sqlTable = "tblAuthTokens";

    public static $sqlColumns = [
        "userId" => [
            "type" => "INT",
            "index" => "INDEX",
            "nullable" => false
        ],
        "token1" => [
            "type" => "CHAR(64)",
            "index" => "UNIQUE",
            "nullable" => true
        ],
        "token2" => [
            "type" => "CHAR(64)",
            "nullable" => false
        ],
        "token2b" => [
            "type" => "CHAR(64)",
            "nullable" => true
        ]
    ];

    public function __xConstruct()
    {
        $this->issueStamp = new time(time());
        $this->expireStamp = new time();
        $this->renewStamp = new time();
    }

    public static function dropExpired()
    {
        $sql = sql::getConnection();
        $sql->quickExec("delete from `" . static::$sqlTable . "` where `expireStamp` is not NULL and `expireStamp` < NOW()");
    }

    public static function getByToken(string $token)
    {
        if (strlen($token) != 128) {
            return null;
        }
        $token1 = substr($token, 0, 64);
        $token2 = substr($token, 64, 64);

        $tx = new static();
        $sql = $tx->getSql();
        $sql->quickExec("START TRANSACTION");
        $row = $sql->quickExec1Line($tx->__sqlSelectTemplate . " where `i`.`token1` = '" . $token1 . "' AND (`expireStamp` is NULL OR `expireStamp` >= NOW()) FOR UPDATE");

        $t = new static($row, $sql);

        $renewTTL = config::get("TOKEN_RENEW_" . $t->type) === null ? static::tokenTypes[$t->type]["renewTTL"] : config::get("TOKEN_RENEW_" . $t->type);
        
        $rv=null;
        if (time() > $t->expireStamp->stamp) {
            // expired, delete
            // $t->delete();
            trigger_error("Token expired");
        } else if (time() < $t->renewStamp->stamp && time() < $t->expireStamp->stamp && $token1 == $t->token1 && ($token2 == $t->token2 || $token2 == $t->token2b)) {
            // token OK (not expired, match 2A or 2B
            $rv= $t;
        } else if (time() > $t->renewStamp->stamp && time() < $t->expireStamp->stamp && $t->token1 == $token1 && $t->token2 == $token2) {
            // renew expired, 2A matched - renew
            trigger_error("Token renew started");
            $t->renew();
            header("X-Fox-Token-Renew: " . $t->token);
            trigger_error("Token renew completed");
            $rv= $t;
        } else if (time() < $t->expireStamp->stamp && time() > ($t->renewStamp->stamp + $renewTTL / 2) && $t->token1 == $token1 && $t->token2 != $token2 && $t->token2b == $token2) {
            // RenewExpired, renew+renewTTL/2 - not expired, 2A failed, 2B matched - not update, void conflict, OK
            trigger_error("Token2B used!");
            $rv= $t;
        } else if ($t->token1 == $token1 && $t->token2 != $token2 && $token2 != $t->token2b) {
            // token2 failed - token are compromised
            trigger_error("Token #".$t->id." are compromised!");
            // $t->delete();
        } else {
            
        }
        
        $sql->quickExec("COMMIT");

        if ($rv===null) {
            trigger_error("Token:: time: ".time()."; Expire: ".$t->expireStamp->stamp."dT(".(time() - $t->expireStamp->stamp)."); Renew: ".$t->renewStamp->stamp."dT(".(time() - $t->renewStamp->stamp).")");
        }
        
        return $rv;
    }

    public static function issue(user $user, $type = null, ?int $expireInDays = null)
    {
        static::dropExpired();
        $t = new authToken();
        $t->userId = $user->id;
        $type = strtoupper($type);
        if (array_key_exists($type, static::tokenTypes)) {
            $t->type = $type;
        } else {
            throw new foxException("Invalid token type");
        }

        if ($expireInDays === null) {
            $expireInDays = config::get("TOKEN_TTL_" . $type) === null ? static::tokenTypes[$type]["defaultTTL"] : config::get("TOKEN_TTL_" . $type);
        }

        $renewTTL = config::get("TOKEN_RENEW_" . $type) === null ? static::tokenTypes[$type]["renewTTL"] : config::get("TOKEN_RENEW_" . $type);

        $t->expireStamp = empty($expireInDays) ? (new time()) : (new time(time() + ($expireInDays * 86400)));
        $t->renewStamp = empty($renewTTL) ? (new time()) : (new time(time() + ($renewTTL * 60)));
        ;

        for ($i = 0; $i < 32; $i ++) {
            $token = substr(preg_replace("/[-_+=\\/]/", "", base64_encode(random_bytes(64))), 0, 64);
            if (static::getByToken($token) === null) {
                break;
            }
        }

        if ($i >= 32) {
            throw new foxException("Unable to find token in $i iterations");
        }
        if ($i > 0) {
            trigger_error("Token found in $i iterations");
        }
        $t->token1 = $token;
        $t->token2 = substr(preg_replace("/[-_+=\\/]/", "", base64_encode(random_bytes(64))), 0, 64);
        $t->save();
        return $t;
    }

    public function renew()
    {
        $expireAllowRenew = config::get("TOKEN_ALLOW_RENEW_" . $this->type) === null ? static::tokenTypes[$this->type]["allowRenew"] : (config::get("TOKEN_ALLOW_RENEW_" . $this->type) == "true");

        $renewTTL = config::get("TOKEN_RENEW_" . $this->type) === null ? static::tokenTypes[$this->type]["renewTTL"] : config::get("TOKEN_RENEW_" . $this->type);

        $this->renewStamp = empty($renewTTL) ? (new time()) : (new time(time() + ($renewTTL * 60)));
        ;
        $this->token2b = $this->token2;
        $this->token2 = substr(preg_replace("/[-_+=\\/]/", "", base64_encode(random_bytes(64))), 0, 64);

        if ($expireAllowRenew) {
            $expireInDays = config::get("TOKEN_TTL_" . $this->type) === null ? static::tokenTypes[$this->type]["defaultTTL"] : config::get("TOKEN_TTL_" . $this->type);
            $this->expireStamp = empty($expireInDays) ? (new time()) : (new time(time() + ($expireInDays * 86400)));
        } else {
            if ($this->expireStamp < $this->renewStamp) {
                $this->renewStamp = $this->expireStamp;
            }
        }
        $this->save();
        return $this->token;
    }

    public function __get($key)
    {
        switch ($key) {
            case "token":
                return $this->token1 . $this->token2;

            case "user":
                if (empty($this->__user) && ! empty($this->userId)) {
                    $this->__user = new user($this->userId);
                }
                return $this->__user;
            default:
                return parent::__get($key);
        }
    }
}
?>