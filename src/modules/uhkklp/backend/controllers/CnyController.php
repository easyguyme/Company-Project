<?php

namespace backend\modules\uhkklp\controllers;

use Yii;
use Yii\base\Exception;
use yii\web\BadRequestHttpException;
use yii\web\ServerErrorHttpException;
use backend\utils\LogUtil;
use backend\utils\MongodbUtil;
use backend\modules\product\models\CampaignLog;
use backend\modules\uhkklp\models\LuckyDrawRecord;
use backend\modules\uhkklp\models\LuckyDrawWinner;
use backend\modules\uhkklp\models\BulkSmsRecord;
use backend\modules\uhkklp\models\KlpAccountSetting;
use backend\modules\uhkklp\models\Activity;
use backend\modules\uhkklp\utils\BulkSmsUtil;

class CnyController extends BaseController
{
    public function actionDraw()
    {
        $params = $this->getParams();
        $accountId = $this->getAccountId();

        $activity = Activity::getActivityByName('cny', $accountId);
        if (empty($activity)) {
            throw new ServerErrorHttpException("Get activity-CNY failed");
        }
        //创建抽奖记录
        $createParams = [
            'activityName' => 'CNY',
            'activityStartDate' => $activity->startDate,
            'activityEndDate' => $activity->endDate,
            'condition' => ['needPoints'=>$activity->luckyDrawInfo['needPoints']],
            'awards' => $params['prizes'],
            'remark' => [
                'drawProcess' => 'drawing'
            ]
        ];
        $recordId = LuckyDrawRecord::createDrawRecord($createParams);
        $exportArgs = [
            'params' => $params,
            'accountId' => (string)$accountId,
            'recordId' => (string)$recordId
        ];
        $jobId = Yii::$app->job->create('backend\modules\uhkklp\job\CnyDraw', $exportArgs);
        unset($createParams, $accountId, $activity, $condition);

        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        if ($jobId == null) {
            return ['code'=>500];
        }
        return ['code'=>200, 'recordId'=>(string)$recordId];

        /*
        //整理奖品
        $prizes = array();  // ['4人份餐劵'=>2, '100元紅包'=>100]  奖品顺序排列，先抽奖品数量少的，一般是大奖
        foreach ($params['prizes'] as $prize) {
            $prizes[$prize['name']] = $prize['quantity'];
        }
        asort($prizes);
        unset($params);

        $prizeNames = array();  // ['4人份餐劵', '4人份餐劵', '100元紅包'...]
        foreach ($prizes as $key => $value) {
            for ($i=0;$i<$value;$i++) {
                $prizeNames[] = $key;
            }
        }
        $prizeCount = count($prizeNames);
        unset($prizes);

        //抽奖
        $drawArr = $this->_draw($members);
        $winners = array_slice($drawArr, 0, $prizeCount, true);
        $winnerCount = count($winners);
        unset($drawArr);

        if ($winnerCount < $prizeCount) {
            $prizeNames = array_slice($prizeNames, 0, $winnerCount);
        }
        $winnerMobiles = array_keys($winners);
        $winners = array_combine($winnerMobiles, $prizeNames);
        unset($winnerMobiles, $prizeCount, $winnerCount, $winnerMobiles);

        // 保存抽奖结果
        foreach ($winners as $mobile => $awardName) {
            foreach ($members as $key => $value) {
                if ($mobile == $members[$key]['mobile']) {
                    $createParams = [
                        'mobile' => strval($mobile),
                        'name' => $members[$key]['name'],
                        'awardName' => $awardName,
                        'drawRecordId' => $recordId,
                        'winInfo' => [
                            'scoreAdded' => $members[$key]['scoreAdded'],
                            'boughtAllProducts' => $members[$key]['canDouble']
                        ]
                    ];
                    LuckyDrawWinner::createWinner($createParams);
                    unset($createParams);
                }
            }
        }
        unset($members);
        */
    }

