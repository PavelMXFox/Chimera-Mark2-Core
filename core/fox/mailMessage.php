<?php 
namespace fox;

require_once 'mailAddress.php';
require_once 'mailAttachment.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception as mException;

use Exception;
use Html2Text\Html2Text;

/**
 * Class fox\mailMessage
 * @copyright MX STAR LLC 2020
 * @version 3.0.0
 * @author Pavel Dmitriev
 * @desc MailMessage class
 * 
 * @property mixed $account
 * @property mixed $messageId
 * @property-read array $mailFrom
 * @property-write mixed $mailFrom
 * @property-read array $rcptTo
 * @property mixed $references
 * @property mixed $inReplyTo
 * @property-write mixed $udate
 * @property-read mixed $date  
 * @property mixed $bodyHTML
 * @property mixed $bodyPlain 
 * @property mixed $direction
 * @property mixed $subject
 * @property ?boolean $fromRobot
 * @property-read boolean $isHTML
 * @property-read array $attachments
 * 
 * 
 **/

class mailMessage extends baseClass {
    protected $id;
    protected $__accountId;
    protected ?mailAccount $__account =null;
    public $messageId;
    protected array $mailFrom=[];
    protected array $rcptTo=[];
    protected array $cc=[];
    protected array $bcc=[];
    protected $direction=null;
    public time $date;
    public time $createDate;
    protected array $refIds=[];
    public $inReptyTo;
    protected $__subject;
    protected $__bodyHTML;
    protected $__bodyPlain;
    protected array $__attachments=[]; // array of mailAttachment
    protected array $__attachmentsID=[];
    public bool $fromRobot=false;
    
    public static $sqlTable="tblMailMessages";
    
    public $conn;
    public $refNum;
    
    protected static $sqlColumns = [
        "accountId"=>["type"=>"INT","nullable"=>false],
        "messageId"=>["type"=>"VARCHAR(255)", "nullable"=>true],
        "direction"=>["type"=>"CHAR(2)", "nullable"=>false],
        "date"=>["type"=>"DATETIME", "nullable"=>true],
        "createDate"=>["type"=>"DATETIME", "nullable"=>true],
        "inReptyTo"=>["type"=>"VARCHAR(255)", "nullable"=>true],
        "subject"=>["type"=>"VARCHAR(255)", "nullable"=>true],
        "bodyHTML"=>["type"=>"TEXT", "nullable"=>true],
        "bodyPlain"=>["type"=>"TEXT", "nullable"=>true],
        "txProto"=>["type"=>"VARCHAR(255)", "nullable"=>true],   
        "conn"=>["type"=>"SKIP"],
        "refNum"=>["type"=>"SKIP"],
    ];
    
    protected function addAddressToIdx(array &$idx, $val) {
        $addr=null;
        
        if ($val instanceof mailAddress) { $addr = $val; }
        elseif ($val instanceof user) {
            if (!common::validateEMail($val->eMail)) { throw new \Exception("Invalid user eMail. Failed.", 1509);};
            if ($val->eMailConfirmed) {
                $addr = new mailAddress($val->fullName, $val->eMail);
            } else {
                trigger_error("Address for user ".$val->id."not added - not eMailConfirmed");
            }
        }
        else {
            $addr = new mailAddress($val);
        }
        if (!$addr) { return false; }
        if (mailBlocklist::getByAddress($addr->address)) {
            trigger_error("Address ".$addr->address." blockListed.");
            return false;
        }
        foreach ($idx as $rcpt) {
            if ($rcpt->address == $addr->address) { return; }
        }
        
        array_push($idx,$addr);
    }

    protected function getAttachments() {
        if (empty($this->__attachments) && !empty($this->__attachmentsID)) {
            foreach ($this->__attachmentsID as $attId) {
                $att = new mailAttachment($attId);
                $att->message=$this;
                array_push($this->__attachments,$att);
            }
        }
        return $this->__attachments;
    }
    
