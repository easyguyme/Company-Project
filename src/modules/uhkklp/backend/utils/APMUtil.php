<?php
namespace backend\modules\uhkklp\utils;

use Yii;
use yii\base\ErrorException;
use backend\modules\uhkklp\models\PushUser;
use backend\modules\uhkklp\models\PushLog;
use backend\modules\uhkklp\models\KlpAccountSetting;
use backend\utils\LogUtil;

class APMUtil
{
    private $fp;
    private $initRetryTimes = 0;

    private function getFp($accountId) {
        $site = KlpAccountSetting::getAccountSite($accountId);
        //$site = 'TW';

        if (!$this->fp) {
            //LogUtil::error('iOS push init fp retry:  ' . $this->initRetryTimes);
            $ctx = stream_context_create();
            if ($site == 'HK') {
                stream_context_set_option($ctx, 'ssl', 'local_cert', Yii::getAlias('@app') . '/modules/uhkklp/hk_pro_klp.pem');
            } else {
                stream_context_set_option($ctx, 'ssl', 'local_cert', Yii::getAlias('@app') . '/modules/uhkklp/klpapns.pem');
            }
            stream_context_set_option($ctx, 'ssl', 'passphrase', 'abc123_');
            $this->fp = stream_socket_client('ssl://gateway.push.apple.com:2195', $err, $errstr, 30, STREAM_CLIENT_CONNECT|STREAM_CLIENT_PERSISTENT, $ctx);

            if (!$this->fp) {
                LogUtil::error('iOS push error:  ' . $err . '. Info: ' . $errstr);
                fclose($this->fp);
                if ($this->initRetryTimes < 5) {
                    $this->initRetryTimes = $this->initRetryTimes + 1;
                    $this->getFp($accountId);
                } else {
                    $this->initRetryTimes = 0;
                    return $err;
                }
            } else {
                $this->initRetryTimes = 0;
                stream_set_blocking($this->fp, 0);
                //stream_set_timeout($this->fp, 1);
                //$info = stream_get_meta_data($this->fp);
                LogUtil::error('iOS push init fp success. ');
                return 2000;
            }
        } else {
            LogUtil::error('iOS push init HK get old fp');
            return 2000;
        }
    }

    public function pushMsg($deviceToken, $msg, $accountId, $rowId) {
        $content = $msg["content"];
        unset($msg["content"]);
        $body = array("aps" => array('alert' => $content, 'badge' => 1, 'sound' => 'default', 'extra' => $msg));
        $payload = json_encode($body);
        $apple_expiry = time() + (90 * 24 * 60 * 60);
        $identify = $rowId;
        $msg = pack("C", 1) . pack("N", $identify) . pack("N", $apple_expiry) . pack("n", 32) . pack('H*', str_replace(' ', '', $deviceToken)) . pack("n", strlen($payload)) . $payload;
        $response = 0;

        $response = $this->getFp($accountId);

        if ($response == 2000) {
            try {
                fwrite($this->fp, $msg, strlen($msg));
                $apple_response = fread($this->fp, 6);
                if (strlen($apple_response) == 0) {
                    $response = 200;
                } else {
                    $error_response = unpack('Ccommand/Cstatus_code/Nidentifier', $apple_response);
                    $status_code = $error_response['status_code'];
                    $identifier = $error_response['identifier'];
                    $response = 'RowId: ' . $identifier . '. Error: ' . $this->getInfoByCode($status_code);
                    $this->closeFp();
                }
            } catch(ErrorException $e) {
                LogUtil::error(' ============================= iOS push apple exception:  ' . $e->getMessage());
                $response = $e->getMessage();
                $this->closeFp();

                $this->saveLog($body, $response, $accountId);
                return $response;
            }
        }

        $this->saveLog($body, $response, $accountId);
        return $response;
    }

    public function closeFp() {
        if ($this->fp) {
            LogUtil::error('iOS push fp close');
            fclose($this->fp);
        }
    }

    private function saveLog($request, $response, $accountId)
    {
        $log = new PushLog();
        $log->request = $request;
        $log->response = $response;
        $log->deviceType = PushUser::DEVICE_IOS;
        $log->accountId = $accountId;
        $log->save();
    }

    private function getInfoByCode($status_code) {
        if ($status_code == '0') {
            return '0-No errors encountered';
        } else if ($status_code == '1') {
            return '1-Processing error';
        } else if ($status_code == '2') {
            return  '2-Missing device token';
        } else if ($status_code == '3') {
            return '3-Missing topic';
        } else if ($status_code == '4') {
            return '4-Missing payload';
        } else if ($status_code == '5') {
            return '5-Invalid token size';
        } else if ($status_code == '6') {
            return '6-Invalid topic size';
        } else if ($status_code == '7') {
            return '7-Invalid payload size';
        } else if ($status_code == '8') {
            return '8-Invalid token';
        } else if ($status_code == '255') {
            return '255-None (unknown)';
        } else {
            return '-Not listed';
        }
    }
}
