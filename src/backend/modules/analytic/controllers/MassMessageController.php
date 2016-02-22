<?php
namespace backend\modules\analytic\controllers;

use Yii;
use backend\utils\StringUtil;
use yii\helpers\Json;
use yii\web\ServerErrorHttpException;
use yii\web\BadRequestHttpException;

class MassMessageController extends BaseController
{

    /**
     * Query mass message statistics
     *
     * <b>Request Type</b>: GET<br/><br/>
     * <b>Request Endpoint</b>:http://{server-domain}/api/dashboard/mass-messages<br/><br/>
     * <b>Response Content-type</b>: application/json<br/><br/>
     * <b>Summary</b>: This api is used for querying mass message statistics
     * <br/><br/>
     *
     * <b>Request Params</b>:<br/>
     *     channelId: string, id of the channel<br/>
     *     startDate: string
     *     endDate: string
     *     title: string
     *     orderby: String, {"sentDate":"DESC"}, sentDate, sentUser, intPageReadUser,oriPageReadUser,shareUser,addToFavUser
     *     per-page: int, required<br/>
     *     page: int, required<br/>
     *     <br/><br/>
     *
     * <b>Response Params:</b><br/>
     *     items: array, json array to queried channels detail information<br/>
     *     _meta: array, the pagination information.
     *     <br/><br/>
     *
     * <br/><br/>
     *
     * <b>Response Example</b>:<br/>
     * <pre>
     * {
     *      "items": [
     *          {
     *              "accountId": "548116bb978ca81ec68d593b",
     *              "massSendId": "548116bb978ca81ec68d593b",
     *              "sentDate": "2014-12-29",
     *              "sentUser": 100,
     *              "title": "12月27日 DiLi日报",
     *              "refDate": 1419857357482,
     *              "intPageReadUser": 23676,
     *              "intPageReadCount": 25615,
     *              "oriPageReadUser": 29,
     *              "oriPageReadCount": 34,
     *              "shareUser": 122,
     *              "shareCount": 994,
     *              "addToFavUser": 1,
     *              "addToFavCount": 3,
     *              "dailyStatistics": [
     *                  {
     *                      "refDate": "2014-12-29",
     *                      "intPageReadUser": 23676,
     *                      "intPageReadCount": 25615,
     *                      "oriPageReadUser": 29,
     *                      "oriPageReadCount": 34,
     *                      "shareUser": 122,
     *                      "shareCount": 994,
     *                      "addToFavUser": 1,
     *                      "addToFavCount": 3
     *                  },
     *                  {
     *                      "refDate": "2014-12-29",
     *                      "intPageReadUser": 23676,
     *                      "intPageReadCount": 25615,
     *                      "oriPageReadUser": 29,
     *                      "oriPageReadCount": 34,
     *                      "shareUser": 122,
     *                      "shareCount": 994,
     *                      "addToFavUser": 1,
     *                      "addToFavCount": 3
     *                  },
     *                  {
     *                      "refDate": "2014-12-29",
     *                      "intPageReadUser": 23676,
     *                      "intPageReadCount": 25615,
     *                      "oriPageReadUser": 29,
     *                      "oriPageReadCount": 34,
     *                      "shareUser": 122,
     *                      "shareCount": 994,
     *                      "addToFavUser": 1,
     *                      "addToFavCount": 3
     *                  },
     *                  {
     *                      "refDate": "2014-12-29",
     *                      "intPageReadUser": 23676,
     *                      "intPageReadCount": 25615,
     *                      "oriPageReadUser": 29,
     *                      "oriPageReadCount": 34,
     *                      "shareUser": 122,
     *                      "shareCount": 994,
     *                      "addToFavUser": 1,
     *                      "addToFavCount": 3
     *                  },
     *                  {
     *                      "refDate": "2014-12-29",
     *                      "intPageReadUser": 23676,
     *                      "intPageReadCount": 25615,
     *                      "oriPageReadUser": 29,
     *                      "oriPageReadCount": 34,
     *                      "shareUser": 122,
     *                      "shareCount": 994,
     *                      "addToFavUser": 1,
     *                      "addToFavCount": 3
     *                  },
     *                  {
     *                      "refDate": "2014-12-29",
     *                      "intPageReadUser": 23676,
     *                      "intPageReadCount": 25615,
     *                      "oriPageReadUser": 29,
     *                      "oriPageReadCount": 34,
     *                      "shareUser": 122,
     *                      "shareCount": 994,
     *                      "addToFavUser": 1,
     *                      "addToFavCount": 3
     *                  },
     *                  {
     *                      "refDate": "2014-12-29",
     *                      "intPageReadUser": 23676,
     *                      "intPageReadCount": 25615,
     *                      "oriPageReadUser": 29,
     *                      "oriPageReadCount": 34,
     *                      "shareUser": 122,
     *                      "shareCount": 994,
     *                      "addToFavUser": 1,
     *                      "addToFavCount": 3
     *                  }
     *              ]
     *          }
     *      ],
     *      "_meta": {
     *          "totalCount": 1,
     *          "pageCount": 1,
     *          "currentPage": 1,
     *          "perPage": 5
     *      }
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

        if (isset($query['orderby'])) {
            $orderBy = $query['orderby'];
            unset($query['orderby']);
            if (StringUtil::isJson($orderBy)) {
                $orderBy = Json::decode($orderBy, true);

                foreach ($orderBy as $key => $value) {
                    if ($value === 'asc') {
                        $query['orderBy'] = $key;
                        $query['ordering'] = 'ASC';
                    } else {
                        $query['orderBy'] = $key;
                        $query['ordering'] = 'DESC';
                    }
                }
            } else {
                $query['orderBy'] = $orderBy;
                $query['ordering'] = 'DESC';
            }
        }

        $query['pageSize'] = isset($query['per-page']) ? $query['per-page'] : 5;
        $query['pageNum'] = isset($query['page']) ? $query['page'] : 1;
        unset($query['per-page'], $query['page'], $query['channelId']);

        $raw = Yii::$app->weConnect->searchMassArticlesStatistics($accountId, $query);

        if (array_key_exists('results', $raw)) {
            return [
                'items' => $raw['results'] ? $raw['results'] : [],
                '_meta' => [
                    'totalCount' => $raw['totalAmount'],
                    'pageCount' => ceil($raw['totalAmount'] / $raw['pageSize']),
                    'currentPage' => $raw['pageNum'],
                    'perPage' => $raw['pageSize']
                ]
            ];
        } else {
            throw new ServerErrorHttpException('Query mass message statistics fail');
        }
    }

    /**
     * Query mass message statistics yesterday
     *
     * <b>Request Type</b>: GET<br/><br/>
     * <b>Request Endpoint</b>:http://{server-domain}/api/dashboard/mass-message/yesterday<br/><br/>
     * <b>Response Content-type</b>: application/json<br/><br/>
     * <b>Summary</b>: This api is used for querying mass message statistics yesterday
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
     *      "accountId": "548116bb978ca81ec68d593b",
     *      "refDate": "2014-12-29",
     *      "intPageReadCount": 25615,
     *      "oriPageReadCount": 34,
     *      "shareCount": 994,
     *      "addToFavCount": 3,
     *      "intPageReadCountDayGrowth": 30,
     *      "intPageReadCountWeekGrowth": 30,
     *      "intPageReadCountMonthGrowth": 30,
     *      "oriPageReadCountDayGrowth": 34,
     *      "oriPageReadCountWeekGrowth": 34,
     *      "oriPageReadCountMonthGrowth": 30,
     *      "shareCountDayGrowth": 30,
     *      "shareCountWeekGrowth": 30,
     *      "shareCountMonthGrowth": 30,
     *      "addToFavCountDayGrowth": 30,
     *      "addToFavCountWeekGrowth": 30,
     *      "addToFavCountMonthGrowth": 30
     * }
     * </pre>
     */
    public function actionYesterday()
    {
        $accountId = $this->getQuery('channelId');
        if (!$accountId) {
            throw new BadRequestHttpException('Missing channel id');
        }

        return Yii::$app->weConnect->getMassArticlesStatistics($accountId, 'yesterday', '');
    }

