<?php
namespace backend\modules\channel\controllers;

use Yii;
use yii\web\ServerErrorHttpException;
use yii\web\BadRequestHttpException;
use yii\helpers\Json;
use backend\utils\TimeUtil;
use backend\utils\StringUtil;
use backend\models\Qrcode;
use backend\behaviors\QrcodeBehavior;

class QrcodeController extends BaseController
{

    /**
     * Query all qrcode
     *
     * <b>Request Type</b>: GET<br/><br/>
     * <b>Request Endpoint</b>:http://{server-domain}/api/channel/qrcodes<br/><br/>
     * <b>Response Content-type</b>: application/json<br/><br/>
     * <b>Summary</b>: This api is used for requesting all qrcode
     * <br/><br/>
     *
     * <b>Request Params</b>:<br/>
     *     channelId: string<br/>
     *     per-page: string<br/>
     *     page: string<br>
     *     orderby: string, {"scanCount":"asc"} or name<br>
     *     <br/><br/>
     *
     * <b>Response Params:</b><br/>
     *     array, json array to querie qrcode detail information<br/>
     *     <br/><br/>
     *
     * <br/><br/>
     *
     * <b>Response Example</b>:<br/>
     * <pre>
     * {
     *   "items": [
     *      {
     *          "id": "54b4770ae4b0494aa8fd30bf",
     *          "accountId": "54add01be4b026aee36dd26e",
     *          "msgType": "TEXT",
     *          "content": "hello world"
     *          "sceneId": 1,
     *          "name": "这只是一个测试",
     *          "type": "EVENT",
     *          "description": "招聘一些人才为公司，以备用",
     *          "ticket": "gQFh8DoAAAAAAAAAASxodHRwOi8vd2VpeGluLnFxLmNvbS9xL3BrampCdVBtVlprc25JVDFHR1RQAAIErHaXVAMEAAAAAA==",
     *          "scanCount": 0,
     *          "subscribeCount": 0,
     *          "createTime": 1421113098304,
     *          "imageUrl": "https://mp.weixin.qq.com/cgi-bin/showqrcode?ticket=gQFh8DoAAAAAAAAAASxodHRwOi8vd2VpeGluLnFxLmNvbS9xL3BrampCdVBtVlprc25JVDFHR1RQAAIErHaXVAMEAAAAAA=="
     *      },
     *      {
     *          "id": "54b4770ae4b0494aa8fd30bf",
     *          "accountId": "54add01be4b026aee36dd26e",
     *          "msgType": "TEXT",
     *          "content": {
     *              "articles": [
     *                  {
     *                      "title": "part1",
     *                      "description": "description2",
     *                      "picUrl": "http://www.baidu.com",
     *                      "sourceUrl": "http://www.baidu.com",
     *                  },
     *                  {
     *                      "title": "part1",
     *                      "description": "description2",
     *                      "picUrl": "http://www.baidu.com",
     *                      "sourceUrl": "http://www.baidu.com",
     *                  }
     *              ]
     *          }
     *          "sceneId": 1,
     *          "name": "这只是一个测试",
     *          "type": "EVENT",
     *          "description": "招聘一些人才为公司，以备用",
     *          "ticket": "gQFh8DoAAAAAAAAAASxodHRwOi8vd2VpeGluLnFxLmNvbS9xL3BrampCdVBtVlprc25JVDFHR1RQAAIErHaXVAMEAAAAAA==",
     *          "scanCount": 0,
     *          "subscribeCount": 0,
     *          "createTime": 1421113098304,
     *          "imageUrl": "https://mp.weixin.qq.com/cgi-bin/showqrcode?ticket=gQFh8DoAAAAAAAAAASxodHRwOi8vd2VpeGluLnFxLmNvbS9xL3BrampCdVBtVlprc25JVDFHR1RQAAIErHaXVAMEAAAAAA=="
     *      },
     *   ],
     *  "_meta": {
     *      "totalCount": 2,
     *      "pageCount": 1,
     *      "currentPage": 1,
     *      "perPage": 20
     *  }
     * }
     * </pre>
     */
    public function actionIndex()
    {
        $query = $this->getQuery();
        $channelId = $this->getChannelId();
        if (empty($channelId)) {
            throw new BadRequestHttpException("Missing channel id");
        }

        unset($query['channelId']);
        $query['pageSize'] = !empty($query['per-page']) ? $query['per-page'] : 20;
        $query['pageNum'] = !empty($query['page']) ? $query['page'] : 1;
        unset($query['per-page']);
        unset($query['page']);

        $raw = Yii::$app->weConnect->getQrcodes($channelId, $query);

        if (array_key_exists('results', $raw)) {
            //add a name for qrcode
            if (!empty($raw['results'])) {
                //get qrcode id
                $qrcodeIds = [];
                foreach ($raw['results'] as $result) {
                    $qrcodeIds[] = $result['id'];
                }

                if (!empty($qrcodeIds)) {
                    //get qrcode name
                    $datas= Qrcode::getQrcodeName($qrcodeIds);
                    foreach ($raw['results'] as &$result) {
                        foreach ($datas as $qrcodeId => $data) {
                            if ($result['id'] == $qrcodeId) {
                                $result['name'] = $data;
                            }
                        }
                    }
                    unset($result, $datas, $data, $qrcodeId, $qrcodeIds);
                }
            }
            return [
                'items' => $raw['results'],
                '_meta' => [
                    'totalCount' => $raw['totalAmount'],
                    'pageCount' => ceil($raw['totalAmount'] / $raw['pageSize']),
                    'currentPage' => $raw['pageNum'],
                    'perPage' => $raw['pageSize']
                ]
            ];
        } else {
            throw new ServerErrorHttpException();
        }
    }

