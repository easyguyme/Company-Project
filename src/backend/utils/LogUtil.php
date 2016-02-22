<?php
namespace backend\utils;

use backend\exceptions\DataException;
use yii\helpers\FileHelper;
use backend\utils\StringUtil;
use yii\base\Exception;
use yii\helpers\Json;

/**
 * This is util for log pure data.
 * The original log for yii contains too much information.
 * @author Devin Jin
 **/

class LogUtil
{
    //integer the permission to be set for newly created directories.
    const DIR_MODE = 0775;
    const WECONNECT_SERVER_ERROR = 10000;
    const API_SERVER_ERROR = 10001;

    public static $filerExceptions = [
                    'ForbiddenHttpException',
                    'InvalidRouteException',
                    'UnauthorizedHttpException',
                    'ApiDataException',
                    'InvalidParameterException'
                ];
    public static $filterStrings = [
              '/You[\S\s]+have[\S\s]+not[\S\s]+logined/i',
            ];
    public static $yiiExceptionPattern = "/(.*)with message '(.*)'(.*)/i";

    public static $openLog = true;//whether open the log

    /**
     * log info message
     * @param $data, array, the data to be logged.
     * @param $target, the target file, "data" as default, mapping to /src/backend/runtime/data.log
     * @author Devin Jin
     **/
    public static function info($data, $target = "data")
    {
        self::_log($data, $target, "info");
    }

    /**
     * log warnin message
     * @param $data, array, the data to be logged.
     * @param $target, the target file, "data" as default, mapping to /src/backend/runtime/data.log
     * @author Devin Jin
     **/
    public static function warn($data, $target = "data")
    {
        self::_log($data, $target, "warn");
    }

    /**
     * log error message
     * @param $data, array, the data to be logged.
     * @param $target, the target file, "data" as default, mapping to /src/backend/runtime/data.log
     * @author Devin Jin
     **/
    public static function error($data, $target = "data")
    {
        self::_log($data, $target . '-error', "error");
    }

    /**
     * Log facade
     * @param $data, array, the data to be logged.
     * @param $target, the target module name, "data" as default
     * @param $type, string log level.
     * @author Vincent Hou
     **/
    private static function _log($data, $target = "data", $type = "error")
    {
        if (1 == FILE_LOG) {
            if (strpos(LOG_LEVEL, $type) !== false) {
                self::_logFile($data, $target, $type);
            }
        } else if (2 == FILE_LOG) {
            if (strpos(LOG_LEVEL, $type) !== false) {
                self::_logSLS($data, $target, $type);
            }
        } else {
            //Only print error log in browser console, info data may be too large for header max size limitation
            if ('error' === $type) {
                self::_logChrome($data, $target, $type);
            }
        }
    }

    /**
     * Log to chrome console
     * @param $data, array, the data to be logged.
     * @param $target, the target module name, "data" as default
     * @param $type, string log level.
     * @author Vincent Hou
     **/
    private static function _logChrome($data, $target = "data", $type = "error")
    {
        $logContent = ['level' => $type, 'module' => $target, 'data' => $data];
        switch ($type) {
            case 'info':
                \ChromePhp::info(json_encode($logContent));
                break;
            case 'warn':
                \ChromePhp::warn($logContent);
                break;
            default:
                \ChromePhp::error($logContent);
                break;
        }
    }

    /**
     * Log to aliyun SLS
     * @param $data, array, the data to be logged.
     * @param $target, the target module name, "data" as default
     * @param $type, string log level.
     * @author Vincent Hou
     **/
    private static function _logSLS($data, $target = "data", $type = "error")
    {
        //change struct for the response and conver to be array,beacuse the duoble conver to json in the next
        if (isset($data['response']) && StringUtil::isJson($data['response'])) {
            $data['response'] = Json::decode($data['response'], true);
        }
        $dataStr = str_replace(["\/","\\\\"], ["/", "\\"], Json::encode($data, JSON_PRETTY_PRINT));

        $parts = explode('Stack trace:', $dataStr);
        $msg  = '';
        $trace = $dataStr;

        if (StringUtil::isJson($parts[0])) {//api error
            $msg = self::_filterJsonMsg($parts[0]);
        } else {//this exception from yii
            $msg = self::_filterLog($parts[0]);
        }

        $logContent = ['_level' => $type, '_category' => $target, '_message' => $msg, 'env' => CURRENT_ENV];
        if (!empty($trace) && 'error' === $type) {
            $logContent['_error'] = $trace;
        } else if (!empty($trace) && 'info' === $type) {
            $logContent['_error'] = print_r($data, true);
        }
        if (self::$openLog) {
            AliyunSls::log($logContent);
        }
    }

    /**
     * filter json msg
     * @param $data,array
     */
    private static function _filterJsonMsg($data)
    {
        $msg = '';
        foreach (self::$filterStrings as $filterString) {
            if (preg_match($filterString, $data)) {
                self::$openLog = false;
                return '';
            }
        }

        $data = Json::decode($data, true);
        if (isset($data['code'])) {
            //api json log
            if (empty($data['response'])) {
                //response is not json type
                $msg = self::_filterHtml($data['response']);
            } else {
                //operate by based on the code of response
                //check the response whether is json or array
                if (StringUtil::isJson($data['response'])) {
                    $data['response'] = Json::decode($data['response'], true);
                }
                $msg = self::_getApiMsg($data['response']);
            }
        } else {
            //local json log or fronted
            $msg = 'info level';

            if (!empty($data['msg'])) {
                $msg = $data['msg'];
            } else if (!empty($data['message'])) {
                $msg = $data['message'];
            } else if (!empty($data['response'])) {
                $response = $data['response'];
                $msg = empty($response['message']) ? $data['response'] : $response['message'];
            }
        }
        return $msg;
    }

