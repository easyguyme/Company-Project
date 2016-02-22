<?php

namespace backend\modules\uhkklp\controllers;

use Yii;
use Yii\base\Exception;
use yii\web\BadRequestHttpException;
use yii\web\ServerErrorHttpException;
use backend\modules\uhkklp\models\ActivityBar;
use backend\modules\uhkklp\models\ActivityPrize;
use backend\modules\uhkklp\models\ActivityUser;
use backend\modules\member\models\Member;
use backend\utils\MongodbUtil;
use backend\utils\LogUtil;

class ActivityUserController extends BaseController
{
    public function actionIndex()
    {
        $params = $this->getQuery();

        if (empty($params['activityId'])) {
            throw new BadRequestHttpException("activityId params missing");
        }

        if (empty($params['currentPage'])) {
            $params['currentPage'] = 1;
            $params['pageSize'] = 10;
        }

        $condition = ['activityId'=>new \MongoId($params['activityId']), 'isDeleted'=>false];
        $count = ActivityUser::getCountByCondition($condition);
        $list = ActivityUser::findList($params['currentPage'], $params['pageSize'], $condition);

        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        return ['count'=>$count, 'list'=>$list];
    }

    public function actionGetActivityName($id)
    {
        if (empty($id)) {
            throw new BadRequestHttpException("activityId params missing, fail to get activity's name");
        }
        $bar = ActivityBar::findOne(new \MongoId($id));
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        return ['activityName'=>$bar->name];
    }

    public function actionExportPrizeStatistic()
    {
        $activityId = $this->getQuery('activityId');
        $activityId = new \MongoId($activityId);
        $accountId = $this->getAccountId(); //MongoId

        if (empty($activityId)) {
            throw new BadRequestHttpException("activityId params missing in exporting prize statistic");
        }

        $condition = ['activityId'=>$activityId, 'accountId'=>$accountId];
        $result = ActivityUser::find()->where($condition)->one();

        if (!empty($result)) {
            $key = '活動獎品統計_' . date('YmdHis');
            $condition = serialize($condition);
            $header = [
                'id' => '獎項代碼',
                'prizeName' => '獎項名稱',
                'count' => '人數',
                'createdAt' => '創建時間',
                'isDeleted' => '是否已被移除'
            ];

            $exportArgs = [
                'key' => $key,
                'header' => $header,
                'accountId' => (string)$accountId,
                'condition' => $condition,
                'description' => 'Direct: export slotgame prize statistic'
            ];
            $jobId = Yii::$app->job->create('backend\modules\uhkklp\job\ExportPrizeStatistic', $exportArgs);
            return ['result' => 'success', 'message' => 'exporting file', 'data' => ['jobId' => $jobId, 'key' => $key]];
        } else {
            LogUtil::error(['message'=>'导出奖品统计表失败', 'reason'=>'没有数据(no data)', 'condition'=>$condition], 'activitybar');
            return ['result' => 'error', 'message' => 'no datas', 'data' => []];
        }
    }

    public function actionExportUserPlayCount()
    {
        $activityId = $this->getQuery('activityId');
        $activityId = new \MongoId($activityId);
        $accountId = $this->getAccountId();

        if (empty($activityId)) {
            throw new BadRequestHttpException("activityId params missing in exporting user play count");
        }

        $condition = ['activityId'=>$activityId, 'accountId'=>$accountId];
        $result = ActivityUser::find()->where($condition)->one();

        if (!empty($result)) {
            $key = '拉霸次數統計表_' . date('YmdHis');
            $condition = serialize($condition);
            $header = [
                'mobile' => '手機號碼',
                'count' => '拉霸次數'
            ];

            $exportArgs = [
                'key' => $key,
                'header' => $header,
                'accountId' => (string)$accountId,
                'condition' => $condition,
                'description' => 'Direct: export numbers of Users play slotgame'
            ];
            $jobId = Yii::$app->job->create('backend\modules\uhkklp\job\ExportUserPlayCount', $exportArgs);
            return ['result' => 'success', 'message' => 'exporting file', 'data' => ['jobId' => $jobId, 'key' => $key]];
        } else {
            LogUtil::error(['message'=>'导出拉霸次数统计表失败', 'reason'=>'没有数据(no data)', 'condition'=>$condition], 'activitybar');
            return ['result' => 'error', 'message' => 'no datas', 'data' => []];
        }
    }

