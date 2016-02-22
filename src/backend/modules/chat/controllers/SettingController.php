<?php
namespace backend\modules\chat\controllers;

use backend\modules\helpdesk\models\SelfHelpDeskSetting;
use yii\web\BadRequestHttpException;
use backend\modules\helpdesk\models\HelpDeskSetting;

class SettingController extends RestController
{
    public $modelClass = 'backend\modules\helpdesk\models\HelpDeskSetting';

    public function actions()
    {
        $actions = parent::actions();
        unset($actions['create'], $actions['delete'], $actions['index']);
        return $actions;
    }

    /**
     * Get the detail information of the helpDesk setting
     *
     * <b>Request Type: </b>GET<br/>
     * <b>Request Endpoint: </b>http://{server-domain}/api/helpdesk/settings<br/>
     * <b>Content-type: </b>Application/json<br/>
     * <b>Summary: </b>This api is for get the detail information of the helpDesk setting.<br/>
     *
     * <b>Response Example: </b>
     * <pre>
     *     {
     *          "maxW*aitTim": 3,
     *          "maxClient": 5,
     *          "ondutyTime": "8:00",
     *          "offdutyTime": "18:00",
     *          "systemReplies": [
     *              {
     *                  "name": "wait_for_service",
     *                  "type": "waitting",
     *                  "replyText": "wait",
     *                  "isEnabled": true
     *              },
     *              {
     *                  "name": "close_service",
     *                  "type": "close",
     *                  "replyText": "close",
     *                  "isEnabled": true
     *              },
     *              {
     *                  "name": "non_working_time",
     *                  "type": "nonworking",
     *                  "replyText": "non working time",
     *                  "isEnabled": true
     *              },
     *              {
     *                  "name": "auto_brake",
     *                  "type": "brake",
     *                  "replyText": "brake",
     *                  "isEnabled": true
     *              },
     *              {
     *                  "name": "connect_success",
     *                  "type": "success",
     *                  "replyText": "success",
     *                  "isEnabled": true
     *              },
     *              {
     *                  "name": "desk_droping",
     *                  "type": "droping",
     *                  "replyText": "droping",
     *                  "isEnabled": true
     *              }
     *          ]
     *      }
     * </pre>
     */
    public function actionIndex()
    {
        $accountId = $this->getAccountId();

        if (!$accountId) {
            $accountId = $this->getQuery('cid');
        }

        if (empty($accountId)) {
            throw new BadRequestHttpException("AccountId is required");
        }
        return HelpDeskSetting::getInstance($accountId);
    }

    /**
     * Get the detail information of the self helpdesk setting
     *
     * <b>Request Type: </b>GET<br/>
     * <b>Request Endpoint: </b>http://{server-domain}/api/helpdesk/setting/self-helpdesk<br/>
     * <b>Content-type: </b>Application/json<br/>
     * <b>Summary: </b>This api is for get the detail information of the self helpdesk setting.<br/>
     *
     * <b>Response Example: </b>
     * <pre>
     * {
     *     "id": "56187e882736e752058b4578",
     *     "settings": {
     *         "type": "reply",
     *         "content": "您好，很高兴为您服务，您可以输入序号查看一下内容：
     * [1]　卡片申请
     * [2]　办卡寄送进度查询
     * [3]　开卡／办卡
     * [4]　推荐亲友办卡",
     *         "menus": {
     *             "1": {
     *                 "content": "回复数字
     * 1. 绑定
     * 2. 解绑
     * 3. 返回上一级",
     *                 "type": "reply",
     *                 "menus": {
     *                     "1": {
     *                         "content": "您已绑定成功",
     *                         "type": "reply"
     *                     },
     *                     "2": {
     *                         "content": "已为您解绑",
     *                         "type": "reply"
     *                     },
     *                     "3": {
     *                         "content": "返回上一级",
     *                         "type": "back"
     *                     }
     *                 }
     *             },
     *             "2": {
     *                 "content": "回复数字
     * 1. 短信服务绑定
     * 2. 短信服务解绑
     * 3. 绑定手机重置密码
     * 4. 返回上一级",
     *                 "type": "reply",
     *                 "menus": {
     *                     "１": {
     *                         "content": "短信服务绑定成功",
     *                         "type": "reply"
     *                     },
     *                     "２": {
     *                         "content": "短信服务解绑成功",
     *                         "type": "reply"
     *                     },
     *                     "３": {
     *                         "content": "手机重置密码绑定成功",
     *                         "type": "reply"
     *                     },
     *                     "４": {
     *                         "content": "",
     *                         "type": "back"
     *                     }
     *                 }
     *             },
     *             "3": {
     *                 "content": "回复数字
     * 1. 密保绑定状态
     * 2. 密保卡使用状态
     * 3. 返回上一级",
     *                 "type": "reply",
     *                 "menus": {
     *                     "1": {
     *                         "content": "密保绑定成功",
     *                         "type": "reply"
     *                     },
     *                     "2": {
     *                         "content": "密保卡已被使用",
     *                         "type": "reply"
     *                     },
     *                     "3": {
     *                         "content": "",
     *                         "type": "back"
     *                     }
     *                 }
     *             },
     *             "4": {
     *                 "content": "",
     *                 "type": "connect",
     *                 "menus": [ ]
     *             }
     *         }
     *     }
     * }
     * </pre>
     */
    public function actionSelfHelpdesk()
    {
        $accountId = $this->getAccountId();

        if (!$accountId) {
            $accountId = $this->getQuery('cid');
        }

        if (empty($accountId)) {
            throw new BadRequestHttpException("AccountId is required");
        }

        return SelfHelpDeskSetting::findOne(['accountId' => $accountId]);
    }
}
