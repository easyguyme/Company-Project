<?php

namespace backend\modules\uhkklp\controllers;

use yii\web\BadRequestHttpException;
use backend\modules\member\models\Member;
use backend\utils\LogUtil;
use backend\utils\MessageUtil;
use backend\modules\uhkklp\models\EarlyBirdSmsRecord;
use backend\modules\uhkklp\utils\EarlybirdSmsUtil;
use Yii;


class EarlyBirdSmsController extends BaseController
{
    public function actionSendSmsByTemplate()
    {
        $smsTag = $this->getQuery('smsTag');
        $operator = $this->getUser()->email;
        $condition = ['isDeleted'=>false];

        if (empty($smsTag)) {
            throw new BadRequestHttpException('param is missing.(smsTag)');
        }
        if ($smsTag == 'sms_three') {
            $condition = array_merge($condition, ['score'=>['$gt'=>0]]);
        }

        $result = EarlybirdSmsUtil::createSmsJob($condition, $operator, $smsTag);
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        return $result;
    }

    public function actionSendTestSms(){
        $params = $this->getQuery();
        if (empty($params) || empty($params['testSms'])) {
            throw new BadRequestHttpException('Failed! Mobile number or content is null.');
        }
        $response = MessageUtil::sendMobileMessage($params['testMobile'], $params['testSms'], $this->getAccountId());
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        if ($response) {
            return ['code'=>200];
        } else {
            LogUtil::error(['message'=>'EarlyBird測試簡訊發送失敗', 'mobile'=>$params['testMobile'], 'content'=>$params['testSms']], 'earlybird');
            return ['code'=>1000];
        }
    }

    public function actionGetTestSms()
    {
        $smsTag = $this->getQuery('smsTag');
        $accountId = $this->getAccountId();
        if (empty($smsTag)) {
            throw new BadRequestHttpException('param is missing.(smsTag)');
        }
        $member = Member::getByMobile(EarlybirdSmsUtil::MOBILE_FOR_TEST);
        $sms = EarlybirdSmsUtil::getSms($member, $smsTag, $accountId);
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        if (empty($member)) {
            return ['code'=>1000];
        }
        return ['code'=>200, 'sms'=>$sms];
    }

    /**
     * @return json
     *   {
     *       "records": {
     *           "sms_one": {
     *               "_id": {
     *                   "$id": "5604ea12475df469168b4568"
     *               },
     *               "total": 0,
     *               "successful": null,
     *               "failed": null,
     *               "process": 1
     *           },
     *           "sms_two": false,
     *           "sms_three": false,
     *           "sms_four": false
     *       },
     *       "sendingId": "5604ea12475df469168b4568"
     *   }
     */
    public function actionGetSmsRecord()
    {
        $records = EarlyBirdSmsRecord::getLastSmsRecord();
        $sendingId = null;
        foreach ($records as $record) {
            if (is_array($record)) {
                if ($record['process'] == 1) {
                    $sendingId = (string)$record['_id'];
                    break;
                }
            }
        }
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        return ['records'=>$records, 'sendingId'=>$sendingId];
    }

    public function actionGetSendInfo($id)
    {
        if (empty($id)) {
            throw new BadRequestHttpException("Param is missing. (smsRecordId)");
        }
        $smsRecordId = new \MongoId($id);
        $smsRecord = EarlyBirdSmsRecord::updateSmsRecordById($smsRecordId);

        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        return [
                    'successful'=>$smsRecord->successful,
                    'failed'=>$smsRecord->failed,
                    'process'=>$smsRecord->process,
                    'smsName'=>$smsRecord->smsName
               ];
    }

    /**
     * @param smsName ('sms_one' or 'sms_two' or 'sms_three' or 'sms_four')
     */
    public function actionExportSendFailed()
    {
        $smsName = $this->getQuery('smsName');
        $result = $this->_exportSmsRecord($smsName, 'EarlyBird簡訊發送失敗記錄表_');
        return $result;
    }

    public function actionExportSmsDetails()
    {
        $smsName = $this->getQuery('smsName');
        $result = $this->_exportSmsRecord($smsName, 'EarlyBird簡訊發送詳情_');
        return $result;
    }

    private function _exportSmsRecord($smsName, $fileName)
    {
        $accountId = $this->getAccountId();
        if (empty($smsName)) {
            throw new BadRequestHttpException("smsName param is missing in exporting early bird sms record");
        }
        $key = $fileName . date('YmdHis');
        $header = [
            'mobile' => '手機號碼',
            'smsContent' => '簡訊內容',
            'status' => '發送狀態',
            'createdAt' => '發送時間'
        ];

        $exportArgs = [
            'key' => $key,
            'header' => $header,
            'accountId' => (string)$accountId,
            'smsName' => $smsName,
            'description' => 'Direct: export early bird sms record'
        ];
        $jobId = Yii::$app->job->create('backend\modules\uhkklp\job\ExportEarlyBirdSmsRecord', $exportArgs);
        return ['result' => 'success', 'message' => 'exporting file', 'data' => ['jobId' => $jobId, 'key' => $key]];
    }

    public function actionMyTest()
    {
        // Add members
        // $tels = array();
        // for ($i=1; $i < 10000 ; $i++) {
        //     $n = rand(10000000,99999999);
        //     if (!in_array($n, $tels)) {
        //         $tels[] = $n;
        //     }
        //     unset($n);
        // }
        // for ($i=1;$i<10000;$i++) {
        //     $properties = [
        //       ['name'=>'name','value'=>'用戶名'.($i+1),'id'=>new \MongoId('56033cad475df4ce0c8b456e')],
        //       ['name'=>'tel','value'=>'09'. (string)$tels[$i], 'id'=>new \MongoId('56033cad475df4ce0c8b456f')]
        //     ];
        //     $member = new Member();
        //     $member->score = 2000 + $i;
        //     $member->totalScore = 2000 + $i;
        //     $member->totalScoreAfterZeroed = 2000 + $i;
        //     $member->avatar = '';
        //     $member->location = null;
        //     $member->tags = [];
        //     $member->properties = $properties;
        //     $member->accountId = new \MongoId("56028cda475df4aa048b4567");

        //     $defaultCard = \backend\modules\member\models\MemberShipCard::getDefault($member->accountId);
        //     $member->cardId = $defaultCard->_id;
        //     $member->cardNumber = Member::generateCardNumber();
        //     $member->origin = Member::PORTAL;

        //     if (!$member->save()) {
        //         return false;
        //     }
        // }

        // $sms['mobile'] = '0912345678';
        // $sms['mobile'] = '886' . substr($sms['mobile'], 1, strlen($sms['mobile']));
        // var_dump($sms['mobile']);

        $id = $this->getQuery('id');
        $member = Member::findByPk(new \MongoId($id));
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        return ['member'=>$member];
    }

}