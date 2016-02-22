<?php
namespace backend\modules\analytic\controllers;

use Yii;
use yii\web\BadRequestHttpException;
use yii\web\ServerErrorHttpException;

class FollowerController extends BaseController
{

    /**
     * Query follower growth statistics
     *
     * <b>Request Type</b>: GET<br/><br/>
     * <b>Request Endpoint</b>:http://{server-domain}/api/dashboard/followers<br/><br/>
     * <b>Response Content-type</b>: application/json<br/><br/>
     * <b>Summary</b>: This api is used for querying follower growth statistics
     * <br/><br/>
     *
     * <b>Request Params</b>:<br/>
     *     channelId: string, id of the channel<br/>
     *     startDate: string, required
     *     endDate: string, required
     *     <br/><br/>
     *
     * <b>Response Params:</b><br/>
     *     <br/><br/>
     *
     * <br/><br/>
     *
     * <b>Response Example</b>:<br/>
     * <pre>
     * {
     *      "items": [
     *          {
     *              "id": "54a14dcde4b04115e33c9eb2",
     *              "accountId": "548116bb978ca81ec68d593b",
     *              "refDate": "2014-12-29",
     *              "newUser": 16,
     *              "cancelUser": 5,
     *              "netIncrease": 11,
     *              "cumulateUser": 403,
     *              "userSourceStatistics": [
     *                  {
     *                      "sourceType": "QRCODE_SCAN",
     *                      "newUser": 2,
     *                      "cancelUser": 2,
     *                      "netIncrease": 2
     *                  },
     *                  {
     *                      "sourceType": "ID_SEARCH",
     *                      "newUser": 2,
     *                      "cancelUser": 2,
     *                      "netIncrease": 2
     *                  }
     *              ]
     *          },
     *          {
     *              "id": "54a14dcde4b04115e33c9eb2",
     *              "accountId": "548116bb978ca81ec68d593b",
     *              "refDate": "2014-12-29",
     *              "newUser": 16,
     *              "cancelUser": 5,
     *              "netIncrease": 11,
     *              "cumulateUser": 403,
     *              "userSourceStatistics": [
     *                  {
     *                      "sourceType": "QRCODE_SCAN",
     *                      "newUser": 2,
     *                      "cancelUser": 2,
     *                      "netIncrease": 2
     *                  },
     *                  {
     *                      "sourceType": "ID_SEARCH",
     *                      "newUser": 2,
     *                      "cancelUser": 2,
     *                      "netIncrease": 2
     *                  }
     *              ]
     *          },
     *          {
     *              "id": "54a14dcde4b04115e33c9eb2",
     *              "accountId": "548116bb978ca81ec68d593b",
     *              "refDate": "2014-12-29",
     *              "newUser": 16,
     *              "cancelUser": 5,
     *              "netIncrease": 11,
     *              "cumulateUser": 403,
     *              "userSourceStatistics": [
     *                  {
     *                      "sourceType": "QRCODE_SCAN",
     *                      "newUser": 2,
     *                      "cancelUser": 2,
     *                      "netIncrease": 2
     *                  },
     *                  {
     *                      "sourceType": "ID_SEARCH",
     *                      "newUser": 2,
     *                      "cancelUser": 2,
     *                      "netIncrease": 2
     *                  }
     *              ]
     *          }
     *      ]
     *  }
     * </pre>
     */
    public function actionIndex()
    {
        $query = $this->getQuery();

        $accountId = $this->getQuery('channelId');
        if (!$accountId) {
            throw new BadRequestHttpException('Missing channel id');
        }
        unset($query['channelId']);

        $result = Yii::$app->weConnect->getUsersGrowthStatistics($accountId, $query);

        return ['items' => $result];
    }

