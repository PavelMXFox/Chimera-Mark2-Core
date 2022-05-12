<?php
namespace fox;

use Exception;

/**
 *
 * Class fox\sql
 * @desc MySQL DB adapter
 *
 * @copyright MX STAR LLC 2021
 * @version 4.0.0
 * @author Pavel Dmitriev
 * @license GPLv3
 *        
 */
class sql
{

    var $mysqli;

    var $stmt;

    var $bind_names;

    var $ctr;

    var $sqlQueryString;

    var $sqlQueryStringL;

    var $param_type;

    var $queryType;

    var bool $paramClosed = false;

    protected bool $connected = false;

    protected ?string $server = null;

    protected ?string $db = null;

    protected ?string $user = null;

    protected ?string $passwd = null;

    public const ERR_DUPLICATE = 1062;

    public const ERR_NOT_NULL_COLUMN = 1048;

    public const ERR_NO_DEFAULT = 1364;

    public static function getConnection(string $idx = "core", $server = null, $db = null, $user = null, $passwd = null): sql
    {
        global $sqlConnArray;
        if (gettype($sqlConnArray) == 'array' && array_key_exists($idx, $sqlConnArray) && ($sqlConnArray[$idx] instanceof sql)) {
            return $sqlConnArray[$idx];
        } else {
            $sqlConnArray[$idx] = new sql($server, $db, $user, $passwd);
            return $sqlConnArray[$idx];
        }
    }

    public static function flushConnections() {
        global $sqlConnArray;
        
        if (gettype($sqlConnArray) == 'array') {
            foreach ($sqlConnArray as $key=>&$conn) {
                $conn->__destruct();
                unset($sqlConnArray[$key]);
            }
        }
        $sqlConnArray=[];
    }
    
    function __destruct() {
        
    }
    
    function __construct($server = null, $db = null, $user = null, $passwd = null)
    {
        if (! isset($server)) {
            $server = config::get("sqlServer");
            $user = config::get("sqlUser");
            $passwd = config::get("sqlPasswd");
            $db = config::get("sqlDB");
        }

        $this->server = $server;
        $this->db = $db;
        $this->user = $user;
        $this->passwd = $passwd;
        $this->connected = false;
    }

    function connect($server = null, $db = null, $user = null, $passwd = null)
    {
        if (! $this->connected) {
            if (isset($server)) {
                $this->server = $server;
                $this->db = $db;
                $this->user = $user;
                $this->passwd = $passwd;
                $this->connected = false;
            }

            $this->mysqli = @mysqli_connect($this->server, $this->user, $this->passwd, $this->db);
            mysqli_set_charset($this->mysqli, "utf8");
            if (! $this->mysqli) { // Если дескриптор равен 0 соединение не установлено
                throw new Exception("SQL Connection to $this->server failed");
            }
            $this->connected = true;
        }
        return $this->mysqli;
    }

    function quickExec($sqlQueryString, &$result = null, $hideError = null)
    {
        $this->connect();
        if ($this->mysqli->connect_errno) {
            if (! isset($hideError)) {
                throw new Exception("SQL Connect error");
            }
            ;
            return null;
        }

        $result = $this->mysqli->query($sqlQueryString);

        if (! $result) {
            if (! isset($hideError)) {
                throw new Exception("SQL Error: ." . $this->mysqli->error);
            }
            ;
            return null;
        }
        return $result;
    }

    function quickExec1Line($sqlQueryString, &$result = null, $hideError = null)
    {
        $this->connect();
        $result = $this->quickExec($sqlQueryString, $result, $hideError);
        if (mysqli_num_rows($result) == 0) {
            return null;
        }

        $retVal = mysqli_fetch_assoc($result);
        return $retVal;
    }

    // General functions
    function prepare()
    {
        $this->ctr = 0;
        $this->bind_names = null;
        $this->param_type = null;
        $this->stmt = null;
        $this->sqlQueryString = null;
        $this->sqlQueryStringL = null;
        $this->paramClosed = false;
    }

    function prepareUpdate($tableName)
    {
        $this->prepare();
        $this->queryType = "update";
        $this->sqlQueryString = "UPDATE `$tableName` SET";
    }

    function prepareInsert($tableName)
    {
        $this->prepare();
        $this->queryType = "insert";
        $this->sqlQueryString = "INSERT INTO `$tableName` (";
    }

    function execute()
    {
        if (! $this->paramClosed && $this->queryType == "insert") {
            $this->paramClose();
        }
        if (! $this->paramClosed) {
            throw new Exception("Params not closed for " . $this->queryType . "!");
        }

        $this->connect();
        if ($this->ctr > 0) {
            $this->stmt = mysqli_prepare($this->mysqli, $this->sqlQueryString);

            if (! $this->stmt) {
                $err = 'ERR:EXEC 1P' . mysqli_errno($this->mysqli) . ' ' . mysqli_error($this->mysqli);
                throw new Exception($err, mysqli_errno($this->mysqli));
            }
            call_user_func_array(array(
                $this->stmt,
                'bind_param'
            ), $this->bind_names);
            if (! (mysqli_stmt_execute($this->stmt))) {
                $err = 'ERR:EXEC 2P' . mysqli_errno($this->mysqli) . ' ' . $this->stmt->error . mysqli_error($this->mysqli);
                $errNo = mysqli_errno($this->mysqli);
                $this->stmt->close();
                throw new Exception($err, $errNo);
            }
            return true;
        }
    }

