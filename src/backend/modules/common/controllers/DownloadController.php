<?php
namespace backend\modules\common\controllers;

use Yii;
use yii\helpers\FileHelper;

class DownloadController extends BaseController
{
    /**
     * Download the image from remote service , then transfer image into base64 and return it
     **/
    public function actionDownloadCorsImage()
    {
        $params = $this->getQuery();
        if (empty($params['url'])) {
            throw new BadRequestHttpException(Yii::t('common', 'invalid_params'));
        }
        if (empty($params['type'])) {
            throw new BadRequestHttpException(Yii::t('common', 'invalid_params'));
        }
        $url = urldecode($params['url']);
        $filetype = $params['type'];
        if (!is_dir(Yii::$app->getRuntimePath(). "//temp/")) {
            FileHelper::createDirectory(Yii::$app->getRuntimePath(). "//temp/", 0777, true);
        }
        $filename = Yii::$app->getRuntimePath(). "//temp/" . date("Ymdhis").".$filetype";

        $curl = curl_init($url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        $imageData = curl_exec($curl);
        $info = array();
        if ($imageData == "") {//need to redirect
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_VERBOSE, true);
            curl_setopt($ch, CURLOPT_HEADER, true);
            curl_setopt($ch, CURLOPT_NOBODY, true);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 20);
            curl_setopt($ch, CURLOPT_AUTOREFERER, true);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            $ret = curl_exec($ch);
            $info = curl_getinfo($ch);
            curl_close($ch);
            $url = $info['url'];
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($curl, CURLOPT_URL, $url);
            $imageData = curl_exec($curl);
        }
        curl_close($curl);

        $tp = fopen($filename, 'a');
        fwrite($tp, $imageData);
        fclose($tp);
        if ($fp = fopen($filename, "rb", 0)) {
            $gambar = fread($fp, filesize($filename));
            fclose($fp);
            $base64 = chunk_split(base64_encode($gambar));
            $base64 = str_replace(array("\r\n", "\r", "\n"), "", $base64);
            unlink($filename);// delete file
            return urlencode("data:image/$filetype;base64,".$base64);
        }
    }
}
