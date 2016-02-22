<?php
namespace backend\modules\mall\controllers;

use Yii;
use MongoId;
use yii\web\ServerErrorHttpException;
use backend\modules\product\models\GoodsExchangeLog;
use backend\modules\member\models\Member;
use backend\utils\ExcelUtil;

class GoodsExchangeLogController extends BaseController
{
    public $modelClass = 'backend\modules\product\models\GoodsExchangeLog';

    public function actions()
    {
        $actions = parent::actions();
        unset($actions['index'], $actions['create'], $actions['update'], $actions['delete']);
        return $actions;
    }

    public function actionIndex()
    {
        $params = $this->getQuery();
        $accountId = $this->getAccountId();
        return GoodsExchangeLog::search($params, $accountId);
    }

    public function actionAddress($id)
    {
        $member = Member::findByPk(new \MongoId($id));
        if (empty($member)) {
            throw new InvalidParameterException(Yii::t('member', 'no_member_find'));
        }

        $goodsExchangeLog = GoodsExchangeLog::getLastExpressByMember($member->_id);
        $address =  empty($goodsExchangeLog->address) ? '' : $goodsExchangeLog->address;
        $postcode = empty($goodsExchangeLog->postcode) ? '' : $goodsExchangeLog->postcode;

        return ['address' => $address, 'postcode' => $postcode];
    }

    public function actionMember($id)
    {
        $params = $this->getQuery();
        $params['isRemoved'] = false;
        $params['memberId'] = $id;
        $params['usedFrom'] = ['usedFrom.type' => ['$ne' => GoodsExchangeLog::USED_FROM_OFFLINE]];
        $accountId = $this->getAccountId();

        return GoodsExchangeLog::search($params, $accountId);
    }

    public function actionDelete($id)
    {
        $idList = explode(',', $id);
        foreach ($idList as &$perId) {
            $perId = new \MongoId($perId);
        }

        if (GoodsExchangeLog::updateAll(['$set' => ['isRemoved' => true]], ['in', '_id', $idList])) {
            Yii::$app->getResponse()->setStatusCode(204);
        } else {
            throw new ServerErrorHttpException('Failed to remove goods exchange log');
        }
    }

    /**
     * export the goods exchange log
     */
    public function actionExport()
    {
        $params = $this->getQuery();
        $accountId = $this->getAccountId();
        $where = GoodsExchangeLog::createCondition($params, $accountId);
        $query = GoodsExchangeLog::find();
        $data = $query->where($where)->one();

        if ($data) {
            //redis hash key and set a default value
            $key = Yii::t('product', 'goods_exchange_logs_file_name'). '_' . date('YmdHis');
            // get the header for the excel
            $headerValues = explode(",", Yii::t('product', 'exchange_log_export'));
            $headerKeys = ['id', 'memberName', 'telephone', 'address', 'postcode', 'expectedScore', 'usedScore', 'goods', 'createdAt', 'channel'];
            $header = [];
            foreach ($headerKeys as $index => $headerKey) {
                $header[$headerKey] = $headerValues[$index];
            }

            $exportArgs = [
                'language' => Yii::$app->language,
                'header' => $header,
                'key' => $key,
                'accountId' => (string) $accountId,
                'condition' => serialize($where),
                'description' => 'Direct: export goods exchange log'
            ];
            $jobId = Yii::$app->job->create('backend\modules\product\job\ExportGoodsExchangeLog', $exportArgs);
            $result = ['result' => 'success', 'message' => 'exporting goods exchange log', 'data' => ['jobId' => $jobId, 'key' => $key]];
        } else {
            $result = ['result' => 'error', 'message' => 'no datas', 'data' => []];
        }
        return $result;
    }

    public function actionShip()
    {
        $params = $this->getParams();
        if (empty($params['id'])) {
            throw new InvalidParameterException(Yii::t('common', 'parameters_missing'));
        }

        GoodsExchangeLog::updateAll(['isDelivered' => true], ['_id' => new MongoId($params['id'])]);
    }
}
