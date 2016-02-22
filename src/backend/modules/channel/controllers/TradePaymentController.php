<?php
namespace backend\modules\channel\controllers;

use Yii;
use yii\web\BadRequestHttpException;
use yii\web\ServerErrorHttpException;
use MongoId;
use MongoDate;
use backend\exceptions\InvalidParameterException;
use backend\utils\StringUtil;
use backend\utils\TimeUtil;
use backend\modules\channel\models\TradePayment;
use backend\exceptions\ApiDataException;
use backend\utils\LogUtil;

class TradePaymentController extends BaseController
{
    public $modelClass = 'backend\modules\channel\models\TradePayment';

    public function actions()
    {
        $actions = parent::actions();
        unset($actions['index'], $actions['create'], $actions['update'], $actions['delete']);
        return $actions;
    }

    public function actionIndex()
    {
        $params = $this->getQuery();
        if (!empty($params['channelId'])) {
            $params['metadata.channelId'] = $params['channelId'];
            unset($params['channelId']);
        } else {
            return ['status' => 'failed', 'data' => ''];
        }
        $params['tradeStatus'] = 'PAY_SUCCESS';
        $tradePaymentList = Yii::$app->tradeService->getOrders($params);
        return $tradePaymentList['data'];
    }

    public function actionExport()
    {
        $params = $this->getQuery();
        if (!empty($params['channelId'])) {
            $params['metadata.channelId'] = $params['channelId'];
            unset($params['channelId']);
        } else {
            return ['result' => 'error', 'message' => 'no datas', 'data' => []];
        }

        $params['tradeStatus'] = 'PAY_SUCCESS';

        $key = Yii::t('channel', 'payment_file_name') . '_' . date('Y-m-d H:i:s');
        $fileTitle = Yii::t('channel', 'export_payment_title');
        $header = [
            'tradeNo',
            'outTradeNo',
            'buyerNickname',
            'totalFee',
            'subject',
            'paymentTime'
        ];
        $header = array_combine($header, explode(',', $fileTitle));

        $args = [
            'accountId' => (string)$this->getAccountId(),
            'key' => $key,
            'header' => $header,
            'condition' => serialize($params),
            'classFunction' => '\backend\modules\channel\models\TradePayment::preProcessData',
            'description' => 'Direct: Export TradePayment data',
        ];
        $jobId = Yii::$app->job->create('backend\modules\common\job\ExportDataFromWeconnect', $args);
        unset($header, $args);

        return ['result' => 'success', 'message' => 'exporting file', 'data' => ['jobId' => $jobId, 'key' => $key]];
    }
}
