<?php
namespace backend\modules\uhkklp\controllers;

use Yii;
use yii\helpers\FileHelper;
use yii\web\Controller;
use backend\utils\LogUtil;
use backend\models\Token;
use yii\mongodb\Query;
use backend\modules\uhkklp\models\PushMessageLog;
use backend\modules\uhkklp\models\Cookbook;
use backend\modules\uhkklp\models\Product;
use backend\modules\uhkklp\models\CookbookBatch;
use backend\modules\uhkklp\models\SmsModel;
use backend\modules\uhkklp\models\SmsRecord;
use backend\modules\uhkklp\models\SmsResultModel;
use backend\modules\uhkklp\models\BulkSmsFailed;
use backend\modules\uhkklp\models\BulkSmsSuccess;
use backend\models\User;
use backend\utils\MongodbUtil;
use backend\utils\MessageUtil;
use yii\helpers\Json;

class SmsController extends BaseController
{
    public $enableCsrfValidation = false;

    //param groupId
    public function actionSendSms()
    {
        //send sms
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;

        $request = Yii::$app->request;
        $groupId = $request->post('groupId');
        $modelContent = $request->post('modelContent');
        $smsBatch = $this->guid();

        $models = SmsModel::find()
        ->select(['mobile', 'content'])
        ->where(['groupId' => $groupId])
        ->orderBy('_id')
        ->asArray()
        ->all();

        $query = new Query();
        $count = $query->from('uhkklpSmsModel')
        ->where(['groupId' => $groupId])
        ->count();

        $args = ["data" => $models, "accountId" => (string)$this->getAccountId(), "modelContent" => $modelContent, "smsBatch" => $smsBatch];

        $jobId = Yii::$app->job->create('backend\modules\uhkklp\job\BulkSendSms', $args);
        // LogUtil::error(date('Y-m-d h:i:s') . ' jobId: ' . $jobId);

        if (!empty($jobId)) {
            return ['result' => 'success', 'count' => $count, 'smsBatch' => $smsBatch];
        } else {
            throw new ServerErrorHttpException("發送失敗，請刷新頁面重試!");
        }
    }

    public function actionQuerySmsSchedule()
    {
        $smsBatch = Yii::$app->request->post("smsBatch");

        $query = new Query();
        $successCount = $query->from('uhkklpBulkSmsSuccess')
        ->where(['smsRecordId' => $smsBatch])
        ->count();

        $query = new Query();
        $failureCount = $query->from('uhkklpBulkSmsFailed')
        ->where(['smsRecordId' => $smsBatch])
        ->count();

        $haveSent = $successCount + $failureCount;

        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        return ['haveSent' => $haveSent];
    }

    //param groupId
    public function actionExportSmsModel()
    {
        $id = $this->getQuery('modelGroupId');
        // LogUtil::error(date('Y-m-d h:i:s') . ' $id: ' . $id);
        $condition = ['groupId'=>$id];
        $result = SmsModel::find()->where($condition)->one();

        if (!empty($result)) {
            $key = 'Sms發送手機號及內容列表' . date('YmdHis');
            $header = [
                'groupId' => '導入批號',
                'mobile' => '手機號碼',
                'content' => '內容'
            ];

            $exportArgs = [
                'key' => $key,
                'header' => $header,
                'condition' => serialize($condition)
            ];
            $jobId = Yii::$app->job->create('backend\modules\uhkklp\job\ExportSmsModel', $exportArgs);
            return ['result' => 'success', 'message' => 'exporting SmsModel', 'data' => ['jobId' => $jobId, 'key' => $key]];
        } else {
            LogUtil::error(['message'=>'smsModel記錄表失败', 'reason'=>'没有数据(no data)', 'condition'=>$condition], 'sms');
            return ['result' => 'error', 'message' => 'no datas', 'data' => []];
        }
    }

