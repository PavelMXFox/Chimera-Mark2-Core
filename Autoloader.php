<?php 

if (file_exists(__DIR__.'/vendor/autoload.php')) {
    require_once __DIR__.'/vendor/autoload.php';
}

spl_autoload_register(function ($name) {
    $r=[];
    $rg = "/^("."fox"."\\\)([^\\\]*)$/";
    
    if (preg_match($rg, $name, $r)) {
        include_once("core/fox/".$r[2].".php");
    } else if (preg_match("/^(fox\\\)([^\\\]*)\\\([^\\\]*)$/", $name, $r)) {
        include_once("core/fox/".$r[2]."/".$r[3].".php");
    } else if (preg_match("/^(fox\\\)([^\\\]*)\\\([^\\\]*)\\\([^\\\]*)$/", $name, $r)) {
        include_once("core/fox/".$r[2]."/".$r[3]."/".$r[4].".php");
    } elseif (preg_match("/([0-9a-zA-Z\_\-\.]*)\\\([^\\\]*)/", $name, $r)) {
        if (file_exists(__DIR__."/modules/".$r[1]."/Autoloader.php")) {
            include_once(__DIR__."/modules/".$r[1]."/Autoloader.php");
        } elseif  (file_exists(__DIR__."/modules/".$r[1]."/".$r[2].".php")) {
            include_once(__DIR__."/modules/".$r[1]."/".$r[2].".php");
        }
    }
});

?>