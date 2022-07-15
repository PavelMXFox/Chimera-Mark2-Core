<?php
namespace fox;

/**
 *
 * Class fox\urlParcer
 *
 * @copyright MX STAR LLC 2021
 * @version 4.0.0
 * @author Pavel Dmitriev
 * @license GPLv3
 *        
 * @property-read user $user
 * @property-read $userId
 *               
 */
class request extends baseClass implements noSqlMigration
{

    public $method;

    public $instance;
    
    public $module;

    public $function;

    public $parameters;

    public $requestBody;

    public $authMethod;

    public $authVal;

    public ?authToken $token;

    public bool $authOK = false;

    protected $clientIp;

    public static function getClientIP()
    {
        if (array_key_exists("HTTP_X_REAL_IP", $_SERVER)) {
            $_SERVER["REMOTE_ADDR"] = $_SERVER["HTTP_X_REAL_IP"];
        } elseif (array_key_exists("HTTP_X_FORWARDED_FOR", $_SERVER)) {
            $_SERVER["REMOTE_ADDR"] = $_SERVER["HTTP_X_FORWARDED_FOR"];
        }
        return $_SERVER["REMOTE_ADDR"];
    }

    function __xConstruct()
    {
        $this->parce();
        return false;
    }

    function parce()
    {
        global $__foxRequestInstance;
        $this->method = (empty($_SERVER["REQUEST_METHOD"]) ? "GET" : $_SERVER["REQUEST_METHOD"]);
        if (array_key_exists("FOX_REWRITE", $_SERVER) && $_SERVER["FOX_REWRITE"] != "yes") {
            $prefix = ($_SERVER["CONTEXT_PREFIX"] . "index.php/");
        } else {
            $prefix = ($_SERVER["CONTEXT_PREFIX"]);
        }

        $this->clientIp = static::getClientIP();

        $this->requestBody = json_decode(file_get_contents("php://input"));

        $prefix = preg_replace([
            "![/]+!",
            "![\.]+!"
        ], [
            "\/",
            "\."
        ], $prefix);
        $req = (preg_replace("/" . $prefix . "/", '', $_SERVER["REQUEST_URI"]));
        $req = explode("/", explode("?", $req, 2)[0]);

        if (count($req) > 0) {
            if ($req[count($req) - 1] == "") {
                array_splice($req, - 1);
            }
            if ($req[0] == "") {
                array_splice($req, 0, 1);
            }
        }

        $this->module = ((count($req) > 0) ? $req[0] : NULL);
        $this->instance=$this->module;
        $__foxRequestInstance=$this->instance;
        $this->function = ((count($req) > 1) ? $req[1] : NULL);

        if (count($req) > 2) {
            array_splice($req, 0, - (count($req) - 2));
        } else {
            $req = [];
        }
        $this->parameters = $req;

        if (array_key_exists("Authorization", apache_request_headers()) || (array_key_exists("Token", $_COOKIE) && preg_match("/^[0-9A-Za-z=+_-]*$/", $_COOKIE["Token"]))) {
            $match = [];

            /*
             * removed Cookie authorization
             *
             *
             * if ((empty($this->__xId) || $this->__xId=="WEB") && array_key_exists("Token", $_COOKIE) && preg_match("/^[0-9A-Za-z=+_-]*$/", $_COOKIE["Token"])) {
             * $this->authMethod="Cookie";
             * $this->__xId="WEB";
             * $this->doTokenAuth($_COOKIE["Token"]);
             *
             * } else
             */
            if (preg_match("/^(Token) ([A-Za-z0-9=+_-]*)$/", apache_request_headers()["Authorization"], $match)) {
                $this->authMethod = $match[1];
                switch ($this->authMethod) {
                    case "Token":
                        $this->doTokenAuth($match[2]);
                        break;
                }
            } else {
                $this->authMethod = $this->authVal = null;
            }
        }
    }

    protected function doTokenAuth($val)
    {
        if (preg_match("/^[0-9A-Za-z=+_-]*$/", $val)) {
            $token = authToken::getByToken($val);
            if ($token && (empty($this->__xId) || $token->type == $this->__xId)) {
                $this->token = $token;

                if ($this->token->user->active) {
                    $this->authOK = true;
                } else {
                    $this->authOK = false;
                }
            }
        } else {
            $this->authMethod = $this->authVal = null;
            $this->authOK = false;
        }
    }

    function shift()
    {
        global $__foxRequestInstance;
        $this->instance=$this->module;
        $this->module = $this->function;
        $__foxRequestInstance=$this->instance;
        $this->function = array_shift($this->parameters);
        return $this;
    }

    public function __get($key)
    {
        switch ($key) {
            case "user":
                if (! $this->authOK) {
                    throw new foxException('Unauthorized', 401);
                }
                return $this->token->user;
            default:
                return parent::__get($key);
        }
    }

    public static function get($type = null)
    {
        global $__foxRequest;
        if (empty($__foxRequest)) {
            $__foxRequest = new static($type);
        }
        return $__foxRequest;
    }
    
    public function blockIfNoAccess(string $rule, string $modInstance=null) {
        if ($modInstance==null) { $modInstance=$this->instance; }
        if (! $this->user->checkAccess($rule, $modInstance)) {
            throw new foxException("Forbidden", 403);
        }
    }

    public function checkAccess(string $rule, string $modInstance=null) {
        if ($modInstance==null) { $modInstance=$this->instance; }
        return $this->user->checkAccess($rule, $modInstance);
    }
    
    public function getRequestBodyItem($key) {
        if ($this->requestBody!=null && property_exists($this->requestBody, $key)) {
            return $this->requestBody->{$key};
        } else {
            return null;
        }
    }

    public function getParamItem($key) {
        if ($this->parameters !=null && array_key_exists($key, $this->parameters)) {
            return $this->parameters[$key];
        } else {
            return null;
        }
    }
    
}
?>