    //param recordId(groupId)
    public function actionExportSmsResult()
    {
        $id = $this->getQuery('_id');
        $resultCondition = ['_id' => new \MongoId($id)];

        $result = SmsRecord::findOne($resultCondition);

        if (!empty($result)) {
            $smsBatch = $result['smsBatch'];
            // LogUtil::error(date('Y-m-d h:i:s') . ' $smsBatch: ' . $smsBatch);
            $condition = ['smsBatch' => $smsBatch];

            $res = SmsResultModel::find()->where($condition)->one();
            if (!empty($res)) {
                $key = 'Sms發送結果(以下順序先爲失敗發送的短信後爲成功發送的短信)_發送總記錄數: ' . $res['totalRecord'] . ' 成功發送記錄數: ' . $res['successRecord'] . ' 失敗發送記錄數: ' . $res['failureRecord'] . ' ' . date('YmdHis');
                $header = [
                    'smsRecordId' => '短信批號',
                    'mobile' => '手機號碼',
                    'smsContent' => '內容'
                ];
                $condi = ['smsRecordId' => $smsBatch];
                $exportArgs = [
                    'key' => $key,
                    'header' => $header,
                    'condition' => serialize($condi)
                ];
                $jobId = Yii::$app->job->create('backend\modules\uhkklp\job\ExportSmsResult', $exportArgs);
                return ['result' => 'success', 'message' => 'exporting SmsResult', 'data' => ['jobId' => $jobId, 'key' => $key]];
            } else {
                LogUtil::error(['message'=>'smsResult記錄表失败', 'reason'=>'没有数据(no data)', 'condition'=>$condition], 'smsResult');
                return ['result' => 'error', 'message' => 'no datas'];
            }
        } else {
            LogUtil::error(['message'=>'smsResult記錄表失败', 'reason'=>'没有数据(no data)', 'condition'=>$resultCondition], 'smsResult');
            return ['result' => 'error', 'message' => 'no datas'];
        }
    }

    //param recordId
    public function actionDeleteSmsResult()
    {
        $id = Yii::$app->request->post("_id");
        $resultCondition = ['_id' => new \MongoId($id)];

        $result = SmsResultModel::findOne($resultCondition);

        if (!empty($result)) {
            $smsBatch = $result['smsBatch'];

            //1. delete result record
            $result->delete();
            $condition = ['smsRecordId' => $smsBatch];
            // LogUtil::error(date('Y-m-d h:i:s') . ' $smsBatch: ' . $smsBatch);

            //2. delete failure record
            $res = BulkSmsFailed::deleteAll($condition);
            if (!empty($res)) {
                return ['result' => 'success', 'message' => 'delete SmsResult'];
            } else {
                return ['result' => 'error', 'message' => 'no datas'];
            }
        } else {
            LogUtil::error(['message'=>'smsResult刪除失败', 'reason'=>'没有数据(no data)', 'condition'=>$resultCondition], 'smsResultDelete');
            return ['result' => 'error', 'message' => 'no datas'];
        }
    }

    //param accountId
    public function actionGetResultList()
    {
        $currentPage = Yii::$app->request->get("currentPage", 1);
        $pageSize = Yii::$app->request->get("pageSize", 10);
        $offset = ($currentPage - 1) * $pageSize;
        $sortName = "_id";
        $sortDesc = Yii::$app->request->get('sortDesc', 'ASC');
        $sort = $sortName . ' ' . $sortDesc;

        $query = new Query();
        $records = $query->from('uhkklpSmsRecordModel')
            ->select(['_id', 'modelContent', 'createdAt', 'totalRecord', 'successRecord', 'failureRecord'])
            ->where(['accountId' => (string)($this->getAccountId())])
            ->orderBy($sort)
            ->offset($offset)
            ->limit($pageSize)
            ->all();

        for ($i = 0;$i < count($records);$i++) {
            $records[$i]['createdAt'] = MongodbUtil::MongoDate2String($records[$i]['createdAt'], 'Y-m-d H:i:s', null);
            $records[$i]['_id'] = (string)$records[$i]['_id'];
        }

        $totalPageCount = $query->from('uhkklpSmsRecordModel')
            ->where(['accountId' => (string)($this->getAccountId())])
            ->count();

        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        return ['code' => 200, 'list' => $records, 'totalPageCount' => $totalPageCount];
    }

