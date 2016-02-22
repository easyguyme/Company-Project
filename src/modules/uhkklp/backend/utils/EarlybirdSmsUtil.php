<?php
namespace backend\modules\uhkklp\utils;

use Yii;
use backend\modules\uhkklp\models\EarlyBirdSmsRecord;
use backend\modules\uhkklp\models\EarlyBirdSmsFailed;
use backend\modules\uhkklp\models\EarlyBirdSmsDetail;
use backend\modules\member\models\Member;
use backend\modules\member\models\ScoreHistory;
use backend\models\MessageTemplate;
use yii\web\ServerErrorHttpException;
use backend\utils\MessageUtil;
use backend\utils\MongodbUtil;
use backend\utils\LogUtil;
use backend\models\Token;

class EarlybirdSmsUtil
{
    const BATCH_COUNT = 1000;

    const EARLY_BIRD_ONE_NAME = 'early_bird_template_one';
    const EARLY_BIRD_TWO_NAME = 'early_bird_template_two';
    const EARLY_BIRD_THREE_NAME = 'early_bird_template_three';
    const EARLY_BIRD_FOUR_NAME = 'early_bird_template_four';

    const EARLY_BIRD_ONE_TEMPLATE = '%username%您好，【集點拿獎2015最後一波優惠】即日起至11/30前，兌200點再加碼抽 $200禮劵！詳情洽http://bit.ly/1P3M1mK。提醒您，截至10/29您擁有%points%點數， 12/31後未兌換之點數將歸零。';
    const EARLY_BIRD_TWO_TEMPLATE = '【集點拿獎 早鳥兌獎抽獎樂】還剩七天！至11/30前兌換贈品，再加碼抽好禮！提醒您: 12/31 23:59, 未兌換之點數將歸零，詳情洽http://bit.ly/1P3M1mK';
    const EARLY_BIRD_THREE_TEMPLATE = '%username%您好，【集點拿獎 2015活動即將結束】截至12/23您尚有%points%點數，12/31 23:59後，未兌換之點數將歸零，還剩七天！請立即兌換！詳情洽http://bit.ly/1J7bjyJ';
    const EARLY_BIRD_FOUR_TEMPLATE = '%username%您好，謝謝您參加早鳥兌獎抽獎樂，您已獲得%prizeName%抽獎資格，抽獎結果將於12/7公布ufs.com 。';

    const EARLY_BIRD_ONE_TIME = '2015-10-29 23:59:59';
    const EARLY_BIRD_THREE_TIME = '2015-12-23 23:59:59';

    const MOBILE_FOR_TEST = '0933431025';

    /**
     * @param $condition Array
     * @param $smsTag   (sms_one, sms_two, sms_three, sms_four)
     * @param $smsRecordId MongoId
     */
    public static function sendSmsBycondition($condition, $smsTag, $smsRecordId)
    {
        EarlyBirdSmsRecord::updateProcessById($smsRecordId, 1);  // 正在發送
        try {
            $memberAll = self::_findSmsTargetByCondition($condition);
            $offset = 0;
            $memberAll->offset($offset)->limit(self::BATCH_COUNT);
            $members = $memberAll->all();
            do {
                if (empty($members)) {
                    break;
                }
                $result = self::_sendSms($members, $smsTag, $smsRecordId, $condition['accountId']);
                unset($members);
                $offset += self::BATCH_COUNT;
                $memberAll->offset($offset)->limit(self::BATCH_COUNT);
                $members = $memberAll->all();
            } while ($result);
            EarlyBirdSmsRecord::updateProcessById($smsRecordId, 2);  // 發送完成
        } catch (\Exception $e) {
            LogUtil::error(['message'=>'EarlyBirdSms發送失敗', 'error'=>$e], 'earlybird');
            EarlyBirdSmsRecord::updateProcessById($smsRecordId, 3);  // 發送故障
            throw $e;
        }
    }

