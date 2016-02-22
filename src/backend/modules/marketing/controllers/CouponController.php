<?php
namespace backend\modules\marketing\controllers;

use Yii;
use MongoDate;
use MongoId;
use Exception;
use backend\modules\product\models\Coupon;
use yii\web\ServerErrorHttpException;
use yii\web\BadRequestHttpException;
use backend\utils\TimeUtil;
use backend\exceptions\InvalidParameterException;
use backend\utils\LogUtil;
use backend\models\Qrcode;
use backend\models\Store;
use backend\utils\UrlUtil;

class CouponController extends BaseController
{
    public $modelClass = 'backend\modules\product\models\Coupon';

    public function actions()
    {
        $actions = parent::actions();
        unset($actions['index'], $actions['create'], $actions['delete'], $actions['update']);
        return $actions;
    }

    public function actionIndex()
    {
        $params = $this->getQuery();
        $params['accountId'] = $this->getAccountId();
        return Coupon::search($params);
    }


    /**
     * create qrcode for receive coupon
     */
    public function actionQrcode()
    {
        $params = $this->getParams();

        if (empty($params['channels'])) {
            throw new InvalidParameterException(Yii::t('channel', 'channel_not_empty'));
        }

        if (empty($params['couponId'])) {
            throw new InvalidParameterException(Yii::t('product', 'invalide_params'));
        }

        $data = $result = [];
        $couponId = new MongoId($params['couponId']);
        $coupon = Coupon::findOne(['_id' => $couponId]);
        if (empty($coupon)) {
            throw new InvalidParameterException(Yii::t('product', 'invalid_couponId'));
        }

        //get the qrcode data in coupon
        $existsQrcode = [];
        if (!empty($coupon->qrcodes)) {
            foreach ($coupon->qrcodes as $couponQrcode) {
                $existsQrcode[$couponQrcode['channelId']] = $couponQrcode;
            }
        }

        list($existsQrcode, $data, $result) = Coupon::getCouponQrcode($params, $coupon, $existsQrcode);

        if (!empty($existsQrcode)) {
            //delete exists qrcode,bacause these qrcodes is deleted by user
            Coupon::deleteCouponQrcode($existsQrcode);
        }

        if (!empty($data) && $data !== $coupon->qrcodes) {
            //update qrcode info
            Coupon::updateAll(['qrcodes' => $data], ['_id' => $couponId]);
        }
        return $result;
    }

    public function actionCreate()
    {
        $params = $this->getParams();

        if (isset($params['total'])) {
            $params['total'] = intval($params['total']);
        }
        if (isset($params['limit'])) {
            $params['limit'] = intval($params['limit']);
        }

        $params['accountId'] = $this->getAccountId();
        //check the coupon time
        $params = Coupon::converCouponTime($params);
        //check the coupon storeType
        Coupon::checkCouponStore($params);
        //check coupon field
        Coupon::checkCouponField($params);

        $coupon = new Coupon();
        $coupon->load($params, '');
        if (false === $coupon->save()) {
            LogUtil::error(['message' => 'Faild to save coupon', 'params' => $params, 'error' => $coupon->getErrors()], 'product');
            throw new ServerErrorHttpException(Yii::t('common', 'save_fail'));
        } else {
            //if fail to create short url, the longUrl replace the short url
            $longUrl = UrlUtil::getDomain() . '/mobile/product/coupon?couponId=' . $coupon->_id;
            try {
                $shortObj = Yii::$app->urlService->shortenUrl($longUrl);
                $coupon->url = $shortObj['Short'];
            } catch (Exception $e) {
                $coupon->url = $longUrl;
            }
            $coupon->save();
            return $coupon;
        }
    }

    public function actionUpdate($id)
    {
        $params = $this->getParams();

        if (empty($params['url']) && empty($params['total']) && empty($params['time'])) {
            throw new InvalidParameterException(Yii::t('product', 'invalide_params'));
        }

        $where = ['_id' => new MongoId($id)];
        $coupon = Coupon::findOne($where);

        if (empty($coupon)) {
            throw new InvalidParameterException(Yii::t('product', 'invalide_params'));
        }

        if (!empty($params['time'])) {
            $params = Coupon::converCouponTime($params);
        }

        $data = [];

        if (!empty($params['total'])) {
            if ($coupon->total + $params['total'] < 0) {
                throw new InvalidParameterException(Yii::t('product', 'coupon_total_overflow'));
            } else {
                $data['$inc'] = ['total' => $params['total']];
                //add a condition to check the total
                if ($params['total'] < 0) {
                    $where['total'] = ['$gte' => intval($params['total'])];
                }
            }
        }

        $saveKeys = ['url', 'time'];
        foreach ($saveKeys as $saveKey) {
            if (isset($params[$saveKey])) {
                $data['$set'][$saveKey] = $params[$saveKey];
            }
        }

        Coupon::updateAll($data, $where);
        return ['result' => 'Ok', 'message' => 'update coupon successful'];
    }
}
