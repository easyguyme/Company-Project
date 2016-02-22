<?php
namespace backend\components\resque;

use Yii;
use yii\base\Component;
use backend\utils\LogUtil;

class Job extends Component
{
    const GLOBAL_QUEUE = 'global';
    const BACKEND_QUEUE = 'backend';

    /**
     * Create a job and put it in global queue
     * @param  string $className
     * @param  array  $args
     * @param  timestamp $executeTime UNIX timestamp when job should be executed, default is now
     * @param  int $span seconds
     * @return string|null      job token or empty
     */
    public function create($className, $args = [], $executeTime = null, $interval = null, $isGlobal = true)
    {
        LogUtil::info(['message' => 'Begin a job', 'args' => $args], 'resque');

        $queue = $isGlobal ? self::GLOBAL_QUEUE : self::BACKEND_QUEUE;
        $args['language'] = Yii::$app->language;
        if (!empty($executeTime)) {
            if (!empty($interval)) {
                $args['cron_job_time_interval'] = '+ '. $interval . ' seconds';
                while ($executeTime < time()) {
                    $executeTime = strtotime($args['cron_job_time_interval'], $executeTime);
                }
                $args['cron_job_execute_time'] = $executeTime;
            }
            return Yii::$app->resque->enqueueJobAt($executeTime, $queue, $className, $args);
        } else {
            return Yii::$app->resque->createJob($queue, $className, $args);
        }
    }

    /**
     * Get the job status
     *
     * @param  string $token    job token
     * @return int
     */
    public function status($token)
    {
        return Yii::$app->resque->status($token);
    }
}
