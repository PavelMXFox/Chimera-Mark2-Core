<?php namespace fox;

/**
 *
 * Class fox\prometheus
 *
 * @copyright MX STAR LLC 2018-2022
 * @version 4.0.0
 * @author Pavel Dmitriev
 * @license GPLv3
 *
 **/

use fox\prometheus\item;

class prometheus implements stringExportable, stringImportable {
    protected array $__items=[];    
    
    public function __construct($ref=null) {
        if (empty($ref)) {
            return;
        } else if (is_string($ref)) {
            $this->parseString($ref);    
        }
    }
    
    public function __get($key) {
        switch($key) {
            case "items":
                return $this->__items;
            default:
                throw new foxException("Invalid READ for ".$key." in class ".__CLASS__);
        }
    }
    
    protected function parseString($str) {
        // TODO: implement fromString()
        $this->__items=[];
        $ref = preg_replace("/\r/", "", $str);
        foreach (explode("\n", $ref) as $line) {
            try {
                $this->__items[]=item::fromString($line);
                
            } catch (\Exception $e) {
                
            }
        }
        
    }
        
    public function __toString(): string
    {
        // TODO: implement __toString()
    }

    public function __fromString($val)
    {
        $this->__construct($val);
    }

    public function isNull(): bool
    {
        return empty($this->__items);
    }
}

