<?php namespace fox;

/**
 *
 * Class fox\restRequest
 *
 * @copyright MX STAR LLC 2018-2022
 * @version 4.0.0
 * @author Pavel Dmitriev
 * @license GPLv3
 *
 **/

class restRequest {
    public function __construct($function=null, $method=null, $data=null,$xtraHeaders=null) {
        if (!empty($function)) { $this->function=$function; }
        if (!empty($method)) { $this->method=$method; }
        if (!empty($data)) { $this->data=$data; }
        if (!empty($xtraHeaders)) { $this->extraHeaders=$xtraHeaders; }
    }
    public $method="GET";
    public $function=null;
    public $data=[];
    public $extraHeaders=[];
    public $successCodes=["200","201"];
}

?>