    public function actionGetDrawProcess($id) {
        $accountId = $this->getAccountId();
        $record = LuckyDrawRecord::findOne(new \MongoId($id));

        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        if ($record != null && $record->remark['drawProcess'] == 'ok') {
            return ['process'=>'ok'];
        } else {
            return ['process'=>'drawing'];
        }
    }

    public function actionIsDrawing() {
        $record = LuckyDrawRecord::findOne(['remark.drawProcess' => 'drawing']);

        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        if ($record != null) {
            return ['code'=>200, 'recordId'=>(string)$record->_id];
        } else {
            return ['code'=>500];
        }
    }

    public function actionGetDrawRecordList()
    {
        $params = $this->getQuery();
        $accountId = $this->getAccountId();
        if (empty($params['currentPage'])) {
            $params['currentPage'] = 1;
            $params['pageSize'] = 10;
        }

        $condition = ['accountId'=>$accountId];
        $count = LuckyDrawRecord::getDrawCount($condition);
        $list = LuckyDrawRecord::findList($params['currentPage'], $params['pageSize'], $condition);

        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        return ['count'=>$count, 'drawRecords'=>$list];
    }

    public function actionExportWinners($id)
    {
        $accountId = $this->getAccountId();
        $condition = ['accountId'=>$accountId, 'drawRecordId'=>new \MongoId($id)];
        $result = LuckyDrawWinner::find()->where($condition)->one();

        if (!empty($result)) {
            $key = 'CNY活動抽獎結果記錄表_' . date('YmdHis');
            $condition = serialize($condition);
            $header = [
                'mobile' => '手機號碼',
                'name' => '姓名',
                'city' => '縣市',
                'site' => '地址',
                'rname' => '餐廳名稱',
                'awardName' => '獎品名稱',
                'scoreAdded' => '寄回總點數',
                'createdAt' => '抽獎時間',
                'remark' => '備註'
            ];

            $exportArgs = [
                'key' => $key,
                'header' => $header,
                'accountId' => (string)$accountId,
                'condition' => $condition,
                'activityName' => 'cny',
                'description' => 'Direct: export CNY winners'
            ];
            $jobId = Yii::$app->job->create('backend\modules\uhkklp\job\ExportLuckyDrawWinners', $exportArgs);
            return ['result' => 'success', 'message' => 'exporting file', 'data' => ['jobId' => $jobId, 'key' => $key]];
        } else {
            LogUtil::error(['message'=>'CNY活動抽獎結果記錄表失败', 'reason'=>'没有数据(no data)', 'condition'=>$condition], 'cny');
            return ['result' => 'error', 'message' => 'no datas', 'data' => $result];
        }
    }

    public function actionExportSmsContent($id)
    {
        $accountId = $this->getAccountId();
        $condition = ['accountId'=>$accountId, 'drawRecordId'=>new \MongoId($id)];
        $result = LuckyDrawWinner::find()->where($condition)->one();

        if (!empty($result)) {
            $key = 'CNY活動中獎通知簡訊_' . date('YmdHis');
            $condition = serialize($condition);
            $header = [
                'mobile' => '手機號碼',
                'smsContent' => '簡訊內容',
            ];

            $exportArgs = [
                'key' => $key,
                'header' => $header,
                'accountId' => (string)$accountId,
                'condition' => $condition,
                'activityName' => 'cny',
                'description' => "Direct: export CNY winners' SMS"
            ];
            $jobId = Yii::$app->job->create('backend\modules\uhkklp\job\ExportLuckyDrawWinners', $exportArgs);
            return ['result' => 'success', 'message' => 'exporting file', 'data' => ['jobId' => $jobId, 'key' => $key]];
        } else {
            LogUtil::error(['message'=>"Failed exporting CNY winners' SMS content", 'reason'=>'没有数据(no data)', 'condition'=>$condition], 'cny');
            return ['result' => 'error', 'message' => 'no datas', 'data' => []];
        }
    }

