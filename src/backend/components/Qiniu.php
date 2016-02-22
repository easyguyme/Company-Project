<?php
namespace backend\components;

use Yii;
use yii\base\Component;
use backend\utils\LogUtil;

/**
 * Qiniu class used to upload file.
 * Upload file to qiniu cloud.
 * @author Harry Sun
 */
class Qiniu extends Component
{
    const QINIU_UPLOAD_BLOCK_BITS = 22;//8m
    /**
     * Bucket on 7niu
     * @var string
     */
    public $bucket;

    /**
    * Access key
    * @var string
    */
    public $accessKey;

    /**
    * Secret key
    * @var string
    */
    public $secretKey;

    /**
     * Domian on 7niu
     * @var string
     */
    public $domain;

    /**
     * Upload domain on 7niu
     * @var string
     */
    public $uploadDomain;

    /**
     * change the bucket to a new bucket
     */
    public function change2Private()
    {
        $this->bucket = QINIU_BUCKET_PRIVATE;
    }

    /**
     * Upload file to qiniu cloud
     * @param  string $filePath file path
     * @param  string $key      file name in qiniu
     * @param  bool   $isAllowedOverwrite
     */
    public function upload($filePath, $key, $isAllowedOverwrite = false)
    {
        if ($isAllowedOverwrite) {
            $upToken = $this->getToken($key);
        } else {
            $upToken = $this->getToken();
        }

        if (filesize($filePath) > (1 << self::QINIU_UPLOAD_BLOCK_BITS)) {
            LogUtil::info(['message' => 'upload file use block', 'filePath' => $filePath], 'resque');
            $putExtra = new \Qiniu_Rio_PutExtra();
            list($ret, $err) = Qiniu_Rio_PutFile($upToken, $key, $filePath, $putExtra);
        } else {
            LogUtil::info(['message' => 'upload file directly', 'filePath' => $filePath], 'resque');
            $putExtra = new \Qiniu_PutExtra();
            $putExtra->Crc32 = 1;
            list($ret, $err) = Qiniu_PutFile($upToken, $key, $filePath, $putExtra);
        }
        if ($err !== null) {
            return $err;
        } else {
            return $ret;
        }
    }

    /**
     * Get qiniu token
     * @param null $key
     * @return string qiniu token
     */
    public function getToken($key = null)
    {
        Qiniu_SetKeys($this->accessKey, $this->secretKey);
        $scope = $this->bucket;

        if (!empty($key)) {
            // allowed to overwrite the file
            $scope .= ':' . $key;
        }

        $putPolicy = new \Qiniu_RS_PutPolicy($scope);
        $upToken = $putPolicy->Token(null);
        return $upToken;
    }

    /**
     * set private domain to download file
     */
    public function change2PrivateDomain()
    {
        $this->domain = QINIU_DOMAIN_PRIVATE;
    }

    public function getPrivateUrl($key)
    {
        $this->change2PrivateDomain();
        Qiniu_setKeys(QINIU_ACCESS_KEY, QINIU_SECRET_KEY);
        $baseUrl = Qiniu_RS_MakeBaseUrl($this->domain, $key);
        $getPolicy = new \Qiniu_RS_GetPolicy();
        return $getPolicy->MakeRequest($baseUrl, null);
    }

    /**
     * get file state in qiniu
     * @return [$ret, $err]
     */
    public function getFileInfo($fileName)
    {
        Qiniu_setKeys(QINIU_ACCESS_KEY, QINIU_SECRET_KEY);
        $client = new \Qiniu_MacHttpClient(null);
        return Qiniu_RS_Stat($client, $this->bucket, $fileName);
    }

    /**
     * Delete file from qiniu cloud
     * @param  string $fileName file name
     * @param  string $privateBucket  whether store file in private bucket
     */
    public function deleteFile($fileName, $privateBucket = false)
    {
        if ($privateBucket) {
            $this->change2Private();
        }
        Qiniu_setKeys(QINIU_ACCESS_KEY, QINIU_SECRET_KEY);
        $client = new \Qiniu_MacHttpClient(null);

        return Qiniu_RS_Delete($client, $this->bucket, $fileName);
    }
}
