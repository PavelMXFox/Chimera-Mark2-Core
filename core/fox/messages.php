<?php namespace fox;

/**
 *
 * Class fox\messages
 *
 * @copyright MX STAR LLC 2022
 * @version 4.0.0
 * @author Pavel Dmitriev
 * @license GPLv3
 *        
 */

class messages extends baseClass implements externalCallable {

    protected $id;
    protected $senderId;
    protected $rcptId;
    protected ?user $__sender=null;
    protected ?user $__recipient=null;
    public bool $read=false;
    public $subject=null;
    public $message=null;
    public time $sentStamp;
    public time $readStamp;

    public static $sqlTable = "tblMessages";

    protected static $sqlColumns = [
        "senderId"=>["type"=>"INT","index"=>"INDEX"],
        "rcptId"=>["type"=>"INT","index"=>"INDEX"],
        "read"=>["type"=>"INT","index"=>"INDEX"],
        "subject"=>["type"=>"VARCHAR(255)"],
        "message"=>["type"=>"TEXT"],
    ];

    protected function __xConstruct() {
        $this->sentStamp=time::current();
        $this->readStamp=new time();    
    }

    public static function send($message) {

    }

    protected static function xSearch($where, $pattern, ?array $options, sql $sql) {
        if (empty($options["sender"]) && empty($options["recipient"])) {
            throw new foxException("Empty rcpt and sender not allowed", 400);
        }

        if ($options["sender"]) {
            if ($options["sender"] instanceof user) {
                $senderId=$options["sender"]->id;
            } elseif (is_numeric($options["sender"])) {
                $senderId=$options["sender"];
            } else {
                throw new foxException("Invalid sender", 400);
            }


        }

        if ($options["recipient"]) {
            if ($options["recipient"] instanceof user) {
                $recipientId=$options["recipient"]->id;
            } elseif (is_numeric($options["recipient"])) {
                $recipientId=$options["recipient"];
            } else {
                throw new foxException("Invalid recipient", 400);
            }

            
        }

        if (!empty($options["unread"])) {

        }


        return ["where"=>$where];
    }
}
