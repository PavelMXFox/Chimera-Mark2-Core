<?php
namespace fox;

/**
 *
 * Class fox\metadata
 *
 * @copyright MX STAR LLC 2021
 * @version 4.0.0
 * @author Pavel Dmitriev
 * @license GPLv3
 *        
 */
class metadata extends dbStoredBase
{

    public static $sqlTable = "tblMetadata";

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

    static function get($key, $module)
    {
        $conf = static::getAll($module);
        if (array_key_exists($key, $conf)) {
            return $conf[$key];
        } else {
            return null;
        }
    }

    static function getAll($module, $forceDB = false, $sql = null)
    {
        $cache = new cache();
        $conf = $cache->get("metadata." . $module);

        if ($forceDB || $conf === null) {
            if (empty($sql)) {
                $sql = new sql();
            }
            $res = $sql->quickExec("select `key`,`value` from `tblMetadata` where `module` = '$module'");
            $conf = [];
            while ($row = mysqli_fetch_assoc($res)) {
                $conf[$row["key"]] = $row["value"];
            }
            $cache->set("metadata." . $module, $conf);
        }
        return (array) $conf;
    }

    static function set($key, $value, $module)
    {
        $sql = new sql();
        if (static::get($key, $module) !== null) {
            $sql->prepareUpdate("tblMetadata");
            $sql->paramAddUpdate("value", $value);
            $sql->paramClose(" `module` = '" . $module . "' and `key` = '" . $key . "'");
            $sql->execute();
        } else {
            $sql->prepareInsert("tblMetadata");
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
        $sql->quickExec("delete from `tblMetadata` where `module` = '$module' and `key`='$key'");
        static::getAll($module, true, $sql);
    }

    static function delAll($module)
    {
        $sql = new sql();
        $sql->quickExec("delete from `tblMetadata` where `module` = '$module'");
        static::getAll($module, true, $sql);
    }
}
