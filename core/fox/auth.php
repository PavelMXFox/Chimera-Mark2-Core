<?php
namespace fox;

/**
 *
 * Class fox\auth
 *
 * @copyright MX STAR LLC 2018-2022
 * @version 4.0.0
 * @author Pavel Dmitriev
 * @license GPLv3
 *
 **/

class auth extends baseClass implements noSqlMigration
{

    public static function doAuth($login, $password)
    {
        $sql = sql::getConnection();
        $res = $sql->quickExec1Line("select * from `" . user::$sqlTable . "` where `login` = '" . common::clearInput($login) . "' and `secret` = '" . xcrypt::hash($password) . "'");
        if ($res) {
            $u = new user($res);
            return $u;
        } else {
            return false;
        }
    }
}

?>