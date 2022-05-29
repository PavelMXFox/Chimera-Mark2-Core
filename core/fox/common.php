<?php
namespace fox;

/**
 *
 * Class fox\common
 *
 * @copyright MX STAR LLC 2021
 * @version 4.0.0
 * @author Pavel Dmitriev
 * @license GPLv3
 *        
 */
class common
{

    static function txt2html($txt)
    {

        // We need some HTML entities back!
        $txt = str_replace('&', '&amp;', $txt);
        $txt = str_replace('<', '&lt;', $txt);
        $txt = str_replace('>', '&gt;', $txt);
        $txt = str_replace("\n", "<br/>", $txt);

        return $txt;
    }

    // получить значение с get или post
    static function getVal($name, $regex = '', $skipQuotes = null, $allowEmptyString = true)
    {
        if ((! isset($_POST[$name])) && (! isset($_GET[$name]))) {
            return null;
        }
        if ($regex != "") {
            if (isset($_POST[$name])) {
                $val = preg_replace('![^' . $regex . ']+!', '', $_POST[$name]);
            } else {
                $val = "";
            }
            if ($val == "" && isset($_GET[$name])) {
                $val = preg_replace('![^' . $regex . ']+!', '', $_GET[$name]);
            }
        } else {

            if (! isset($skipQuotes)) {
                if (isset($_POST[$name])) {
                    $val = preg_replace("![\'\"]+!", '\"', $_POST[$name]);
                } else {
                    $val = "";
                }
                if ($val == "" && isset($_GET[$name])) {
                    $val = preg_replace("![\'\"]+!", '\"', $_GET[$name]);
                }
            } else {
                if (isset($_POST[$name])) {
                    $val = $_POST[$name];
                } else {
                    $val = $_GET[$name];
                }
            }
        }
        if (! $allowEmptyString && $val == "") {
            $val = null;
        }

        return $val;
    }

    static function dropcslash($val)
    {
        $val = preg_replace("!\\\([\'\"])+!", "$1", $val);
        return $val;
    }
    
    static function validateEMail($str) {
        return preg_match("/[0-9A-Za-z_.-]*@[0-9A-Za-z_.-]/", $str);
    }

    /**
     * проверяем, что функция mb_ucfirst не объявлена
     * и включено расширение mbstring (Multibyte String Functions)
     */
    static function mbx_ucfirst($str, $encoding = 'UTF-8')
    {
        $str = mb_ereg_replace('^[\ ]+', '', $str);
        $str = mb_strtoupper(mb_substr($str, 0, 1, $encoding), $encoding) . mb_substr($str, 1, mb_strlen($str), $encoding);
        return $str;
    }

    static function getGUIDc()
    {
        mt_srand((double) microtime() * 10000); // optional for php 4.2.0 and up.
        $charid = strtoupper(md5(uniqid(rand(), true)));
        $hyphen = chr(45); // "-"
        return substr($charid, 0, 8) . $hyphen . substr($charid, 8, 4) . $hyphen . substr($charid, 12, 4) . $hyphen . substr($charid, 16, 4) . $hyphen . substr($charid, 20, 12);

    }

    static function getGUID()
    {
        return chr(123) . getGUIDc() . chr(125);
    }

    static function fullname2qname($first, $mid, $last)
    {
        return $last . " " . mb_substr($first, 0, 1) . ". " . mb_substr($mid, 0, 1) . ".";
    }

    static function text2html($src)
    {
        $src = preg_replace("/[\n]/", "</br>", $src);
        return $src;
    }

    static function genPasswd($number, $arr = null)
    {
        if (! isset($arr)) {
            $arr = array(
                'a',
                'b',
                'c',
                'd',
                'e',
                'f',
                'g',
                'h',
                'i',
                'j',
                'k',
                'l',
                'm',
                'n',
                'o',
                'p',
                'r',
                's',
                't',
                'u',
                'v',
                'x',
                'y',
                'z',
                '1',
                '2',
                '3',
                '4',
                '5',
                '6',
                '7',
                '8',
                '9',
                '0'
            );
        }
        // Генерируем пароль
        $pass = "";
        for ($i = 0; $i < $number; $i ++) {
            // Вычисляем случайный индекс массива
            $index = rand(0, count($arr) - 1);
            $pass .= $arr[$index];
        }
        return $pass;
    }

    static function clearInput($val, $regex = "")
    {
        if (empty($regex)) {
            $regex = "[0-9a-zA-Z_.@-]";
        }
        return preg_replace('![^' . $regex . ']+!', '', $val);
    }
    
    static function replaceMessageFromArray($text, $ref, $template='\$\{([^}]*)\}', $delimiter=".") {
        $match=[];
        preg_match_all('/'.$template.'/', $text, $match);
        
        foreach ($match[1] as $idx=>$val) {
            $text=str_replace($match[0][$idx], static::getArrayKeyByPath($ref,explode($delimiter,$val)), $text);
        }
        return $text;
    }
    
    static function getArrayKeyByPath($arr, $path) {
        if (gettype($path)=="NULL") {
            return "undefined";
        } elseif (gettype($path)=="string") { $path=[$path]; }
        
        while ($key = array_shift($path)) {
            if (array_key_exists($key, (array)$arr)) {
                $arr=((array)$arr)[$key];
            } else {
                return "undefined";
            }
        }
        return $arr;
    }
}
?>