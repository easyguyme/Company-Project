<?php

namespace backend\modules\resque\components\lib\Resque;

use backend\modules\resque\components\ResqueUtil;
use backend\modules\resque\components\lib\ResqueScheduler;

/**
 * This file contains the event handlers for system message
 *
 * @author Vincent.Hou <vincenthou@augmentum.com.cn>
 */

/**
 * Resque_EventHandler class perferm the default behavior for events.
 *
 * @package system\resque\components
 * @author Vincent.Hou <vincenthou@augmentum.com.cn>
 *
 */
// Resque_Event::listen('afterEnqueue', array('Resque_EventHandler', 'afterEnqueue'));
// Resque_Event::listen('beforePerform', array('Resque_EventHandler', 'beforePerform'));
// Resque_Event::listen('afterPerform', array('Resque_EventHandler', 'afterPerform'));
// Resque_Event::listen('beforeFirstFork', array('Resque_EventHandler', 'beforeFirstFork'));
// Resque_Event::listen('beforeFork', array('Resque_EventHandler', 'beforeFork'));
// Resque_Event::listen('afterFork', array('Resque_EventHandler', 'afterFork'));
Resque_Event::listen ( 'onFailure', array ('Resque_EventHandler','onFailure') );

// Resque_Event::listen('afterSchedule', array('Resque_EventHandler', 'afterSchedule'));
// Resque_Event::listen('afterRetry', array('Resque_EventHandler', 'afterRetry'));
class Resque_EventHandler
{

    public static function afterEnqueue($class, $args, $queue)
    {
        ResqueUtil::log ( '[afterEnqueue]Job class ' . $class . ' enqueued ' . $queue . ' queue with arguments:' . CJSON::encode ( $args ) );
    }

    public static function beforePerform($job)
    {
        ResqueUtil::log ( '[beforePerform]Begin to perform job ' . $job );
    }

    public static function afterPerform($job)
    {
        ResqueUtil::log ( '[afterPerform]Just performed job ' . $job );
    }

    public static function beforeFirstFork($worker)
    {
        ResqueUtil::log ( '[beforeFirstFork]Worker started. Listening on queues: ' . implode ( ', ', $worker->queues ( false ) ) );
    }

    public static function beforeFork($job)
    {
        ResqueUtil::log ( '[beforeFork]Just about to fork to run job ' . $job );
    }

    public static function afterFork($job)
    {
        ResqueUtil::log ( '[afterFork]Forked to run ' . $job );
    }

    public static function onFailure($exception, $job)
    {
        ResqueUtil::log ( '[OnFailure]Job ' . $job . ' failed with exception ' . $exception );
        $job->retry ();
    }

    public static function afterSchedule($at, $queue, $class, $args)
    {
        $at = ResqueScheduler::getTimestamp ( $at );
        ResqueUtil::log ( '[afterSchedule]Job ' . $class . ' enqueue delayed queue ' . $queue . ' with arguments ' . CJSON::encode ( $args ) . '. It will be enqueued actually at ' . $at );
    }

    public static function afterRetry($payload, $enqueuedAt)
    {
        $enqueuedAt = ResqueScheduler::getTimestamp ( $enqueuedAt );
        ResqueUtil::log ( '[afterRetry]Failed job ' . CJSON::encode ( $payload ) . 'is enqueued again at ' . $enqueuedAt );
    }
}