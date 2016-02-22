<?php
namespace backend\modules\analytic\controllers;

use Yii;
use yii\web\BadRequestHttpException;

class StatusController extends BaseController
{

    /**
     * Query weibo statuses statistics
     *
     * <b>Request Type</b>: GET<br/><br/>
     * <b>Request Endpoint</b>:http://{server-domain}/api/dashboard/statuss<br/><br/>
     * <b>Response Content-type</b>: application/json<br/><br/>
     * <b>Summary</b>: This api is used for querying weibo statuses statistics statistics
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
     *      "id": "54d32bd12b879e4a6088f57b",
     *       "accountId": "54d32a67e4b0f0223b610992",
     *       "refDate": 1422979200000,
     *       "cumulateStatuses": 3, //总微博数量
     *       "cumulateReposts": 5, //总微博转发
     *       "cumulateComments": 3, //总微博评论
     *       "cumulateOriginal": 1, //总微博原创数
     *       "avgStatuses": 1.5, //每日平均微数量
     *       "avgReposts": 1.5, //每日平均转发
     *       "avgComments": 1.5, //每日平均评论
     *       "originalPercentage": 33.333332, //微博原创率
     *       "newStatuses": 2, //当日微博数
     *       "newReposts": 3, //当日被转发数
     *      "newComments": 2 //当日评论数
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

        return Yii::$app->weConnect->getStatusSummary($accountId, $query);
    }

    /**
     * Query weibo daily statuses statistics
     *
     * <b>Request Type</b>: GET<br/><br/>
     * <b>Request Endpoint</b>:http://{server-domain}/api/dashboard/status/daily<br/><br/>
     * <b>Response Content-type</b>: application/json<br/><br/>
     * <b>Summary</b>: This api is used for querying weibo daily statuses statistics
     * <br/><br/>
     *
     * <b>Request Params</b>:<br/>
     *     channelId: string, id of the channel<br/>
     *     startDate: string, required
     *     endDate: string, required
     *     type: string, required STATUSES(每日发微博数), REPOSTS(被转发量), COMMENTS(被评论量)
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
     *      "userCount": [351, 316, 29, 963, 292, 72, 52, 501, 772, 472],
     *      "statDate": ["2015-01-10","2015-01-11","2015-01-12","2015-01-13","2015-01-14","2015-01-15","2015-01-16","2015-01-17","2015-01-18","2015-01-19"],
     *  }
     * </pre>
     */
    public function actionDaily()
    {
        $query = $this->getQuery();
        $startDate = $query["startDate"];
        $endDate = $query["endDate"];
        $accountId = $this->getQuery('channelId');
        if (!$accountId) {
            throw new BadRequestHttpException('Missing channel id');
        }
        unset($query['channelId']);

        $result = Yii::$app->weConnect->getDailyStatus($accountId, $query);
        $destResult = $this->formateResponseData($result, ['userCount' => 'count'], $startDate, $endDate);

        return $destResult;
    }
}