    /**
     * Query follower growth statistics yesterday
     *
     * <b>Request Type</b>: GET<br/><br/>
     * <b>Request Endpoint</b>:http://{server-domain}/api/dashboard/follower/yesterday<br/><br/>
     * <b>Response Content-type</b>: application/json<br/><br/>
     * <b>Summary</b>: This api is used for querying follower growth statistics yesterday
     * <br/><br/>
     *
     * <b>Request Params</b>:<br/>
     *     channelId: string, id of the channel<br/>
     *     <br/><br/>
     *
     * <b>Response Params:</b><br/>
     *     <br/><br/>
     *
     * <br/><br/>
     *
     * <b>Response Example</b>:<br/>
     * <pre>
     * {
     *      "id": "54a14dcde4b04115e33c9eb2",
     *      "accountId": "548116bb978ca81ec68d593b",
     *      "refDate": "2014-12-29",
     *      "userSourceStatistices": [
     *           {
     *               "userSourceType": "OTHER",
     *               "newUser": 3,
     *               "cancelUser": 5,
     *               "netUser": -2
     *           },
     *           {
     *               "userSourceType": "ID_SEARCH",
     *               "newUser": 1,
     *               "cancelUser": 0,
     *               "netUser": 1
     *           }
     *       ],
     *      "newUser": 16,
     *      "cancelUser": 5,
     *      "netIncrease": 11,
     *      "cumulateUser": 403,
     *      "newUserDay": "NaN",
     *      "newUserWeek": "NaN",
     *      "newUserMonth": "NaN",
     *      "cancelUserDay": "NaN",
     *      "cancelUserWeek": "NaN",
     *      "cancelUserMonth": "NaN",
     *      "netIncreaseDay": "NaN",
     *      "netIncreaseWeek": "NaN",
     *      "netIncreaseMonth": "NaN",
     *      "cumulateUserDay": "NaN",
     *      "cumulateUserWeek": "NaN",
     *      "cumulateUserMonth": "NaN"
     *  }
     * </pre>
     */
    public function actionYesterday()
    {
        $accountId = $this->getQuery('channelId');
        if (!$accountId) {
            throw new BadRequestHttpException('Missing channel id');
        }

        return Yii::$app->weConnect->getUsersGrowthStatisticsByYesterday($accountId);
    }

    /**
     * Get users growth statistics information
     *
     * <b>Request Type</b>: GET<br/><br/>
     * <b>Request Endpoint</b>:http://{server-domain}/api/dashboard/follower/statistics<br/><br/>
     * <b>Response Content-type</b>: application/json<br/><br/>
     * <b>Summary</b>: This api is used to querying follower growth statistics.
     * <br/><br/>
     *
     * <b>Request Params</b>:<br/>
     *     accountId: string<br/>
     *     type: string:
     *         wechat ['NEW'(新增), 'CANCEL'(取关), 'INCREASE'(净增), 'CUMULATE'(累计)], required <br/>
     *         weibo ''CUMULATE'(粉丝总量)', 'NET(粉丝日增长量)'
     *     subType: string:
     *         wechat ['CARD_SHARE'(名片分享), 'ID_SEARCH'(搜号码), 'NAME_SEARCH'(查询微信公众账号), 'MPNEWS'(图文页右上角菜单), 'OTHER'(扫二维码及其他), 'UNKNOWN'(未知渠道)], default all<br/>
     *         weibo none
     *     startDate: integer<br/>
     *     endDate: integer<br/>
     *     <br/><br/>
     *
     * <b>Response Params:</b><br/>
     *     array, json array to get users growth statistics information<br/>
     *     <br/><br/>
     *
     * <br/><br/>
     *
     * <b>Response Example</b>:<br/>
     * <pre>
     * {
     *      "userCount": [351, 316, 29, 963, 292, 72, 52, 501, 772, 472],
     *      "statDate": ["2015-01-10","2015-01-11","2015-01-12","2015-01-13","2015-01-14","2015-01-15","2015-01-16","2015-01-17","2015-01-18","2015-01-19"],
     *  }
     * </pre>
     */
    public function actionStatistic()
    {
        $accountId = $this->getQuery('channelId');
        $startDate = $this->getQuery("startDate");
        $endDate = $this->getQuery("endDate");
        $type = $this->getQuery("type");
        $subType = $this->getQuery("subType");

        if (!$accountId) {
            throw new BadRequestHttpException('Missing channel id');
        }
        if (empty($type) || empty($startDate) || empty($endDate)) {
            throw new BadRequestHttpException('Missing param');
        }

        $condition = array();
        $condition['startDate'] = $startDate;
        $condition['endDate'] = $endDate;
        $condition['type'] = $type;

        if (!empty($subType)) {
            $condition['subType'] = $subType;
        }

        $result = Yii::$app->weConnect->getFollowersGrowthStatistics($accountId, $condition);
        $destResult = $this->formateResponseData($result, ['userCount' => 'count'], $startDate, $endDate);

        return $destResult;
    }

