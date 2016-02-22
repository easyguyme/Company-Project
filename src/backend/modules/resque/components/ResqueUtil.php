<?php
namespace backend\modules\resque\components;

use yii\helpers\Json;
class ResqueUtil
{
    public static function log($message)
    {
        $logger = new FileLogger();
        $logger->setLogPath(\Yii::$app->runtimePath . '/logs');
        $logFile = \Yii::$app->resque->logFile;
        if(!empty($logFile))
        {
            $logger->setLogFile($logFile);
        }
        if (is_array($message)) {
            $message = Json::encode($message);
        }

        $logger->processLog(array($message, time()));
    }
}