    //=======================================================================================================

    public function actionExportSmsTemplate()
    {
        $id = $this->getQuery('_id');
        $condition = ['_id' => new \MongoId($id)];

        if (!empty($id)) {
            $key = 'Sms發送手機號及內容列表' . date('YmdHis');
            $header = [
                'mobile' => '手機號碼',
                'content' => '內容'
            ];

            $exportArgs = [
                'key' => $key,
                'header' => $header,
                'condition' => serialize($condition)
            ];
            $jobId = Yii::$app->job->create('backend\modules\uhkklp\job\ExportSmsTemplate', $exportArgs);
            return ['result' => 'success', 'message' => 'exporting SmsModel', 'data' => ['jobId' => $jobId, 'key' => $key]];
        } else {
            LogUtil::error(['message'=>'smsModel記錄表失败', 'reason'=>'没有数据(no data)', 'condition'=>$condition], 'sms');
            return ['result' => 'error', 'message' => 'no datas', 'data' => []];
        }
    }

    public function actionSaveSms()
    {
        $request = Yii::$app->request;
        $modelContent = $request->post('modelContent');
        $sendTime = $request->post('sendTime');
        $list = $request->post('list');
        $smsBatch = $this->guid();
        // LogUtil::error(date('Y-m-d h:i:s') . ' $modelContent: ' . $modelContent);

        $tmpSendTime = $sendTime;
        $sendTime = $sendTime / 1000;
        $args = ["data" => $list, "accountId" => (string)$this->getAccountId(), "smsBatch" => $smsBatch, "modelContent" => $modelContent];

        LogUtil::error(date('Y-m-d h:i:s') . ' create job.. ' );
        $res = Yii::$app->resque->enqueueJobAt((int)$sendTime, 'sms', 'backend\modules\uhkklp\job\SendSMS', $args);
        LogUtil::error(date('Y-m-d h:i:s') . ' $res: ' . json_encode($res));

        if (!empty($res)) {
            $SmsRecord = new SmsRecord();
            $SmsRecord->accountId = $this->getAccountId();
            $SmsRecord->modelContent = $modelContent;
            $SmsRecord->sendTime = $tmpSendTime;
            $SmsRecord->importResultList = $list;
            $SmsRecord->isSend = false;
            $SmsRecord->token = $res['token'];
            $SmsRecord->smsBatch = $smsBatch;
            $result = $SmsRecord->save();

            Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;

            if ($result > 0) {
                return ['msg' => 'success', 'code' => '200'];
            } else {
                return ['msg' => 'failed', 'code' => '500'];
            }
        } else {
            return ['msg' => 'failed', 'code' => '500'];
        }
    }

    public function actionGetSmsResultList()
    {
        // $this->getDelayJob();
        $currentPage = Yii::$app->request->get("currentPage", 1);
        $pageSize = Yii::$app->request->get("pageSize", 10);
        $offset = ($currentPage - 1) * $pageSize;
        $sortName = "_id";
        $sortDesc = Yii::$app->request->get('sortDesc', 'ASC');
        $sort = $sortName . ' ' . $sortDesc;

        $query = new Query();
        $records = $query->from('uhkklpSmsRecord')
            ->select(['_id', 'modelContent', 'createdAt', 'sendTime', 'isSend'])
            ->where(['accountId' => $this->getAccountId()])
            ->orderBy($sort)
            ->offset($offset)
            ->limit($pageSize)
            ->all();

        for ($i = 0;$i < count($records);$i++) {
            $records[$i]['createdAt'] = MongodbUtil::MongoDate2String($records[$i]['createdAt'], 'Y-m-d H:i:s', null);
            $records[$i]['_id'] = (string)$records[$i]['_id'];
            $records[$i]['sendTime'] = MongodbUtil::MongoDate2String(MongodbUtil::msTimetamp2MongoDate($records[$i]['sendTime']));
        }

        $totalPageCount = $query->from('uhkklpSmsRecord')
            ->where(['accountId' => ($this->getAccountId())])
            ->count();

        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        return ['code' => 200, 'list' => $records, 'totalPageCount' => $totalPageCount];
    }