    public function actionExportSmsDetail($id)
    {
        if (empty($id)) {
            throw new BadRequestHttpException("param is missing.");
        }
        $drawRecord = LuckyDrawRecord::findByPk(new \MongoId($id));
        $accountId = $this->getAccountId();
        $result = BulkSmsUtil::createExportSmsRecordJob($drawRecord->remark['smsRecordId'], $accountId, 'CNY中獎者簡訊記錄');
        return $result;
    }

    public function actionSendSmsToWinners($id)
    {
        if (empty($id)) {
            throw new BadRequestHttpException('param is missing.(LuckyDrawRecordId)');
        }

        $accountId = $this->getAccountId();
        $operator = $this->getUser()->email;
        $condition = ['accountId'=>$accountId, 'drawRecordId'=>new \MongoId($id)];

        $result = BulkSmsUtil::createSmsJob($condition, $operator, 'cny_winners');
        if (!empty($result)) {
            $remark = ['sentSMS'=>true, 'smsRecordId'=>new \MongoId($result['smsRecordId'])];
            LuckyDrawRecord::addRemark(new \MongoId($id), $remark);
        }
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        return $result;
    }

    public function actionGetSendSmsInfo($id)
    {
        if (empty($id)) {
            throw new BadRequestHttpException('param is missing.(LuckyDrawRecordId)');
        }

        $drawRecord = LuckyDrawRecord::findByPk(new \MongoId($id));
        $smsRecord = BulkSmsRecord::updateSmsRecordById($drawRecord->remark['smsRecordId']);

        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        return [
                    'total' => $smsRecord->total,
                    'successful'=>$smsRecord->successful,
                    'failed'=>$smsRecord->failed,
                    'process'=>$smsRecord->process,
                    'smsTemplate' => $smsRecord->smsTemplate
               ];
    }

    public function actionGetAccountSetting()
    {
        $site = KlpAccountSetting::getAccountSite($this->getAccountId());
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        return ['site'=>$site];
    }

    public function actionUpdateActivity()
    {
        $params = $this->getParams();
        if (empty($params['needPoints']) || empty($params['drawDate'])) {
            throw new BadRequestHttpException("Miss params.");
        }

        foreach ($params['drawDate'] as $key => $value) {
            $params['drawDate'][$key] = MongodbUtil::msTimetamp2MongoDate($value);
        }
        $params['name'] = 'cny';
        $params['accountId'] = $this->getAccountId();
        $params['luckyDrawInfo'] = [
            'needPoints' => $params['needPoints'],
            'drawDate' => $params['drawDate']
        ];
        unset($params['needPoints'], $params['drawDate']);

        $activity = Activity::updateActivityByName('cny', $params);
        if (empty($activity)) {
            $activity = Activity::createActivity($params);
        }

        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        if (!empty($activity)) {
            return ['code'=>200];
        } else {
            return ['code'=>1000];
        }
    }

    public function actionGetActivityInfo()
    {
        $drawDate = array();
        $activity = Activity::getActivityByName('cny');
        if (!empty($activity)) {
            foreach ($activity->luckyDrawInfo['drawDate'] as $key => $value) {
                $drawDate[] = MongodbUtil::MongoDate2msTimeStamp($value);
            }

            Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
            return [
                'startDate' => MongodbUtil::MongoDate2msTimeStamp($activity->startDate),
                'endDate' => MongodbUtil::MongoDate2msTimeStamp($activity->endDate),
                'drawDate' => $drawDate,
                'needPoints' => $activity->luckyDrawInfo['needPoints']
            ];
        }
    }