    /**
     * Query mass message statistics
     *
     * <b>Request Type</b>: GET<br/><br/>
     * <b>Request Endpoint</b>:http://{server-domain}/api/dashboard/mass-message/interval<br/><br/>
     * <b>Response Content-type</b>: application/json<br/><br/>
     * <b>Summary</b>: This api is used for querying mass message statistics
     * <br/><br/>
     *
     * <b>Request Params</b>:<br/>
     *     channelId: string, id of the channel<br/>
     *     type: string, INT_PAGE_READ(图文阅读), ORI_PAGE_READ(原文阅读), ADD_TO_FAV(转发收藏), SHARE(分享), required
     *     startDate: string, required
     *     endDate: string, required
     *     subType: string, FRIENDS_TRANSPOND(好友转发), FRIENDS_CIRCLE(朋友圈), TENCENT_WEIBO(腾讯微博), OTHER(其他), default all
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
     *     "userNumber": [50, 60, 200, 13, 34, 100, 90, 50, 150, 300],
     *     "userCount": [351, 316, 29, 963, 292, 72, 52, 501, 772, 472],
     *     "statDate": ["2015-01-10","2015-01-11","2015-01-12","2015-01-13","2015-01-14","2015-01-15","2015-01-16","2015-01-17","2015-01-18","2015-01-19"],
     * }
     * </pre>
     */
    public function actionInterval()
    {
        $query = $this->getQuery();
        $accountId = $this->getQuery('channelId');

        if (!$accountId) {
            throw new BadRequestHttpException('Missing channel id');
        }

        if (empty($query['type']) || empty($query['startDate']) || empty($query['endDate'])) {
            throw new BadRequestHttpException('Missing param');
        }
        unset($query['channelId']);
        $startDate = $query['startDate'];
        $endDate = $query['endDate'];

        $result = Yii::$app->weConnect->getNpnewsStatisticsByDate($accountId, $query);
        $destResult = $this->formateResponseData($result, ['userCount' => 'count', 'userNumber' => 'user'], $query['startDate'], $query['endDate']);

        return $destResult;
    }
}
