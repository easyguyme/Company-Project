<?php
namespace backend\utils;

use Yii;

/**
 * This is class file for class StringUtil
 * It contains the common string related functions
 *
 * @author Devin Jin
 **/

class StringUtil
{
    const ALL_DIGITS_LETTERS = 6;

    //const for url regrex
    const URL_REGREX = '/^(ftp|http|https):\/\/([\w-]+\.)+(\w+)(:[0-9]+)?(\/|([\w#!:.?+=&%@!\-\/]+))?$/';

    /**
     * Generate random string of (int)$length length and type $type
     *
     * @param int $length The keyword length, default value is 5.
     * @param int $type The characters type The default is 0
     * @param string $charlist The characters
     *
     * @return string The keyword
     */
    public static function rndString($length = 5, $type = 0, $charlist = '')
    {
        $str = '';
        $length = intval($length);
        // define possible characters
        switch ($type) {
            // custom char list, or comply to charset as defined in config
            case 0:
                $possible = (!empty($charlist)) ? $charlist : self::getCharset();
                break;

            // no vowels to make no offending word, no 0/1/o/l to avoid confusion between letters & digits. Perfect for passwords.
            case 1:
                $possible = "23456789bcdfghjkmnpqrstvwxyz";
                break;

            // Same, with lower + upper
            case 2:
                $possible = "23456789bcdfghjkmnpqrstvwxyzBCDFGHJKMNPQRSTVWXYZ";
                break;

            // all letters, lowercase
            case 3:
                $possible = "abcdefghijklmnopqrstuvwxyz";
                break;

            // all letters, lowercase + uppercase
            case 4:
                $possible = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ";
                break;

            // all digits & letters lowercase
            case 5:
                $possible = "0123456789abcdefghijklmnopqrstuvwxyz";
                break;

            // all digits & letters lowercase + uppercase
            case 6:
                $possible = "0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ";
                break;
        }

        $i = 0;
        while ($i < $length) {
            $str .= substr($possible, mt_rand(0, strlen($possible) - 1), 1);
            $i++;
        }

        return $str;
    }

    /**
     * Get the config charset
     *
     * @return string The characters
     * @author Harry Sun
     */
    private static function getCharset()
    {
        // The default char list
        $charlist = '0123456789abcdefghijklmnopqrstuvwxyz';

        if (!empty(\Yii::$app->params['charlist'])) {
            $charlist = \Yii::$app->params['charlist'];
        }

        return $charlist;
    }

    /**
     * Generates the UUID
     *
     * Uses this function to generate uuid
     * Use time and other parameter to generate the uuid
     *
     * @example
     *      BaseModel.php Generate uuid before save user. <br>
     *      Use Util::uuid(), it will return an unique string in system <br>
     *      Uuid format 1225c695-cfb8-4ebb-aaaa-80da344e8352 <br>
     * @return string an unique string in system
     *
     */
    public static function uuid()
    {
        mt_srand((double) microtime() * 10000);//optional for php 4.2.0 and up.
        $charid = md5(uniqid(rand(), true));
        $hyphen = chr(45);// -;
        $uuid = substr($charid, 0, 8) . $hyphen
               . substr($charid, 8, 4) . $hyphen
               . substr($charid, 12, 4) . $hyphen
               . substr($charid, 16, 4) . $hyphen
               . substr($charid, 20, 12);
        return $uuid;
    }

    /**
     * Determine whether a string is JSON
     *
     * @param string $string
     * @return boolean whether $string is JSON
     * @author Harry Sun
     */
    public static function isJson($string)
    {
        return is_string($string) &&
                is_object(json_decode($string)) &&
                (json_last_error() == JSON_ERROR_NONE) ? true : false;
    }

    /**
     * Validate whether a string is email format
     *
     * @param string $string
     * @return boolean whether $string is JSON
     * @author Harry Sun
     */
    public static function isEmail($string)
    {
        $pattern = '/^[A-Za-z0-9._-]+@[A-Za-z0-9._-]+\.[A-Za-z]{2,6}$/';
        $result = preg_match($pattern, $string);
        if ($result == 0) {
            return false;
        }
        return true;
    }

