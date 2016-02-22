<?php
namespace backend\utils;

class UrlUtil
{
    /**
     * Get domain, trim domain '/'
     * @return string
     */
    public static function getDomain()
    {
        return trim(DOMAIN, '/');
    }
}
