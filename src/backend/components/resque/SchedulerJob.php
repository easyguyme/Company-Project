<?php
namespace backend\components\resque;

use Yii;
use yii\helpers\ArrayHelper;
use yii\helpers\Json;

/**
* Job for SchedulerJob
*/
class SchedulerJob
{
    public function setUp()
    {
        $args = $this->args;
        if (!empty($args['cron_job_time_interval'])) {
            //json format jobs
            $delayJobs = Yii::$app->resque->getDelayedJobs();
            $className = $this->job->payload['class'];

            //check if Job is exists in $delayJobs
            $isJobExists = false;
            foreach ($delayJobs as $delayJob) {
                $delayJob = Json::decode($delayJob);
                //get delayjob args, unset job id
                $delayJobArgs = $delayJob['args'][0];
                unset($delayJobArgs['djID'], $args['djID']);
                if ($delayJob['class'] == $className && $delayJobArgs == $args) {
                    $isJobExists = true;
                }
            }
            if (!$isJobExists) {
                $args['cron_job_execute_time'] = strtotime($args['cron_job_time_interval'], $args['cron_job_execute_time']);
                Yii::$app->job->create($className, $args, $args['cron_job_execute_time']);
            }
        }
    }

    public function perform()
    {
    }

    public function tearDown()
    {
    }
}
