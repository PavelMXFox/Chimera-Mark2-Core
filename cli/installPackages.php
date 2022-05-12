<?php

use fox\modules;
use fox\config;

include(__DIR__."/../Autoloader.php");

if (strtolower(config::get("Environment"))=="development") {
    print "Development mode. Package installation skipped.\n";
    exit;
}

print "Packages installer started\n";
foreach (scandir(modules::packagesDir) as $file) {

    if (!preg_match("/\.zip$/", $file)) {
        continue;
    }
    if (! is_file(modules::packagesDir . "/" . $file)) {
        continue;
    }

    // read config
    $newModInfo=json_decode(file_get_contents("zip://".modules::packagesDir . "/" . $file."#module.json"));
    
    // check if module present
    $modInstalled=false;
    $needUpdate=false;
    if (file_exists(modules::modulesDir."/".$newModInfo->name) && file_exists(modules::modulesDir."/".$newModInfo->name."/module.json")) {
        $exModInfo=json_decode(file_get_contents(modules::modulesDir."/".$newModInfo->name."/module.json"));
        if (!empty($exModInfo)) {
            $modInstalled=true;
            $needUpdate=(property_exists($exModInfo, "hash") && $exModInfo->hash!=$newModInfo->hash);
            
        }
    }
    print "Module ".$newModInfo->name.": ".($modInstalled?"Installed, ".($needUpdate?"Outdated":"Actual"):"Not installed").".\n";
    if ($modInstalled && $needUpdate) {
        system("rm -rf \"".modules::modulesDir."/".$newModInfo->name."\"");
    }
    
    if (!$modInstalled || $needUpdate) {
        print "Install...";
        $zip = new \ZipArchive();
        $zip->open(modules::packagesDir . "/" . $file);
        mkdir (modules::modulesDir."/".$newModInfo->name);
        $zip->extractTo(modules::modulesDir."/".$newModInfo->name);
        
        if (file_exists(modules::modulesDir."/".$newModInfo->name."/fox-start.d")) {
            system("chmod a+x \"".modules::modulesDir."/".$newModInfo->name."/fox-start.d/\"*");
        }
        print "OK\n";
    }    
}

print "Packages installer completed\n";
