<?php
namespace fox;

use Exception;

/**
 *
 * Class fox\UID
 *
 * @copyright MX STAR LLC 2021
 * @version 4.0.0
 * @author Pavel Dmitriev
 * @license GPLv3
 *        
 */
class UID extends baseClass implements stringExportable, stringImportable
{

    protected $id;

    protected $__loaded = false;

    protected $instance;

    protected $class;

    public static $sqlColumns = [
        "instance" => [
            "type" => "VARCHAR(255)",
            "nullable" => false,
            "index" => "INDEX"
        ],
        "class" => [
            "type" => "VARCHAR(255)",
            "nullable" => false
        ]
    ];

    public function __get($key)
    {
        if (! empty($this->id) && $this->__loaded == false) {
            $this->fill($this->id);
        }

        return parent::__get($key);
    }

    public function __fromString($val)
    {
        if (! static::check($val)) {
            throw new Exception("Invalid UID format");
        }
        $uid = substr(static::clear($val), 0, 9);
        $this->id = $uid - $this->getOffset();
    }

    public static function clear($code)
    {
        return preg_replace('![^0-9]+!', '', $code);
    }

    public function print()
    {
        return (substr($this->__toString(), 0, 4) . "-" . substr($this->__toString(), 4, 4) . "-" . substr($this->__toString(), 8, 2));
    }

    public static function check($code)
    {
        $code = static::clear($code);
        if (strlen($code) != 10) {
            return false;
        }
        return (substr($code, 9, 1) == self::checksum($code));
    }

    protected static function checksum($code)
    {
        $sum1 = substr($code, 1, 1) + substr($code, 3, 1) + substr($code, 5, 1) + substr($code, 7, 1);
        $sum1 = $sum1 * 3;
        $sum2 = substr($code, 0, 1) + substr($code, 2, 1) + substr($code, 4, 1) + substr($code, 6, 1) + substr($code, 8, 1);
        $sum = $sum1 + $sum2;
        $ceil = ceil(($sum / 10)) * 10;
        $delta = $ceil - $sum;
        return $delta;
    }

    public static $sqlTable = "tblRegistry";

    protected function getOffset()
    {
        $offset = config::get("UIDOffset");
        if (! is_numeric($offset)) {
            $offset = 0;
        } elseif ($offset > 90) {
            $offset = 90;
        }
        $offset = 100000000 + ($offset * 1000000);
        return $offset;
    }

    public function __toString(): string
    {
        if (empty($this->id)) {
            return "";
        } else {
            $code = $this->getOffset() + $this->id;
            return $code . static::checksum($code);
        }
    }

    public function isNull(): bool
    {
        return ($this->id === null);
    }

    public function issue($instance, $class)
    {
        $this->instance = $instance;
        $this->class = $class;
        $this->save();
    }
    
    public static function qIssue($instance, $class) {
        $uid = new static();
        $uid->issue($instance, $class);
        return $uid;
    }
    
    public function jsonSerialize() {
        return $this->__toString();
    }
}
?>