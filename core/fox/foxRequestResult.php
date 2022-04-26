<?php
namespace fox;

/**
 *
 * Class fox\foxRequestResult
 *
 * @copyright MX STAR LLC 2018-2022
 * @version 4.0.0
 * @author Pavel Dmitriev
 * @license GPLv3
 *
 **/

class foxRequestResult extends \Exception
{

    public $retVal;

    public static function throw($code, $message, $retVal = null)
    {
        $e = new static();
        $e->code = $code;
        $e->message = $message;
        $e->retVal = $retVal;
        throw $e;
    }
}

?>