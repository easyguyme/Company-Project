<?php

namespace backend\modules\resque\components\lib\Resque;

use backend\modules\resque\components\ResqueUtil;
require_once dirname(__FILE__) . '/../Resque.php';
require_once dirname ( __FILE__ ) . '/Worker.php';
Resque_Event::listen ( 'afterEnqueue', array ('Resque_Scaler', 'afterEnqueue') );
Resque_Event::listen ( 'beforeFork', array ('Resque_Scaler', 'beforeFork') );

class Resque_Scaler
{
    // define how many jobs require how many workers.
    public static $SCALE_SETTING = array (
        15 => 2,
        25 => 3,
        40 => 4,
        60 => 5
    );

    public static function afterEnqueue($class, $arguments, $queue)
    {
        ResqueUtil::log ( "Job was queued for " . $class . ".\n" );

        if (self::check_need_worker ( $queue )) {
            ResqueUtil::log ( "we need more workers\n" );
            self::add_worker ( $queue );
        } else {
            ResqueUtil::log ( "workers is enough.\n" );
        }
    }

    public static function beforeFork($job)
    {
        ResqueUtil::log ( "Just about to perform " . $job . "\n" );
        if (self::check_kill_worker ( $job->queue )) {
            ResqueUtil::log ( "too many workers...kill this one.\n" );

            // NOTE: tried to kill with $worker->shutdown but it's not working. use kill to send SIGQUIT instead.
            $server_workers = self::server_workers ( self::get_all_workers ( $job->queue ) );
            $current_workers = $server_workers [self::get_hostname ()];

            $supervisor = new SupervisorClient ( SUPERVISOR_SOCK );
            $queueName = GROUP_NAME . ':' . $current_workers [0] ['programname'];
            $processInfo = $supervisor->getProcessInfo ( $queueName );
            ResqueUtil::log ( CJSON::encode ( $processInfo ) );
            if (20 == $processInfo ['state']) {
                $stopProcessResult = $supervisor->stopProcess ( $queueName );
                ResqueUtil::log ( 'Stop process : ' . $queueName . ' result ' . $stopProcessResult );
            } else {
                ResqueUtil::log ( 'Process : ' . $queueName . ' is starting up' );
            }
            // `kill -3 {$current_workers[0]["pid"]}`;
            // $worker = $job->worker;
            // $worker->shutdown();
        } else {
            ResqueUtil::log ( "we still need this worker.\n" );
        }
    }

    // -----------------
    public static function cal_need_worker($queue)
    {
        $need_worker = 1;
        $pending_job_count = Resque::size ( $queue );

        // check if we need more workers
        $scaleSetting = ! empty ( Yii::app ()->resque->scaleSetting ) ? Yii::app ()->resque->scaleSetting : (self::$SCALE_SETTING);
        foreach ( $scaleSetting as $job_count => $worker_count ) {
            if ($pending_job_count > $job_count) {
                $need_worker = $worker_count;
            }
        }

        return $need_worker;
    }

    public static function check_kill_worker($queue)
    {
        $need_worker = self::cal_need_worker ( $queue );
        $current_worker = sizeof ( self::get_all_workers ( $queue ) );

        return ($current_worker > $need_worker) ? TRUE : FALSE;
    }

    public static function check_need_worker($queue)
    {
        $need_worker = self::cal_need_worker ( $queue );
        $current_worker = sizeof ( self::get_all_workers ( $queue ) );

        return ($need_worker > $current_worker) ? TRUE : FALSE;
    }

    // get worker info directly from redis, bad practice.
    // TODO: refactor with a Resque_Scaler_Worker extends Resque_Worker
    public static function get_all_workers($queue = NULL)
    {
        $ret = array ();

        $workers = Resque::redis ()->smembers ( 'workers' );
        if (! is_array ( $workers )) {
            $workers = array ();
        }
        foreach ( $workers as $workerId ) {
            $worker_data = explode ( ':', $workerId, 4 );

            $worker = array ();
            $worker ['hostname'] = $worker_data [0];
            $worker ['queues'] = explode ( ',', $worker_data [2] );
            $worker ['pid'] = $worker_data [1];
            $worker ['programname'] = $worker_data [3];
            $worker ['workerId'] = $workerId;

            if (($queue && (in_array ( $queue, $worker ['queues'] ) || in_array ( "*", $worker ['queues'] ))) || ! $queue) {
                $ret [] = $worker;
            }
        }

        return $ret;
    }

    public static function set_backend()
    {
        Resque::setBackend ( "localhost:6379" );
    }

    public static function server_workers($workers = array())
    {
        $ret = array ();
        foreach ( $workers as $worker ) {
            $ret [$worker ['hostname']] [] = $worker;
        }

        return $ret;
    }

    public static function get_hostname()
    {
        if (function_exists ( 'gethostname' )) {
            $hostname = gethostname ();
        } else {
            $hostname = php_uname ( 'n' );
        }

        return $hostname;
    }

    public static function add_worker($queue)
    {
        // $server_workers = self::server_workers(self::get_all_workers($queue));
        // $current_workers = isset($server_workers[self::get_hostname()]) ? $server_workers[self::get_hostname()] : array();
        // if(sizeof($current_workers) > 0) {
        // $pid = pcntl_fork();
        // if($pid == -1) {
        // ResqueUtil::log("Could not fork worker ".$i."\n");
        // }
        // // Child, start the worker
        // else if(!$pid) {

        // // if there are more than 1 types of workers on this machine, we don't know which kind to create. just create the first one.
        // $worker = new Resque_Worker($current_workers[0]['queues']);
        // // TODO: set logLevel
        // //$worker->logLevel = 2;
        // ResqueUtil::log('*** Starting worker '.$worker."\n");
        // // TODO: set interval
        // $worker->work();
        // }

        // return TRUE;
        // }

        // return FALSE;
        $supervisor = new SupervisorClient ( SUPERVISOR_SOCK );
        $queueName = $queue . uniqid ();
        $result = $supervisor->addProgramToGroup ( GROUP_NAME, $queueName, array (
            'command' => 'php ' . REPO_PATH . '/src/protected/modules/resque/components/bin/resque',
            'number' => '1',
            'redirect_stderr' => 'true',
            'autostart' => 'true',
            'autorestart' => 'true',
            'environment' => 'QUEUE="' . $queue . '", PROGRAM="' . $queueName . '", APP_INCLUDE="' . REPO_PATH . '/src/protected/modules/resque/components/lib/Resque/RequireFile.php"'
        ) );
        $result && (ResqueUtil::log ( 'Create process ' . $queueName . ' successfully' ));

        return $result;
    }
}