    public function addRecipient($rcptTo) {
        $this->addAddressToIdx($this->rcptTo, $rcptTo);
    }

    public function addSender($mailFrom) {
        $this->addAddressToIdx($this->mailFrom, $mailFrom);
    }

    public function addCC($mailFrom) {
        $this->addAddressToIdx($this->cc, $mailFrom);
    }

    public function addBCC($mailFrom) {
        $this->addAddressToIdx($this->bcc, $mailFrom);
    }
    
    public function addAttachment(mailAttachment $att) {
        array_push($this->attachments, $att);
    }
    
    public function __get($key) {
        switch ($key) {
            case "bodyPlain":
                if (empty($this->__bodyPlain)) {
                    $this->__bodyPlain = (new Html2Text($this->bodyHTML))->getText();
                };
                return $this->__bodyPlain;
            case "bodyHTML":
                if (empty($this->__bodyHTML)) {
                    return base64_decode($this->__bodyPlain);
                } else {
                    return base64_decode($this->__bodyHTML);
                }
            case "attachments":
                return $this->getAttachments();
                break;
            case "isHTML":
                return (!empty($this->bodyHTML));
                break;
            case "account":
                if (empty($this->__account) and !empty($this->__accountId)) {
                    $this->__account = new mailAccount($this->__accountId);
                }
                return $this->__account;
                
            case "subject":
                return base64_decode($this->__subject);
            default: return parent::__get($key);
        }
    }
    
    public function __set($key, $val) {
        switch ($key) {
            case "account":
                if (empty($val)) {
                    $this->__account=null;
                    $this->__accountId=null;
                } elseif ($val instanceof mailAccount) {
                    $this->__account = $val;
                    $this->__accountId=$val->id;
                } elseif (gettype($val) == 'string' || gettype($val) == 'integer') {
                    $this->__account = new mailAccount($val);
                    $this->__accountId = $this->__account->id;
                }
                break;
            case "references":
                $ref = explode(" ", $val);
                $this->refIds=[];
                foreach ($ref as $ref_item) {
                    $ref_item=preg_replace("!(^\<)|(\>$)!", '', $ref_item);
                    array_push($this->refIds, $ref_item);
                }
                break;
                
            case "inReplyTo":
                $this->inReptyTo=preg_replace("!(^\<)|(\>$)!", '', $val);
                break;

            case "messageId":
                $this->messageId=preg_replace("!(^\<)|(\>$)!", '', $val);
                break;
            case "subject":
                $this->__subject=base64_encode($val);
                break;
                
            case "direction":
                $dir = strtoupper($val);
                if ($dir=='RX' || $dir=='TX') {
                    $this->direction=$dir;
                } else {
                    throw new \Exception("Invalid direction");
                }
                break;
            case "mailFrom":
                $this->mailFrom=[];
                if (!empty($val)) {
                    $this->addSender($val);
                }
                break;
                
            case "bodyPlain":
                $this->__bodyPlain=base64_encode($val);
                break;
            case "bodyHTML":
                $this->__bodyHTML=base64_encode($val);
                break;
                
            default: parent::__set($key, $val);
        }
    }

    public function createMessageID($uid=null) {
        if (empty($uid))  ($uid="XXXX-0000-00");
        $this->messageId=common::getGUIDc()."-CHIMERA-".$uid."-FOX-".time();
    }
    
    protected function serializeAddresses(&$arr) {
        $rv=[];
        foreach ($arr as $addr) {
            array_push($rv, $addr->full);
        }
        return $rv;
    }
  
    protected function deSerealizeAddresses($arr) {
        $rv=[];
        foreach ($arr as $addr) {
            array_push($rv, new mailAddress($addr));
        }
        return $rv;
    }
    
    protected function validateSave() {
        if (empty($this->accountId)) { throw new \Exception("AccountID can't be empty"); }
        if (empty($this->direction) || ($this->direction !== 'RX' && $this->direction !== 'TX')) { throw new \Exception("direction must be in [RX:TX]");}
        return true;
    }
    