    /**
     * Update qrcode
     *
     * <b>Request Type</b>: PUT<br/><br/>
     * <b>Request Endpoint</b>:http://{server-domain}/api/channel/qrcode/{qrcodeId}<br/><br/>
     * <b>Response Content-type</b>: application/json<br/><br/>
     * <b>Summary</b>: This api is used for update qrcode
     * <br/><br/>
     *
     * <b>Request Params</b>:<br/>
     *     channelId: string<br/>
     *     name: string <br/>
     *     description, string <br/>
     *     msgType: TEXT or NEWS<br/>
     *     content: string, if TEXT<br/>
     *     content.articles: array
     *     <br/><br/>
     *
     * <b>Response Params:</b><br/>
     *     array, json array to querie qrcode detail information<br/>
     *     <br/><br/>
     *
     * <br/><br/>
     *
     * <b>Response Example</b>:<br/>
     * <pre>
     * {
     *  "id": "5473ffe7db7c7c2f0bee5c71",
     *  "accountId": "5473ffe7db7c7c2f0bee5c71",
     *  "replyMessage": {
     *      "msgType": "NEWS",
     *      "articles": [
     *          {
     *              "title": "没有",
     *              "description": "",
     *              "url": "http://vincenthou.qiniudn.com/94b6e2756acb030f6f76f690.jpg",
     *              "content": "<p>哈哈哈哈</p>"
     *          }
     *      ]
     *  },
     *  "sceneId": 1,
     *  "name": "这只是一个测试",
     *  "type": "EVENT",
     *  "description": "招聘一些人才为公司，以备用",
     *  "ticket": "gQFh8DoAAAAAAAAAASxodHRwOi8vd2VpeGluLnFxLmNvbS9xL3BrampCdVBtVlprc25JVDFHR1RQAAIErHaXVAMEAAAAAA==",
     *  "scanCount": 0,
     *  "subscribeCount": 0,
     *  "createTime": 1421113098304,
     *  "imageUrl": "https://mp.weixin.qq.com/cgi-bin/showqrcode?ticket=gQFh8DoAAAAAAAAAASxodHRwOi8vd2VpeGluLnFxLmNvbS9xL3BrampCdVBtVlprc25JVDFHR1RQAAIErHaXVAMEAAAAAA=="
     * }
     * </pre>
     */
    public function actionUpdate($id)
    {
        $qrcode = $this->getParams();
        $channelId = $this->getChannelId();
        unset($qrcode['channelId']);
        if (empty($qrcode['msgType']) || empty($qrcode['content'])) {
            //0 is empty
            unset($qrcode['content'], $qrcode['msgType']);
            $qrcode['type'] = 'CHANNEL';
        } else {
            $qrcode['type'] = 'EVENT';
        }
        $result = Yii::$app->weConnect->updateQrcode($channelId, $id, $qrcode);

        return $result;
    }

