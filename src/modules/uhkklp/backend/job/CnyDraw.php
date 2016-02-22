<?php
namespace backend\modules\uhkklp\job;

use Yii;
use Yii\base\Exception;
use backend\modules\product\models\CampaignLog;
use backend\modules\uhkklp\models\LuckyDrawRecord;
use backend\modules\uhkklp\models\LuckyDrawWinner;
use backend\modules\uhkklp\models\Activity;
use yii\web\BadRequestHttpException;
use yii\web\ServerErrorHttpException;
use backend\modules\resque\components\ResqueUtil;

class CnyDraw
{
    public function perform()
    {
        $args = $this->args;
        if (empty($args['accountId']) || empty($args['params'])) {
            ResqueUtil::log(['status' => 'fail to lucky draw', 'message' => 'missing params', 'args' => $args]);
            $this->_changeDrawProcess(new \MongoId($args['recordId']), 'error: missing params');
            return false;
        }
        $params = $args['params'];
        $accountId = new \MongoId($args['accountId']);
        $recordId = new \MongoId($args['recordId']);
        if (empty($params['prizes'][0]['quantity'])) {
            $this->_changeDrawProcess($recordId, 'error: missing params');
            throw new BadRequestHttpException("Miss params.");
        }
        $activity = Activity::getActivityByName('cny', $accountId);
        if (empty($activity)) {
            $this->_changeDrawProcess($recordId, 'Get activity-CNY failed');
            throw new ServerErrorHttpException("Get activity-CNY failed");
        }
        $condition = ['accountId'=>$accountId, 'redeemTime'=>['$gte'=>$activity->startDate, '$lte'=>$activity->endDate]];
        $members = $this->_getCanDrawMembers($condition, $activity->luckyDrawInfo['needPoints']);
        // if (empty($members)) {
        //     throw new BadRequestHttpException("No data.");
        // }
        unset($args, $activity, $condition);

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
                        'accountId' => $accountId,
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
        
        $this->_changeDrawProcess($recordId, 'ok');
        return true;
    }

    private function _changeDrawProcess($recordId, $process) {
        $currentRecord = LuckyDrawRecord::findOne($recordId);
        $remark = $currentRecord->remark;
        $remark['drawProcess'] = $process;
        $currentRecord->remark = $remark;
        $currentRecord->save();
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
    private function _getCanDrawMembers($condition, $pointsPerOdds)
    {
        $mobiles = CampaignLog::distinct('member.phone', $condition);
        $redeemRecords = CampaignLog::find()->where($condition)->all();

        $members = array();  // mobile, scoreAdded, canDouble
        $mobilesCount = count($mobiles);
        for ($i=0; $i<$mobilesCount; $i++) {
            $members[$i] = ['mobile'=>$mobiles[$i], 'scoreAdded'=>0, 'canDouble'=>false];
            $checkDouble = array();
            foreach ($redeemRecords as $redeemRecord) {
                $product = $redeemRecord['productName'];

                if ($mobiles[$i] == $redeemRecord['member']['phone']) {
                    $members[$i]['name'] = $redeemRecord['member']['name'];

                    if ($redeemRecord['member']['type'] == 'score') {
                        $members[$i]['scoreAdded'] += $redeemRecord['member']['scoreAdded'];
                    }

                    //判断是否购买全部3支品项
                    if (!$members[$i]['canDouble']) {
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
                            $members[$i]['canDouble'] = true;
                        }
                    }
                }
                unset($product);
            }
            unset($checkDouble);
        }
        unset($mobiles, $redeemRecords, $mobilesCount);

        foreach ($members as $key => $value) {
            if ($members[$key]['canDouble']) {
                $members[$key]['odds'] = intval($members[$key]['scoreAdded'] / $pointsPerOdds)* 2;
            } else {
                $members[$key]['odds'] = intval($members[$key]['scoreAdded'] / $pointsPerOdds);
            }
            if ($members[$key]['odds'] < 1) {
                unset($members[$key]);
            }
            // unset($members[$key]['canDouble']);
        }
        return $members;
    }

    /**
     * @param $members _getCanDrawMembers()的返回值
     * @return $drawArr  eg:["0984757748"=>15, "0933431025"=>13, "0912345678"=>12, ...]
     * 抽奖算法： 根据用户入点数得到的概率权重odds，生成个人权重到总权重之间的随机数,从大到小排列
     */
    private function _draw($members)
    {
        $drawArr = array();
        foreach ($members as $key => $value) {
            $drawArr[$members[$key]['mobile']] = $members[$key]['odds'];
        }
        $oddsSum = array_sum($drawArr);
        foreach ($drawArr as $key => $value) {
            $drawArr[$key] = mt_rand($value, $oddsSum);
        }
        arsort($drawArr);
        return $drawArr;
    }
}
