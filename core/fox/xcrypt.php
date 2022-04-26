<?php
namespace fox;

/**
 *
 * Class fox\xcrypt
 *
 * @copyright MX STAR LLC 2021
 * @version 4.0.0
 * @author Pavel Dmitriev
 * @license GPLv3
 *        
 */
class xcrypt
{

    static function encrypt($val, $key = null)
    {
        if (isset($key)) {
            $ENC_KEY = substr(md5($key), 0, 24);
        } else {
            $ENC_KEY = substr(md5(config::get("masterSecret")), 0, 24);
        }
        $ivlen = openssl_cipher_iv_length($cipher = "AES-128-CBC");
        $iv = openssl_random_pseudo_bytes($ivlen);
        $ciphertext_raw = openssl_encrypt($val, $cipher, $ENC_KEY, OPENSSL_RAW_DATA, $iv);
        $hmac = hash_hmac('sha256', $ciphertext_raw, $ENC_KEY, true);
        $ciphertext = base64_encode($iv . $hmac . $ciphertext_raw);
        return $ciphertext;
    }

    static function decrypt($val, $key = null)
    {
        $ENC_KEY = substr(md5(config::get("masterSecret")), 0, 24);

        if (isset($key)) {
            $ENC_KEY = substr(md5($key), 0, 24);
        } else {
            $ENC_KEY = substr(md5(config::get("masterSecret")), 0, 24);
        }

        $c = base64_decode($val);
        $ivlen = openssl_cipher_iv_length($cipher = "AES-128-CBC");
        $iv = substr($c, 0, $ivlen);
        $hmac = substr($c, $ivlen, $sha2len = 32);
        $ciphertext_raw = substr($c, $ivlen + $sha2len);
        $plaintext = openssl_decrypt($ciphertext_raw, $cipher, $ENC_KEY, OPENSSL_RAW_DATA, $iv);
        $calcmac = hash_hmac('sha256', $ciphertext_raw, $ENC_KEY, true);
        if ($hmac && hash_equals($hmac, $calcmac)) {
            return $plaintext;
        } else {
            return null;
        }
    }

    static function hash($val, $key = null)
    {
        if (isset($key)) {
            $ENC_KEY = substr(md5($key), 0, 24);
        } else {
            $ENC_KEY = substr(md5(config::get("masterSecret")), 0, 24);
        }

        return hash_hmac('sha256', $val, $ENC_KEY, false);
    }
}