#!/usr/bin/php
<?php
/**
 *
 * Script migration.php
 *
 * @copyright MX STAR LLC 2018-2022
 * @version 4.0.0
 * @author Pavel Dmitriev
 * @license GPLv3
 *
 **/

include(__DIR__."/../Autoloader.php");


try {
    $module = fox\moduleInfo::getByInstance("core");
} catch (Exception $e) {
    $module = new fox\moduleInfo();
    $module->name="core";
    $module->instanceOf="core";
    $module->namespace="fox";
}

fox\sql::doMigration($module,__DIR__."/../core/fox");

?>