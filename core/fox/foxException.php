<?php
namespace fox;

/**
 *
 * Class fox\foxException
 *
 * @copyright MX STAR LLC 2018-2022
 * @version 4.0.0
 * @author Pavel Dmitriev
 * @license GPLv3
 *
 **/

class foxException extends \Exception
{

    protected $xCode=null;
    protected $status = "ERR";

    public const STATUS_ERR = "ERR";

    public const STATUS_WARN = "WARN";

    public const STATUS_ALERT = "ALERT";

    public const STATUS_INFO = "INFO";

    public static function throw($status, $message, $code, $xCode=null, $prev = null)
    {
        $e = new self($message, $code, $prev);
        $e->setStatus($status);
        $e->setXCode($xCode);
        throw $e;
    }

    public function setStatus($status)
    {
        $this->status = $status;
    }
    
    public function setXCode($xCode)
    {
        $this->xCode=$xCode;
    }
    
    public function getStatus()
    {
        return $this->status;
    }

    public function getXCode()
    {
        return empty($this->xCode)?$this->code:$this->xCode;
    }
    
}

?>