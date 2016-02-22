<?php

namespace backend\utils;

/**
 * This is class file for mongodb utils
 * @author Devin Jin
 **/
class MongodbUtil
{

    /**
     * Transfer the MongoDate to unix timestamp
     * @param $mongoDate, MongoDate object.
     * @return timestamp
     * @author Devin Jin
     **/
    public static function MongoDate2TimeStamp($mongoDate)
    {
        return empty($mongoDate) ? 0 : $mongoDate->sec;
    }

    /**
     * Transfer the MongoDate to ms unix timestamp
     * @param $mongoDate, MongoDate object.
     * @return int
     * @author Rex Chen
     **/
    public static function MongoDate2msTimeStamp($mongoDate)
    {
        return empty($mongoDate) ? 0 : $mongoDate->sec * 1000 + $mongoDate->usec / 1000;
    }

    /**
     * Transfer ms unix timestamp to MongoDate
     * @param int.
     * @return MongoDate object.
     * @author Rex Chen
     **/
    public static function msTimetamp2MongoDate($timeStamp)
    {
        return new \MongoDate($timeStamp / 1000, ($timeStamp % 1000) * 1000);
    }

    /**
     * Transfer MongoDate to string
     * @param $mongoDate, MongoDate object.
     * @return String
     * @author Devin Jin
     **/
    public static function MongoDate2String($mongoDate, $format = 'Y-m-d H:i:s', $timezoneOffset = null)
    {
        if (empty($mongoDate)) {
            return '';
        }

        return TimeUtil::msTime2String($mongoDate->sec * TimeUtil::MILLI_OF_SECONDS, $format);
    }

    /**
     * Check whether the mongodate is expired(compare it to current time)
     * @param $mongoDate, MongoDate object.
     * @return boolean
     * @author Devin Jin
     **/
    public static function isExpired($mongoDate)
    {
        return self::MongoDate2TimeStamp($mongoDate) <= time();
    }

    /**
     * Transfer id list to mongoId List
     * @param array $params
     */
    public static function toMongoIdList($params)
    {
        if (!is_array($params)) {
            return $params;
        } else {
            foreach ($params as &$param) {
                $param = new \MongoId($param);
            }

            return $params;
        }
    }
}
