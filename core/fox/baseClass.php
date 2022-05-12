<?php
namespace fox;

use Exception;

/**
 * 
 * Class fox\baseClass
 * 
 * @desc baseClass mk 2 class
 * @copyright MX STAR LLC 2021
 * @version 4.0.0
 * @author Pavel Dmitriev
 * @license GPLv3
 * @property-read mixed $changelog
 * @property-read string $sqlSelectTemplate
 *
 **/

class baseClass extends dbStoredBase implements \JsonSerializable, jsonImportable
{

    protected ?sql $sql = null;

    protected $fillPrefix = "";

    protected $changelog = null;

    protected $__settings;

    protected $__xId = null;

    // id for xConstruct;

    # if null - generated automatically
    public static $sqlSelectTemplate = null;

    # primary index field. Default is id
    public static $sqlIdx = "id";
    
    # if true then DELETE when called delete() method, else mark as deleted
    public static $allowDeleteFromDB = false;

    # if !null - this field will set to 1 on delete() when $allowDeleteFromDB==false
    public static $deletedFieldName = null;

    protected $__sqlSelectTemplate = null;

    # basic exclude props
    protected static $excludePropsBase = [
        'sql',
        'changelog',
        '__sqlSelectTemplate',
        'fillPrefix'
    ];

    # custom excluded props from export and var_dump
    protected static $excludeProps = [];

    public function getSqlSchema()
    {
        $rv = [];
        foreach (static::$sqlColumns as $key => $conf) {
            if ($conf["type"] !== "SKIP") {
                $rv[$key] = static::$sqlColumns[$key];
            }
        }

        foreach ($this as $key => $val) {
            if (array_key_exists($key, static::$sqlColumns)) {
                continue;
            }
            if (preg_match("/^[^_][^_].*/", $key) && (array_search($key, array_merge(static::$excludeProps, static::$excludePropsBase)) === false)) {
                $type = null;
                $idx = null;
                $null = null;
                switch (gettype($val)) {
                    case "NULL":
                        if ($key == static::$sqlIdx) {
                            $type = "INT";
                            $idx = "AI";
                            $null = false;
                        } else {
                            throw new \Exception("Invalid type conversion for key $key at " . get_class($this));
                        }
                        break;
                    case "array":
                        $type = "TEXT";
                        break;
                    case "integer":
                        $type = "INT";
                        break;
                    case "string":
                        $type = "VARCHAR(255)";
                        break;
                    case "boolean":
                        $type = "INT";
                        break;

                    case "object":
                        if (! empty($val::$SQLType)) {
                            $type = $val::$SQLType;
                        } elseif ($val instanceof stringExportable) {
                            $type = "VARCHAR(255)";
                        } elseif ($val instanceof \JsonSerializable) {
                            $type = "VARCHAR(255)";
                        } else {
                            throw new \Exception("Invalid type conversion for key $key at " . get_class($this));
                        }
                        break;
                    default:
                        throw new \Exception("Invalid type conversion for key $key at " . get_class($this));
                        break;
                }
                $rv[$key]["type"] = $type;
                if ($idx) {
                    $rv[$key]["index"] = $idx;
                }
                if ($null !== null) {
                    $rv[$key]["nullable"] = $null;
                }
                if ($key == static::$sqlIdx) {
                    $rv[$key]["first"] = true;
                } else {
                    $rv[$key]["first"] = false;
                }
            }
        }
        return $rv;
    }

    protected function checkSql()
    {
        if ($this->sql === null) {
            $this->sql = sql::getConnection();
        }
    }

    protected function __xConstruct()
    {
        return true;
    }

    public function __construct($id = null, ?namespace\sql $sql = null, $prefix = null, $settings = null)
    {
        # How to call from child template:
        # parent::__construct($id, $sql, $prefix, $settings);
        $this->__settings = $settings;
        if (empty($this::$sqlSelectTemplate) && ! empty($this::$sqlTable)) {
            $this->__sqlSelectTemplate = "select * from `" . $this::$sqlTable . "` as `i`";
        } else {
            $this->__sqlSelectTemplate = $this::$sqlSelectTemplate;
        }

        if (isset($sql)) {
            $this->sql = &$sql;
        }
        $this->fillPrefix = $prefix;

        $this->__xId = $id;

        if ($this->__xConstruct() === false) {
            // stop autoload content if __xConstruct return FALSE;
            return;
        }

        switch (gettype($id)) {
            case "array":
                $this->fillFromRow($id);
                break;
            case "string":
                if ($this instanceof stringImportable) {
                    $this->__fromString($id);
                } elseif (is_numeric($id)) {
                    $this->fill($id);
                } elseif ($x = json_decode($id)) {
                    $this->fillFromRow($x);
                } else {
                    throw new \Exception("Invalid input format", 597);
                }
                break;
            case "integer":
                $this->fill($id);
                break;
            case "NULL":
                break;
            default:
                throw new \Exception("Invalid type " . gettype($id) . " for " . get_class($this) . "->__construct", 591);
                break;
        }
    }

