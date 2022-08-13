<?php
require_once(__DIR__.'/../Autoloader.php');

/**
 *
 * Script hook\index.php
 *
 * @copyright MX STAR LLC 2022
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
    if ($request->module !== 'hook') {
        throw new foxException("Invalid request",400);
    }
    
    $request->shift();

    $modules=moduleInfo::getAll();
    
    if (!array_key_exists(request::get()->module, $modules)) {
        throw new foxException("Invalid module",404);
    }
    $request->shift();
    
    $modNS=$modules[request::get()->instance]->namespace;
    $className=$modNS."\\".request::get()->module;
    
    if (!class_exists($className)) {
        throw new foxException("Not found",404);
    }
    
    if(!is_a($className, fox\webhookCallable::class,true)) {
        throw new foxException("Not found",404);
    }
    
    $hookMethod=fox\common::clearInput($request->method,"A-Z");
    $hookFunction=fox\common::clearInput($request->function,"a-zA-Z0-9");
    $hookXFunction=empty($request->parameters[0])?NULL:fox\common::clearInput($request->parameters[0],"a-zA-Z0-9");
    
    $hookCallMethod="HOOK_".$hookMethod."_".$hookFunction;
    $hookXCallMethod="HOOKX_".$hookMethod."_".$hookXFunction;
    $hookZCallMethod="HOOK_".$hookMethod;
    
    if (method_exists($className, $hookCallMethod)) {
        $rv=$className::$hookCallMethod($request);
    } else if (($hookXFunction!==null) && method_exists($className, $hookXCallMethod)) {
        $rv=$className::$hookXCallMethod($request);
    } else if (method_exists($className, $hookZCallMethod)) {
        $rv=$className::$hookZCallMethod($request);
    } else if (method_exists($className, "HOOKCall")) {
        $rv=$className::hookCall(request::get());
    } else {
        throw new foxException("Method not allowed", 405);
    }
    
    if ($rv!==false) {    
        ob_clean();
        header('Content-Type: application/json; charset=utf-8');
        foxRequestResult::throw("200", "OK", $rv);
    }
    
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