    /**
     * Get users growth statistics information
     *
     * <b>Request Type</b>: GET<br/><br/>
     * <b>Request Endpoint</b>:http://{server-domain}/api/dashboard/follower/statistics<br/><br/>
     * <b>Response Content-type</b>: application/json<br/><br/>
     * <b>Summary</b>: This api is used to querying follower growth statistics.
     * <br/><br/>
     *
     * <b>Request Params</b>:<br/>
     *     channelIds: array, the channel id list<br/>
     *     type: string:
     *         wechat ['NEW'(新增), 'CANCEL'(取关), 'INCREASE'(净增), 'CUMULATE'(累计)], required <br/>
     *         weibo ''CUMULATE'(粉丝总量)', 'NET(粉丝日增长量)'
     *     subType: string:
     *         wechat ['CARD_SHARE'(名片分享), 'ID_SEARCH'(搜号码), 'NAME_SEARCH'(查询微信公众账号), 'MPNEWS'(图文页右上角菜单), 'OTHER'(扫二维码及其他), 'UNKNOWN'(未知渠道)], default all<br/>
     *         weibo none
     *     startDate: integer<br/>
     *     endDate: integer<br/>
     *     <br/><br/>
     *
     * <b>Response Params:</b><br/>
     *     array, json array to get each channel users growth statistics information<br/>
     *     <br/><br/>
     *
     * <br/><br/>
     *
     * <b>Response Example</b>:<br/>
     * <pre>
     * {
     *      "54d9c155e4b0abe717853ee1": {"2015-02-28":0},
     *      "54dab42de4b07ae8cd725287": {"2015-02-24":0,"2015-02-25":0,"2015-02-26":0,"2015-02-27":0,"2015-02-28":0,"2015-03-01":0,"2015-03-02":0},
     *      "54dbfbace4b09d7f7799e96b":{"2015-02-24":0,"2015-02-25":0,"2015-02-26":0,"2015-02-27":0,"2015-02-28":0,"2015-03-01":0,"2015-03-02":0},
     *      "54f15555e4b0de4c0197353d":{"2015-02-28":0},
     *      "54dbfc44e4b09d7f7799e96d":{"2015-02-24":0,"2015-02-25":0,"2015-02-26":0,"2015-02-27":0,"2015-02-28":0,"2015-03-01":0,"2015-03-02":0},
     *      "54e1a24ce4b02c78eb8d3753":{"2015-02-25":1,"2015-02-26":0,"2015-02-27":2,"2015-02-28":0,"2015-03-01":0,"2015-02-24":3}}
     *  }
     * </pre>
     */
    public function actionStatistics()
    {
        $accountIds = $this->getQuery('channelIds');
        $startDate = $this->getQuery("startDate");
        $endDate = $this->getQuery("endDate");
        $type = $this->getQuery("type");
        $subType = $this->getQuery("subType");

        if (!$accountIds) {
            throw new BadRequestHttpException('Missing channel id');
        }
        if (empty($type) || empty($startDate) || empty($endDate)) {
            throw new BadRequestHttpException('Missing param');
        }

        $condition = array();
        $condition['startDate'] = $startDate;
        $condition['endDate'] = $endDate;
        $condition['type'] = $type;

        if (!empty($subType)) {
            $condition['subType'] = $subType;
        }

        $resultArr = [];
        foreach ($accountIds as $accountId) {
            $result = Yii::$app->weConnect->getFollowersGrowthStatistics($accountId, $condition);
            foreach ($result as $item) {
                $dateItem = date("Y-m-d", $item['refDate'] / 1000 + 8 * 60 * 60);
                $resultArr[$accountId][$dateItem] = $item['count'];
            }
        }

        return $resultArr;
    }

