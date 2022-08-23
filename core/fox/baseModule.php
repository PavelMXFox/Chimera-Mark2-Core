<?php
namespace fox;

/**
 *
 * Class fox\baseModule
 *
 * @copyright MX STAR LLC 2021
 * @version 4.0.0
 * @author Pavel Dmitriev
 * @license GPLv3
 *        
 */
class baseModule
{

    /**
     * @deprecated - moved into module.json
     */
    public static $title = "Basic Module Template";

    /**
     * @deprecated - moved into module.json
     */
    public static $desc = "Базовый класс модуля-заглушки";

    /**
     * @deprecated - moved into module.json
     */
    public static $version = "0.0.0";

    public static $type = "fake";

    public static $allowAlias = false;

    public static $authRequred = true;

    /*
     * globalAccessKey:
     * Уровень доступа, необходимый для вызова функций mainPage и ajaxPage.
     * Если у пользователя нет необходимого уровня доступа для инстанса или all
     * то селектор не передает запросы модулю.
     *
     * Если установлен в null - контроль доступа к модулю системой не осуществляется,
     * а осуществляется только средствами самого модуля.
     *
     * isRoot - базовый ключ доступа, означающий запрет доступа для всех, кроме Администратора
     *
     */
    public static $globalAccessKey = "isRoot";

    /*
     * Features:
     * - page - модуль отображает странцу web-интерфейса
     * - auth - модуль предоставляет интерфейс аутентификации
     * (Используется как модуль для coreAuth)
     * - cron - модуль имеет функции обслуживания, которые нужно запускать по cron
     * (Используется как модуль для coreCron)
     * - log - модуль используется как интерфейс для coreLogger
     *
     * Эти фичи все или частично записываются в таблицу при установке модуля. Если фичи нет
     * в таблице - она использоваться не будет. Запись в таблицу фич, которых нет в модуле
     * недопустимо так как может привести к ошибкам в работе системы.
     *
     */
    public static $features = [];

    public static $menuItem = [];

    public static $dependsOn = [];

    public static $ACLRules = [];

    public static $configKeys = [];

    public static $languages = [];
    
    public static $crontab=[];

    public static $themes=[];

    public static function getModInfo(): moduleInfo
    {
        $mi = new moduleInfo();
        $mi->name = $mi->namespace = substr(static::class, 0, strrpos(static::class, '\\'));
        $mi->features = static::$features;
        $mi->isTemplate = true;
        $mi->singleInstanceOnly = ! static::$allowAlias;
        $mi->authRequired = static::$authRequred;
        $mi->ACLRules = static::$ACLRules;
        $mi->menuItem = static::$menuItem;
        $mi->globalAccessKey = static::$globalAccessKey;
        $mi->languages = static::$languages;
        $mi->configKeys=static::$configKeys;
        $mi->themes=static::$themes;

        return $mi;
    }
    
    public static function doMigration() {
        if (static::$allowAlias) {
            throw new \Exception("Embedded migration not allowed for multi-instance modules");
        }
        
        $modInfo = static::getModInfo();
        $instances = $modInfo->getInstances();
        
        foreach ($instances as $module) {
            \fox\sql::doMigration($module);
        }
        
    }
}
