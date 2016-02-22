<?php
namespace backend\modules\uhkklp\models;

use backend\components\BaseModel;

class PushUser extends BaseModel
{
    const DEVICE_ANDROID = 'Android';
    const DEVICE_IOS = 'iOS';

    public static function collectionName()
    {
        return 'uhkklpPushUser';
    }

    public function attributes()
    {
        return array_merge(
            parent::attributes(),
            ['mobile', 'token', 'deviceType']
        );
    }

    public function safeAttributes()
    {
        return array_merge(
            parent::attributes(),
            ['mobile', 'token', 'deviceType']
        );
    }

    public function rules()
    {
        return array_merge(
            parent::rules(),
            [
                [['token'], 'required'],
            ]
        );
    }

    public function fields()
    {
        return array_merge(
            parent::fields(),
            ['mobile', 'token', 'deviceType']
        );
    }

    public static function getList($deviceType, $accountId)
    {
        return self::find()
            ->where(['deviceType' => $deviceType, 'accountId' => new \MongoId($accountId)])
            ->orderBy('createdAt')
            ->all();
    }

    public static function getListByPagination($limit, $offset, $deviceType, $accountId)
    {
        return self::find()
            ->where(['deviceType' => $deviceType, 'accountId' => new \MongoId($accountId)])
            ->orderBy('createdAt')
            ->limit($limit)
            ->offset($offset)
            ->all();
    }

    public static function getListByMobile($mobile, $deviceType, $accountId)
    {
        return self::findAll(['mobile' => $mobile, 'deviceType' => $deviceType, 'accountId' => new \MongoId($accountId)]);
    }

    public static function saveToken($query)
    {
        $token = $query->token;
        $mobile = null;
        if (isset($query->mobile) && !empty($query->mobile)) {
            $mobile = $query->mobile;
        }
        $deviceType = $query->deviceType;
        $accountId = $query->accountId;
        $item = self::findOne(['mobile' => $mobile, 'token' => $token, 'deviceType' => $deviceType, 'accountId' => new \MongoId($accountId)]);
        if ($item == null) {
            $item = new PushUser();
            $item->mobile = $mobile;
            $item->deviceType = $deviceType;
            $item->accountId = $accountId;
            $item->token = $token;
            $item->save();
        }
        return $item;
    }
}