    public function actionDeleteSmsRecord()
    {
        $id = Yii::$app->request->post("_id");
        $resultCondition = ['_id' => new \MongoId($id)];

        $result = SmsRecord::findOne($resultCondition);

        if (!empty($result)) {
            $smsBatch = $result['smsBatch'];

            //1. delete result record
            $result->delete();

            if (!$result['isSend']) {
                $ress = $this->cancleDelayJob("backend\modules\uhkklp\job\SendSMS", $result['sendTime'], $result['token']);
                // LogUtil::error(date('Y-m-d h:i:s') . ' $ress: ' . $ress);

                return ['result' => 'success', 'message' => 'delete SmsResult'];
            }

            //2. delete failure record
            $re = SmsResultModel::findOne(['smsBatch' => $smsBatch]);
            $re->delete();
            // LogUtil::error(date('Y-m-d h:i:s') . ' $re: ' . json_encode($re));

            if (!empty($re)) {
                $condition = ['smsRecordId' => $smsBatch];
                $res = BulkSmsFailed::deleteAll($condition);
                $resSuc = BulkSmsSuccess::deleteAll($condition);

                if (empty($res) && empty($resSuc)) {
                    return ['result' => 'error', 'message' => 'no datas'];
                } else {
                    return ['result' => 'success', 'message' => 'delete SmsResult'];
                }
            } else {
                return ['result' => 'error', 'message' => 'no datas'];
            }
        } else {
            LogUtil::error(['message'=>'smsResult刪除失败', 'reason'=>'没有数据(no data)', 'condition'=>$resultCondition], 'smsResultDelete');
            return ['result' => 'error', 'message' => 'no datas'];
        }
    }

    public function actionUpdateSms()
    {
        $request = Yii::$app->request;
        $id = $request->post("_id");
        $modelContent = $request->post('modelContent');
        $sendTime = $request->post('sendTime');
        $list = $request->post('list');

        $resultCondition = ['_id' => new \MongoId($id)];

        $SmsRecord = SmsRecord::findOne($resultCondition);

        $originalSmsBatch = $SmsRecord['smsBatch'];
        $newSmsBatch = $SmsRecord['smsBatch'];

        //have sent
        if ($SmsRecord['isSend']) {
            $newSmsBatch = $this->guid();

            //1. delete original record
            $re = SmsResultModel::findOne(['smsBatch' => $originalSmsBatch]);
            $re->delete();

            if (!empty($re)) {
                $condition = ['smsRecordId' => $originalSmsBatch];
                $res = BulkSmsFailed::deleteAll($condition);
                $resSuc = BulkSmsSuccess::deleteAll($condition);

                if (empty($res) && empty($resSuc)) {
                    return ['code' => '500', 'message' => 'failed to delete'];
                }
            } else {
                return ['code' => '500', 'message' => 'failed to delete'];
            }

        //not sent
        } else {
            //RESET job
            //1. cancel origin job
            $ress = $this->cancleDelayJob("backend\modules\uhkklp\job\SendSMS", $SmsRecord['sendTime'], $SmsRecord['token']);
            LogUtil::error(date('Y-m-d h:i:s') . ' $ress: ' . $ress);
        }

        //2. new job
        $args = ["data" => $list, "accountId" => (string)$this->getAccountId(), "smsBatch" => $newSmsBatch, "modelContent" => $modelContent];
        $res = Yii::$app->resque->enqueueJobAt((int)$sendTime, 'sms', 'backend\modules\uhkklp\job\SendSMS', $args);

        $SmsRecord->modelContent = $modelContent;
        $SmsRecord->smsBatch = $newSmsBatch;
        $SmsRecord->sendTime = $sendTime;
        $SmsRecord->token = $res['token'];
        $SmsRecord->importResultList = $list;
        $SmsRecord->isSend = false;
        $result = $SmsRecord->save();

        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        if (!empty($result)) {
            return ['code' => '200'];
        } else {
            return ['code' => '500'];
        }
    }

