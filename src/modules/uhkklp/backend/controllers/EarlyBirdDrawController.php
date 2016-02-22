<?php

namespace backend\modules\uhkklp\controllers;

use Yii;
use Yii\base\Exception;
use yii\web\BadRequestHttpException;
use yii\web\ServerErrorHttpException;
use backend\utils\LogUtil;
use backend\utils\MongodbUtil;
use backend\modules\uhkklp\utils\EarlybirdSmsUtil;
use backend\modules\member\models\ScoreHistory;
use backend\modules\member\models\Member;
use backend\modules\uhkklp\models\EarlyBirdWinner;
use backend\modules\uhkklp\models\EarlyBirdDrawRecord;

class EarlyBirdDrawController extends BaseController
{
    public function actionDrawPrize()
    {
        $params = $this->getParams();
        $accountId = $this->getAccountId();
        if (empty($params) || count($params) < 8) {
            throw new BadRequestHttpException('params are missing');
        }

        //拿到所有活动期间兑换过奖品的人 array('560399d6475df4c7378b4572'=>-200)
        $scores = EarlybirdSmsUtil::getExchangeGoodsScore($params['startDate'], $params['endDate'], $accountId);

        $scoresOne = array();
        $scoresTwo = array();
        $scoresThree = array();
        foreach ($scores as $key => $value) {
            $member = Member::findByPk(new \MongoId($key));
            if (!$member->isDeleted) {
                //拿到符合一等奖条件的人
                if (abs($value) >= $params['pointsOne']) { //eg: points>=2000
                    $scoresOne[] = $key;
                }
                //拿到符合二等奖条件的人
                if (abs($value) < $params['pointsOne'] && abs($value) >= $params['pointsTwo']) { //eg: 1000<=points<2000
                    $scoresTwo[] = $key;
                }
                //拿到符合三等奖条件的人
                if (abs($value) < $params['pointsTwo'] && abs($value) >= $params['pointsThree']) { //eg: 200<=points<1000
                    $scoresThree[] = $key;
                }
            }
            unset($member);
        }
        shuffle($scoresOne); //抽奖 (随机打乱阵列)
        shuffle($scoresTwo);
        shuffle($scoresThree);

        $prizeOneIds = null;
        $prizeTwoIds = null;
        $prizeThreeIds = null;

        //抽一等奖
        if (count($scoresOne) < $params['quantityOne']) {
            $prizeOneIds = $scoresOne;
        } else {
            $prizeOneIds = array_slice($scoresOne, 0, $params['quantityOne']);
        }

        //抽二等奖
        if (count($scoresTwo) < $params['quantityTwo']) {
            $prizeTwoIds = $scoresTwo;
        } else {
            $prizeTwoIds = array_slice($scoresTwo, 0, $params['quantityTwo']);
        }

        //抽三等奖
        if (count($scoresThree) < $params['quantityThree']) {
            $prizeThreeIds = $scoresThree;
        } else {
            $prizeThreeIds = array_slice($scoresThree, 0, $params['quantityThree']);
        }
        unset($scoresOne, $scoresTwo, $scoresThree);

        //通过中奖者memberId获取中奖者信息
        $prizeOneMembers = array();
        $prizeTwoMembers = array();
        $prizeThreeMembers = array();

        foreach ($prizeOneIds as $key => $value) {
            $prizeOneMembers[] = $this->_getMemberInfo($value, 'one', $scores);
        }
        foreach ($prizeTwoIds as $key => $value) {
            $prizeTwoMembers[] = $this->_getMemberInfo($value, 'two', $scores);
        }
        foreach ($prizeThreeIds as $key => $value) {
            $prizeThreeMembers[] = $this->_getMemberInfo($value, 'three', $scores);
        }
        unset($prizeOneIds, $prizeTwoIds, $prizeThreeIds, $scores);

        //保存抽奖记录
        if (empty($prizeOneMembers) && empty($prizeTwoMembers) && empty($prizeThreeMembers)) {
            Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
            return ['code'=>1000];
        }
        $recordId = EarlyBirdDrawRecord::createDrawRecord($params);
        $this->_saveWinner($prizeOneMembers, $recordId, $params['prizeNameOne']);
        $this->_saveWinner($prizeTwoMembers, $recordId, $params['prizeNameTwo']);
        $this->_saveWinner($prizeThreeMembers, $recordId, $params['prizeNameThree']);
        // return ['prizeOne'=>$prizeOneMembers, 'prizeTwo'=>$prizeTwoMembers, 'prizeThree'=>$prizeThreeMembers];
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
        $count = EarlyBirdDrawRecord::getDrawCount($condition);
        $list = EarlyBirdDrawRecord::findList($params['currentPage'], $params['pageSize'], $condition);

        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        return ['count'=>$count, 'list'=>$list];
    }