    // for sms_four
    public static function sendSmsByExchangeGoodsScore($condition, $smsTag, $smsRecordId)
    {
        EarlyBirdSmsRecord::updateProcessById($smsRecordId, 1);  // 正在發送
        try {
            $smsArr = self::getSmsForMemberCanDraw($condition);
            foreach ($smsArr as $sms) {
                $response = MessageUtil::sendMobileMessage($sms['mobile'], $sms['content'], $condition['accountId']);
                EarlyBirdSmsDetail::createSmsDetail($sms['mobile'], $sms['content'], $response, $smsRecordId, $condition['accountId']);
                if (!$response) {
                    LogUtil::error(['message'=>'EarlyBirdSms發送失敗', 'mobile'=>$sms['mobile'], 'content'=>$sms['content']], 'earlybird');
                    EarlyBirdSmsFailed::createSmsFailed($sms['mobile'], $sms['content'], $smsRecordId, $condition['accountId']);
                }
            }

            EarlyBirdSmsRecord::updateProcessById($smsRecordId, 2);  // 發送完成
        } catch (\Exception $e) {
            LogUtil::error(['message'=>'EarlyBirdSms發送失敗', 'error'=>$e], 'earlybird');
            EarlyBirdSmsRecord::updateProcessById($smsRecordId, 3);  // 發送故障
            throw $e;
        }
    }

    private static function _sendSms($members, $smsTag, $smsRecordId, $accountId)
    {
        try {
            if (!empty($members) && count($members) > 0) {
                for ($i=0; $i<count($members); $i++) {
                    $member = $members[$i];
                    $sms = self::getSms($member, $smsTag, $accountId);

                    if (!empty($sms) && !empty($sms['mobile'])) {
                        $response = MessageUtil::sendMobileMessage($sms['mobile'], $sms['smsContent'], $accountId);
                        EarlyBirdSmsDetail::createSmsDetail($sms['mobile'], $sms['smsContent'], $response, $smsRecordId, $accountId);
                        if (!$response) {
                            LogUtil::error(['message'=>'EarlyBirdSms發送失敗', 'mobile'=>$sms['mobile'], 'name'=>$sms['name']], 'earlybird');
                            EarlyBirdSmsFailed::createSmsFailed($sms['mobile'], $sms['smsContent'], $smsRecordId, $accountId);
                        }
                        unset($sms, $member, $response);
                    }
                }
            }
            return true;
        } catch (\Exception $e) {
            LogUtil::error(['message'=>'EarlyBirdSms發送失敗', 'error'=>$e], 'earlybird');
            EarlyBirdSmsRecord::updateProcessById($smsRecordId, 3);  // 發送故障
            throw $e;
        }
    }

    public static function getSms($member, $smsTag, $accountId)
    {
        $sms = array();
        $smsTemplate = null;

        if (!empty($member)) {
            $properties = $member['properties'];

            if (!empty($properties)) {
                foreach ($properties as $property) {
                    if ($property['name'] == 'tel') {
                        $sms['mobile'] = $property['value'];
                    }
                    if ($property['name'] == 'name') {
                        $sms['name'] = $property['value'];
                    }
                }

                if ($smsTag == 'sms_one') {
                    $member['score'] -= self::getScoreOffset(self::EARLY_BIRD_ONE_TIME, $member['_id'], $accountId); // 計算截至10/29的積分
                    $smsTemplate = self::EARLY_BIRD_ONE_TEMPLATE;
                    // $smsTemplate = MessageTemplate::findOne(['accountId'=>$accountId, 'name'=>self::EARLY_BIRD_ONE_NAME]);
                    // $smsTemplate = $smsTemplate->mobile['message'];
                    $smsTemplate = str_replace("%username%", $sms['name'], $smsTemplate);
                    $sms['smsContent'] = str_replace("%points%", $member['score'], $smsTemplate);
                }
                if ($smsTag == 'sms_two') {
                    $smsTemplate = self::EARLY_BIRD_TWO_TEMPLATE;
                    // $smsTemplate = MessageTemplate::findOne(['accountId'=>$accountId, 'name'=>self::EARLY_BIRD_TWO_NAME]);
                    // $smsTemplate = $smsTemplate->mobile['message'];
                    $sms['smsContent'] = $smsTemplate;
                }
                if ($smsTag == 'sms_three') {
                    $member['score'] -= self::getScoreOffset(self::EARLY_BIRD_THREE_TIME, $member['_id'], $accountId);
                    $smsTemplate = self::EARLY_BIRD_THREE_TEMPLATE;
                    // $smsTemplate = MessageTemplate::findOne(['accountId'=>$accountId, 'name'=>self::EARLY_BIRD_THREE_NAME]);
                    // $smsTemplate = $smsTemplate->mobile['message'];
                    $smsTemplate = str_replace("%username%", $sms['name'], $smsTemplate);
                    $sms['smsContent'] = str_replace("%points%", $member['score'], $smsTemplate);
                }
                unset($properties, $smsTemplate);
            }

            // $smsFile = fopen(Yii::$app->getRuntimePath() . '/temp/sms_test.txt', 'a');
            // fwrite($smsFile, $sms['mobile'] . ': ' . $sms['smsContent'] . "\n");
            // fwrite($smsFile, '----------------------------------------------------------' . "\n \n");
            // fclose($smsFile);
        }
        return $sms;
    }

