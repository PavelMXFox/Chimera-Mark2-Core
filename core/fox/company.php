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
class company extends baseClass implements externalCallable
{

    protected $id;

    protected UID $invCode;

    public $name;

    public $qName;

    public $description;

    public string $type = "company";

    public bool $deleted = false;

    public const types=[
        "company",
        "client",
        "supplier",
        "partner"
    ];
    
    public static $sqlTable = "tblCompany";

    public static $deletedFieldName = "deleted";

    public static $sqlColumns = [
        "name" => [
            "type" => "VARCHAR(255)",
            "nullable" => false,
            "search"=>"like"
        ],
        "qName" => [
            "type" => "VARCHAR(255)",
            "nullable" => false,
            "search"=>"like"
        ],
        "description" => [
            "type" => "VARCHAR(255)",
            "search"=>"like"
        ],
        "type" => [
            "type" => "VARCHAR(255)",
            "nullable" => false
        ],
        "invCode"=> [
            "type"=>"CHAR(10)",
            "nullable"=>false,
            "search"=>"invCode"
        ]
    ];

    public function __xConstruct()
    {
        $this->invCode = new UID();
    }

    protected function validateSave()
    {
        if (empty($this->name)) { foxException::throw(foxException::STATUS_ERR, "Empty name not allowed", 406, "ENNA"); }
        if (empty($this->qName)) { foxException::throw(foxException::STATUS_ERR, "Empty qName not allowed", 406, "EQNA"); }
        if (array_search($this->type, $this::types)===false) { foxException::throw(foxException::STATUS_ERR, "Invalid type", 406, "ITNA"); }
        if ($this->invCode->isNull()) { 
            $this->invCode->issue("core", get_class($this));
        }
        return true;
    }
    
    ### REST API
    public static function API_GET(request $request) {
        if (!empty($request->parameters)) { 
            throw new foxException("Invalid request",400);
        }
        $request->blockIfNoAccess("viewCompanies");
        return new static(common::clearInput($request->function));
    }
    
    public static function API_DELETE(request $request) {
        if (!empty($request->parameters)) {
            throw new foxException("Invalid request",400);
        }
        $request->blockIfNoAccess("adminCompanies");
        $c = new static(common::clearInput($request->function));
        $c->delete();
        foxRequestResult::throw("201", "Deleted");
    }
    
    public static function API_GET_list(request $request) {
        if (!empty($request->parameters)) {
            throw new foxException("Invalid request",400);
        }
        $request->blockIfNoAccess("viewCompanies");
        return static::search();
    }

    public static function API_GET_types(request $request) {
        if (!empty($request->parameters)) {
            throw new foxException("Invalid request",400);
        }
        $request->blockIfNoAccess("viewCompanies");
        return static::types;
    }
    
    public static function API_PUT(request $request) {
        if (!empty($request->parameters)) {
            throw new foxException("Invalid request",400);
        }
        $request->blockIfNoAccess("adminCompanies");
        $c=new static();
        $c->name=$request->requestBody->name;
        $c->qName=$request->requestBody->qName;
        $c->description=$request->requestBody->description;
        $c->type=$request->requestBody->type;
        $c->save();
        return $c;
    }

    public static function API_PATCH(request $request) {
        if (!empty($request->parameters)) {
            throw new foxException("Invalid request",400);
        }
        $request->blockIfNoAccess("adminCompanies");
        $c=new static(common::clearInput($request->function,"0-9"));
        $c->name=$request->requestBody->name;
        $c->qName=$request->requestBody->qName;
        $c->description=$request->requestBody->description;
        $c->type=$request->requestBody->type;
        $c->save();
        return $c;
    }
    
    public static function API_POST_search(request $request) {
        $request->blockIfNoAccess("viewCompanies");
        $opts=[];
        $pattern=null;
        if (property_exists($request->requestBody, "pattern")) {
            $pattern=$request->requestBody->pattern;
        }
        
        $pagesize=null;
        if (property_exists($request->requestBody, "pageSize")) {
            $pagesize=common::clearInput($request->requestBody->pageSize,"0-9");
        }

        $page=1;
        if (property_exists($request->requestBody, "page")) {
            $page=common::clearInput($request->requestBody->page,"0-9");
        }
        
        return static::search($pattern, $pagesize, $page, $opts);
    }
    
}
?>