    /**
     * Create default rule
     *
     * <b>Request Type</b>: POST<br/><br/>
     * <b>Request Endpoint</b>:http://{server-domain}/api/channel/qrcodes<br/><br/>
     * <b>Response Content-type</b>: application/json<br/><br/>
     * <b>Summary</b>: This api is used to create qrcode.
     * <br/><br/>
     *
     * <b>Request Params</b>:<br/>
     *     channelId: string<br/>
     *     name: string <br/>
     *     description, string <br/>
     *     msgType: TEXT or NEWS<br/>
     *     content: string, if TEXT<br/>
     *     content.articles: array
     *     <br/><br/>
     *
     * <b>Response Params:</b><br/>
     *     array, json array to querie qrcode detail information<br/>
     *     <br/><br/>
     *
     * <br/><br/>
     *
     * <b>Response Example</b>:<br/>
     * <pre>
     * {
     *  "id": "5473ffe7db7c7c2f0bee5c71",
     *  "accountId": "5473ffe7db7c7c2f0bee5c71",
     *  "replyMessage": {
     *      "msgType": "NEWS",
     *      "articles": [
     *          {
     *              "title": "没有",
     *              "description": "",
     *              "url": "http://vincenthou.qiniudn.com/94b6e2756acb030f6f76f690.jpg",
     *              "content": "<p>哈哈哈哈</p>"
     *          }
     *      ]
     *  },
     *  "sceneId": 1,
     *  "name": "这只是一个测试",
     *  "type": "EVENT",
     *  "description": "招聘一些人才为公司，以备用",
     *  "ticket": "gQFh8DoAAAAAAAAAASxodHRwOi8vd2VpeGluLnFxLmNvbS9xL3BrampCdVBtVlprc25JVDFHR1RQAAIErHaXVAMEAAAAAA==",
     *  "scanCount": 0,
     *  "subscribeCount": 0,
     *  "createTime": 1421113098304,
     *  "imageUrl": "https://mp.weixin.qq.com/cgi-bin/showqrcode?ticket=gQFh8DoAAAAAAAAAASxodHRwOi8vd2VpeGluLnFxLmNvbS9xL3BrampCdVBtVlprc25JVDFHR1RQAAIErHaXVAMEAAAAAA=="
     * }
     * </pre>
     */
    public function actionCreate()
    {
        $qrcode = $this->getParams();
        $channelId = $this->getChannelId();

        unset($qrcode['channelId']);
        if (empty($qrcode['msgType']) || empty($qrcode['content'])) {
            //0 is empty
            unset($qrcode['content'], $qrcode['msgType']);
            $qrcode['type'] = 'CHANNEL';
        } else {
            $qrcode['type'] = 'EVENT';
        }

        $result = Yii::$app->weConnect->createQrcode($channelId, $qrcode);
        return $result;
    }

    /**
     * Delete qrcode
     *
     * <b>Request Type</b>: DELETE<br/><br/>
     * <b>Request Endpoint</b>:http://{server-domain}/api/channel/qrcode/{qrcodeId}<br/><br/>
     * <b>Response Content-type</b>: application/json<br/><br/>
     * <b>Summary</b>: This api is used to delete qrcode.
     * <br/><br/>
     *
     * <b>Request Params</b>:<br/>
     *     channelId: string<br/>
     *     <br/><br/>
     *
     * <b>Response Params:</b><br/>
     *     message: string, if enable fail, it contains the error message<br/>
     *     <br/><br/>
     *
     * <br/><br/>
     *
     * <b>Response Example</b>:<br/>
     * <pre>
     * {
     *    "message": "OK"
     * }
     * </pre>
     */
    public function actionDelete($id)
    {
        $channelId = $this->getChannelId();

        //delete qrcode info
        $this->attachBehavior('QrcodeBehavior', new QrcodeBehavior);
        $this->deleteQrcode($channelId, $id);

        $result = Yii::$app->weConnect->deleteQrcode($channelId, $id);
        if ($result) {
            return ['message' => 'OK'];
        } else {
            throw new ServerErrorHttpException('delete qrcode fail.');
        }
    }

