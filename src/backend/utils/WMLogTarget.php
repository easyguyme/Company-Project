<?php
namespace backend\utils;

use Yii;
use yii\log\Target;
use yii\log\Logger;

class WMLogTarget extends Target
{
    const I18N_MESSAGE_KEY = 'PhpMessageSource';

    /**
     * Writes log messages to a file.
     */
    public function export()
    {
        $data = [];
        foreach ($this->messages as $message) {
            list($text, $level, $category, $timestamp) = $message;
            $level = Logger::getLevelName($level);
            //$messageString = date('Y-m-d H:i:s', $timestamp) . " [$level][$category] $text";
            //$messageArray = explode(PHP_EOL, $messageString);
            $needSkip = (strpos($category, self::I18N_MESSAGE_KEY) !== false);
            if (strpos(LOG_LEVEL, $level) !== false && !$needSkip) {
                //$data[$level][] = $messageArray;
                $data[$level] = $text;
            }
        }
        foreach ($data as $key => $value) {
            LogUtil::$key($value);
        }
    }
}