    public function actionExportBarUseRecord()
    {
        $activityId = $this->getQuery('activityId');
        $activityId = new \MongoId($activityId);
        $accountId = $this->getAccountId();

        if (empty($activityId)) {
            throw new BadRequestHttpException("activityId params missing in exporting bar use record");
        }

        $condition = ['activityId'=>$activityId, 'accountId'=>$accountId];
        $result = ActivityUser::find()->where($condition)->one();

        if (!empty($result)) {
            $key = '使用者拉霸記錄表_' . date('YmdHis');
            $condition = serialize($condition);
            $header = [
                'createdAt' => '拉霸時間',
                'mobile' => '手機號碼',
                'prizeContent' => '中獎內容',
                'deviceId' => 'Device ID'
            ];

            $exportArgs = [
                'key' => $key,
                'header' => $header,
                'accountId' => (string)$accountId,
                'condition' => $condition,
                'description' => 'Direct: export slotgame use record'
            ];
            $jobId = Yii::$app->job->create('backend\modules\uhkklp\job\ExportBarUseRecord', $exportArgs);
            return ['result' => 'success', 'message' => 'exporting file', 'data' => ['jobId' => $jobId, 'key' => $key]];
        } else {
            LogUtil::error(['message'=>'导出使用者拉霸记录表失败', 'reason'=>'没有数据(no data)', 'condition'=>$condition], 'activitybar');
            return ['result' => 'error', 'message' => 'no datas', 'data' => []];
        }
    }

    /**
     * Judge whether the user can play games
     *
     * <b>Request Type</b>: GET<br/><br/>
     * <b>Request Endpoint</b>:http://{server-domain}/api/uhkklp/activity-user/can-play<br/><br/>
     * <b>Response Content-type</b>: application/json<br/><br/>
     * <b>Summary</b>: This api is used for judging whether the user can play games.
     * <br/><br/>
     *
     * <b>Request Params</b>:<br/>
     *     mobile: string <br/>
     *     <br/><br/>
     *
     * <b>Response Params:</b><br/>
     *      result: boolean, <br/>
     *      reason: String  <br/>
     *     <br/><br/>
     *
     * <br/><br/>
     *
     * <b>Response Example</b>:<br/>
     *      {
     *           "code": 200,
     *           "msg": "ok",
     *           "result": {
     *               "canPlay": "yes"
     *           }
     *       }
     *  <br/><br/>
     *
     * <b>Error code</b>:<br/>
     *      200: 正常
     *      1000: 缺失参数
     *      1001: 没有上架拉霸或拉霸已过期
     *      1002: 今天已经玩过拉霸
     *      1003: 该手机号码未注册
     * <pre>
     * </pre>
     */
    public function actionCanPlay()
    {
        $code = null;
        $msg = null;
        $canPlay = null;
        $params = $this->getQuery();
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;

        if (empty($params['mobile'])) {
            return ['code'=>1000, 'msg'=>'mobile params missing', 'result'=>['canPlay'=>'no']];
        }

        //判断是否是注册用户
        $member = Member::getByMobile($params['mobile']);
        if (empty($member)) {
            return ['code'=>1003, 'msg'=>'This mobile is not registered', 'result'=>['canPlay'=>'no']];
        }

        $bar = $this->_getCanPlayActivity();
        $user = $this->_getPlayToday($params['mobile']);

        if (empty($bar)) {
            $code = 1001;
            $msg = 'no activity onshelve or out of date';
            $canPlay = 'no';
        } else if (!empty($user)) {
            $code = 1002;
            $msg = 'played today';
            $canPlay = 'no';
        }

        if (!empty($bar) && empty($user)) {
            $code = 200;
            $msg = 'ok';
            $canPlay = 'yes';
        }

        return ['code'=>$code, 'msg'=>$msg, 'result'=>['canPlay'=>$canPlay]];
    }

    /**
     * Get activity infomations
     *
     * <b>Request Type</b>: GET<br/><br/>
     * <b>Request Endpoint</b>:http://{server-domain}/api/uhkklp/activity-user/get-bar<br/><br/>
     * <b>Response Content-type</b>: application/json<br/><br/>
     * <b>Summary</b>: This api is used for getting activity infomations
     * <br/><br/>
     *
     * <b>Request Params</b>:<br/>
     *     none <br/>
     *     <br/><br/>
     *
     * <b>Response Params:</b><br/>
     *    name: 拉霸名称, <br/>
     *    imageUrl: 拉霸主视觉图片, <br/>
     *    rule: 拉霸活动规则 <br/>
     *     <br/><br/>
     *
     * <br/><br/>
     *
     * <b>Response Example</b>:<br/>
     *    {
     *         "code": 200,
     *         "msg": "ok",
     *         "result": {
     *             "name": "拉霸活动",
     *             "imageUrl": "http://vincenthou.qiniudn.com/6dd5871e611b40c613cb150c.jpg",
     *             "rule": "<p><span style=\"text-decoration: underline; background-color: rgb(141, 179, 226);\">活动办法</span></p>"
     *         }
     *     }
     * <b>Error code</b>:<br/>
     *      200: 正常
     *      1001: 没有上架拉霸或拉霸已过期
     * <pre>
     * </pre>
     */
    public function actionGetBar()
    {
        $bar = $this->_getCanPlayActivity();
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;

        if (!empty($bar)) {
            $result = ['name'=>$bar->name, 'imageUrl'=>$bar->mainImgUrl, 'rule'=>$bar->rule];
            return ['code'=>200, 'msg'=>'ok', 'result'=>$result];
        } else {
            return ['code'=>1001, 'msg'=>'no activity onshelve or out of date', 'result'=>[]];
        }
    }