    private static function _findSmsTargetByCondition($condition)
    {
        return Member::find()->where($condition)->orderBy(['createdAt' => SORT_ASC]);
    }

    public static function createSmsJob($condition, $operator, $smsTag)
    {
        $accountId = Token::getAccountId();
        switch ($smsTag) {
            case 'sms_one': $smsTemplate = self::EARLY_BIRD_ONE_TEMPLATE; break;
            case 'sms_two': $smsTemplate = self::EARLY_BIRD_TWO_TEMPLATE; break;
            case 'sms_three': $smsTemplate = self::EARLY_BIRD_THREE_TEMPLATE; break;
            case 'sms_four': $smsTemplate = self::EARLY_BIRD_FOUR_TEMPLATE; break;
            default: break;
        }
        $recordId = EarlyBirdSmsRecord::createSmsRecord($operator, $smsTag, $smsTemplate);
        $count = 0;

        if (is_bool($recordId) && !$recordId) {
            throw new ServerErrorHttpException("發送失敗，請刷新頁面重試!");
        }
        if (!array_key_exists('accountId', $condition)) {
            $condition = array_merge($condition, ['accountId'=>$accountId]);
        }
        if ($smsTag == 'sms_four') {
            $count = count(self::getMemberCanDraw($condition)); // get發送總量
        } else {
            $count = Member::find()->where($condition)->count();
        }

        $smsRecord = EarlyBirdSmsRecord::findOne($recordId);  // set發送總量
        $smsRecord->total = $count;
        $smsRecord->save();
        unset($smsRecord);

        $args = [
            'condition' => serialize($condition),
            'smsTag' => $smsTag,
            'smsRecord' => (string)$recordId
        ];
        $jobId = Yii::$app->job->create('backend\modules\uhkklp\job\EarlyBirdSendSms', $args);

        if (!empty($jobId)) {
            return ['smsRecordId'=>(string)$recordId, 'count'=>$count];
        } else {
            throw new ServerErrorHttpException("發送失敗，請刷新頁面重試!");
        }
    }

    /**
     * @param $startDate int msTimetamp
     * @param $endDate int msTimetamp
     * @return array (eg: ['560399d6475df4c7378b4572'=>-200, ...] )
     */
    public static function getExchangeGoodsScore($startDate, $endDate, $accountId)
    {
        $startDate = MongodbUtil::msTimetamp2MongoDate($startDate);
        $endDate = MongodbUtil::msTimetamp2MongoDate($endDate);
        $condition = ['brief'=>ScoreHistory::ASSIGNER_EXCHAGE_GOODS, 'createdAt'=>['$gte'=>$startDate, '$lte'=>$endDate], 'accountId'=>$accountId];
        $memberIds = ScoreHistory::distinct('memberId', $condition);
        $histories = ScoreHistory::find()->where($condition)->all();
        $scores = array();
        try {
            if (!empty($histories) && !empty($memberIds)) {
                foreach ($memberIds as $memberId) {
                    $scores[(string)$memberId] = 0;
                    foreach ($histories as $history) {
                        if ($memberId == $history->memberId) {
                            $scores[(string)$memberId] += $history->increment;
                        }
                    }
                }
            }
        } catch (Exception $e) {
            LogUtil::error(['message'=>'Fail to getExchangeGoodsScore', 'exception'=>$e], 'earlybird');
            throw new ServerErrorHttpException('Fail to getExchangeGoodsScore');
        }
        unset($condition, $memberIds, $histories);
        return $scores;
    }

