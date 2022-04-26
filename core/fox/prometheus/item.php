<?php namespace fox\prometheus;

/**
 *
 * Class fox\prometheus\item
 *
 * @copyright MX STAR LLC 2018-2022
 * @version 4.0.0
 * @author Pavel Dmitriev
 * @license GPLv3
 *
 **/

use fox\stringExportable;
use fox\stringImportable;
use fox\foxException;

class item implements stringExportable,stringImportable {
    public $key;
    public $value;
    public array $metaData=[];
    
    public function __construct($key=null, $val=null, $meta=null) {
        $this->key=$key;
        $this->value=$val;
        if ($meta) { $this->metaData=$meta; }
    }
    
    public function __toString(): string
    {
        return $this->key.(empty($this->metaData)?"":"{".$this->encodeMetadata()."}")." ".$this->value;
    }
    
    protected function encodeMetadata() : string {
        $rv="";
        foreach ($this->metaData as $key=>$val) {
            $rv.=(empty($rv)?"":",").$key."=\"".$val."\"";
        }
        return $rv;
    }
    
    public static function fromString($val) {
        $item=new static();
        $item->__fromString($val);
        return $item;
    }
    
    public function __fromString($val)
    {
        $ref=[];
        if (preg_match("/^([^# ][^ {]*)(\{([^ }]*)\}){0,1} (.*)$/", $val, $ref)) {
            $this->key=$ref[1];
            if (is_numeric($ref[4])) {
                if ((int)$ref[4]==(float)$ref[4]) {
                    $this->value=(int)$ref[4];
                } else {
                    $this->value=(float)$ref[4];
                }
            } else {
                $this->value=$ref[4];
            }

            if ($ref[3] !== "") {
                $this->metaData=[];
                foreach(explode(",", $ref[3]) as $kvp) {
                    $kvx=[];
                    if (preg_match("/([^=]*)=(.*)$/", $kvp,$kvx)) {
                        $xVal=preg_replace('/^\"/', '', preg_replace('/\"$/','',$kvx[2]));
                        $this->metaData[$kvx[1]]=$xVal;
                    }
                }
            }
            
        } else {
            throw new foxException("Unable to parce string ".$val." in ".__CLASS__."->__fromString");
        }
    }

    public function isNull(): bool
    {
        return ($this->value === null || $this->value === false || $this->value === "");
    }
}
?>