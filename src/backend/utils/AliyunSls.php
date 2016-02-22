<?php
namespace backend\utils;

class AliyunSls
{
    /**
     * @var AliyunSls
     */
    protected static $_instance;

    /**
     * @var Aliyun_Sls_Client
     */
    private $client;


    /**
     * constructor
     */
    private function __construct()
    {
        $this->client = new \Aliyun_Sls_Client(SLS_ENDPOINT, SLS_ACCESS_KEY_ID, SLS_ACCESS_KEY_SECRET);
    }

    /**
     * gets instance of this class
     *
     * @return AliyunSls
     */
    public static function getInstance()
    {
        if (self::$_instance === null) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    /**
     * Writes log messages to a SLS.
     */
    private function _log($contents = '')
    {
        $logItem = new \Aliyun_Sls_Models_LogItem();
        $logItem->setTime(time());
        $logItem->setContents($contents);
        $logitems = [$logItem];
        $request = new \Aliyun_Sls_Models_PutLogsRequest(SLS_PROJECT_ID, SLS_LOG_STORE, SLS_LOG_TOPIC, null, $logitems);

        try {
            $res = $this->client->putLogs($request);
        } catch (\Aliyun_Sls_Exception $ex) {
            // Throw away exception
            //var_dump($ex);
        } catch (\Exception $ex) {
            //var_dump($ex);
        }
    }

    /**
     * Writes log messages to a SLS.
     */
    public static function log($contents)
    {
        $sls = self::getInstance();
        $sls->_log($contents);
    }
}
