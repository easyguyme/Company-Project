<?php
namespace console\utils;

use yii\helpers\Console;

class LogUtil
{
    public static function success($msg)
    {
        $msg = Console::ansiFormat($msg, [Console::FG_GREEN, Console::ITALIC]);
        Console::output($msg);
    }

    public static function error($msg)
    {
        $msg = Console::ansiFormat('Error: ' . $msg, [Console::FG_RED, Console::BOLD]);
        Console::output($msg);
    }

    public static function warn($msg)
    {
        $msg = Console::ansiFormat('Warning: ' . $msg, [Console::FG_YELLOW, Console::BOLD]);
        Console::output($msg);
    }

    public static function info($msg)
    {
        $msg = Console::ansiFormat($msg, [Console::FG_BLUE, Console::BOLD]);
        Console::output($msg);
    }
}
