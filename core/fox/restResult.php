<?php namespace fox;

/**
 *
 * Class fox\restResult
 *
 * @copyright MX STAR LLC 2018-2022
 * @version 4.0.0
 * @author Pavel Dmitriev
 * @license GPLv3
 *
 **/

class restResult {
    public bool $success=false;
    public ?string $statusCode=null;
    public ?string $statusText=null;
    public ?string $rawReply=null;
    public $reply=null;
    public array $replyHeaders=[];
   
    public function handleHeaderLine($curl, $header_line) {
        $r=[];
        
        if (preg_match("/^([^\ \:]*):(.*)/", trim($header_line), $r)) {
            $this->replyHeaders[trim($r[1])] = trim($r[2]);
        } elseif (preg_match("/^(HTTP\/.*)/", trim($header_line), $r)) {
            $this->replyHeaders["HTTP_STATUS_LINE"] = trim($r[1]);
            $this->statusText=trim($r[1]);
        } else if (strlen(trim($header_line)) > 0 ){
            array_push($this->replyHeaders, trim($header_line));
        }
        return strlen($header_line);
        
    }
}

?>