    /**
     * View qrcode detail
     *
     * <b>Request Type</b>: GET<br/><br/>
     * <b>Request Endpoint</b>:http://{server-domain}/api/channel/qrcode/{qrcodeId}<br/><br/>
     * <b>Response Content-type</b>: application/json<br/><br/>
     * <b>Summary</b>: This api is used to view qrcode detail.
     * <br/><br/>
     *
     * <b>Request Params</b>:<br/>
     *     channelId: string<br/>
     *     <br/><br/>
     *
     * <b>Response Params:</b><br/>
     *     array, json array to querie qrcode detail information<br/>
     *     <br/><br/>
     *
     * <br/><br/>
     *
     * <b>Response Example</b>:<br/>
     * <pre>
     * {
     *  "id": "5473ffe7db7c7c2f0bee5c71",
     *  "accountId": "5473ffe7db7c7c2f0bee5c71",
     *  "msgType": "NEWS",
     *  "articles": [
     *      {
     *          "title": "没有",
     *          "description": "",
     *          "url": "http://vincenthou.qiniudn.com/94b6e2756acb030f6f76f690.jpg",
     *          "content": "<p>哈哈哈哈</p>"
     *      }
     *  ],
     *  "sceneId": 1,
     *  "name": "这只是一个测试",
     *  "type": "EVENT",
     *  "description": "招聘一些人才为公司，以备用",
     *  "ticket": "gQFh8DoAAAAAAAAAASxodHRwOi8vd2VpeGluLnFxLmNvbS9xL3BrampCdVBtVlprc25JVDFHR1RQAAIErHaXVAMEAAAAAA==",
     *  "scanCount": 0,
     *  "subscribeCount": 0,
     *  "createTime": 1421113098304,
     *  "imageUrl": "https://mp.weixin.qq.com/cgi-bin/showqrcode?ticket=gQFh8DoAAAAAAAAAASxodHRwOi8vd2VpeGluLnFxLmNvbS9xL3BrampCdVBtVlprc25JVDFHR1RQAAIErHaXVAMEAAAAAA=="
     * }
     * </pre>
     */
    public function actionView($id)
    {
        $channelId = $this->getChannelId();
        $result = Yii::$app->weConnect->getQrcode($channelId, $id);
        if (!empty($result)) {
            $datas = Qrcode::getQrcodeName([$id]);
            $result['name'] = isset($datas[$id]) ? $datas[$id] : $result['name'];
        }
        return $result;
    }

    /**
     * Get qrcode statistics information
     *
     * <b>Request Type</b>: GET<br/><br/>
     * <b>Request Endpoint</b>:http://{server-domain}/api/channel/qrcode/statistics<br/><br/>
     * <b>Response Content-type</b>: application/json<br/><br/>
     * <b>Summary</b>: This api is used to view qrcode detail.
     * <br/><br/>
     *
     * <b>Request Params</b>:<br/>
     *     channelId: string<br/>
     *     qrcode: string<br/>
     *     startDate: integer<br/>
     *     endDate: integer<br/>
     *     <br/><br/>
     *
     * <b>Response Params:</b><br/>
     *     array, json array to get qrcode statistics information<br/>
     *     <br/><br/>
     *
     * <br/><br/>
     *
     * <b>Response Example</b>:<br/>
     * <pre>
     * {
     *  "scan": [50, 60, 200, 13, 34, 100, 90, 50, 150, 300],
     *  "subscribe": [351, 316, 29, 963, 292, 72, 52, 501, 772, 472],
     *  "statDate": ["2015-01-10","2015-01-11","2015-01-12","2015-01-13","2015-01-14","2015-01-15","2015-01-16","2015-01-17","2015-01-18","2015-01-19"],
     *  "ydayStat": {
     *      "scan":0,
     *      "subscribe":0,
     *      "totalScan":0,
     *      "totalSubscribe":0
     *  }
     * </pre>
     */
    public function actionStatistics()
    {
        $channelId = $this->getChannelId();
        $qrcodeId = $this->getQuery("qrcodeId");
        $startDate = $this->getQuery("startDate");
        $endDate = $this->getQuery("endDate");

        $result = Yii::$app->weConnect->getQrcodeStatistics($channelId, $qrcodeId, $startDate, $endDate);

        $ydayDate = date('Y-m-d', strtotime('-1 day'));
        $destResult = ['ydayStat' => ['scan' => 0, 'subscribe' => 0, 'totalScan' => 0, 'totalSubscribe' => 0]];

        if (isset($result[$ydayDate])) {
            $ydayData = $result[$ydayDate];
            $destResult['ydayStat']['scan'] = $ydayData['scan'];
            $destResult['ydayStat']['subscribe'] = $ydayData['subscribe'];
            $destResult['ydayStat']['totalScan'] = $ydayData['totalScan'];
            $destResult['ydayStat']['totalSubscribe'] = $ydayData['totalSubscribe'];
        }

        // timezone to add 8 hours
        $startTimestamp = intval(TimeUtil::ms2sTime($startDate)) + 60 * 60 * 8;
        $endTimestamp = intval(TimeUtil::ms2sTime($endDate)) + 60 * 60 * 8;
        for ($recur = $startTimestamp; $recur <= $endTimestamp; $recur += 60 * 60 * 24) {
            $dateItem = date("Y-m-d", $recur);
            if (isset($result[$dateItem])) {
                $destResult['statDate'][] = $dateItem;
                $destResult['scan'][] = $result[$dateItem]['scan'];
                $destResult['subscribe'][] = $result[$dateItem]['subscribe'];
            }
        }
        return $destResult;
    }