    function quickExecute()
    {
        if (! $this->execute()) {
            if ($this->stmt) {
                $this->stmt->close();
            }
            throw new \Exception('ERR:EXEC 3P' . mysqli_errno($this->mysqli) . '  ' . mysqli_error($this->mysqli), mysqli_errno($this->mysqli));
        }
        $this->stmt->close();
        return true;
    }

    function getInsertId()
    {
        return mysqli_insert_id($this->mysqli);
    }

    function paramAdd($sqlParamName, $paramValue, $setNull = false)
    {
        if ($this->queryType == "insert") {
            $this->paramAddInsert($sqlParamName, $paramValue, $setNull);
        } elseif ($this->queryType == "update") {
            $this->paramAddUpdate($sqlParamName, $paramValue, $setNull);
        }
    }

    function paramAddInsert($sqlParamName, $paramValue = null, $setNull = null)
    {
        $var = $paramValue;

        if (($var !== null) || $setNull) {
            if ($setNull) {
                $var = null;
            }
            if ($this->ctr != 0) {
                $this->sqlQueryString .= ', ';
                $this->sqlQueryStringL .= ', ';
            }
            $this->sqlQueryString .= "`$sqlParamName` ";
            $this->sqlQueryStringL .= "? ";
            $this->ctr ++;
            if (! isset($this->bind_names)) {
                $x = 'XX';
                $bind_name = 'bind' . $this->ctr;
                $$bind_name = $x;
                $this->bind_names[] = &$$bind_name;
            }
            $bind_name = 'bind' . $this->ctr;
            $$bind_name = $var;
            $this->bind_names[] = &$$bind_name;
            $this->param_type .= 's';
        }
    }

    function paramAddUpdate($sqlParamName, $paramValue = null, $setNull = null)
    {
        if ($setNull) {
            $var = null;
        } elseif (isset($paramValue)) {
            $var = $paramValue;
        }

        if ($var !== null || $setNull) {
            if ($setNull) {
                $var = null;
            }
            if ($this->ctr != 0) {
                $this->sqlQueryString .= ', ';
            }
            $this->sqlQueryString .= "`$sqlParamName`=? ";
            $this->ctr ++;
            if (! isset($this->bind_names)) {
                $x = 'XX';
                $bind_name = 'bind' . $this->ctr;
                $$bind_name = $x;
                $this->bind_names[] = &$$bind_name;
            }
            $bind_name = 'bind' . $this->ctr;
            $$bind_name = $var;
            $this->bind_names[] = &$$bind_name;
            $this->param_type .= 's';
        }
    }

    function paramClose($sqlQueryStringWhere = null)
    {
        $bind_name = 'bind';
        $$bind_name = $this->param_type;
        $this->bind_names[0] = &$$bind_name;

        if ($this->queryType == "insert") {
            $this->sqlQueryString = $this->sqlQueryString . ") VALUES (" . $this->sqlQueryStringL . ")";
        } elseif (isset($sqlQueryStringWhere)) {
            $this->sqlQueryString .= " where " . $sqlQueryStringWhere;
        }
        $this->paramClosed = true;
    }

    function export($tlist)
    {
        $retval = "";
        if (gettype($tlist) == "string") {
            $tlist = array(
                $tlist
            );
        } elseif (gettype($tlist) != "array") {
            throw new Exception("Incorrect type " . gettype($tlist) . " for tables. Expecting 'string' or 'array'");
        }

        foreach ($tlist as $table) {
            $res = $this->quickExec1Line("SHOW CREATE TABLE `$table`");
            if (array_key_exists("Create Table", $res)) {
                $t_create = $res["Create Table"];
                $retval .= "CREATE TABLE IF NOT EXISTS $table (zzz int);\n";
                $columns = preg_split("/[\n\r]/", $t_create);

                // Определяем столбцы
                foreach ($columns as $col) {
                    $matches = [];
                    if (preg_match("/^[ ]*(`(.*)`\ [a-z\(0-9\)]*\ [A-Z _'0-9a-z]*)/", $col, $matches)) {
                        // var_dump($matches);
                        if (preg_match("/AUTO_INCREMENT/", $col)) {
                            $retval .= "ALTER TABLE `$table` ADD COLUMN IF NOT EXISTS " . $matches[1] . " PRIMARY KEY;\n";
                        } else {
                            $retval .= "ALTER TABLE `$table` ADD COLUMN IF NOT EXISTS " . $matches[1] . ";\n";
                        }
                    }
                }
                $retval .= "ALTER TABLE `$table` DROP COLUMN IF EXISTS zzz;\n";

                // Определяем индексы
                foreach ($columns as $col) {
                    $matches = [];
                    if (preg_match("/^[ ]*(([A-Z ]*)KEY\ `(.*)`\ \((.*)\))/", $col, $matches)) {
                        $retval .= "DROP INDEX IF EXISTS`" . $matches[3] . "` ON `$table`;\n";
                        $retval .= "CREATE " . $matches[2] . "INDEX `" . $matches[3] . "` ON `$table` (" . $matches[4] . ");\n";
                    }
                }
            } elseif (array_key_exists("Create View", $res)) {
                $retval .= "DROP VIEW IF EXISTS `" . $table . "`;\n";
                $retval .= $res["Create View"] . ";\n";
            }
        }
        return $retval;
    }

