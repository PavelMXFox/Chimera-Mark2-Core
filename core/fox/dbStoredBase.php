<?php
namespace fox;

/**
 *
 * Class fox\dbStoredBase
 *
 * @copyright MX STAR LLC 2021
 * @version 4.0.0
 * @author Pavel Dmitriev
 * @license GPLv3
 *        
 */
class dbStoredBase
{

    # text string without quotes
    public static $sqlTable = null;

    # Sql columns additional reference. Format - array
    # if column not listed - try to determine type by property data type,
    # by (refType)::$SQLType or standard types map in sql::typesMap cons
    # (key) = sqlColumnName, without quotes, eg id
    # [type] = sqlColumnType, eg VARCHAR(255). if SKIP = then skip this field
    # [index] = true or index type ["PRIMARY","UNIQUE","INDEX","AI"]
    # [nullable] = if true = @null@ will allowed in schema
    # [default] = default value of column. If null and [null] is set - default is NULL
    # [first] = make column FIRST in schema
    
    # [search] = { none - don't serarch, strict - `column` = 'val', like - `column` like '%val%', start - `column` like 'val%', invCode - UID::clean && check val; `column`='clean(val)' }, default = none
    protected static $sqlColumns = [];

    public function getSqlSchema()
    {
        return $this::$sqlColumns;
    }
}
?>