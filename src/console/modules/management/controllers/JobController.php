<?php
namespace console\modules\management\controllers;

use Yii;
use Exception;
use ReflectionClass;
use backend\utils\FileUtil;
use yii\console\Controller;
use yii\helpers\Json;
use backend\utils\LogUtil;

/**
 * Manage resque job
 */
class JobController extends Controller
{
    /**
     * Init all cron jobs
     * @param string $isRemoveOldJob
     */
    public function actionInit($isRemoveOldJob = true)
    {
        $backendModuls = FileUtil::getModule('backend');
        if (!empty($backendModuls)) {
            foreach ($backendModuls as $module) {
                $reflectionClass = new ReflectionClass($module['class']);
                if ($reflectionClass->hasMethod('setScheduleJob')) {
                    $jobs = call_user_func([$module['class'], 'setScheduleJob']);
                    if (!empty($jobs)) {
                        foreach ($jobs as $job) {
                            if (empty($job['class']) || empty($job['executeAt']) || empty($job['interval'])) {
                                echo 'missing param: class or executeAt or interval' . PHP_EOL;
                                echo 'the configure params in this job is :' . var_export($job, true) . PHP_EOL;
                                LogUtil::error(['message' => 'missing params(class or executeAt or interval) in create job', 'params' => $job], 'management');
                                continue;
                            }
                            $this->createJob($isRemoveOldJob, $job);
                        }
                    }
                }
            }
        }
        echo 'over' . PHP_EOL;
    }

    public function createJob($isRemoveOldJob, $job)
    {
        $removeResult = true;
        $class = $job['class'];
        echo 'Job: ' . $class . PHP_EOL;

        if ($isRemoveOldJob) {
            $removeResult = $this->cancleDelayJob($class);
        }
        if ($removeResult) {
            $args = $this->getJobAnnotationArgs($class);
            //surport to configure args in module.php
            if (!empty($args) && is_array($args)) {
                $args = isset($job['args']) ? array_merge($args, $job['args']) : $args;
            } else {
                $args = isset($job['args']) ? $job['args'] : [];
            }
            $result = Yii::$app->job->create($class, $args, strtotime(date($job['executeAt'])), $job['interval']);
            $result = is_array($result) ? $result['token'] . ' executeAt ' . date('Y-m-d H:i:s', $result['at']) : $result;
            echo 'Success: Created job ' . $result . PHP_EOL;
        }
    }

    /**
     * Create a job
     * @param string $class             Job class name              @example   'backend\\modules\\resque\\components\\job\\DemoEcho'
     * @param string $executeAt         Job execute time            @example   '2015-06-16 10:00:00'
     * @param string $interval          Job execute interval time   @example    60
     * @param string $isReplaceExistJob Is remove exists job
     * @param string $args              Job args
     */
    public function actionCreate($class, $executeAt = null, $interval = null, $isReplaceExistJob = false, $args = '')
    {
        $defaultArgs = ['description' => 'Delay: ' . $class];
        $docArgs = $this->getJobAnnotationArgs($class);
        $args = empty($args) ? [] : Json::decode($args);
        $args = array_merge($defaultArgs, $docArgs, $args);

        //replace exists job
        if ($isReplaceExistJob) {
            $removeResult = $this->cancleDelayJob($class);
            if (!$removeResult) {
                return;
            }
        }

        $result = Yii::$app->job->create($class, $args, strtotime($executeAt), $interval);
        $result = is_array($result) ? $result['token'] . ' executeAt ' . date('Y-m-d H:i:s', $result['at']) : $result;
        echo 'Created job ' . $result . PHP_EOL;
    }

    /**
     * Remove delay job by job class name
     * @param sting $class
     */
    public function actionRemove($class)
    {
        $this->cancleDelayJob($class);
    }

    /**
     * Get job perform annotation args
     * @param string $class
     */
    private function getJobAnnotationArgs($class)
    {
        //get method perform's doc comments
        $reflection = new ReflectionClass($class);
        $perform = $reflection->getMethod('perform');
        $performDoc = $perform->getDocComment();

        //get annotation @args
        $docArray = explode('@args', $performDoc);
        if (!empty($docArray[1])) {
            $argsArray = explode(PHP_EOL, trim($docArray[1]));
            try {
                $docArgs = Json::decode(trim($argsArray[0]));
                return $docArgs;
            } catch (Exception $e) {
                echo 'Cannot decode args' . PHP_EOL;
                return false;
            }
        }

        return [];
    }

    /**
     * Cancle delay job by class name
     * @param string $class
     * @return boolean
     */
    private function cancleDelayJob($class)
    {
        $delayJobs = Yii::$app->resque->getDelayedJobs();
        if (empty($delayJobs)) {
            echo 'Success: There is no delay job' . PHP_EOL;
            return true;
        }
        foreach ($delayJobs as $delayJob) {
            $delayJobArray = Json::decode($delayJob);
            //get delayjob args
            $delayJobArgs = $delayJobArray['args'][0];
            if ($delayJobArray['class'] == $class) {
                $tokenAndTimstamp = [
                    'token' => $delayJobArgs['djID'],
                    'at' => $delayJobArgs['cron_job_execute_time'],
                ];
                $removeResult = Yii::$app->resque->cancelDelayedJob($tokenAndTimstamp, $delayJobArray['queue'], $class, $delayJobArgs);
                echo $removeResult ? 'Success: removed ' : 'Failed: remove ';
                echo $class . ' executeAt ' . date('Y-m-d H:i:s', $tokenAndTimstamp['at']) . PHP_EOL;
                if (!$removeResult) {
                    return false;
                }
            }
        }

        return true;
    }
}
