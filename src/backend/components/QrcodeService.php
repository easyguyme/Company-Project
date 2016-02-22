<?php
namespace backend\components;

use Yii;
use yii\base\Component;
use backend\models\Qrcode;
use yii\helpers\FileHelper;
use backend\utils\LogUtil;
use PHPQRCode\Constants;

class QrcodeService extends Component
{
    public function create($domain, $type, $associatedId, $accountId, $useDefaultDomain = true, $ecLevel = Constants::QR_ECLEVEL_H)
    {
        if ($useDefaultDomain) {
            $content = $domain . "/webapp/$type/$associatedId";
        } else {
            $content = $domain;
        }
        $qrcodeId = new \MongoId();
        $fileName = $qrcodeId . '.png';
        $filePath = $this->_getFilePath($fileName);
        \PHPQRCode\QRcode::png($content, $filePath, $ecLevel, 4, 2);

        $uploadResult = $this->_upload($filePath, $fileName);
        if ($uploadResult) {
            $recordResult = $this->_record($qrcodeId, $type, $associatedId, $content, $fileName, $accountId);
            if ($recordResult) {
                return $recordResult;
            }
        }

        return false;
    }

    public function getUrl($fileName)
    {
        return QINIU_DOMAIN . '/' . $fileName;
    }

    private function _record($qrcodeId, $type, $associatedId, $content, $qiniuKey, $accountId)
    {
        $qrcode = new Qrcode;
        $qrcode->_id = $qrcodeId;
        $qrcode->type = $type;
        $qrcode->associatedId = $associatedId;
        $qrcode->content = $content;
        $qrcode->qiniuKey = $qiniuKey;
        $qrcode->accountId = $accountId;
        if (!$qrcode->save()) {
            LogUtil::error(['message' => 'Record qrcode fail', 'qrcode' => json_encode($qrcode), 'error' => $qrcode->getErrors()]);
            return false;
        }
        return $qrcode;
    }

    private function _getFilePath($fileName)
    {
        $filePath = Yii::$app->getRuntimePath() . '/temp/' . $fileName;
        $path = dirname($filePath);
        if (!is_dir($path)) {
            FileHelper::createDirectory($path, 0777, true);
        }
        return $filePath;
    }

    private function _upload($filePath, $fileName)
    {
        $result = Yii::$app->qiniu->upload($filePath, $fileName);
        @unlink($filePath);
        if (empty($result->Err)) {
            return true;
        } else {
            LogUtil::error(['message' => 'fail to upload file to qiniu','result' => json_encode($result)], 'qiniu');
            return false;
        }
    }
}
