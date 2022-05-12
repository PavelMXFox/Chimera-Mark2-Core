#!/usr/bin/php
<?php

/**
 *
 * Script initialize.php
 *
 * @copyright MX STAR LLC 2018-2022
 * @version 4.0.0
 * @author Pavel Dmitriev
 * @license GPLv3
 *
 **/

use fox\modules;
use fox\moduleInfo;
use fox\company;
use fox\user;
use fox\userGroup;
use fox\config;

include(__DIR__."/../Autoloader.php");

$m = fox\modules::list();

$initUser=empty(config::get("INIT_USERNAME"))?"admin":config::get("INIT_USERNAME");
$initPass=empty(config::get("INIT_PASSWORD"))?"chimera":config::get("INIT_PASSWORD");

// installing modules
foreach ($m as $mod) {
    if (array_key_exists($mod->name, modules::pseudoModules) && !$mod->getInstances()) {
        print "Install module ".$mod->name."...";
        $mod->save();
        print "OK\n";
    }
}

if (company::getCount()==0) {
    
    $c = new fox\company();
    $c->name="Default company";
    $c->qName="Company";
    print "Create company ".$c->name."...";
    $c->save();
    print "OK\n";
}

// create user
if (user::getCount()==0 && userGroup::getCount()==0) {
    $u = new fox\user();
    $u->fullName="Administrator";
    $u->login=$initUser;
    $u->setPassword($initPass);
    $u->authType="internal";
    print "Create user ".$u->login."...";
    $u->save();
    
    print "OK\n";
    
    $ug = new fox\userGroup();
    $ug->name="Administrators";
    $ug->addAccessRule("isRoot");
    print "Create usergroup ".$ug->name."...";
    $ug->save();
    print "OK\n";
    print "Join user into ".$u->login." group ".$ug->name."...";
    $ug->join($u);
    print "OK\n";
}





?>