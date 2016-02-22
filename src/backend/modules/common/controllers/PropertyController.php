<?php
namespace backend\modules\common\controllers;

use yii\web\ServerErrorHttpException;
use yii\web\BadRequestHttpException;

class PropertyController extends BaseController
{

    /**
     * Query property count lists
     *
     * <b>Request Type</b>: GET<br/><br/>
     * <b>Request Endpoint</b>:http://{server-domain}/api/common/propertys<br/><br/>
     * <b>Response Content-type</b>: application/json<br/><br/>
     * <b>Summary</b>: This api is used for requesting property info.
     * <br/><br/>
     *
     * <b>Request Params</b>:<br/>
     *     channelId: string,<br/>
     *     property: string, gender,language,subscribeSource<br/>
     *     parent: string,<br/>
     *     <br/><br/>
     *
     * <b>Response Params:</b><br/>
     *     items: array, json array to queried property detail information<br/>
     *     <br/><br/>
     *
     * <br/><br/>
     *
     * <b>Response Example</b>:<br/>
     * <pre>
     * property is subscribeSource
     * {
     *  "items": [
     *      {
     *          "value": "other",
     *          "count": 1389,
     *          "percentage": 100
     *      }
     *  ]
     * }
     * property is gender
     * {
     *  "items": [
     *      {
     *          "gender": {
     *              "male": 35,
     *              "female": 44,
     *              "unknow": 20
     *          }
     *      }
     *  ]
     * }
     * property is language
     * {
     *  "items": [
     *      {
     *          "value": "ko",
     *          "count": 1,
     *          "percentage": 0.07199424
     *      },
     *      {
     *          "value": "hi",
     *          "count": 1,
     *          "percentage": 0.07199424
     *      },
     *      {
     *          "value": "zh_TW",
     *          "count": 8,
     *          "percentage": 0.5759539
     *      },
     *      {
     *          "value": "en",
     *          "count": 21,
     *          "percentage": 1.5118791
     *      },
     *      {
     *          "value": "zh_CN",
     *          "count": 1358,
     *          "percentage": 97.76818
     *      }
     *  ]
     * }
     * </pre>
     */
    public function actionIndex()
    {
        $query = $this->getQuery();

        $channelId = $this->getChannelId();
        if (empty($query['property'])) {
            throw new BadRequestHttpException('Missing property');
        }

        unset($query['channelId']);
        $raw = \Yii::$app->weConnect->getProperty($channelId, $query);

        return ['items' => $raw];
    }

    /**
     * Query location count lists
     *
     * <b>Request Type</b>: GET<br/><br/>
     * <b>Request Endpoint</b>:http://{server-domain}/api/common/property/location<br/><br/>
     * <b>Response Content-type</b>: application/json<br/><br/>
     * <b>Summary</b>: This api is used for requesting location info.
     * <br/><br/>
     *
     * <b>Request Params</b>:<br/>
     *     channelId: string,<br/>
     *     locationProperty: string, country,province,city<br/>
     *     parentCountry: string<br/>
     *     parentProvince: string<br/>
     *     <br/><br/>
     *
     * <b>Response Params:</b><br/>
     *     items: array, json array to queried location detail information<br/>
     *     <br/><br/>
     *
     * <br/><br/>
     *
     * <b>Response Example</b>:<br/>
     * <pre>
     * {
     *  "items": [
     *      {
     *          "value": "UNKNOWN",
     *          "count": 1,
     *          "percentage": 100
     *      },
     *      {
     *          "value": "湖南",
     *          "count": 1,
     *          "percentage": 6.6666665
     *      },
     *      {
     *          "value": "上海",
     *          "count": 8,
     *          "percentage": 53.333332
     *      },
     *      {
     *          "value": "山西",
     *          "count": 1,
     *          "percentage": 6.6666665
     *      },
     *      {
     *          "value": "湖北",
     *          "count": 2,
     *          "percentage": 13.333333
     *      },
     *      {
     *          "value": "江苏",
     *          "count": 2,
     *          "percentage": 13.333333
     *      },
     *      {
     *          "value": "广东",
     *          "count": 1,
     *          "percentage": 6.6666665
     *      }
     *  ]
     * }
     * </pre>
     */
    public function actionLocation()
    {
        $query = $this->getQuery();
        $channelId = $this->getChannelId();
        if (empty($query['locationProperty'])) {
            throw new BadRequestHttpException('Missing location property');
        }
        unset($query['channelId']);
        $raw = \Yii::$app->weConnect->getLocation($channelId, $query);

        return ['items' => $raw];
    }
}
