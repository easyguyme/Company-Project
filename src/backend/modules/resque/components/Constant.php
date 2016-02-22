<?php

namespace backend\modules\resque\components;

/**
 * This file contains the constants used across files.
 *
 * @author Vincent.Hou <vincenthou@augmentum.com.cn>
 */

/**
 * Constant class collect all the constants used in the module.
 *
 * @package system\resque\components
 * @author Vincent.Hou <vincenthou@augmentum.com.cn>
 *
 */
class Constant
{
    public static $IS_LOGINED = 1;
    public static $SUPERVISORD_CONF = '/etc/supervisor/supervisord.conf';
    public static $TOKEN_EXPIRE_TIME = 3600; // one hour
    public static $RETRY_TIMES = 3; // retry times after a job failed
    public static $FAILED_TIMES_EXPIRED_TIME = 3600; // one hour
    public static $FAILED_JOB_DELAYED_TIME = 300; // 5 minutes
    const NOT_DELETED = 0;
    public static $LAST_RETRY_QUEUE = 'LastRetry';
}