    protected function fill($id)
    {
        if (! empty($this->__sqlSelectTemplate)) {
            $this->checkSql();
            $row = $this->sql->quickExec1Line($this->__sqlSelectTemplate . " where `i`." . $this::$sqlIdx . " = '" . $id . "'");
            if (! empty($row)) {
                $this->fillFromRow($row);
            } else {
                throw new \Exception("Record with " . (static::$sqlIdx) . " " . $id . " not found in " . get_class($this), 691);
            }
        } else {
            throw new \Exception("Fill by ID not implemented in " . get_class($this), 592);
        }
    }

    protected function fillFromRow($row)
    {
        foreach ($row as $key => $val) {
            if (! empty($this->fillPrefix)) {
                if (! preg_match("/^" . $this->fillPrefix . "/", $key)) {
                    continue;
                }
                $key = preg_replace("/^" . $this->fillPrefix . "/", "", $key);
            }

            if (property_exists($this, $key) || property_exists($this, "__" . $key)) {
                if (property_exists($this, "__" . $key)) {
                    $key = "__" . $key;
                }

                if (gettype($this->{$key}) == 'boolean') {
                    $this->{$key} = $val == 1;
                } elseif ((($this->{$key}) instanceof jsonImportable) || (($this->{$key}) instanceof stringImportable)) {
                    $typeof = get_class(($this->{$key}));
                    $this->{$key} = new $typeof($val);
                } elseif (gettype($this->{$key}) == "array") {
                    if (gettype($val) == "string") {
                        $this->{$key} = (array) json_decode($val, true);
                    } elseif (gettype($val) == "array") {
                        $this->{$key} = $val;
                    } elseif ($val === null) {
                        $this->{$key} = [];
                    } elseif (gettype($val) == "object") {
                        $this->{$key} = (array) $val;
                    } else {
                        throw new Exception("Invalid type " . gettype($val) . " for " . $key . " in " . get_class($this));
                    }
                } else {
                    $this->{$key} = $val;
                }
            }
        }
    }

    public function save()
    {
        if (! $this->validateSave()) {
            return false;
        }
        $this->checkSql();

        if (property_exists($this, static::$sqlIdx) && ($this->{static::$sqlIdx} == null)) {
            return $this->create();
        } else {

            $class = get_class($this);
            if (is_numeric($this->{static::$sqlIdx})) {
                $ref = new $class((int) $this->{static::$sqlIdx});
            } else {
                $ref = new $class($this->{static::$sqlIdx});
            }

            $this->changelog = "";
            foreach ($this as $key => $val) {

                if ((array_search($key, array_merge(static::$excludeProps, static::$excludePropsBase)) === false) && $ref->{$key} != $this->{$key}) {
                    $stringRef = (is_bool($ref->{$key}) || ! (is_object($ref->{$key}) || is_array($ref->{$key})));
                    $stringVal = (is_bool($val) || ! (is_object($val) || is_array($val)));
                    
                    if (preg_match("/^_/",$key)) {
                        $this->changelog .= "key: " . $key . " changed \n";
                    } else {
                        $this->changelog .= "key: " . $key . " changed from " . ($stringRef ? (is_bool($ref->{$key}) ? ($ref->{$key} ? "true" : "false") : $ref->{$key}) : "<" . gettype($ref->{$key}) . ">") . " to " . ($stringVal ? (is_bool($val) ? ($val ? "true" : "false") : $val) : "<" . gettype($val) . ">") . ";\n ";
                    }
                    
                }
            }

            if (empty($this->changelog)) {
                return true;
            }
            return $this->update();
        }
    }

