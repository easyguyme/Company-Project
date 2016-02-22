<?php

namespace backend\modules\resque\components;

use backend\modules\resque\components\lib\Resque;
use backend\modules\resque\components\lib\ResqueScheduler;
use backend\modules\resque\components\lib\Resque\Job\Resque_Job_Status;
use backend\modules\resque\components\lib\Resque\Resque_Stat;
use backend\modules\resque\components\lib\Resque\Resque_Event;
use backend\modules\resque\components\lib\Resque\Resque_Redis;

/**
 * Yii Resque Component
 *
 * Yii component to work with php resque
 *
 * PHP version 5
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @author Rolies Deby <rolies106@gmail.com>
 * @copyright Copyright 2012, Rolies Deby <rolies106@gmail.com>
 * @link http://www.rolies106.com/
 * @package yii-resque
 * @since 0.1
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
class RResque extends \yii\base\Component
{
    /**
     *
     * @var string Redis server address
     */
    public $server = 'localhost';

    /**
     *
     * @var string Redis port number
     */
    public $port = '6379';

    /**
     *
     * @var int Redis database index
     */
    public $database = 0;

    /**
     *
     * @var string Redis password auth
     */
    public $password = '';

    /**
     *
     * @var array Workers to be started
     */
    public $workers = array ();

    /**
     *
     * @var array Workers auto scale settings.
     */
    public $scaleSetting = array ();

    /**
     *
     * @var string The path of supervisord configuration file
     */
    public $supervisordConfigPath = null;

    /**
     *
     * @var string The log path after the event is triggered
     */
    public $logFile = null;

    /**
     *
     * @var string Redis data prefix
     */
    public $prefix = '';

    /**
     * Initializes the connection.
     */
    public function init()
    {
        if (! class_exists ( 'RResqueAutoloader', false )) {
            // Turn off our amazing library autoload
            spl_autoload_unregister ( array ('Yii', 'autoload') );

            // # Include Autoloader library
            // include(dirname(__FILE__) . '/RResqueAutoloader.php');
            require_once dirname ( __FILE__ ) . '/RResqueAutoloader.php';

            // Run request autoloader
            RResqueAutoloader::register ();

            // Give back the power to Yii
            spl_autoload_register ( array ('Yii', 'autoload') );
        }

        Resque::setBackend ( $this->server . ':' . $this->port, $this->database, $this->password );
        if ($this->prefix) {
            Resque_Redis::prefix ( $this->prefix );
        }
    }

    /**
     * Create a new job and save it to the specified queue.
     *
     * @param string $queue
     *            The name of the queue to place the job in.
     * @param string $class
     *            The name of the class that contains the code to execute the job.
     * @param array $args
     *            Any optional arguments that should be passed when the job is executed.
     *
     * @return string
     */
    public function createJob($queue, $class, $args = array(), $trackStatus = true)
    {
        return Resque::enqueue ( $queue, $class, $args, $trackStatus );
    }

    /**
     * Cancel a delayed job.
     *
     * @param array $tokenAndTimstamp
     *            the UUID of delayed job and timestamp
     * @param string $queue
     *            The name of the queue to the job placed in.
     * @param string $class
     *            The name of the class that contains the code to execute the job.
     * @param array $args
     *            Any optional arguments that should be passed when the job is executed.
     * @param DateTime|int $at
     *            Instance of DateTime or UNIX timestamp (optinal).
     *
     * @return boolean whether the delayed job is canceled
     */
    public function cancelDelayedJob($tokenAndTimstamp, $queue, $class, $args = array())
    {
        return ResqueScheduler::removeDelayedJob ( $tokenAndTimstamp, $queue, $class, $args );
    }

    /**
     * Create a new scheduled job and save it to the specified queue.
     *
     * @param int $in
     *            Second count down to job.
     * @param string $queue
     *            The name of the queue to place the job in.
     * @param string $class
     *            The name of the class that contains the code to execute the job.
     * @param array $args
     *            Any optional arguments that should be passed when the job is executed.
     *
     * @return array the token of delayed job and timestamp
     */
    public function enqueueJobIn($in, $queue, $class, $args = array())
    {
        return ResqueScheduler::enqueueIn ( $in, $queue, $class, $args );
    }

    /**
     * Create a new scheduled job and save it to the specified queue.
     *
     * @param timestamp $at
     *            UNIX timestamp when job should be executed.
     * @param string $queue
     *            The name of the queue to place the job in.
     * @param string $class
     *            The name of the class that contains the code to execute the job.
     * @param array $args
     *            Any optional arguments that should be passed when the job is executed.
     *
     * @return array the token of delayed job and timestamp
     */
    public function enqueueJobAt($at, $queue, $class, $args = array())
    {
        return ResqueScheduler::enqueueAt ( $at, $queue, $class, $args );
    }

    /**
     * Get delayed jobs count
     *
     * @return int
     */
    public function getDelayedJobsCount()
    {
        $timestamps = Resque::redis ()->zrange ( 'delayed_queue_schedule', 0, - 1 );
        $count = 0;
        foreach ( $timestamps as $timestamp ) {
            $count += ResqueScheduler::getDelayedTimestampSize ( $timestamp );
        }
        return $count;
    }

    /**
     * Check job status
     *
     * @param string $token
     *            Job token ID
     *
     * @return string Job Status
     */
    public function status($token)
    {
        $status = new Resque_Job_Status ( $token );
        return $status->get ();
    }

    /**
     * Return Redis
     *
     * @return object Redis instance
     */
    public function redis($server = 'localhost:6379', $database = 0, $password = '', $prefix = 'resque:')
    {
        $numargs = func_num_args ();

        if ($numargs > 0) {
            return Resque::createRedis ( $server, $database, $password, $prefix );
        } else {
            return Resque::redis ();
        }
    }

    /**
     * Get delayed defailed information of jobs
     *
     * @return array JSON format job information list
     */
    public function getDelayedJobs()
    {
        $timestamps = Resque::redis ()->zrange ( 'delayed_queue_schedule', 0, - 1 );
        $jobs = array ();
        foreach ( $timestamps as $timestamp ) {
            $jobs = array_merge ( $jobs, ResqueScheduler::getDelayedTimestampJobs ( $timestamp ) );
        }
        return $jobs;
    }

    /**
     * Get the amount of executed jobs
     *
     * @param string $workerId
     *            the id of worker
     *
     * @return int
     */
    public function getExecutedJobsCount($workerId = null)
    {
        if (!empty ( $workerId )) {
            return Resque_Stat::get ( 'processed:' . $workerId );
        } else {
            return Resque_Stat::get ( 'processed' );
        }
    }

    /**
     * Get the amount of failed jobs
     *
     * @param string $workerId
     *            the id of worker
     *
     * @return int
     */
    public function getFailedJobsCount($workerId = null)
    {
        if (!empty ( $workerId )) {
            return Resque_Stat::get ( 'failed:' . $workerId );
        } else {
            return Resque_Stat::get ( 'failed' );
        }
    }

    /**
     * List all the failed job
     *
     * @return array the failed jobs in JSON format
     */
    public function getFailedJobs()
    {
        return Resque::redis ()->lrange ( 'failed', 0, - 1 );
    }

    /**
     * List all the queues
     *
     * @return array the queue names
     */
    public function getQueues()
    {
        return Resque::queues ();
    }

    /**
     * List all the workers
     *
     * @return array the worker names
     */
    public function getWorkers()
    {
        $workers = self::redis ()->smembers ( 'workers' );
        if (! is_array ( $workers )) {
            $workers = array ();
        }
        return $workers;
    }

    /**
     * Get idle workers
     */
    public function getIdleWorkers()
    {
        $workers = self::redis ()->smembers ( 'workers' );
        $idleWorkers = array ();
        if (is_array ( $workers )) {
            foreach ( $workers as $worker ) {
                $exist = Resque::redis ()->get ( 'worker:' . $worker );
                ! $exist && array_push ( $idleWorkers, $worker );
            }
        }
        return $idleWorkers;
    }

    /**
     * List the pending jobs in queue
     *
     * @return array the pending jobs information in JSON format
     */
    public function getPendingJobsInQueue($queue)
    {
        return Resque::redis ()->lrange ( 'queue:' . $queue, 0, - 1 );
    }

    /**
     * Listen on the pre-defined event
     *
     * This makes it possible to listen on the event triggered on runtime,
     * and execute the callback function passed in when the event is triggered.
     * The supported events can be found in the project wiki
     *
     * @param string $event
     *            Name of event to listen on.
     * @param mixed $callback
     *            Any callback callable by call_user_func_array.
     */
    public function listen($event, $callback)
    {
        Resque_Event::listen ( $event, $callback );
    }

    /**
     * Stop listen on the pre-defined event
     *
     * This makes it possible to stop the registered callback function for a specified event.
     * The supported events can be found in the project wiki
     *
     * @param string $event
     *            Name of event to listen on.
     * @param mixed $callback
     *            Any callback callable by call_user_func_array.
     */
    public function stopListening($event, $callback)
    {
        Resque_Event::stopListening ( $event, $callback );
    }
}