    protected function create() {
        $this->attachmentsID=[];
        foreach ($this->attachments as $att) {
            $att->writeAttachment();
            
            array_push($this->attachmentsID, $att->id);
        }
        parent::create();
        if (isset($this->conn)) { imap_setflag_full($this->conn, $this->refNum, "\Seen");}
    }
    

    public function preSave() {
        if ($this->direction=='TX') {
            if (empty($this->__accountId)) {
                $this->__account=mailAccount::getDefaultAccount($this->sql);
                if (empty($this->__account)) {
                    throw new \Exception("Default mail account absent!");
                }
                $this->__accountId=$this->__account->id;
            }
            if (empty($this->mailFrom)) { $this->addSender($this->__account->address);}
            if (empty($this->messageId)) { $this->createMessageID();}
            if (empty($this->date)) { $this->date=time::current();};
            
        }
        
    }
    
    public function save() {
        $this->preSave();
        parent::save();
        
    }
    
    public function send($save=false) {
        
        if (empty($this->direction)) { $this->direction="TX";}
        if ($this->direction != 'TX') {
            throw new \Exception("Unable to send message with ".$this->direction." type!");
        }
        $this->preSave();
                
        $this->__get("account");
        
        if ($this->account->txProto!='smtp' || $this->account->txServer == null) {
            throw  new \Exception("This account can't send messages");
        }
        $mail = new PHPMailer(true);
        $mail->CharSet = PHPMailer::CHARSET_UTF8;
        $mail->ContentType=PHPMailer::CHARSET_UTF8;
        try {
            $mail->isSMTP();                                            // Send using SMTP
            $mail->Host       = $this->account->txServer;                    // Set the SMTP server to send through
            $mail->SMTPAuth   = !empty($this->account->login);                                   // Enable SMTP authentication
            $mail->Username   = $this->account->login;                     // SMTP username
            $mail->Password   = $this->account->password;                               // SMTP password
            $mail->SMTPOptions = ['ssl'=> ['allow_self_signed' => true]];
            if ($this->account->txSSL) {
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;         // Enable TLS encryption; `PHPMailer::ENCRYPTION_SMTPS` encouraged
            } else {
                $mail->SMTPSecure = false;
                if ($this->account->txPort==587) {
                    $mail->SMTPAutoTLS=true;
                } else {
                    $mail->SMTPAutoTLS=false;
                }
            }
            $mail->Port = $this->account->txPort;                                    // TCP port to connect to, use 465 for `PHPMailer::ENCRYPTION_SMTPS` above
            
            if (empty($this->rcptTo)) {
                trigger_error("Empty recipients list, send aborted");
                return false;
            }
            //Recipients
            $mail->setFrom($this->mailFrom[0]->address, $this->mailFrom[0]->name);
            foreach ($this->rcptTo as $addr) {
                $mail->addAddress($addr->address, '=?UTF-8?B?'.base64_encode($addr->name).'?=');
            }
            
            foreach ($this->cc as $addr) {
                $mail->addCC($addr->address, $addr->name);
            }
            
            foreach ($this->bcc as $addr) {
                $mail->addBCC($addr->address, $addr->name);
            }
            
            $this->getAttachments();

            foreach ($this->__get("attachments") as $att) {
                $mail->addAttachment($att->getPath(), $att->filename);         // Add attachments
            }
            
            // Content
            $mail->isHTML($this->isHTML);                                  // Set email format to HTML
            $mail->Subject =  '=?UTF-8?B?'.base64_encode($this->subject).'?=';
            $mail->Subject =  $this->subject;
            $mail->Body    = $this->bodyHTML;
            $mail->AltBody = $this->bodyPlain;
            $mail->addCustomHeader("Auto-Submitted", "auto-replied");          
            $mail->send();
            
            
            
        } catch (mException $e) {
            throw new \Exception("Message could not be sent. Mailer Error: {$mail->ErrorInfo}");
        }
        
    }

}
?>