    public function delete()
    {
        if (property_exists($this, static::$sqlIdx) && ($this->{static::$sqlIdx} == null)) {
            return false;
        }
        
        if (!$this->validateDelete()) {
            throw new \Exception("ValidateDelete failed");
        }
        
        if (static::$allowDeleteFromDB) {
            $this->checkSql();
            $this->sql->quickExec("DELETE FROM `" . static::$sqlTable . "` where " . static::$sqlIdx . " = '" . $this->{static::$sqlIdx} . "'");
            if (! (empty(static::$deletedFieldName))) {
                $this->{static::$deletedFieldName} = true;
            }
            $this->{static::$sqlIdx} = null;
        } elseif (! (empty(static::$deletedFieldName))) {
            $this->checkSql();
            $this->sql->quickExec("UPDATE `" . static::$sqlTable . "` set `" . static::$deletedFieldName . "`='1' where " . $this::$sqlIdx . " = '" . $this->{static::$sqlIdx} . "'");
            $this->{static::$deletedFieldName} = true;
            return true;
        } else {
            throw new \Exception("DELETE not implemented in " . get_class($this), 592);
        }
    }

    protected function validateDelete()
    {
        return true;
    }

    protected function update()
    {
        if (! empty($this::$sqlTable)) {
            $this->sql->prepareUpdate($this::$sqlTable);
        }

        if (empty($this::$sqlTable) || ! $this->updateAddParams()) {
            throw new \Exception("Method update not implemented in " . get_class($this), 593);
        }
        $this->sql->paramClose($this::$sqlIdx . " = '" . $this->{static::$sqlIdx} . "'");
        $this->sql->quickExecute();
        return false;
    }

    protected function create()
    {
        if (! empty($this::$sqlTable)) {
            $this->sql->prepareInsert($this::$sqlTable);
        }

        if (empty($this::$sqlTable) || ! $this->createAddParams()) {
            throw new \Exception("Method create not implemented in " . get_class($this), 594);
        }

        $this->sql->paramClose();
        $this->sql->quickExecute();
        if (property_exists($this, static::$sqlIdx)) {
            $this->{static::$sqlIdx} = $this->sql->getInsertId();
            if (is_numeric($this->{static::$sqlIdx})) {
                $this->fill((int) $this->{static::$sqlIdx});
            } else {
                $this->fill($this->{static::$sqlIdx});
            }
        }
        return true;
    }

    protected function updateAddParams()
    {
        return $this->addParams();
    }

    protected function createAddParams()
    {
        return $this->addParams();
    }

    protected function addParams()
    {
        $this->checkSql();

        foreach ($this->getSqlSchema() as $key => $conf) {
            if ($key == static::$sqlIdx) {
                continue;
            }

            if (property_exists($this, $key)) {
                $val = $this->{$key};
            } elseif (property_exists($this, "__" . $key)) {
                $val = $this->{"__" . $key};
            } else {
                $val = null;
            }

            if (is_array($val)) {
                $sqlVal = json_encode($val);
                $sqlNull = empty($val);
            } elseif (is_object($val) && ! ($val instanceof stringExportable)) {
                if ($val instanceof \JsonSerializable) {
                    $sqlVal = json_encode($val);
                    $sqlNull = empty($val);
                } else {
                    throw new Exception("Oups... $key is not jsonSerialiazable");
                }
            } elseif (is_object($val) && ($val instanceof stringExportable)) {
                if ($val->isNull()) {
                    $sqlVal = null;
                    $sqlNull = true;
                } else {
                    $sqlVal = (string) $val;
                    $sqlNull = ($val === null);
                }
            } elseif (is_bool($val)) {
                $sqlVal = $val ? 1 : 0;
                $sqlNull = false;
            } else {
                $sqlVal = (string) $val;
                $sqlNull = ($val === null);
            }

            if ($sqlNull && array_key_exists("nullable", $conf) && $conf["nullable"] === false) {
                throw new Exception("Field $key can't be null in " . get_class($this));
            }

            $this->sql->paramAdd($key, $sqlVal, $sqlNull);
        }

        return true;
    }

    protected function validateSave()
    {
        return true;
    }

    public function __get($key)
    {
        switch ($key) {
            case "sqlSelectTemplate":
                return $this->__sqlSelectTemplate;
                break;
            case "sql":
                $this->checkSql();
                return $this->sql;
            case "changelog":
                return $this->changelog;
                break;
            default:
                if (property_exists($this, $key)) {
                    if ($this->{$key} instanceof stringExportable && !($this->{$key} instanceof \JsonSerializable)) {
                        return (string) $this->{$key};
                    } else {
                        return $this->{$key};
                    }
                } else {
                    throw new \Exception("property $key not availiable for read in class " . get_class($this), 595);
                }
        }
    }

    public function getSql() : sql
    {
        $this->checkSql();
        return $this->sql;
    }