    /**
     * Play slotgame and get the result
     *
     * <b>Request Type</b>: POST<br/><br/>
     * <b>Request Endpoint</b>:http://{server-domain}/api/uhkklp/activity-user/play<br/><br/>
     * <b>Response Content-type</b>: application/json<br/><br/>
     * <b>Summary</b>: This api is used for getting the result of slotgame
     * <br/><br/>
     *
     * <b>Request Params</b>:<br/>
     *     mobile: String, <br/>
     *     deviceId: String <br/>
     *     <br/><br/>
     *
     * <b>Response Params:</b><br/>
     *    hasPrize: 是否中奖, <br/>
     *    prizeName: 奖品名称, <br/>
     *    prizeImage: 奖品图片地址 <br/>
     *     <br/><br/>
     *
     * <br/><br/>
     *
     * <b>Response Example</b>:<br/>
     *   {
     *         "code": 200,
     *         "msg": "ok",
     *         "result": {
     *             "hasPrize": "N",
     *             "prizeName": "銘謝惠顧",
     *             "prizeImage": ""
     *         }
     *     }
     *
     * <b>Error code</b>:<br/>
     *      200: 正常
     *      1000: 缺失参数
     *      1001: 没有上架拉霸或拉霸已过期
     *      1002: 今天已经玩过拉霸
     *      1003: 该手机号码未注册
     * <pre>
     * </pre>
     */
    public function actionPlay()
    {
        $mobile = $this->getParams('mobile');
        $deviceId = $this->getParams('deviceId');
        // $mobile = Yii::$app->request->post('mobile');
        // $deviceId = Yii::$app->request->post('deviceId');

        $bar = $this->_getCanPlayActivity();
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;

        if (empty($mobile)) {
            return ['code'=>1000, 'msg'=>'mobile params missing', 'result'=>[]];
        }
        if (empty($deviceId)) {
            return ['code'=>1000, 'msg'=>'deviceId params missing', 'result'=>[]];
        }

        //判断是否是注册用户
        $member = Member::getByMobile($mobile);
        if (empty($member)) {
            return ['code'=>1003, 'msg'=>'This mobile is not registered', 'result'=>[]];
        }

        $user = $this->_getPlayToday($mobile);

        //正在玩时拉霸被下架
        if (empty($bar)) {
            return ['code'=>1001, 'msg'=>'activity offshelve or out of date', 'result'=>[]];
        }
        //今天已经玩过拉霸(非法用户)
        if (!empty($user)) {
            return ['code'=>1002, 'msg'=>'played today', 'result'=>[]];
        }

        //抽奖
        $result = $this->_prizeDraw($bar->_id, $mobile, $deviceId);
        return $result;
    }

    //查找今天能玩的拉霸
    private function _getCanPlayActivity()
    {
        $accountId = $this->getAccountId();
        $currentTime = strtotime(date('Y-m-d H:i:s'));
        $currentMongoDate = new \MongoDate($currentTime);
        $bar = ActivityBar::findOne([
                                        'status'=>'Y',
                                        'isDeleted'=>false,
                                        'startDate'=>['$lte'=>$currentMongoDate],
                                        'endDate'=>['$gt'=>$currentMongoDate],
                                        'accountId'=>$accountId
                                    ]);
        return $bar;
    }

    //查找今天玩的记录
    private function _getPlayToday($mobile)
    {
        // date_default_timezone_set('Asia/Taipei');
        // $timeStr = MongodbUtil::MongoDate2String(new \MongoDate($today), 'Y-m-d H:i:s', null);
        $accountId = $this->getAccountId();
        $today = strtotime(date('Y-m-d'));
        $endDay = strtotime('+1 day', $today);
        $user = ActivityUser::findOne([
                                        'mobile'=>$mobile,
                                        'createdAt'=>['$gte'=>new \MongoDate($today), '$lt'=>new \MongoDate($endDay)],
                                        'accountId'=>$accountId
                                      ]);
        return $user;
    }

