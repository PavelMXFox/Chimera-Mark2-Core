<?php
namespace fox;

use Exception;

/**
 *
 * Class fox\config
 *
 * @copyright MX STAR LLC 2021
 * @version 4.0.0
 * @author Pavel Dmitriev
 * @license GPLv3
 *        
 */
class config extends dbStoredBase
{

    public static $sqlTable = "tblSettings";

    protected static $sqlColumns = [
        "id"=>[
            "type"=>"INT",
            "index"=>"AI",
        ],
        "module" => [
            "type" => "VARCHAR(128)",
            "index" => "INDEX"
        ],
        "key" => [
            "type" => "VARCHAR(128)",
            "index" => "INDEX"
        ],
        "value" => [
            "type" => "VARCHAR(128)"
        ]
    ];

    // stop-list - never search this keys in SQL
    private const envLockedKeys = [
        "FOX_SQLSERVER",
        "FOX_SQLUSER",
        "FOX_SQLPASSWD",
        "FOX_SQLDB",
        "FOX_CACHEHOST",
        "FOX_CACHEPORT",
        "FOX_TITLE",
        "FOX_SITEPREFIX",
        "FOX_MASTERSECRET",
        "FOX_UIDOFFSET",
        "FOX_S3_ENDPOINT",
        "FOX_S3_LOGIN",
        "FOX_S3_SECRET",
        "FOX_S3_REGION",
        "FOX_INIT_PASSWORD",
        "FOX_INIT_USERNAME",
        "FOX_TOKEN_TTL_WEB",
        "FOX_TOKEN_TTL_API",
        "FOX_TOKEN_TTL_APP",
        "FOX_TOKEN_ALLOW_RENEW_WEB",
        "FOX_TOKEN_ALLOW_RENEW_API",
        "FOX_TOKEN_ALLOW_RENEW_APP",
        "FOX_TOKEN_RENEW_WEB",
        "FOX_TOKEN_RENEW_API",
        "FOX_TOKEN_RENEW_APP",
        "FOX_DEFAULT_THEME",
        "FOX_DEFAULT_PAGESIZE",
        "FOX_DEFAULT_LANGUAGE",
        "FOX_DEFAULT_MODULE",
        "FOX_SESSION_RENEW_SEC",
        "FOX_ALLOW_REGISTER"
    ];

    static function get($key, $module = "core")
    {

        // try serach in ENV
        if (getenv("FOX_" . strtoupper($key)) !== false) {
            return getenv("FOX_" . strtoupper($key));
        }

        // check stop-list
        if (array_search("FOX_" . strtoupper($key), static::envLockedKeys) !== false) {
            return null;
        }

        if (static::get("SQLSERVER")===null) {
            throw new Exception("Error: SQL config not found");
        }
        
        $conf = static::getAll($module);
        if (array_key_exists($key, $conf)) {
            return $conf[$key];
        } else {
            return null;
        }
    }

    static function getAll($module = "core", $forceDB = false, $sql = null)
    {
        $cache = new cache();
        $conf = $cache->get("config." . $module);

        if ($forceDB || $conf === null) {
            if (empty($sql)) {
                $sql = new sql();
            }
            $res = $sql->quickExec("select `key`,`value` from `tblSettings` where `module` = '$module'");
            $conf = [];
            while ($row = mysqli_fetch_assoc($res)) {
                $conf[$row["key"]] = $row["value"];
            }
            $cache->set("config." . $module, $conf);
        }
        return (array) $conf;
    }

    static function set($key, $value, $module)
    {
        $sql = new sql();
        if (static::get($key, $module) !== null) {
            $sql->prepareUpdate("tblSettings");
            $sql->paramAddUpdate("value", $value);
            $sql->paramClose(" `module` = '" . $module . "' and `key` = '" . $key . "'");
            $sql->execute();
        } else {
            $sql->prepareInsert("tblSettings");
            $sql->paramAddInsert("module", $module);
            $sql->paramAddInsert("key", $key);
            $sql->paramAddInsert("value", $value);
            $sql->paramClose();
            $sql->execute();
        }
        static::getAll($module, true, $sql);
    }

    static function del($key, $module)
    {
        $sql = new sql();
        $sql->quickExec("delete from `tblSettings` where `module` = '$module' and `key`='$key'");
        static::getAll($module, true, $sql);
    }

    static function delAll($module)
    {
        $sql = new sql();
        $sql->quickExec("delete from `tblSettings` where `module` = '$module'");
        static::getAll($module, true, $sql);
    }
}
?>