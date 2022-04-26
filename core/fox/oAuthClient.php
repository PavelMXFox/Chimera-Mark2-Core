<?php namespace fox;

/*
 * Config:
 *  authorization_endpoint
 *  token_endpoint
 *  userinfo_endpoint
 */

/**
 *
 * Class fox\oAuthClient
 *
 * @copyright MX STAR LLC 2018-2022
 * @version 4.0.0
 * @author Pavel Dmitriev
 * @license GPLv3
 *
 **/
class oAuthClient {
    protected $url;
    protected $appId;
    protected $appKey;
    protected $redirectURL;
    protected $scope;
    protected $config;
    protected $xTokens;
    protected $mode="generic";
    
    public const configs=[
        "vk"=>[
            "authorization_endpoint"=>"https://oauth.vk.com/authorize",
            "token_endpoint"=>"https://oauth.vk.com/access_token",
            "icon"=>"fab fa-vk",
        ],
        "yandex"=>[
            "authorization_endpoint"=>"https://oauth.yandex.ru/authorize",
            "token_endpoint"=>"https://oauth.yandex.ru/token",
            "userinfo_endpoint"=>"https://login.yandex.ru/info",
            "icon"=>"fab fa-yandex",
        ],
        "gitlab"=>[
            "authorization_endpoint"=>'${URL}/oauth/authorize',
            "token_endpoint"=>'${URL}/oauth/token',
            "userinfo_endpoint"=>'${URL}/oauth/userinfo',
            "icon"=>"fab fa-gitlab",
            "scope"=>"openid",
        ],
        "gitea"=>[
            "authorization_endpoint"=>'${URL}/login/oauth/authorize',
            "token_endpoint"=>'${URL}/login/oauth/access_token',
            "jwks_uri"=>'${URL}/login/oauth/keys',
            "userinfo_endpoint"=>'${URL}/login/oauth/userinfo',
            "icon"=>"fas fa-coffee",
            "scope"=>"openid",
        ],
        
    ];
    
    public function __construct($url, $id, $key, $redirect, $scope="openid", $config=null) {
        $this->url=$url;
        $this->appId=$id;
        $this->appKey=$key;
        $this->redirectURL=$redirect;
        $this->scope=$scope;
        
        if (gettype($config)=="string") {
            if (array_key_exists($config, static::configs)) {
                $this->mode=$config;
                $ref=[];
                foreach (static::configs[$config] as $key=>$val) {
                    $ref[$key]=str_replace('${URL}', $this->url, $val);
                }
                $this->config=(object)$ref;
                if (empty($this->scope) && !empty($ref["scope"])) { $this->scope = $ref["scope"]; }
            } else {
                throw new foxException("Invalid config mode");
            }
            
        } else {
            $this->config=$config;
        }
    }
    
    public function getConfig() {
        if (empty($this->config)) {
            $this->config=json_decode(file_get_contents($this->url."/.well-known/openid-configuration"));
            if (empty($this->config)) {
                throw new \Exception("Unable to fetch Oauth2 Config");
            }
        }
    }
    
    public function getAuthURL() {
        $this->getConfig();
        return $this->config->authorization_endpoint."?response_type=code&client_id=$this->appId&redirect_uri=".urlencode($this->redirectURL).(empty($this->scope)?"":"&scope=".$this->scope);
    }
    
    public function getAuthIcon() {
        if (property_exists($this->config,"icon")) {
            return $this->config->icon;
        } else {
            return "fa-brands fa-openid";
        }
    }
    
    public function getToken() {
        return $this->xTokens;
    }
    
    public function getTokenByCode($code,$mode="generic") {
        if (empty($code)) {
            throw new foxException("Empty code not allowed");
        }
        $this->getConfig();
        switch ($this->mode) {
            case "yandex":
                $req=new restRequest(null,"POST","grant_type=authorization_code&client_id=".urlencode($this->appId)."&client_secret=".urlencode($this->appKey)."&code=$code");
                break;
            case "vk":
                $req=new restRequest(null,"GET",[
                    "client_id"=>$this->appId,
                    "client_secret"=>$this->appKey,
                    "code"=>$code,
                    "grant_type"=>"authorization_code",
                    "redirect_uri"=>$this->redirectURL
                ]);
                break;
            default:
                $req=new restRequest(null,"POST",[
                     "client_id"=>$this->appId,
                     "client_secret"=>$this->appKey,
                     "code"=>$code,
                     "grant_type"=>"authorization_code",
                     "redirect_uri"=>$this->redirectURL
                 ]);
                break;
        }
        
        $client=new restClient($this->config->token_endpoint);
        $res=$client->exec($req);
        if ($res->success) {
            $this->xTokens=$res->reply;
            return $this->xTokens;
        } else {
            trigger_error(json_encode($res));
            throw new foxException("Invalid token");
            exit;
        }
    }
    
    public function getUserInfo($mode="generic") {
        $this->getConfig();
        $this->getToken();

        switch ($this->mode) {
            case "vk":
                $client = new restClient("https://api.vk.com/method/users.get", $this->xTokens->access_token, "Authorization: Bearer");
                $res2=$client->exec(new restRequest(null,"GET",[
                    'uids' => $this->xTokens->user_id,
                    'fields' => 'uid,first_name,last_name',
                    'v'=>'5.131',
                ]));
                break;
            default:
                $client = new restClient($this->config->userinfo_endpoint, $this->xTokens->access_token, "Authorization: Bearer");
                $res2=$client->exec(new restRequest(null,"GET"));
                break;
        }
        
        
        
        if ($res2->success) {
            switch ($this->mode) {
                case "yandex":
                    $rv=(object)[
                        "name"=>$res2->reply->real_name,
                        "sub"=>$res2->reply->id,
                        "email"=>$res2->reply->default_email,
                        "groups"=>[],
                    ];
                    break;
                case "vk";
                    $rv=(object)[
                        "name"=>$res2->reply->response[0]->first_name." ".$res2->reply->response[0]->last_name,
                        "sub"=>$res2->reply->response[0]->id,
                        "email"=>null,
                        "groups"=>[],
                    ];
                    break;
                    
                default:
                    @$rv=(object)[
                        "name"=>$res2->reply->name,
                        "sub"=>$res2->reply->sub,
                        "email"=>$res2->reply->email,
                        "groups"=>$res2->reply->groups,
                    ];
                    break;
            }
            return $rv;            
        } else {
            trigger_error(json_encode($res2));
            throw new foxException("Unable to fetch userinfo");
        }
    }
 }