     /**
     * Validate whether a string is mobile format
     *
     * @param string $string
     * @return boolean whether $string is JSON
     * @return boolean true|false
     */
    public static function isMobile($string)
    {
        $reZero = "/^0?1[0-9]{10}$/";
        $reTel = "/^09[0-9]{8}$/";
        $reTelthree = "/^\d{8}$/";
        $reTelFour = "/^853[0-9]{8}$/";

        if (!preg_match($reZero, $string) && !preg_match($reTel, $string)
            && !preg_match($reTelthree, $string) && !preg_match($reTelFour, $string)) {
            return false;
        }
        return true;
    }

    /**
     * Convert HEX color to RGBA color
     *
     * @param string $hex the HEX color value
     * @return array the RGB color value
     * @author Vincent Hou
     */
    public static function hex2rgb($hex = '')
    {
        //three character HEX value, example: #aaa
        if (strlen($hex) == 4) {
            $tmpHex = "#";
            for ($i = 1; $i < 4; $i += 1) {
                $tmpHex = $tmpHex . substr($hex, $i, 1) . substr($hex, $i, 1);
            }
            $hex = $tmpHex;
        }

        $rgb = [];
        for ($i = 1; $i < 7; $i += 2) {
            array_push($rgb, (intval(substr($hex, $i, 2), 16)));
        }
        return $rgb;
    }

    public static function regStrFormat($searchkey)
    {
        $char        = ['\\', '*', '.', '?', '+', '$', '^', '[', ']', '(', ')', '{', '}', '|', '/'];
        $replaceChar = ['\\\\', '\*', '\.', '\?', '\+', '\$', '\^', '\[', '\]', '\(', '\)', '\{', '\}', '\|', '\/'];

        return str_replace($char, $replaceChar, $searchkey);
    }

    /**
     * to suport chinese name to divide two part:firstName and surname
     * @param $fullName, string
     * @return [$firstName,$lastName]
     */
    public static function splitName($fullName)
    {
        $hyphenated = [
            '欧阳','太史','端木','上官','司马','东方','独孤','南宫','万俟','闻人','夏侯','诸葛','尉迟','公羊','赫连','澹台','皇甫',
            '宗政','濮阳','公冶','太叔','申屠','公孙','慕容','仲孙','钟离','长孙','宇文','城池','司徒','鲜于','司空','汝嫣','闾丘','子车','亓官',
            '司寇','巫马','公西','颛孙','壤驷','公良','漆雕','乐正','宰父','谷梁','拓跋','夹谷','轩辕','令狐','段干','百里','呼延','东郭','南门',
            '羊舌','微生','公户','公玉','公仪','梁丘','公仲','公上','公门','公山','公坚','左丘','公伯','西门','公祖','第五','公乘','贯丘','公皙',
            '南荣','东里','东宫','仲长','子书','子桑','即墨','达奚','褚师'
        ];
        $vLength = mb_strlen($fullName, 'utf-8');
        $lastName = '';
        $firstName = '';
        if ($vLength > 2) {
            //check the two word in the head whether in the hyphenated
            $preTwoWords = mb_substr($fullName, 0, 2, 'utf-8');
            if (in_array($preTwoWords, $hyphenated)) {
                $firstName = $preTwoWords;
                $lastName = mb_substr($fullName, 2, null, 'utf-8');
            } else {
                $firstName = mb_substr($fullName, 0, 1, 'utf-8');
                $lastName = mb_substr($fullName, 1, null, 'utf-8');
            }
        } else if ($vLength == 2) {
            //only two word,the first word is first name,the second word is last name
            $firstName = mb_substr($fullName, 0, 1, 'utf-8');
            $lastName = mb_substr($fullName, 1, null, 'utf-8');
        } else {
            $firstName = $fullName;
        }
        return [$firstName, $lastName];
    }
}
