<?php
namespace backend\modules\resque\components\lib\Resque;

use backend\modules\resque\components\ResqueUtil;
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
Resque_Event::listen('afterEnqueue', array('Resque_EmailEventHandler', 'afterEnqueue'));
Resque_Event::listen('beforePerform', array('Resque_EmailEventHandler', 'beforePerform'));
Resque_Event::listen('afterPerform', array('Resque_EmailEventHandler', 'afterPerform'));
Resque_Event::listen('beforeFirstFork', array('Resque_EmailEventHandler', 'beforeFirstFork'));
Resque_Event::listen('beforeFork', array('Resque_EmailEventHandler', 'beforeFork'));
Resque_Event::listen('afterFork', array('Resque_EmailEventHandler', 'afterFork'));
Resque_Event::listen('onFailure', array('Resque_EmailEventHandler', 'onFailure'));

Resque_Event::listen('afterSchedule', array('Resque_EmailEventHandler', 'afterSchedule'));
Resque_Event::listen('afterRetry', array('Resque_EmailEventHandler', 'afterRetry'));

class Resque_EmailEventHandler
{
    public static function afterEnqueue($class, $args, $queue)
    {
        ResqueUtil::log('[afterEnqueue]Job class ' . $class . ' enqueued ' . $queue . ' queue with arguments:' . CJSON::encode($args));
    }

    public static function beforePerform($job)
    {
        ResqueUtil::log('[beforePerform]Begin to perform job ' . $job);
    }

    public static function afterPerform($job)
    {
        ResqueUtil::log('[afterPerform]Just performed job ' . $job);
    }

    public static function beforeFirstFork($worker)
    {
        ResqueUtil::log('[beforeFirstFork]Worker started. Listening on queues: ' . implode(', ', $worker->queues(false)));
    }

    public static function beforeFork($job)
    {
        ResqueUtil::log('[beforeFork]Just about to fork to run job ' . $job);
    }

    public static function afterFork($job)
    {
        ResqueUtil::log('[afterFork]Forked to run ' . $job);
    }

    public static function onFailure($exception, $job)
    {
        ResqueUtil::log('[OnFailure]Job ' . CJSON::encode($job->payload['args'][0]['email']) . ' failed with exception ' .$exception);
        $userId = $job->payload['args'][0]['id'];
        User::model()->deleteByPk($userId);
        RoleUser::model()->deleteAllByAttributes(array('user' => $userId));
        ProjectUser::model()->deleteAllByAttributes(array('user' => $userId));
        Validation::model()->deleteAllByAttributes(array('code' => $job->payload['args'][0]['code']));
    }

    public static function afterSchedule($at, $queue, $class, $args)
    {
        ResqueUtil::log('[afterSchedule]Job ' . $class . ' enqueue delayed queue ' . $queue . ' with arguments ' . CJSON::encode($args) . '. It will be enqueued actually at ' . $at);
    }

    public static function afterRetry($payload, $enqueuedAt)
    {
        ResqueUtil::log('[afterRetry]Failed job ' . CJSON::encode($payload) . 'is enqueued again at ' . $enqueuedAt);
    }
}