    //抽奖  $activityId: MongoId
    private function _prizeDraw($activityId, $mobile, $deviceId)
    {
        $bar = ActivityBar::findOne($activityId);

        $probalility = $bar->probability; //未中奖几率
        $drawArr = array(); //抽奖阵列

        // 加入中奖的比率
        $i = 100 - (int)$probalility;
        while ($i > 0) {
            $drawArr[] = 'Y';
            $i--;
        }

        // 加入沒中奖的比率
        $i = (int)$probalility;
        while ($i > 0) {
            $drawArr[] = 'N';
            $i--;
        }

        shuffle($drawArr);   // 抽奖 (随机打乱阵列)
        $hasPrize = $drawArr[0];  // 抽出其中一个看是否中奖

        $resultPrize = null;
        $thankPrize = array('name' => "銘謝惠顧", 'prizeImgUrl'=>"", '_id'=>'thanks');

        //给中奖用户抽奖品
        if ($hasPrize == 'Y') {
            $prizeList = ActivityPrize::getValidPrizesByActivityId($activityId);

            if (!empty($prizeList)) {
                $prizeArr = array();  //奖品阵列
                $today = strtotime(date('Y-m-d H:i:s'));
                $todayMstime = MongodbUtil::MongoDate2msTimeStamp(new \MongoDate($today));

                foreach ($prizeList as $prize) {
                    if ($prize['type'] == 'topPrize') {
                        if ($prize['startDate'] <= $todayMstime and $todayMstime < $prize['endDate']) {
                            $prizeArr[] = $prize;
                        }
                    } else {
                        $prizeArr[] = $prize;
                    }
                }
                unset($prizeList);

                shuffle($prizeArr);
                $resultPrize = $prizeArr[0];

            } else { //奖项列表为空，改发鸣谢惠顾
                $hasPrize = 'N';
                $resultPrize = $thankPrize;
            }

        } else {
            $resultPrize = $thankPrize;
        }

        //判断奖品数量是否足够
        $activityPrize = ActivityPrize::findOne($resultPrize['_id']);

        if (!empty($activityPrize)) {
            $quantity = $activityPrize->quantity - 1;
            $activityPrize->quantity = $quantity;

            if ($quantity >= 0) {
                if (!$activityPrize->save()) {  //更新奖品数量(减1), 失敗,改发鸣谢惠顾
                    $hasPrize = 'N';
                    $resultPrize = $thankPrize;
                }
            } else {  //奖品不够，改发鸣谢惠顾
                $hasPrize = 'N';
                $resultPrize = $thankPrize;
            }
        }

        if ($resultPrize['_id'] != 'thanks' and $resultPrize['isPoint'] == 'Y') {
            //add points
            try {
                $service = Yii::$app->service;
                $service->accountId = $this->getAccountId();
                $member = Member::getByMobile($mobile, $this->getAccountId());
                $rewardResult = $service->member->rewardScore([$member['_id']], $resultPrize['points'], '拉霸活動中獎');

                if (is_bool($rewardResult) && $rewardResult) {
                    $resultPrize['name'] = $resultPrize['name'] . '(已發獎' . $resultPrize['points'] . '積分)';
                } else {
                    $hasPrize = 'N';
                    $resultPrize = $thankPrize; //Failed，改发鸣谢惠顾
                }
                unset($service, $member, $rewardResult);

            } catch (Exception $e) {
                $hasPrize = 'N';
                $resultPrize = $thankPrize;
                LogUtil::error(['message'=>'获奖者加积分捕获未知错误', 'result'=>'改发鸣谢惠顾', 'mobile'=>$mobile, 'exception'=>$e], 'activitybar');
            }
        }

        //save in DB
        $params = array(
                  'activityId' => $activityId,
                  'prizeId' => $resultPrize['_id'],
                  'deviceId' => $deviceId,
                  'mobile' => $mobile,
                  'prizeContent' => $resultPrize['name']
                );
        $isOK = ActivityUser::createUser($params);

        if (!$isOK) {
            $hasPrize = 'N';
            $resultPrize = $thankPrize;
        }

        $result = ['hasPrize'=>$hasPrize, 'prizeName'=>$resultPrize['name'], 'prizeImage'=>$resultPrize['prizeImgUrl']];
        unset($thankPrize, $resultPrize, $params);
        return ['code'=>200, 'msg'=>'ok', 'result'=>$result];
    }

}