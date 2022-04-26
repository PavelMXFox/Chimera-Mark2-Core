<?php namespace fox;

/**
 *
 * Class fox\file
 *
 * @copyright MX STAR LLC 2018-2022
 * @version 4.0.0
 * @author Pavel Dmitriev
 * @license GPLv3
 *        
 **/

class file extends baseClass {
    protected $id;
    public $fileName;
    public $module;
    public $class;
    public $public=false;
    public $private=false;
    public $ownerId=null;
    public time $expireStamp;
    
    public static $sqlTable="tblFiles";
    
    protected function __xConstruct() {
        $this->expireStamp=new time();
    }
    
    public static $sqlColumns = [
        "fileName" => [
            "type" => "VARCHAR(255)",
        ],
        "module" => [
            "type" => "VARCHAR(255)",
            "index" => "INDEX"
        ],
        "class" => [
            "type" => "VARCHAR(255)",
            "index" => "INDEX"
        ],
        "ownerId" => [
            "type" => "INT",
            "index" => "INDEX",
            "nullable"=>true
        ],
        "expireStamp" => [
            "type" => "DATETIME",
            "index" => "INDEX",
            "nullable"=>true
        ],
    ];
    
}


?>