<?php
namespace backend\modules\member\controllers;

use backend\modules\member\models\MemberStatistics;
use yii\web\BadRequestHttpException;

/**
 * Controller class for Statistics
 */
class StatisticsController extends BaseController
{
    public $modelClass = "backend\modules\member\models\MemberStatistics";

    public function actions()
    {
        $actions = parent::actions();
        unset($actions['index']);
        return $actions;
    }

    /**
     * Query statistics info
     *
     * <b>Request Type: </b>GET<br/>
     * <b>Request Endpoint: </b>http://{server-domain}/api/member/statisticss
     * <b>Summary: </b>This api is for query statistics info
     *
     * <b>Request Params</b>:<br/>
     *     locationProperty: string,country, province, city<br/>
     *     parentCountry: string<br/>
     *     parentProvince: string<br/>
     *     <br/><br/>
     *
     * <b>Response Example: </b>
     * <pre>
     *  {
     *      "items": [
     *          {
     *              "value": "黄冈"
     *          },
     *          {
     *              "value": "武汉"
     *          }
     *      ]
     *  }
     * </pre>
     */
    public function actionIndex()
    {
        $accountId = $this->getAccountId();
        $locationProperty = $this->getQuery('locationProperty');
        $parentCountry = $this->getQuery('parentCountry');
        $parentProvince = $this->getQuery('parentProvince');

        if (empty($locationProperty)) {
            throw new BadRequestHttpException('missing property');
        }

        //get location statistics
        $statistics = MemberStatistics::getByAccount($accountId);
        if (empty($statistics['locationStatistics'])) {
            return ['items' => []];
        }
        $locationStatistics = $statistics['locationStatistics'];

        $items = [];
        //get all country unset provinces
        if ($locationProperty == 'country') {
            foreach ($locationStatistics as $locationStatistic) {
                unset($locationStatistic['provinces']);
                if (!empty($locationStatistic['value'])) {
                    $items[] = $locationStatistic;
                }
            }
        } else if ($locationProperty == 'province') {
            $provinces = [];
            //get provinces
            foreach ($locationStatistics as $locationStatistic) {
                if ($locationStatistic['value'] == $parentCountry) {
                    $provinces = $locationStatistic['provinces'];
                }
            }
            //unset city
            foreach ($provinces as $province) {
                unset($province['cities']);
                $items[] = $province;
            }
        } else if ($locationProperty == 'city') {
            foreach ($locationStatistics as $locationStatistic) {
                if ($locationStatistic['value'] == $parentCountry) {
                    $provinces = $locationStatistic['provinces'];
                    foreach ($provinces as $province) {
                        $cities = $province['cities'];
                        if ($province['value'] == $parentProvince) {
                            $items = $cities;
                        }
                    }
                }
            }
        }
        $result = ['items' => $items];

        return $result;
    }
}
