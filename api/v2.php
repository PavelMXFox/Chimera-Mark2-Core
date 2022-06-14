<?php
require_once(__DIR__.'/../Autoloader.php');

/**
 *
 * Script api\v2.php
 *
 * @copyright MX STAR LLC 2018-2022
 * @version 4.0.0
 * @author Pavel Dmitriev
 * @license GPLv3
 *
 **/

use fox\request;
use fox\moduleInfo;
use fox\foxException;
use fox\foxRequestResult;
$request = request::get();

try {
    if ($request->module !== 'api') {
        throw new foxException("Invalid request",400);
    }
    
    $request->shift();
    
    if ($request->module !== "v2") {
        throw new foxException("Invalid API version",400);
    }
    $request->shift();
    $modules=moduleInfo::getAll();
    if (!array_key_exists(request::get()->module, $modules)) {
        if (request::get()->authOK) {
            throw new foxException("Invalid module",404);
        } else {
            throw new foxException("Unauthorized",401);
        }
    }
    
    if ($modules[request::get()->module]->authRequired && !request::get()->authOK) {
        throw new foxException("Unauthorized",401);
    }
    
    $modNS=$modules[request::get()->module]->namespace;
    request::get()->shift();
    $className=$modNS."\\".request::get()->module;
    if (!class_exists($className)) {
        throw new foxException("Not found",404);
    }
    
    if(!is_a($className, fox\externalCallable::class,true)) {
        throw new foxException("Not found",404);
    }
    
    ob_clean();
    header('Content-Type: application/json; charset=utf-8');
    
    $apiMethod=fox\common::clearInput($request->method,"A-Z");
    $apiFunction=fox\common::clearInput($request->function,"a-zA-Z0-9");
    $apiXFunction=empty($request->parameters[0])?NULL:fox\common::clearInput($request->parameters[0],"a-zA-Z0-9");
    
    $apiCallMethod="API_".$apiMethod."_".$apiFunction;
    $apiXCallMethod="APIX_".$apiMethod."_".$apiXFunction;
    $apiZCallMethod="API_".$apiMethod;
    
    if (method_exists($className, $apiCallMethod)) {
        $rv=$className::$apiCallMethod($request);
    } else if (($apiXFunction!==null) && method_exists($className, $apiXCallMethod)) {
        $rv=$className::$apiXCallMethod($request);
    } else if (method_exists($className, $apiZCallMethod)) {
        $rv=$className::$apiZCallMethod($request);
    } else if (method_exists($className, "APICall")) {
        $rv=$className::apiCall(request::get());
    } else {
        throw new foxException("Method not allowed", 405);
    }
    
    foxRequestResult::throw("200", "OK", $rv);
    
} catch (fox\foxRequestResult $e) {
    ob_clean();
    header('Content-Type: application/json; charset=utf-8');
    header('HTTP/1.0 '.$e->getCode().' '.$e->getMessage(), true, $e->getCode());
    if ($e->retVal===null) { 
        print json_encode(["status"=>$e->getMessage()]);
    } else {
        print json_encode($e->retVal);
    }
    exit;
} catch (fox\foxException $e) {
    if (($e->getCode()>=400 && $e->getCode()<500) || ($e->getCode() == 501) || ($e->getCode() >= 600 && $e->getCode()<900)) {
        trigger_error($e->getStatus().": ".$e->getCode().": ".$e->getMessage()." in ".$e->getFile()." at line ".$e->getLine(), E_USER_WARNING);
        print(json_encode(["error"=>["code"=>$e->getCode(),"message"=>$e->getMessage(), "xCode"=>$e->getXCode()]]));
        if ($e->getCode()>=400 && $e->getCode()<502) {
            header('HTTP/1.0 '.$e->getCode().' '.$e->getMessage(), true, $e->getCode());
        } else {
            header('HTTP/1.0 501 '.$e->getMessage(), true, $e->getCode());
        }
    } else {
        trigger_error($e->getStatus().": ".$e->getCode().": ".$e->getMessage()." in ".$e->getFile()." at line ".$e->getLine(), E_USER_WARNING);
        print(json_encode(["error"=>["errCode"=>500,"message"=>"Internal server error","xCode"=>$e->getXCode()]]));
        header('HTTP/1.0 500 Internal server error', true, 500);
    }
    exit;
} catch (Exception $e) {
    trigger_error($e->getCode().": ".$e->getMessage()." in ".$e->getFile()." at line ".$e->getLine(), E_USER_WARNING);
    print(json_encode(["error"=>["errCode"=>500,"message"=>"Internal server error", "xCode"=>"ERR"]]));
    header('HTTP/1.0 500 Internal server error', true, 500);
    throw($e);
}
exit;

?>