    public function actionGetOne()
    {
        $id = Yii::$app->request->post("_id");
        $resultCondition = ['_id' => new \MongoId($id)];
        $result = SmsRecord::findOne($resultCondition);
        if (!empty($result)) {
            return ['result' => 'success', 'modelContent' => $result['modelContent'], 'sendTime' => $result['sendTime'], 'list' => $result['importResultList']];
        } else {
            return ['result' => 'failed'];
        }
    }

    public function actionTestSms()
    {
        $request = Yii::$app->request;
        $testNumber = $request->post('testNumber');
        $testContent = $request->post('testContent');
        $accountId = (string)$this->getAccountId();
        // LogUtil::error(date('Y-m-d h:i:s') . ' $testNumber: ' . $testNumber);
        // LogUtil::error(date('Y-m-d h:i:s') . ' $testContent: ' . $testContent);
        // LogUtil::error(date('Y-m-d h:i:s') . ' $accountId: ' . $accountId);

        try {
                if (!empty($testNumber) && !empty($testContent) && !empty($accountId))
                {
                    $response = MessageUtil::sendMobileMessage($testNumber, $testContent, $accountId);
                    // $response = MessageUtil::sendMobileMessage($testNumber, $testContent);
                    if (!$response) {
                        LogUtil::error(['message'=>'sendSmsTest失敗', 'mobile'=>$testNumber, 'SMSContent'=>$testContent], 'SendSmsTest');
                        return ['result' => 'failed'];
                    } else {
                        LogUtil::error(['message'=>'sendSmsTest成功', 'mobile'=>$testNumber, 'SMSContent'=>$testContent], 'SendSmsTest');
                        return ['result' => 'success'];
                    }
                    unset($response);
                } else {
                    return ['result' => 'no data'];
                }
        } catch (\Exception $e) {
            LogUtil::error(['message'=>'TestSms發送失敗', 'error'=>$e], 'TestSms');
            throw $e;
        }
    }

    public function guid() {
        if (function_exists('com_create_guid')) {
            return com_create_guid();
        } else {
            mt_srand((double)microtime() * 10000);//optional for php 4.2.0 and up.
            $charid = strtoupper(md5(uniqid(rand(), true)));
            $uuid =  substr($charid, 0, 8)
                    .substr($charid, 8, 4)
                    .substr($charid, 12, 4)
                    .substr($charid, 16, 4)
                    .substr($charid, 20, 12);
            return $uuid;
        }
    }

    private function cancleDelayJob($class, $time, $token)
    {
        $delayJobs = Yii::$app->resque->getDelayedJobs();
        if (empty($delayJobs)) {
            return true;
        }

        foreach ($delayJobs as $delayJob) {
            $delayJobArray = Json::decode($delayJob);

            $delayJobArgs = $delayJobArray['args'][0];

            if ($delayJobArray['class'] == $class) {
                $tokenAndTimstamp = [
                    'token' => $token,
                    'at' => $time,
                ];
                $removeResult = Yii::$app->resque->cancelDelayedJob($tokenAndTimstamp, $delayJobArray['queue'], $class, $delayJobArgs);
                if (!$removeResult) {
                    return false;
                }
            }
        }
        return true;
    }

    private function getDelayJob()
    {
        $delayJobs = Yii::$app->resque->getDelayedJobs();
        if (empty($delayJobs)) {
            return true;
        }

        foreach ($delayJobs as $delayJob) {
            $delayJobArray = Json::decode($delayJob);
            $delayJobArgs = $delayJobArray['args'][0];

            LogUtil::error(date('Y-m-d h:i:s') . ' $class: ' . $delayJobArray['class']);
            LogUtil::error(date('Y-m-d h:i:s') . ' $token: ' . $delayJobArgs['djID']);
        }
        return true;
    }
}
