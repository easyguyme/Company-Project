<?php
namespace backend\modules\channel\controllers;

use Yii;
use yii\web\BadRequestHttpException;
use yii\web\ServerErrorHttpException;
use MongoId;
use MongoDate;
use backend\exceptions\InvalidParameterException;
use backend\utils\LogUtil;
use backend\utils\StringUtil;
use backend\utils\TimeUtil;
use backend\modules\channel\models\TradeRefund;

class TradeRefundController extends BaseController
{

    public $modelClass = 'backend\modules\channel\models\TradeRefund';

    public function actions()
    {
        $actions = parent::actions();
        unset($actions['index'], $actions['create'], $actions['delete'], $actions['update']);
        return $actions;
    }

    public function actionIndex()
    {
        $params= $this->getQuery();
        if (!empty($params['channelId'])) {
            $params['metadata.channelId'] = $params['channelId'];
            unset($params['channelId']);
        } else {
            return ['status' => 'failed', 'data' => ''];
        }
        $params['refundStatus'] = 'SUCCESS';
        $refunds = Yii::$app->tradeService->getRefunds($params);
        return $refunds['data'];
    }

    public function actionExport()
    {
        $params= $this->getQuery();
        if (!empty($params['channelId'])) {
            $params['metadata.channelId'] = $params['channelId'];
            unset($params['channelId']);
        } else {
            return ['result' => 'error', 'message' => 'no datas', 'data' => []];
        }
        $params['refundStatus'] = 'SUCCESS';

        $key = Yii::t('channel', 'refund_file_name') . '_' . date('Y-m-d H:i:s');
        $fileTitle = Yii::t('channel', 'export_refund_title');
        $header = [
            'refundNo',
            'buyerNickname',
            'refundFee',
            'subject',
            'createTime'
        ];
        $header = array_combine($header, explode(',', $fileTitle));

        $args = [
            'accountId' => (string)$this->getAccountId(),
            'key' => $key,
            'header' => $header,
            'condition' => serialize($params),
            'classFunction' => '\backend\modules\channel\models\TradeRefund::preProcessData',
            'description' => 'Direct: Export TradeRefund data',
        ];
        $jobId = Yii::$app->job->create('backend\modules\common\job\ExportDataFromWeconnect', $args);
        unset($header, $args);

        return ['result' => 'success', 'message' => 'exporting file', 'data' => ['jobId' => $jobId, 'key' => $key]];
    }
}