    /**
     * @param $condition array eg: ['accountId'=>$accountId, 'redeemTime'=>['$gte'=>$startDate, '$lte'=>$endDate]];
     * @return $member array
     *     [
     *        [   "mobile" => '0933431025',
     *            "scoreAdded" => 30,
     *            "canDouble" => ture,
     *            "name": "用戶名1",
     *            "odds": 6
     *        ],
     *        [   "mobile" => '0912345678',
     *            "scoreAdded": 10,
     *            "canDouble" => false,
     *            "name": "用戶名2",
     *            "odds": 1
     *        ],
     *     ]
     */
    // private function _getCanDrawMembers($condition, $pointsPerOdds)
    // {
    //     $mobiles = CampaignLog::distinct('member.phone', $condition);
    //     $redeemRecords = CampaignLog::find()->where($condition)->all();

    //     $members = array();  // mobile, scoreAdded, canDouble
    //     $mobilesCount = count($mobiles);
    //     for ($i=0; $i<$mobilesCount; $i++) {
    //         $members[$i] = ['mobile'=>$mobiles[$i], 'scoreAdded'=>0, 'canDouble'=>false];
    //         $checkDouble = array();
    //         foreach ($redeemRecords as $redeemRecord) {
    //             $product = $redeemRecord['productName'];

    //             if ($mobiles[$i] == $redeemRecord['member']['phone']) {
    //                 $members[$i]['name'] = $redeemRecord['member']['name'];

    //                 if ($redeemRecord['member']['type'] == 'score') {
    //                     $members[$i]['scoreAdded'] += $redeemRecord['member']['scoreAdded'];
    //                 }

    //                 //判断是否购买全部3支品项
    //                 if (!$members[$i]['canDouble']) {
    //                     if ($product == '2015 雞粉2.2kg' || $product == '2015 雞粉1.1kg' || $product == '2016 康寶雞粉 1.1KG' || $product == '2016 康寶雞粉 2.2KG') {
    //                         $checkDouble['chickenPowder'] = true;
    //                     }
    //                     if ($product == '2015 鮮雞汁' || $product == '2016 康寶濃縮鮮雞汁') {
    //                         $checkDouble['chickenJuice'] = true;
    //                     }
    //                     if ($product == '2015 鰹魚粉1kg' || $product == '2015 鰹魚粉1.5kg' || $product == '2016 康寶鰹魚粉 1KG' || $product == '2016 康寶鰹魚粉 1.5KG') {
    //                         $checkDouble['fishmeal'] = true;
    //                     }

    //                     if (count($checkDouble) == 3) {
    //                         $members[$i]['canDouble'] = true;
    //                     }
    //                 }
    //             }
    //             unset($product);
    //         }
    //         unset($checkDouble);
    //     }
    //     unset($mobiles, $redeemRecords, $mobilesCount);

    //     foreach ($members as $key => $value) {
    //         if ($members[$key]['canDouble']) {
    //             $members[$key]['odds'] = intval($members[$key]['scoreAdded'] / $pointsPerOdds)* 2;
    //         } else {
    //             $members[$key]['odds'] = intval($members[$key]['scoreAdded'] / $pointsPerOdds);
    //         }
    //         if ($members[$key]['odds'] < 1) {
    //             unset($members[$key]);
    //         }
    //         // unset($members[$key]['canDouble']);
    //     }
    //     return $members;
    // }

    /**
     * @param $members _getCanDrawMembers()的返回值
     * @return $drawArr  eg:["0984757748"=>15, "0933431025"=>13, "0912345678"=>12, ...]
     * 抽奖算法： 根据用户入点数得到的概率权重odds，生成个人权重到总权重之间的随机数,从大到小排列
     */
    // private function _draw($members)
    // {
    //     $drawArr = array();
    //     foreach ($members as $key => $value) {
    //         $drawArr[$members[$key]['mobile']] = $members[$key]['odds'];
    //     }
    //     $oddsSum = array_sum($drawArr);
    //     foreach ($drawArr as $key => $value) {
    //         $drawArr[$key] = mt_rand($value, $oddsSum);
    //     }
    //     arsort($drawArr);
    //     return $drawArr;
    // }
}
