<?php
namespace fox;

/**
 *
 * Class fox\company
 *
 * @copyright MX STAR LLC 2021
 * @version 4.0.0
 * @author Pavel Dmitriev
 * @license GPLv3
 *        
 */
class company extends baseClass
{

    protected $id;

    protected UID $invCode;

    public $name;

    public $qName;

    public $description;

    public string $type = "company";

    public bool $deleted = false;

    public static $sqlTable = "tblCompany";

    public static $deletedFieldName = "deleted";

    public static $sqlColumns = [
        "name" => [
            "type" => "VARCHAR(255)",
            "nullable" => false
        ],
        "qName" => [
            "type" => "VARCHAR(255)",
            "nullable" => false
        ],
        "description" => [
            "type" => "VARCHAR(255)"
        ],
        "type" => [
            "type" => "VARCHAR(255)",
            "nullable" => false
        ]
    ];

    public function __xConstruct()
    {
        $this->invCode = new UID();
    }

    protected function validateSave()
    {
        if ($this->invCode->isNull()) {
            $this->invCode->issue("core", get_class($this));
        }
        return true;
    }
}
?>