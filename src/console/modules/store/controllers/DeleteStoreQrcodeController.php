<?php
namespace console\modules\store\controllers;

use Yii;
use MongoId;
use backend\models\Store;
use yii\console\Controller;
use backend\models\Account;
use backend\exceptions\ApiDataException;

/**
 * delete store qrcode info when qrcode info has been deleted before
 */
class DeleteStoreQrcodeController extends Controller
{
    public function actionIndex($accountId = '')
    {
        if (empty($accountId)) {
            $accounts = Account::findAll(['isDeleted' => false]);
        } else {
            $accounts = Account::findAll(['_id' => new MongoId($accountId)]);
        }

        $channels = ['wechat', 'weibo', 'alipay'];

        if (!empty($accounts)) {
            foreach ($accounts as $account) {
                $stores = Store::findAll(['accountId' => $account->_id]);
                foreach ($stores as $store) {
                    $flag = false;
                    foreach ($channels as $channel) {
                        if (isset($store->$channel) && isset($store->{$channel}['channelId']) && isset($store->{$channel}['qrcodeId'])) {
                            $channelId = $store->{$channel}['channelId'];
                            $qrcodeId = $store->{$channel}['qrcodeId'];

                            $result = Yii::$app->weConnect->getQrcode($channelId, $qrcodeId);

                            if (!empty($result['deleteTime'])) {
                                $store->$channel = null;
                                $flag = true;
                            }
                        }
                    }

                    if ($flag == true && $store->save()) {
                        echo 'update storeId:' . $store->_id . PHP_EOL;
                    }
                }
            }
        }
        echo 'Update data successful'. PHP_EOL;
    }
}
