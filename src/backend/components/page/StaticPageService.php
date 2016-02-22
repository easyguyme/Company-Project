<?php
namespace backend\components\page;

use Yii;
use yii\base\Component;
use yii\web\ServerErrorHttpException;
use backend\utils\StringUtil;

/**
 * static page service used to generate a static page.
 * @author Harry Sun
 */
class StaticPageService extends Component
{
    /**
     * The temp file path
     * @var string
     */
    public $tempFilePath = '/html/';

    private $qiniuUrl;

    /**
     * Initializes the static page service.
     */
    public function init()
    {
        $this->qiniuUrl = Yii::$app->qiniu->domain . '/';
        parent::init();
    }

    /**
     * Generate a html page with content
     * @param  string $fileContent The file content
     * @param  string $fileName The html page name
     * @return string
     */
    public function generateHtml($fileContent, $fileName)
    {
        $file = __DIR__ . $this->tempFilePath . $fileName;
        $handle = fopen($file, "w");
        fwrite($handle, $fileContent);
        fclose($handle);
        return $file;
    }

    /**
     * Generate a html page with content and upload to qiniu
     * @param  string $fileContent The file content
     * @param  string $fileName The html page name
     * @param  bool   $isAllowedOverwrite
     * @return string Html file url
     * @throws ServerErrorHttpException
     */
    public function generateQiniuFile($fileContent, $isAllowedOverwrite = false, $fileName = null)
    {
        if (empty($fileName)) {
            $fileName = StringUtil::uuid() . '.html';
        }

        $file = $this->generateHtml($fileContent, $fileName);
        $result = Yii::$app->qiniu->upload($file, $fileName, $isAllowedOverwrite);
        unlink($file);

        if (!empty($result['key'])) {
            return $this->qiniuUrl . $result['key'];
        } else {
            throw new ServerErrorHttpException(\Yii::t('common', 'upload_fail'));
        }
    }

    /**
     * Get a qiniu file name
     * @param  string $fileUrl the file url
     * @return string the file name
     */
    public function getQiniuFileName($fileUrl)
    {
        return str_replace($this->qiniuUrl, '', $fileUrl);
    }

    /**
     * Convert content to html code
     * @param  string $fileContent
     * @return string
     */
    private function convertContent2Html($fileContent)
    {
        return '<html>
                    <head>
                    <meta charset="UTF-8">
                    </head>
                    <body>' .
                    $fileContent .
                    '</body>
                </html>';
    }

    /**
     * Whether file content and file is the same
     * @param $fileContent  the file content
     * @param $fileUrl      the html file url
     * @return bool
     */
    public function isSame($fileContent, $fileUrl)
    {
        return strcmp($this->convertContent2Html($fileContent), file_get_contents($fileUrl)) === 0;
    }
}
