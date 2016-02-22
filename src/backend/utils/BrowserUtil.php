<?php

namespace backend\utils;

/**
 * This is class file for class WeiXinUtil
 * It contains the common WeiXin related functions
 *
 **/

class BrowserUtil
{
    const WEIXIN_BROWSER_AGENT = "MicroMessenger";
    const WEIBO_BROWSER_AGENT = 'Weibo';
    const ALIPAY_BROWSER_AGENT = 'Ali';

    /**
     *If the browser is WeChat browser
     *@return boolean, whether browser is WeChat browser
     *@author Judith Huang
     **/
    public static function isWeixinBrowser()
    {
        $agent = $_SERVER ['HTTP_USER_AGENT'];

        if (!strpos($agent, self::WEIXIN_BROWSER_AGENT)) {
            return false;
        }
        return true;
    }

    public static function isWeiboBrower()
    {
        $agent = $_SERVER ['HTTP_USER_AGENT'];

        if (!strpos($agent, self::WEIBO_BROWSER_AGENT)) {
            return false;
        } else {
            return true;
        }
    }

    public static function isAliBrower()
    {
        $agent = $_SERVER ['HTTP_USER_AGENT'];

        if (!strpos($agent, self::ALIPAY_BROWSER_AGENT)) {
            return false;
        } else {
            return true;
        }
    }

    public static function isMobileBrowser()
    {
        if (isset($_SERVER['HTTP_X_WAP_PROFILE']))
        {
            return true;
        }
        if (isset($_SERVER['HTTP_VIA']))
        {
            return stristr($_SERVER['HTTP_VIA'], "wap") ? true : false;
        }
        if (isset($_SERVER['HTTP_USER_AGENT']))
        {
            $clientkeywords = array ('nokia', 'sony', 'ericsson', 'mot',
                'samsung', 'htc', 'sgh', 'lg', 'sharp', 'sie-', 'philips',
                'panasonic', 'alcatel', 'lenovo', 'iphone', 'ipod', 'blackberry',
                'meizu', 'android', 'netfront', 'symbian', 'ucweb', 'windowsce',
                'palm', 'operamini', 'operamobi', 'openwave', 'nexusone',
                'cldc', 'midp', 'wap', 'mobile'
                );
            if (preg_match("/(" . implode('|', $clientkeywords) . ")/i", strtolower($_SERVER['HTTP_USER_AGENT'])))
            {
                return true;
            }
        }
        if (isset($_SERVER['HTTP_ACCEPT']))
        {
            if ((strpos($_SERVER['HTTP_ACCEPT'], 'vnd.wap.wml') !== false) && (strpos($_SERVER['HTTP_ACCEPT'], 'text/html') === false || (strpos($_SERVER['HTTP_ACCEPT'], 'vnd.wap.wml') < strpos($_SERVER['HTTP_ACCEPT'], 'text/html'))))
            {
                return true;
            }
        }
        return false;
    }
}
