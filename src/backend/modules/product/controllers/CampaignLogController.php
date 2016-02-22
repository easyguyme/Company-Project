<?php
namespace backend\modules\product\controllers;

use Yii;
use backend\modules\product\models\CampaignLog;
use backend\modules\product\models\PromotionCode;
use yii\web\BadRequestHttpException;

class CampaignLogController extends BaseController
{
    public $modelClass = 'backend\modules\product\models\campaignLog';

    public function actions()
    {
        $actions = parent::actions();
        unset($actions['index'], $actions['delete']);
        return $actions;
    }

    public function actionIndex()
    {
        $params = $this->getQuery();
        return CampaignLog::search($params, $this->getAccountId());
    }

    /**
     * get the summary for the different types of promotioncode has been exchanged by member
     */
    public function actionTotal()
    {
        $query = $this->getQuery();

        if (empty($query['memberId'])) {
            throw new BadRequestHttpException('param misssing');
        }
        return CampaignLog::getTypeTotal(new \MongoId($query['memberId']));
    }

    /**
     * export the promotioncode has been redeemed
     */
    public function actionExport()
    {
        $accountId = $this->getAccountId();
        $params = $this->getQuery();
        $where = CampaignLog::createCondition($params, $accountId);
        $data = CampaignLog::find()->where($where)->one();
        if ($data) {
            //redis hash key and set a default value
            $key = Yii::t('product', 'file_name'). '_' . date('YmdHis');
            // get the header for the excel
            $headerValues = explode(",", Yii::t('product', 'promotion_redeemed_export'));
            $headerKeys = ['id', 'cardNumber', 'memberName', 'tel', 'sku', 'productName', 'code', 'prize', 'redeemTime', 'createdAt', 'redeemptionChannelName', 'campaignName', 'backendUser'];
            $header = array_combine($headerKeys, $headerValues);

            $exportArgs = [
                'collection' => 'campaignLog',
                'classFunction' => '\backend\modules\product\models\CampaignLog::preProcessRedeemedCodeData',
                'sort' => [],
                'language' => Yii::$app->language,
                'header' => $header,
                'key' => $key,
                'params' => [],
                'fields' => '_id,code,productId,productName,campaignName,sku,operaterEmail,member,redeemTime,usedFrom,createdAt',
                'accountId' => (string)$accountId,
                'condition' => serialize(CampaignLog::getCollection()->buildCondition($where)),
                'description' => 'Direct: export promotionCodes that is been redeemed'
            ];

            $jobId = Yii::$app->job->create('backend\modules\common\job\MongoExportFile', $exportArgs);
            $result = ['result' => 'success', 'message' => 'exporting redeemed code', 'data' => ['jobId' => $jobId, 'key' => $key]];
        } else {
            $result = ['result' => 'error', 'message' => 'no datas', 'data' => []];
        }
        return $result;
    }
}
