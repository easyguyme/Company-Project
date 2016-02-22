<?php
namespace backend\controllers;

use Yii;
use yii\helpers\FileHelper;
use PHPQRCode\Constants;
use backend\components\Controller;

/**
 * Image controller
 */
class ImageController extends Controller
{
    /**
     * Covent a image url to image data
     * @return string       image data
     */
    public function actionDownload()
    {
        $url = $this->getQuery('url');
        $name = $this->getQuery('name');
        header('Content-Type:image/png');
        header('Content-Disposition:attachment;filename='.$name);
        $image = file_get_contents($url);
        echo $image;
    }

    /**
     * get image from base64 string
     * @return string       image data
     */
    public function actionCanvasDownload()
    {
        $url = $this->getQuery('url');
        $name = $this->getQuery('name');

        header('Content-Type:image/png');
        header('Content-Disposition:attachment;filename='.$name);

        $filePath = $this->_getFilePath($name);
        \PHPQRCode\QRcode::png($url, $filePath, Constants::QR_ECLEVEL_H, 8, 2);

        $image = file_get_contents($filePath);

        @unlink($filePath);

        // Output the actual image data
        echo $image;

    }

    private function _getFilePath($fileName)
    {
        $filePath = Yii::$app->getRuntimePath() . '/temp/qrcode/' . $fileName;
        $path = dirname($filePath);
        if (!is_dir($path)) {
            FileHelper::createDirectory($path, 0777, true);
        }
        return $filePath;
    }
}