    /**
     * get the message base on the code from api
     *  @param $response,array
     * */
    private static function _getApiMsg($response)
    {
        if (isset($response['code'])) {
            $msg = "code:" . $response['code'];
            if ($response['code'] == '500' && isset($response['message'])) {
                $msg = $response['message'];
            } else if (isset($response['data']['errorMessage'])) {//412
                //check the message whether is NullPointerException or other exception
                $msg = self::_filterApiException($response['data']['errorMessage']);
            } else if (isset($response['msg'])) {
                //suport yunpian
                $msg = $response['msg'];
            }
        } else {
            //code is not exists,but it exists errcode and errmsg
            $msg = isset($response['errmsg']) ? $response['errmsg'] : Json::encode($response);
        }
        return $msg;
    }

    /**
     * filter the api exception for title,because the msg is too long
     */
    private static function _filterApiException($data)
    {
        if (preg_match('/(\w+)Exception/i', $data, $match)) {
            $data = $match[0] . 'Exception';
        }
        return $data;
    }

    /**
     * filter log with html
     */
    private static function _filterHtml($data)
    {
        //catch the error log with html
        if (preg_match('/<body[\s\S]*?>([\s\S]*?)<\/body>/i', $data, $match)) {
            self::$openLog = true;
            return strip_tags($match[0]);
        }
        return strip_tags($data);
    }

    /**
     *filter some exceptions from yii
     *@param $data string
     */
    private static function _filterLog($data)
    {
        if (!is_string($data)) {
            throw new Exception("Invalid data for filtering");
        }

        //catch the error log with html
        $msg =  self::_filterHtml($data);
        if ($msg != $data) {
            return $msg;
        }
        //filter exception
        foreach (self::$filerExceptions as $filerException) {
            if (false !== stristr($data, $filerException)) {
                self::$openLog = false;
                return '';
            }
        }

        //filter string
        foreach (self::$filterStrings as $filterString) {
            if (preg_match($filterString, $data, $matches)) {
                self::$openLog = false;
                return '';
            }
        }

        preg_match(self::$yiiExceptionPattern, $data, $matches);

        if (!isset($matches[2])) {
            self::$openLog = false;
            return '';
        }

        $msg = $matches[2];
        //convert unicode
        if (preg_match('/\\\\u([0-9a-f]{4})/i', $matches[2])) {
            $msg = preg_replace_callback('/\\\\u([0-9a-f]{4})/i', function ($match) {
                return  mb_convert_encoding(pack('H*', $match[1]), 'UTF-8', 'UCS-2BE');
            }, $matches[2]);
        }
        return str_replace(['\\'], [''], $msg);
    }

    /**
     * get the message from  frontend log
     *@param $data array
     */
    private static function _getFrontendLogMsg($data)
    {
        if (is_array($data)) {
            if (isset($data['msg'])) {
                return $data['msg'];
            } else {
                return print_r($data, true);
            }
        }
        throw new Exception("Invalid data for frontend log");
    }


    /**
     * Log to file
     * @param $data, array, the data to be logged.
     * @param $target, the target file, "data" as default, mapping to /src/backend/runtime/data.log
     * @param $type, string.
     * @author Devin Jin
     **/
    private static function _logFile($data, $target = "data", $type = "error")
    {
        $target = strtolower($target);
        $logFile = \Yii::$app->getRuntimePath() . '/logs/' . $target . '.log';
        $logPath = dirname($logFile);
        if (!is_dir($logPath)) {
            FileHelper::createDirectory($logPath, self::DIR_MODE, true);
        }

        $file = fopen($logFile, 'a');
        $message = "[" . strtoupper($type) . "][TIME]" . date('Y-m-d H:i:s') . PHP_EOL;

        if (is_string($data)) {
            $message = $data;
        } else if (is_array($data)) {
            foreach ($data as $key => $value) {
                $message .= '[' . strtoupper($key) . ']' . (is_array($value) ? json_encode($value) : $value) . PHP_EOL;
            }
        } else {
            throw new Exception("Invalid data for log");
        }

        //$message .= self::_logTrace();
        $message .= PHP_EOL . '---------------------------------------------------------' . PHP_EOL . PHP_EOL;
        fwrite($file, $message);
        fclose($file);
    }

    /**
     * Log error trace for file
     * @return string the trace message
     * @author Devin Jin
     **/
    private static function _logTrace()
    {
        $msg = '';
        $count = 0;
        $traces = debug_backtrace();

        foreach ($traces as $trace) {
            if (isset($trace['file'],$trace['line'])) {
                $count ++;
                //The first line of the trace
                if ($count === 1) {
                    $msg .= "[STACK TRACE]";
                }

                $msg.="\n    ".$trace['file'].' ('.$trace['line'].')';
            }
        }

        return $msg;
    }

    private static function _logLiteTrace()
    {
        $traceMap = [];
        $traces = debug_backtrace();

        foreach ($traces as $trace) {
            if (isset($trace['file'],$trace['line'])) {
                $traceMap[$trace['file']] = $trace['line'];
            }
        }

        return $traceMap;
    }
}