    public function actionExportDrawWinners($id)
    {
        $accountId = $this->getAccountId();
        $condition = ['accountId'=>$accountId, 'drawRecordId'=>new \MongoId($id)];
        $result = EarlyBirdWinner::find()->where($condition)->one();

        if (!empty($result)) {
            $key = '早鳥活動抽獎結果記錄表_' . date('YmdHis');
            $condition = serialize($condition);
            $header = [
                'prizeLevel' => '獎項',
                'mobile' => '手機號碼',
                'name' => '姓名',
                'prizeName' => '獎品名稱',
                'exchangeGoodsScore' => '總兌換積分',
                'createdAt' => '抽獎時間'
            ];

            $exportArgs = [
                'key' => $key,
                'header' => $header,
                'accountId' => (string)$accountId,
                'condition' => $condition,
                'description' => 'Direct: export early bird winners'
            ];
            $jobId = Yii::$app->job->create('backend\modules\uhkklp\job\ExportEarlyBirdWinners', $exportArgs);
            return ['result' => 'success', 'message' => 'exporting file', 'data' => ['jobId' => $jobId, 'key' => $key]];
        } else {
            LogUtil::error(['message'=>'导出早鳥活動抽獎結果記錄表失败', 'reason'=>'没有数据(no data)', 'condition'=>$condition], 'earlybird');
            return ['result' => 'error', 'message' => 'no datas', 'data' => []];
        }
    }

    public function actionSendDrawSms()
    {
        $operator = $this->getUser()->email;
        $params = $this->getParams();
        if (empty($params) || count($params) < 8) {
            throw new BadRequestHttpException('params are missing');
        }
        $condition = $params;
        $result = EarlybirdSmsUtil::createSmsJob($condition, $operator, 'sms_four');
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        return $result;
    }

    public function actionExportDrawMembers()
    {
        $params = $this->getQuery();
        $accountId = $this->getAccountId();
        if (empty($params) || count($params) < 8) {
            throw new BadRequestHttpException('params are missing');
        }
        $params = array_merge($params, ['accountId'=>$accountId]);
        $result = EarlybirdSmsUtil::getMemberCanDraw($params);

        if (!empty($result)) {
            $key = '具有EarlyBird活動資格的會員名單_' . date('YmdHis');
            $condition = serialize($params);
            $header = [
                'cardNumber' => '會員編號',
                'prizeLevel' => '資格',
                'mobile' => '手機號碼',
                'name' => '姓名',
                'prizeName' => '可抽取獎品名稱',
                'exchangeGoodsScore' => '總兌換積分',
                'id' => '會員ID'
            ];

            $exportArgs = [
                'key' => $key,
                'header' => $header,
                'accountId' => (string)$accountId,
                'condition' => $condition,
                'description' => 'Direct: export early bird members'
            ];
            $jobId = Yii::$app->job->create('backend\modules\uhkklp\job\ExportEarlyBirdDrawMembers', $exportArgs);
            return ['result' => 'success', 'message' => 'exporting file', 'data' => ['jobId' => $jobId, 'key' => $key]];
        } else {
            LogUtil::error(['message'=>'導出具有EarlyBird活動資格的會員名單失敗', 'reason'=>'没有数据(no data)', 'params'=>$params], 'earlybird');
            return ['result' => 'error', 'message' => 'no datas', 'data' => []];
        }
    }

    public function actionGetDrawTestSms()
    {
        $params = $this->getQuery();
        if (empty($params) || count($params) < 8) {
            throw new BadRequestHttpException('params are missing');
        }

        $member = Member::getByMobile(EarlybirdSmsUtil::MOBILE_FOR_TEST);
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        if (empty($member)) {
            return ['code'=>1000];
        }

        $condition = array_merge($params, ['accountId'=>$this->getAccountId()]);
        $smsArr = EarlybirdSmsUtil::getSmsForMemberCanDraw($condition);
        $testSms = array();
        foreach ($smsArr as $sms) {
            if (!empty($sms) && $sms['mobile'] == EarlybirdSmsUtil::MOBILE_FOR_TEST) {
                $testSms['mobile'] = $sms['mobile'];
                $testSms['smsContent'] = $sms['content'];
            }
        }
        if (empty($testSms)) {
            return ['code'=>2000]; //测试号码不满足抽奖条件
        }
        return ['code'=>200, 'sms'=>$testSms];
    }

    /**
     * @param $memberId string
     * @param $prizeLevel string (eg: 'one'表示一等奖)
     * @param $exchangeGoodsScores array (eg:['560399d6475df4c7378b4572'=>-200, ...])
     * @return array  (eg: ['id'=>'5604f..', 'mobile'=>'0912345678', 'name'=>'用戶名', 'exchangeGoodsScore'=>200, 'prizeLevel'=>'three'])
     */
    private function _getMemberInfo($memberId, $prizeLevel, $exchangeGoodsScores = null)
    {
        $memberId = new \MongoId($memberId);
        $member = Member::findByPk($memberId);
        $result = array();
        if (!empty($member->properties)) {
            $result['id'] = (string)$memberId;
            foreach ($member->properties as $property) {
                if ($property['name'] == 'tel') {
                    $result['mobile'] = $property['value'];
                }
                if ($property['name'] == 'name') {
                    $result['name'] = $property['value'];
                }
            }
            if (!empty($exchangeGoodsScores)) {
                foreach ($exchangeGoodsScores as $key => $value) {
                    if ($key == $result['id']) {
                        $result['exchangeGoodsScore'] = abs($value);
                        break;
                    }
                }
            }
            $result['prizeLevel'] = $prizeLevel;
        }
        unset($member);
        return $result;
    }

    //$winners array
    private function _saveWinner($winners, $recordId, $prizeName)
    {
        if (!empty($winners)) {
            foreach ($winners as $winner) {
                EarlyBirdWinner::creatWinner($winner, $recordId, $prizeName);
            }
        }
    }

}