    public static function getMigration($folder, $namespace)
    {
        $res = "";
        foreach (scandir($folder) as $file) {
            $r = [];
            if (! preg_match("/(.*)\.php$/", $file, $r)) {
                continue;
            }
            $mod = $namespace . "\\" . $r[1];
            if (! is_a($mod, dbStoredBase::class, true)) {
                continue;
            }
            if (is_a($mod, noSqlMigration::class, true)) {
                continue;
            }

            $m = new $mod();
            if (! empty($m::$sqlTable)) {
                $res .= "-- $mod\n";
                $res .= (static::getMigrationForClass($m));
            }
        }
        return $res;
    }

    protected static function getMigrationForClass(dbStoredBase $type)
    {
        if (empty($type::$sqlTable)) {
            throw new Exception("Empty SQLTable not allowed here");
        }

        $res = "CREATE TABLE IF NOT EXISTS `" . $type::$sqlTable . "` (zzz int);\n";
        // columns
        foreach ($type->getSqlSchema() as $key => $val) {
            $res .= "ALTER TABLE `" . $type::$sqlTable . "` ADD COLUMN IF NOT EXISTS `" . $key . "` " . $val["type"] . ((array_key_exists("nullable", $val) && $val["nullable"] == false) ? " NOT NULL" : ((array_key_exists("default", $val)) ? (" DEFAULT \"" . $val["default"] . "\"") : " DEFAULT NULL")) . ((array_key_exists("index", $val) && $val["index"] == "AI") ? " AUTO_INCREMENT PRIMARY KEY" : "") . ((array_key_exists("first", $val) && $val["first"] == true) ? " FIRST" : "") . ";\n";
        }

        $res .= "ALTER TABLE `" . $type::$sqlTable . "` DROP COLUMN IF EXISTS zzz;\n";
        foreach ($type->getSqlSchema() as $key => $val) {
            if (empty($val["index"]) || $val["index"] == "AI") {
                continue;
            }
            $res .= "DROP INDEX IF EXISTS `" . $key . "` ON `" . $type::$sqlTable . "`;\n";
            $res .= "CREATE " . (($val["index"] == "INDEX") ? "" : $val["index"] . " ") . "INDEX `" . $key . "` ON `" . $type::$sqlTable . "` (`" . $key . "`);\n";
        }

        return $res;
    }

    function getAffectedRows()
    {
        return $this->mysqli->affected_rows;
    }
    
    public static function doMigration(moduleInfo $module, $classFolder=null) {
        
        if (!$module->singleInstanceOnly) {
            throw new \Exception("Migration of multi-instance modules not allowed");
        }
        
        if (empty($classFolder)) {
            $classFolder=__DIR__."/../../modules/".$module->instanceOf."/".$module->instanceOf;
        }
        
        $sql = new static();
        if ($sql->quickExec1Line("SHOW TABLES LIKE '__SqlSchemaVersion'")) {
            $version = $sql->quickExec1Line("SELECT `version` from `__SqlSchemaVersion` where `module`='".$module->name."'");
            if ($version) { $version=$version["version"];}
        } else {
            $version=null;
        }

        $migration=$sql::getMigration($classFolder, $module->namespace);
        
        if ($version != hash("md5",$migration)) {
            
            print "Module '".$module->name."' DB schema version mismatch $version != ".hash("md5",$migration)."\n";
            print "Updating Module '".$module->name."' DB schema...";
            $sql->quickExec("CREATE TABLE IF NOT EXISTS `__SqlSchemaVersion` (`module` VARCHAR(255), `version` VARCHAR(255))");
            foreach (explode("\n", $migration) as $line) {
                if (!empty($line) && !preg_match("/^--/", $line)) {
                    $sql->quickExec($line);
                }
            }
            
            $sql->quickExec("DELETE FROM `__SqlSchemaVersion` where `module` = '".$module->name."'");
            $sql->quickExec("INSERT INTO `__SqlSchemaVersion` (`module`, `version`) VALUES ('".$module->name."', '".hash("md5",$migration)."')");
            print "OK\n";
            return true;
            
        }
    }
}

?>