    /**
     * @param $condition array  (抽奖设置的数据)
     * @return array (eg: ['560399d6475df4c7378b4572'=>'太平洋百貨2000商品兌換劵', ...] )
     */
    public static function getMemberCanDraw($condition)
    {
        $memberIdsArray = self::getExchangeGoodsScore($condition['startDate'], $condition['endDate'], $condition['accountId']);
        $members = array();
        foreach ($memberIdsArray as $key => $value) {
            $member = Member::findByPk(new \MongoId($key));
            if (!$member->isDeleted) {
                //拿到符合一等奖条件的人
                if (abs($value) >= $condition['pointsOne']) { //eg: points>=2000
                    $members[$key] = $condition['prizeNameOne'];
                }
                //拿到符合二等奖条件的人
                if (abs($value) < $condition['pointsOne'] && abs($value) >= $condition['pointsTwo']) { //eg: 1000<=points<2000
                    $members[$key] = $condition['prizeNameTwo'];
                }
                //拿到符合三等奖条件的人
                if (abs($value) < $condition['pointsTwo'] && abs($value) >= $condition['pointsThree']) { //eg: 200<=points<1000
                    $members[$key] = $condition['prizeNameThree'];
                }
            }
            unset($member);
        }
        unset($memberIdsArray);
        return $members;
    }

    /**
     * @param $condition array  (抽奖设置的数据)
     * @return array (eg: [['mobile'=>'0933431025','content'=>'..您已獲得7-11超市200商品兌換劵抽獎資格..'], ...] )
     */
    public static function getSmsForMemberCanDraw($condition)
    {
        $members = self::getMemberCanDraw($condition);
        $smsArr = array();
        foreach ($members as $key => $value) {
            $memberId = new \MongoId($key);
            $member = Member::findByPk($memberId);
            if (!empty($member->properties)) {
                $mobile = '';
                $name = '';
                foreach ($member->properties as $property) {
                    if ($property['name'] == 'tel') {
                        $mobile = $property['value'];
                    }
                    if ($property['name'] == 'name') {
                        $name = $property['value'];
                    }
                }
                if (!empty($mobile)) {
                    $smsContent = self::EARLY_BIRD_FOUR_TEMPLATE;
                    $smsContent = str_replace("%username%", $name, $smsContent);
                    $smsContent = str_replace("%prizeName%", $value, $smsContent);
                    $sms['mobile'] = $mobile;
                    $sms['content'] = $smsContent;
                    $smsArr[] = $sms;
                    unset($memberId, $member, $mobile, $name, $smsContent, $sms);
                }
            }
        }
        return $smsArr;
    }

    /**
     * @param $fromDate string (eg:'2015-09-30 23:59:59')
     * @param $memberId MongoId
     * @param $accountId MongoId
     * @return int
     *
     * createdAt: a < createdAt <= b
     */
    public static function getScoreOffset($fromDate, $memberId, $accountId)
    {
        $scoreOffset = 0;
        $time = strtotime($fromDate);
        $fromDate = new \MongoDate($time);
        $now = new \MongoDate();
        $condition = ['accountId'=>$accountId, 'memberId'=>$memberId, 'createdAt'=>['$gt'=>$fromDate, '$lte'=>$now]];
        $histories= ScoreHistory::find()->where($condition)->all();
        if (!empty($histories) && count($histories)>0) {
            foreach ($histories as $history) {
                $scoreOffset += $history->increment;
            }
        }
        unset($time, $fromDate, $now, $condition, $histories, $accountId);
        return $scoreOffset;
    }

    /*public static function createEarlyBirdSmsTemplate($accountId)
    {
        $names = [
            'templateOne' => [
                'name' => self::EARLY_BIRD_ONE_NAME,
                'message' => self::EARLY_BIRD_ONE_TEMPLATE
            ],
            'templateTwo' => [
                'name' => self::EARLY_BIRD_TWO_NAME,
                'message' => self::EARLY_BIRD_TWO_TEMPLATE
            ],
            'templateThree' => [
                'name' => self::EARLY_BIRD_THREE_NAME,
                'message' => self::EARLY_BIRD_THREE_TEMPLATE
            ],
            'templateFour' => [
                'name' => self::EARLY_BIRD_FOUR_NAME,
                'message' => self::EARLY_BIRD_FOUR_NAME
            ]
        ];
        $datas = [];
        foreach ($names as $name) {
            $datas[] = [
                'name' => $name['name'],
                'weChat' => ['templateId' => ''],
                'email' => ['title' => '', 'content' => ''],
                'mobile' => ['message' => $name['message']],
                'accountId' => $accountId,
                'createdAt' => new \MongoDate(),
                'updatedAt' => new \MongoDate(),
            ];
        }
        MessageTemplate::batchInsert($datas);
    }*/

}