    /**
     * Get qrcode yesterday key indicator information
     *
     * <b>Request Type</b>: GET<br/><br/>
     * <b>Request Endpoint</b>:http://{server-domain}/api/channel/qrcode/key-indicator<br/><br/>
     * <b>Response Content-type</b>: application/json<br/><br/>
     * <b>Summary</b>: This api is used to view qrcode yesterday key indicator information
     * <br/><br/>
     *
     * <b>Request Params</b>:<br/>
     *     channelId: string<br/>
     *     qrcodeId: string<br/>
     *     <br/><br/>
     *
     * <b>Response Params:</b><br/>
     *     array, json array to get qrcode key indicator information<br/>
     *     <br/><br/>
     *
     * <br/><br/>
     *
     * <b>Response Example</b>:<br/>
     * <pre>
     * {
     *   "id": "550a0370ad274f0b473fdda8",
     *   "qrcodeId": "55010092e4b0c022a400b7e3",
     *   "accountId": "54fd0571e4b055a0030461fb",
     *   "refDate": 1426608000000,
     *   "scan": 0,
     *   "subscribe": 0,
     *   "unsubscribe": 0,
     *   "totalScan": 4,
     *   "totalSubscribe": 2,
     *   "totalUnsubscribe": 0,
     *   "createTime": 1426719600655
     * }
     * </pre>
     */
    public function actionKeyIndicator()
    {
        $channelId = $this->getChannelId();
        $qrcodeId = $this->getQuery("qrcodeId");

        $result = Yii::$app->weConnect->getQrcodekeyIndicator($channelId, $qrcodeId);
        return $result;

    }

        /**
     * Get qrcode time series statistic information
     *
     * <b>Request Type</b>: GET<br/><br/>
     * <b>Request Endpoint</b>:http://{server-domain}/api/channel/qrcode/time-series<br/><br/>
     * <b>Response Content-type</b>: application/json<br/><br/>
     * <b>Summary</b>: This api is used to view qrcode time series statistic information
     * <br/><br/>
     *
     * <b>Request Params</b>:<br/>
     *     channelId: string<br/>
     *     qrcodeId: string<br/>
     *     startDate: integer<br/>
     *     endDate: integer<br/>
     * <br/><br/>
     *
     * <b>Response Params:</b><br/>
     *     array, json array to get qrcode time series statistic information<br/>
     *     <br/><br/>
     *
     * <br/><br/>
     *
     * <b>Response Example</b>:<br/>
     * <pre>
     *{
     *   "statDate": ["2015-03-12", "2015-03-13", "2015-03-14", "2015-03-15", "2015-03-16", "2015-03-17","2015-03-18"],
     *   "scan": [1, 1, 0, 0, 0, 0, 0],
     *   "subscribe": [1, 1, 0, 0, 0, 0, 0]
     *}
     * </pre>
     */
    public function actionTimeSeries()
    {
        $channelId = $this->getChannelId();
        $qrcodeId = $this->getQuery("qrcodeId");

        $dateCondition = [];
        $dateCondition['startDate'] = $this->getQuery("startDate");
        $dateCondition['endDate'] = $this->getQuery("endDate");
        $dateCondition['type'] = 'SCAN';

        $result = Yii::$app->weConnect->getQrcodeTimeSeries($channelId, $qrcodeId, $dateCondition);
        $destResult = $this->formateResponseData($result, ['scan' => 'scan', 'subscribe' => 'subscribe'], $dateCondition['startDate'], $dateCondition['endDate']);
        return $destResult;
    }

    public function actionExportQrcodeInfo()
    {
        $channelId = $this->getChannelId();
        $qrcodeId = $this->getQuery("qrcodeId");

        $dateCondition = [];
        $dateCondition['startDate'] = $this->getQuery("startDate");
        $dateCondition['endDate'] = $this->getQuery("endDate");
        $dateCondition['type'] = 'SCAN';

        //file name
        $key = Yii::t('channel', 'qrcode_statistics') . '_' . date('YmdHis');
        list($refDate, $scan, $subscribe) = explode(',', Yii::t('channel', 'export_qrcode_header'));
        $exportArgs = [
            'header' => ['refDate' => $refDate, 'scan' => $scan, 'subscribe' => $subscribe],
            'key' => $key,
            'accountId' => (string)$this->getAccountId(),
            'condition' => serialize($dateCondition),
            'channelId' => $channelId,
            'qrcodeId' => $qrcodeId,
            'description' => 'Direct: export qrcode info',
        ];
        $jobId = Yii::$app->job->create('backend\modules\channel\job\ExportQrcodeInfo', $exportArgs);
        return ['result' => 'success', 'message' => 'exporting file', 'data' => ['jobId' => $jobId, 'key' => $key]];
    }
}
