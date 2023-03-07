<?php
namespace fox;

use Exception;

/**
 *
 * Class fox\time
 *
 * @copyright MX STAR LLC 2021
 * @version 4.0.0
 * @author Pavel Dmitriev
 * @license GPLv3
 * 
 * @property-read time hourStart
 * @property-read time dayStart
 * @property-read time monthStart
 * @property-read time yearStart
 *        
 */

class time implements stringExportable, stringImportable, \JsonSerializable
{
    
    public $stamp = null;
    
    public $format="Y-m-d H:i:s";
    
    public static $SQLType = "DATETIME";

    public function __construct($time = null, $format=null)
    {
        // установка часового пояса по умолчанию.
        date_default_timezone_set('UTC');
        if ($format) { $this->format = $format; }
        if ($time === null || $time == 0) {
            $this->stamp = null;
        } elseif (is_numeric($time)) {
            $this->stamp = $time;
        } elseif (gettype($time) == "string") {
            $this->stamp = strtotime($time);
        } elseif ($time instanceof time) {
            $this->stamp = $time->stamp;
        } else {
            throw new Exception("Invalid input");
        }
    }

    public function __fromString($val)
    {
        $this->__construct($val);
    }

    public function __toString(): string
    {
        date_default_timezone_set('UTC');
        return date($this->format, $this->stamp);
    }

    public function print($format=null, $TZ=null) {
        if ($TZ !==null) {
            date_default_timezone_set($TZ);
        } else {
            date_default_timezone_set('UTC');
        }
        if (empty($format)) { $format=$this->format; }
        return date($format, $this->stamp);
    }
    
    public function isNull(): bool
    {
        return ($this->stamp == null);
    }

    public function __get($key) {
        switch ($key) {
            case "hourStart":
                return new static(strtotime(date("Y-m-d H:00:00", $this->stamp)));                
            case "dayStart":
                return new static(strtotime(date("Y-m-d 00:00:00", $this->stamp)));
            case "monthStart":
                return new static(strtotime(date("Y-m-01 00:00:00", $this->stamp)));
            case "yearStart":
                return new static(strtotime(date("Y-01-01 00:00:00", $this->stamp)));
                
            default:
                throw new \Exception("Invalid property ".$key." for read in class ".__CLASS__);
        }
    }
    
    public function addSec($sec) {
        return new static($this->stamp+$sec);
    }
        
    public static function current() : time {
        return new static(time());
    }
    public function jsonSerialize()
    {
        return $this->stamp;
    }

    public static function formatInterval($val, $lang="en") {

        $lang=langPack::get("core.timeIntervalsShort",$lang);

        $durWeeks=floor($val/604800);
        $durXWeeks=$val % 604800;
        $durDays=floor($durXWeeks/86400);
        $durXDays=$durXWeeks % 86400;
        $durHrs=floor($durXDays/3600);
        $durXHrs=$durXDays % 3600;
        $durMins=floor($durXHrs/60);
        $durSecs=floor($durXHrs % 60);
        
        return ($durWeeks>0?$durWeeks.$lang["weeks"]." ":"").($durDays>0?$durDays.$lang["days"]." ":"").str_pad($durHrs,2, "0", STR_PAD_LEFT).":".str_pad($durMins,2, "0", STR_PAD_LEFT).":".str_pad($durSecs,2, "0", STR_PAD_LEFT);
    }
}

?>