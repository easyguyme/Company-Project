<?php
namespace backend\modules\uhkklp\controllers;

use Yii;
use Yii\base\Exception;
use yii\web\BadRequestHttpException;
use yii\web\ServerErrorHttpException;
use backend\utils\LogUtil;
use backend\utils\MongodbUtil;
use backend\modules\uhkklp\models\Activity;
use backend\modules\member\models\Member;
use backend\modules\product\models\CampaignLog;
use backend\utils\MessageUtil;
use backend\modules\uhkklp\utils\BulkSmsUtil;
use backend\modules\uhkklp\models\SmsLog;

class WebhookController extends BaseController
{

    public function actionCnyPromotion()
    {
        $params = $this->getParams('data', null);
        $accountId = new \MongoId($params['account_id']);

        if (empty($params) || empty($params['member_id']) || empty($params['type']) || empty($params['score']) || empty($accountId)) {
            throw new BadRequestHttpException("params are missing.");
        }
        if ($params['type'] != 'promotion_code_redeemed') {
            Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
            return ['message'=>'Not promotion_code_redeemed'];
        }

        //get memberInfo
        $member = Member::findByPk(new \MongoId($params['member_id']));
        $memberInfo = [
            'addScore'=>$params['score'],
            'id'=>new \MongoId($params['member_id']),
            'score' => $member->score
        ];
        if (!empty($member->properties)) {
            foreach ($member->properties as $propertie) {
                if ($propertie['name'] == 'name') {
                    $memberInfo['name'] = $propertie['value'];
                }
                if ($propertie['name'] == 'tel') {
                    $memberInfo['mobile'] = $propertie['value'];
                }
            }
        }
        unset($member, $params);

        // get CNY Info
        $activity = Activity::findOne(['name'=>'cny', 'accountId'=>$accountId]);
        if (empty($activity)) {
            throw new ServerErrorHttpException("Get CNY information failed or No CNY");
        }
        $needPoints = $activity->luckyDrawInfo['needPoints'];
        $drawDates = $activity->luckyDrawInfo['drawDate'];
        $conditionForOdds = ['member.id'=>$memberInfo['id'],
                             'redeemTime'=>['$gte'=>$activity->startDate, '$lte'=>$activity->endDate]
                            ];
        unset($activity);

        // get day
        // sort($drawDates);
        // $currentDate = new \mongoDate();
        // $targetDate = null;
        // $day = 0;
        // foreach ($drawDates as $drawDate) {
        //     if ($drawDate > $currentDate) {
        //         $targetDate = $drawDate;
        //         break;
        //     }
        // }
        // if (!empty($targetDate)) {
        //     $offsetTime = MongodbUtil::MongoDate2msTimeStamp($targetDate) - MongodbUtil::MongoDate2msTimeStamp($currentDate);
        //     $day = ceil($offsetTime / (1000 * 60 * 60 * 24));
        //     unset($drawDates, $currentDate, $targetDate);
        // }

        // get odds
        $oddsCount = 0;
        $checkDouble = [];
        $canDouble = false;
        $redeemRecords = CampaignLog::find()->where($conditionForOdds)->all();
        if (!empty($redeemRecords)) {
            foreach ($redeemRecords as $redeemRecord) {
                $product = $redeemRecord['productName'];
                $oddsCount += $redeemRecord['member']['scoreAdded'];
                if (!$canDouble) {
                    if ($product == '2015 雞粉2.2kg' || $product == '2015 雞粉1.1kg' || $product == '2016 康寶雞粉 1.1KG' || $product == '2016 康寶雞粉 2.2KG') {
                        $checkDouble['chickenPowder'] = true;
                    }
                    if ($product == '2015 鮮雞汁' || $product == '2016 康寶濃縮鮮雞汁') {
                        $checkDouble['chickenJuice'] = true;
                    }
                    if ($product == '2015 鰹魚粉1kg' || $product == '2015 鰹魚粉1.5kg' || $product == '2016 康寶鰹魚粉 1KG' || $product == '2016 康寶鰹魚粉 1.5KG') {
                        $checkDouble['fishmeal'] = true;
                    }

                    if (count($checkDouble) == 3) {
                        $canDouble = true;
                    }
                }
                unset($product);
            }
        }
        $oddsCount = intval($oddsCount / $needPoints);
        if ($canDouble) {
            $oddsCount = $oddsCount * 2;
        }
        unset($checkDouble, $canDouble, $needPoints, $redeemRecords, $conditionForOdds);

        //$memberInfo:id,name,mobile,addScore,score; $day; $oddsCount
        $mobile = BulkSmsUtil::processSmsMobile($accountId, $memberInfo['mobile']);
        $smsContent = null;

        $currentDate = MongodbUtil::MongoDate2msTimeStamp(new \mongoDate());
        $topPrizeDate = MongodbUtil::MongoDate2msTimeStamp(new \MongoDate(strtotime("2016-02-29 00:00:00")));
        $offsetDay = ceil(($topPrizeDate - $currentDate) / (1000 * 60 * 60 * 24));

        if ($offsetDay > 10) {  // 還沒倒計時
            $smsContent = $memberInfo['name'] . '您好,您郵寄的點數已入點完成，此次共入點'
                      . $memberInfo['addScore'] . '點，您目前點數為'
                      . $memberInfo['score'] . '點。恭喜您同時累積活動『年年好味不能沒有你』'
                      . $oddsCount . '次抽獎機會，累積點數越多，中獎機會越大，詳細活動辦法請見http://bit.ly/1P4yZEA';
        } elseif ($offsetDay > 3 && $offsetDay < 11) {
            $smsContent = $memberInfo['name'] . '您好,您郵寄的點數已入點完成，此次共入點'
                      . $memberInfo['addScore'] . '點，您目前點數為'
                      . $memberInfo['score'] . '點。恭喜您同時累積活動『年年好味不能沒有你』'
                      . $oddsCount . '次抽獎機會，距離30萬元紅包抽獎，只剩'
                      . $offsetDay . '天，詳細活動辦法請見http://bit.ly/1P4yZEA';
        } elseif ($offsetDay >= 0 && $offsetDay <= 3) {
            $smsContent = $memberInfo['name'] . '您好,您郵寄的點數已入點完成，此次共入點'
                      . $memberInfo['addScore'] . '點，您目前點數為'
                      . $memberInfo['score'] . '點。恭喜您同時累積活動『年年好味不能沒有你』'
                      . $oddsCount . '次抽獎機會，距離30萬元紅包抽獎，倒數'
                      . abs($offsetDay) . '天，詳細活動辦法請見http://bit.ly/1P4yZEA';
        }

        Smslog::createSmsLog($mobile, $smsContent, 'CNY webhook SMS', 'sending', $accountId);
        MessageUtil::sendMobileMessage($mobile, $smsContent, $accountId);

        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        return ['mobile'=>$mobile, 'smsContent'=>$smsContent];
    }

}