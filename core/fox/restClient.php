<?php namespace fox;

/**
 *
 * Class fox\restClient
 *
 * @copyright MX STAR LLC 2018-2022
 * @version 4.0.0
 * @author Pavel Dmitriev
 * @license GPLv3
 *
 **/

class restClient {
    public $authToken=null;
    public $authTokenName=null;
    public $urlPrefix=null;
    public $extraHeaders=["Content-type: application/json"];
    
    public function __construct($urlPrefix, $authToken=null, $authTokenName="Authorization: Token") {
        $this->urlPrefix=$urlPrefix;
        $this->authToken=$authToken;
        $this->authTokenName=$authTokenName;
    }
    
    public function exec(restRequest $request, bool $debug=false) : restResult {
        $rv=new restResult();
        
        $ch = curl_init();
        if (gettype($request->extraHeaders)=='array') {
            $options = array_merge($this->extraHeaders, (gettype($request->extraHeaders)=='array')?$request->extraHeaders:[]);
        } else {
            $options = $this->extraHeaders;
        }
        
        if ($this->authToken) { array_push($options, $this->authTokenName." ".$this->authToken); };

        switch (strtoupper($request->method)) {
            case "PUT":
                curl_setopt($ch, CURLOPT_PUT, 1);
                break;

            case "POST":
                curl_setopt($ch, CURLOPT_POST, 1);
                break;
                
            default:
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, strtoupper($request->method));
                break;
                
        }
        
        
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        
        curl_setopt($ch, CURLOPT_HEADERFUNCTION, array($rv, "handleHeaderLine"));
        
        curl_setopt($ch, CURLOPT_HTTPHEADER, $options);
        
        $payload=$request->data;
        if (gettype($payload) != 'string') {
            $payload=json_encode($payload);
        }
      
        $url=$this->urlPrefix.$request->function;
        
        if (!empty($payload)) {
            if ($request->method=="GET") {
                $urlSuffix="";
                foreach($request->data as $key=>$val) {
                    $urlSuffix.=(empty($urlSuffix)?"":"&").urlencode($key)."=".urlencode($val);
                }
                $url.="?".$urlSuffix;
                
            } else {
                curl_setopt( $ch, CURLOPT_POSTFIELDS, $payload);
            }
        }
        curl_setopt($ch, CURLOPT_URL, $url);
        
        $rv->rawReply = curl_exec($ch);
        $rv->reply=json_decode($rv->rawReply);        
        if ($debug) { $rv->curlInfo=curl_getinfo($ch); } else { $rv->curlInfo=null; }
        $rv->statusCode= curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        $rv->success=(array_search($rv->statusCode, $request->successCodes)!==false);
        
        curl_close($ch);
        return $rv;

    }
    
}
?>