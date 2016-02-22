<?php

namespace backend\utils;

use Yii;

/**
 * Time util provides the time related functions
 */

class TimeUtil
{
    const SECONDS_OF_MINUTE = 60;

    const SECONDS_OF_HOUR = 3600;

    const MILLI_OF_SECONDS = 1000;

    const SECONDS_OF_DAY = 86400;

    public static $timezones = [
        '-12' => 'Pacific/Kwajalein',
        '-11' => 'Pacific/Samoa',
        '-10' => 'Pacific/Honolulu',
        '-9' => 'America/Juneau',
        '-8' => 'America/Los_Angeles',
        '-7' => 'America/Denver',
        '-6' => 'America/Mexico_City',
        '-5' => 'America/New_York',
        '-4' => 'America/Caracas',
        '-3.5' => 'America/St_Johns',
        '-3' => 'America/Argentina/Buenos_Aires',
        '-2' => 'Atlantic/Azores',// no cities here so just picking an hour ahead
        '-1' => 'Atlantic/Azores',
        '0' => 'Europe/London',
        '1' => 'Europe/Paris',
        '2' => 'Europe/Helsinki',
        '3' => 'Europe/Moscow',
        '3.5' => 'Asia/Tehran',
        '4' => 'Asia/Baku',
        '4.5' => 'Asia/Kabul',
        '5' => 'Asia/Karachi',
        '5.5' => 'Asia/Calcutta',
        '6' => 'Asia/Colombo',
        '7' => 'Asia/Bangkok',
        '8' => 'Asia/Singapore',
        '9' => 'Asia/Tokyo',
        '9.5' => 'Australia/Darwin',
        '10' => 'Pacific/Guam',
        '11' => 'Asia/Magadan',
        '12' => 'Asia/Kamchatka'
    ];

    /**
     * Get the timestamp of 0:00 today.
     * Without considering timezone related issues till now
     * @return timestamp
     */
    public static function today()
    {
        //consider issues caused by timezone difference in the future
        return $todayTimeStamp = strtotime(date('Y-m-d'));
    }

    /**
     * Get the timestamp of 01-01 today.
     * @return timestamp
     */
    public static function thisYear()
    {
        return $todayTimeStamp = strtotime(date('Y-01-01'));
    }

    /**
     * Get the millisecond timestamp
     * @return int
     */
    public static function msTime($time = null)
    {
        return empty($time) ?  time() * self::MILLI_OF_SECONDS : $time * self::MILLI_OF_SECONDS;
    }

    /**
     * Get milliseconds of time
     * @param timestamp $mstime
     * @return int
     */
    public static function ms2sTime($mstime)
    {
        return $mstime / self::MILLI_OF_SECONDS;
    }

    /**
     * Formate millisecond timestamp to string
     * @param timestamp $time
     * @param string $formate
     */
    public static function msTime2String($mstime, $format = 'Y-m-d H:i:s', $timezoneOffset = null)
    {
        $timezone = self::getDefaultTimezone();
        if (!is_null($timezoneOffset)) {
            $timezoneOffset = - $timezoneOffset;
            $timezone = self::$timezones[(string) $timezoneOffset];
        }

        date_default_timezone_set($timezone);

        return date($format, $mstime / self::MILLI_OF_SECONDS);
    }

    /**
     * Formate second timestamp to string
     * @param timestamp $time
     * @param string $formate
     */
    public static function sTime2String($stime, $format = 'Y-m-d H:i:s', $timezoneOffset = null)
    {
        return self::msTime2String($stime * self::MILLI_OF_SECONDS, $format, $timezoneOffset);
    }

    /**
     * Transform the time string to milliseconds integer
     * @param time string $timeStr
     * @param integer milliseconds for time string
     */
    public static function string2MsTime($timeStr)
    {
        return strtotime($timeStr) * self::MILLI_OF_SECONDS;
    }

    /**
     * Check if the current hour is in the specific hour interval
     * @param  string $start in format of "H:i"
     * @param  string $end   in format of "H:i"
     * @param  integer $timezoneOffset the offset hours with UTC
     * @return boolean
     */
    public static function checkHourInterval($start, $end, $timezoneOffset)
    {
        $currentTime = strtotime(date('H:i'));
        $startTimestamp = strtotime($start) + $timezoneOffset * self::SECONDS_OF_HOUR;
        $endTimestamp = strtotime($end) + $timezoneOffset * self::SECONDS_OF_HOUR;
        return ($startTimestamp < $currentTime) && ($endTimestamp > $currentTime);
    }

    public static function getTimezoneOffset()
    {
        return -8;
    }

    public static function getDefaultTimezone()
    {
        return Yii::$app->timeZone;
    }

    /**
     * Get the quarter value from timestamp value
     * @param int $timestamp
     * @return int
     */
    public static function getQuarter($timestamp)
    {
        return intval((date('n', $timestamp) + 2) / 3);
    }

    /**
     * Get the datetime from time string, use current datetime as fallback
     * @param string $dateStr
     * @return int
     */
    public static function getDatetime($dateStr)
    {
        if (empty($dateStr)) {
            $today = strtotime(date('Y-m-d'));
            $datetime = strtotime('-1 day', $today);
        } else {
            $datetime = strtotime($dateStr);
        }
        return $datetime;
    }
}