    public static function getCount($where = null)
    {
        $s = new static();
        if (empty($s::$sqlTable)) {
            throw new \Exception("Method getTotalCount not implemented in " . get_class($s), 691);
        }

        $sql = $s->getSql();
        $res = $sql->quickExec1Line("select count(" . (empty(static::$sqlIdx) ? "*" : static::$sqlIdx) . ") as `cnt` from `" . static::$sqlTable . "`" . (empty($where) ? "" : " where $where"));
        return $res["cnt"];
    }

    public function __set($key, $val)
    {
        switch ($key) {
            case "settings":
                $this->__settings = $val;
                break;
            default:
                throw new \Exception("property $key not availiable for write in class " . get_class($this), 596);
                break;
        }
    }

    public function __debugInfo()
    {
        $rv = [];
        foreach ($this as $key => $value) {
            if (array_search($key, array_merge(static::$excludeProps, static::$excludePropsBase)) === false && ! preg_match("!^_!", $key)) {
                if ($value instanceof stringExportable) {
                    if ($value->isNull()) {
                        $rv[$key] = null;
                    } else {
                        $rv[$key] = (string) $value;
                    }
                } else {
                    $rv[$key] = $value;
                }
            }
        }
        return $rv;
    }

    public function export()
    {
        $rv = [];
        foreach ($this as $key => $value) {
            if (array_search($key, array_merge(static::$excludeProps, static::$excludePropsBase)) === false && ! preg_match("!^_!", $key)) {
                if (($this->__get($key)) instanceof \JsonSerializable) {
                    $rv[$key] = $this->__get($key);
                } elseif (($this->__get($key)) instanceof stringExportable) {
                    if ($this->__get($key)->isNull()) {
                        $rv[$key] = null;
                    } else {
                        $rv[$key] = (string) ($this->__get($key));
                    }
                } else {
                    $rv[$key] = $this->__get($key);
                }
            }
        }
        return $rv;
    }

    public function jsonSerialize()
    {
        $rv = $this->export();
        $rv["_type"] = get_class($this);
        return $rv;
    }
    
    protected static function xSearch($where, $pattern, ?array $options, sql $sql) {
        
        return ["where"=>$where, "join"=>null];
    }

    public static function search($pattern=null, $pageSize=null, $page=1, $options=[]) {
        if (static::$sqlTable == null) {
            throw new \Exception("Search not implemented for ".static::class);
        }
        $ref=new static();
        $sql = $ref->getSql();
        
        $where="";
        if (!empty($pattern)) {
            foreach (static::$sqlColumns as $key=>$val) {
                if (!empty($val["search"])) {
                    switch (strtolower($val["search"])) {
                        case "strict":
                            $where.=(empty($where)?"":" OR ")."`$key`='".common::clearInput($pattern)."'";
                            break;
                            
                        case "like":
                            $where.=(empty($where)?"":" OR ")."`$key` like '%".common::clearInput($pattern)."%'";
                            break;
                            
                        case "start":
                            $where.=(empty($where)?"":" OR ")."`$key` like '%".common::clearInput($pattern)."'";
                            break;
                            
                        case "invCode":
                            $where.=(empty($where)?"":" OR ")."`$key`='".UID::clear($pattern)."'";
                            break;
                            
                        default:
                            break;
                    }
                   
                }
            }
        }
        
        if (static::$deletedFieldName && empty($options["showDeleted"])) {
            $where = (empty($where)?"":"(".$where.") AND ")."`".static::$deletedFieldName."` = 0";
        }
        
        if ($pageSize!==null) {
            if ($page<1) { $page=1;}
            $limit = "LIMIT ".($pageSize*($page-1)).", ".$pageSize;
        } else {
            $limit="";
        }
        
        $xRes=static::xSearch($where, $pattern, $options, $sql);
        $where = $xRes["where"];
        $join=$xRes["join"];
        
        $sqlQueryString=$ref->sqlSelectTemplate.(empty($join)?"":" ".$join).(empty($where)?"":" WHERE ".$where).(empty($limit)?"":" ".$limit);
        
        $res=$sql->quickExec($sqlQueryString);
        $rv=new searchResult();
        $rv->setIndexByPage($page, $pageSize);
        while ($row=mysqli_fetch_assoc($res)) {
            $rv->push(new static($row));
        }
        return $rv;
    }
    
    protected static function log(string $instance, $method, string $message, ?user $user=null, ?string $refType=null, ?string $refId=null, string $msgCode=null, ?string $severity="INFO", $payload=null) {
        return logEntry::add($instance, static::class, $method, $msgCode, $message, $severity, $user,  $refType, $refId, $payload);
    }
}