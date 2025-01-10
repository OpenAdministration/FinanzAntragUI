<?php

/**
 * class Crypto
 * framework class
 *
 * INTERTOPIA BASE FRAMEWORK
 *
 * @category        framework
 *
 * @author 			Michael Gnehr
 *
 * @since 			17.02.2018
 *
 * @copyright 		Copyright (C) 2018 - All rights reserved - do not copy without permission
 *
 * @platform        PHP
 *
 * @requirements    PHP 7.0 or higher
 */

namespace framework;

use Defuse\Crypto\Crypto;
use Defuse\Crypto\Key;
use Defuse\Crypto\KeyProtectedByPassword;

/**
 * class Crypto
 * framework class
 *
 * BASE FRAMEWORK
 *
 * @category        framework
 *
 * @author 			Michael Gnehr
 *
 * @since 			17.02.2018
 *
 * @copyright 		Copyright (C) 2018 - All rights reserved - do not copy without permission
 *
 * @platform        PHP
 *
 * @requirements    PHP 7.0 or higher
 */
class CryptoHandler
{
    /**
     * private constructor, all member static
     */
    private function __construct() {}

    // general ========================================================

    /**
     * generates secure random hex string of length: 2*$length
     *
     * @param  int  $length  0.5 string length
     *
     * @throws \Exception
     */
    public static function generateRandomString($length): ?string
    {
        if (! is_int($length)) {
            throw new \Exception('Invalid argument type. Integer expected.');
        }

        return bin2hex(random_bytes($length));
    }

    // string padding =========================================================

    /**
     * pad string to minimum length of
     *
     * encryption does not, and is not intended to, hide the length of the data being encrypted
     * hide this before encryption
     *
     * @param  int  $length
     *
     * @throws \Exception
     */
    public static function pad_string(string $string, $length = 128): string
    {
        $padlength = 0;
        if (mb_strlen($string) < $length) {
            $padlength = $length - mb_strlen($string);
        }
        $exp = strlen(''.$length);
        $base = 10 ** $exp;
        $base += $padlength;
        $padstr = substr(self::generateRandomString((int) (floor($padlength / 2) + 1)), 0, $padlength);
        $string .= $padstr.'__padded__'.$base.'__';

        return $string;
    }

    /**
     * unpad padded string
     * restore padded string
     *
     * encryption does not, and is not intended to, hide the length of the data being encrypted
     * hide this before encryption
     */
    public static function unpad_string(string $string): string
    {
        if (preg_match('/__padded__\d\d+__$/', $string, $matches, PREG_OFFSET_CAPTURE)) {
            $tmpout = substr($string, 0, $matches[0][1]);
            $padinfo = explode('__', substr($string, $matches[0][1]));
            $triminfo = (int) substr($padinfo[2], 1);
            $string = substr($tmpout, 0, -$triminfo);
        }

        return $string;
    }

    // defuse crypto =========================================================

    // without password -----------------------------------------------

    /**
     * encrypt string with key - defuse
     *
     * @see https://github.com/defuse/php-encryption/blob/master/docs/Tutorial.md
     *
     * @param  string  $data
     * @param  string  $keyAscii
     * @return string encrypted string
     */
    public static function encrypt_by_key($data, $keyAscii)
    {
        $key = Key::loadFromAsciiSafeString($keyAscii);

        return Crypto::encrypt($data, $key);
    }

    /**
     * decrypt string with secret key - defuse
     *
     * @see https://github.com/defuse/php-encryption/blob/master/docs/Tutorial.md
     *
     * @return string|false decrypted string | false if cipher was manipulated
     */
    public static function decrypt_by_key(string $ciphertext, string $keyAscii)
    {
        try {
            $key = Key::loadFromAsciiSafeString($keyAscii);

            return Crypto::decrypt($ciphertext, $key);
        } catch (\Defuse\Crypto\Exception\WrongKeyOrModifiedCiphertextException $e) {
            // An attack! Either the wrong key was loaded, or the ciphertext has
            // changed since it was created -- either corrupted in the database or
            // intentionally modified by Eve trying to carry out an attack.
            return false;
        }
    }

    // with password --------------------------------------------------

    /**
     * encrypt string with key (key locked with password) - defuse
     *
     * @see https://github.com/defuse/php-encryption/blob/master/docs/Tutorial.md
     *
     * @return string encrypted string
     */
    public static function encrypt_by_key_pw(string $data, string $keyAscii, string $password): string
    {
        $key = KeyProtectedByPassword::loadFromAsciiSafeString($keyAscii);
        $key = $key->unlockKey($password);

        return Crypto::encrypt($data, $key);
    }

    /**
     * decrypt string with key (key locked with password) - defuse
     *
     * @see https://github.com/defuse/php-encryption/blob/master/docs/Tutorial.md
     *
     * @return string|false decrypted string | false if cipher was manipulated
     */
    public static function decrypt_by_key_pw(string $ciphertext, string $keyAscii, string $password)
    {
        $key = KeyProtectedByPassword::loadFromAsciiSafeString($keyAscii);
        $key = $key->unlockKey($password);
        try {
            return Crypto::decrypt($ciphertext, $key);
        } catch (\Defuse\Crypto\Exception\WrongKeyOrModifiedCiphertextException $e) {
            // An attack! Either the wrong key was loaded, or the ciphertext has
            // changed since it was created -- either corrupted in the database or
            // intentionally modified by Eve trying to carry out an attack.
            return false;
        }
    }

    /**
     * read key secret from file
     *
     * @return string key
     */
    public static function get_key_from_file(string $filename): ?string
    {
        $out = $KEY_SECRET = null;
        if (file_exists($filename)) {
            require $filename;
            $out = $KEY_SECRET;
            unset($KEY_SECRET);
        }

        return $out;
    }

    // HASHING (passwords, etc) =========================================

    /**
     * hashes password with best password algorithem and return data
     * if argon2 is available this will be used, if not, bcrypt will be used
     */
    public static function hashPassword(string $password): string
    {
        if (defined('PASSWORD_ARGON2I')) {
            return self::hashPasswordArgon2($password);
        }

        return self::hashPasswordBcrypt($password);
    }

    /**
     * hashes password with argon2 algorithm
     */
    public static function hashPasswordArgon2(string $password): string
    {
        return password_hash($password, PASSWORD_ARGON2I);
    }

    /**
     * hashes password with bcrypt algorithm
     */
    public static function hashPasswordBcrypt(string $password): string
    {
        $options = [
            'cost' => 12,
        ];

        return password_hash($password, PASSWORD_BCRYPT, $options);
    }
}