    /**
     * Get user property statistics
     *
     * <b>Request Type</b>: GET<br/><br/>
     * <b>Request Endpoint</b>:http://{server-domain}/api/dashboard/follower/location<br/><br/>
     * <b>Response Content-type</b>: application/json<br/><br/>
     * <b>Summary</b>: This api is used to get user statistics by location.
     * <br/><br/>
     *
     * <b>Request Params</b>:<br/>
     *     accountId: string<br/>
     *     property: string, required, 'province', 'city'<br/>
     *     parentCountry: string, default '中国'
     *     parentProvince: string
     *     per-page: int, default 8
     *     page: int, default 1
     *     <br/><br/>
     *
     * <b>Response Params:</b><br/>
     *     array, json array to get users statistics information by property<br/>
     *     <br/><br/>
     *
     * <br/><br/>
     *
     * <b>Response Example</b>:<br/>
     * <pre>
     * {
     *      "items": [
     *           {
     *               "value": "南京",
     *               "count": 1,
     *               "percentage": 7.6923075
     *           },
     *           {
     *               "value": "南通",
     *               "count": 1,
     *               "percentage": 7.6923075
     *           }
     *        ],
     *       "_meta": {
     *           "totalCount": 2,
     *           "pageCount": 1,
     *           "currentPage": 1,
     *           "perPage": 8
     *       }
     *  }
     * </pre>
     */
    public function actionLocation()
    {
        $accountId = $this->getQuery('channelId');
        $property = $this->getQuery("property");
        $query = $this->getQuery();

        if (!$accountId) {
            throw new BadRequestHttpException('Missing channel id');
        }
        if (empty($property)) {
            throw new BadRequestHttpException('Missing param');
        }

        if (isset($query['per-page'])) {
            $query['pageSize'] = $query['per-page'];
        }
        if (isset($query['page'])) {
            $query['pageNum'] = $query['page'];
        }

        unset($query['per-page'], $query['page'], $query['property'], $query['channelId']);

        $raw = Yii::$app->weConnect->getUsersByLocation($accountId, $query, $property);

        if (isset($raw['totalAmount'])) {
            return [
                'items' => !empty($raw['results']) ? $raw['results'] : [],
                '_meta' => [
                    'totalCount' => $raw['totalAmount'],
                    'pageCount' => ceil($raw['totalAmount'] / $raw['pageSize']),
                    'currentPage' => $raw['pageNum'],
                    'perPage' => $raw['pageSize']
                ]
            ];
        } else {
            return [
                'items' => !empty($raw) ? $raw : [],
                '_meta' => [
                    'totalCount' => 0
                ]
            ];
        }
    }

    /**
     * Get user property statistics
     *
     * <b>Request Type</b>: GET<br/><br/>
     * <b>Request Endpoint</b>:http://{server-domain}/api/dashboard/follower/property<br/><br/>
     * <b>Response Content-type</b>: application/json<br/><br/>
     * <b>Summary</b>: This api is used to get user statistics by property.
     * <br/><br/>
     *
     * <b>Request Params</b>:<br/>
     *     accountId: string<br/>
     *     property: string,
     *         wechat 'gender', 'language', 'subscribeSource'<br/>
     *         weibo 'gender', 'language', 'userFansCountDist(用户粉丝数分布)'<br/>
     *     <br/><br/>
     *
     * <b>Response Params:</b><br/>
     *     array, json array to get users statistics information by property<br/>
     *     <br/><br/>
     *
     * <br/><br/>
     *
     * <b>Response Example</b>:<br/>
     * <pre>
     * wechat gender
     * {
     *      "items":
     *          [
     *              {
     *                   "value": "MALE",
     *                   "count": 5,
     *                   "percentage": 50
     *               },
     *               {
     *                  "value": "FEMALE",
     *                   "count": 3,
     *                   "percentage": 30
     *               },
     *               {
     *                   "value": "UNKNOWN",
     *                   "count": 2,
     *                   "percentage": 20
     *               }
     *          ]
     *  }
     *  weibo userFansCountDist:
     *  {
     *      "items":
     *          [
     *              {
     *                   "value": "500-1999",
     *                   "count": 0,
     *                   "percentage": 0
     *               },
     *               {
     *                   "value": "10000-49999",
     *                   "count": 0,
     *                   "percentage": 0
     *               },
     *               {
     *                   "value": ">=50000",
     *                   "count": 0,
     *                   "percentage": 0
     *               }
     *          ]
     *  }
     * </pre>
     */
    public function actionProperty()
    {
        $accountId = $this->getQuery('channelId');
        $property = $this->getQuery("property");
        $refDate = $this->getQuery("refDate");
        $conditions = [];
        if ($refDate) {
            $conditions['refDate'] = $refDate;
        }
        if (!$accountId) {
            throw new BadRequestHttpException('Missing channel id');
        }
        if (empty($property)) {
            throw new BadRequestHttpException('Missing param');
        }

        $result = Yii::$app->weConnect->getUsersByProperty($accountId, $conditions, $property);

        if ($property == 'userFansCountDist') {
            $count = 0;
            foreach ($result as $item) {
                $count += $item['count'];
            }
            if ($count != 0) {
                $lastCount = 100;
                for ($i = 0; $i < (count($result) - 1); $i++) {
                    $result[$i]['count'] = sprintf('%.2f', $result[$i]['count'] / $count * 100);
                    $lastCount -= $result[$i]['count'];
                }
                $result[count($result) - 1]['count'] = sprintf('%.2f', $lastCount);
            }
        }
        return ['items' => $result];
    }
}
