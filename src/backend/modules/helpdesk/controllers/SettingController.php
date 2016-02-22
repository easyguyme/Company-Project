<?php
namespace backend\modules\helpdesk\controllers;

use backend\modules\helpdesk\models\HelpDeskSetting;
use backend\models\Token;
use backend\components\BaseModel;
use backend\utils\StringUtil;
use yii\web\ServerErrorHttpException;
use yii\web\BadRequestHttpException;
use backend\exceptions\InvalidParameterException;
use Yii;

class SettingController extends BaseController
{
    public $modelClass = 'backend\modules\helpdesk\models\HelpDeskSetting';

    public function actions()
    {
        $actions = parent::actions();
        unset($actions['create'], $actions['delete'], $actions['index'], $actions['update']);
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
     *          "maxWaitTim": 3,
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
     *          ],
     *          "channels": [
     *              {
     *                  "id": "549a728de4b0e2d4498c8e8f",
     *                  "isSet": false
     *              }
     *          ],
     *          "websites": [
     *              {
     *                  "id": "549a728de4b0e2d4498c8e8f",
     *                  "name": "test",
     *                  "url": "www.baidu.com",
     *                  "code": "<script><\/script>"
     *              }
     *          ]
     *      }
     * </pre>
     */
    public function actionIndex()
    {
        $accountId = $this->getAccountId();
        return HelpDeskSetting::getInstance($accountId);
    }

    public function actionUpdate($id)
    {
        $accountId = $this->getAccountId();
        //transfer the id from string to MongoId
        $id = new \MongoId($id);
        $model = HelpDeskSetting::findByPk($id);

        $model->scenario = BaseModel::SCENARIO_UPDATE;
        $helpDeskSetting = json_decode(Yii::$app->getRequest()->getRawBody(), true);
        $model->load($helpDeskSetting, '');

        if ($model->save() === false && !$model->hasErrors()) {
            throw new ServerErrorHttpException('Failed to update the object for unknown reason.');
        } else {
            $channels = $model->channels;
            if($channels && count($channels)) {
                $customerServicesSessionExpire = intval($helpDeskSetting['maxWaitTime']) * 60 * 1000;
                $accessToken = Token::createForWechat($accountId);
                foreach($channels as $channel) {
                    Yii::$app->weConnect->updateCustomerServiceSetting($channel['id'], $customerServicesSessionExpire, $accessToken->accessToken);
                }
            }
        }

        $model->_id .= '';
        return $model;
    }


    /**
     * Add a channel into help desk setting
     *
     * <b>Request Type: </b>PUT<br/>
     * <b>Request Endpoint: </b>http://{server-domain}/api/helpdesk/setting/add-channel<br/>
     * <b>Content-type: </b>Application/json<br/>
     * <b>Summary: </b>This api is for adding a channel into help desk setting.<br/>
     *
     * <b>Request Example: </b>
     * <pre>
     *     {
     *          "settingId": '52d791327ae252f9149547cb',
     *          "channelId": '52d791307ae252f9149547c9'
     *     }
     * </pre>
     */
    public function actionAddChannel()
    {
        $settingId = $this->getParams('settingId');
        $channelIdStr = $this->getParams('channelId');
        $accountId = $this->getAccountId();

        if (!empty($channelIdStr) && !empty($settingId)) {
            $channelIds = explode(',', $channelIdStr);
            $helpDeskSetting = HelpDeskSetting::getInstance($accountId);
            $customerServicesSessionExpire = intval($helpDeskSetting->maxWaitTime) * 60 * 1000;
            $channels = [];
            foreach ($channelIds as $channelId) {
                array_push($channels, ['id'=>$channelId, 'isSet' => false]);

                $accessToken = Token::createForWechat($accountId);
                Yii::$app->weConnect->updateCustomerServiceSetting($channelId, $customerServicesSessionExpire, $accessToken->accessToken);
            }
            $settingId = new \MongoId($settingId);

            // Add a channel into help desk setting
            $result = HelpDeskSetting::updateAll(
                ['$addToSet' => ['channels' => ['$each' => $channels]]],
                ['_id' => $settingId]
            );

            if ($result) {
                return $channels;
            }

            throw new ServerErrorHttpException('add channel fail');
        }

        throw new BadRequestHttpException('parameters missing');
    }
    /**
     * Remove a channel into help desk setting
     *
     * <b>Request Type: </b>PUT<br/>
     * <b>Request Endpoint: </b>http://{server-domain}/api/helpdesk/setting/remove-channel<br/>
     * <b>Content-type: </b>Application/json<br/>
     * <b>Summary: </b>This api is for removing a channel into help desk setting.<br/>
     *
     * <b>Request Example: </b>
     * <pre>
     *     {
     *          "settingId": '52d791327ae252f9149547cb',
     *          "channelId": '52d791307ae252f9149547c9'
     *     }
     * </pre>
     */
    public function actionRemoveChannel()
    {
        $settingId = $this->getParams('settingId');
        $channelId = $this->getParams('channelId');

        if (!empty($channelId) && !empty($settingId)) {
            $settingId = new \MongoId($settingId);

            // Add a channel into help desk setting
            $result = HelpDeskSetting::updateAll(
                ['$pull' => ['channels' => ['id' => $channelId]]],
                ['_id' => $settingId]
            );

            if ($result) {
                return $channelId . '';
            }

            throw new ServerErrorHttpException('remove channel fail');
        }

        throw new BadRequestHttpException('parameters missing');
    }

    /**
     * Add a website into help desk setting
     *
     * <b>Request Type: </b>PUT<br/>
     * <b>Request Endpoint: </b>http://{server-domain}/api/helpdesk/setting/add-website<br/>
     * <b>Content-type: </b>Application/json<br/>
     * <b>Summary: </b>This api is for adding a website into help desk setting.<br/>
     *
     * <b>Request Example: </b>
     * <pre>
     *     {
     *          "settingId": '52d791327ae252f9149547cb',
     *          "Website"  : {
     *              "name" : '爱不宅',
     *              "url"  : 'www.ibuzhai.com'
     *          }
     *     }
     * </pre>
     */
    public function actionAddWebsite()
    {
        $checkUrl = "((http|ftp|https)://)(([a-zA-Z0-9\._-]+\.[a-zA-Z]{2,6})|([0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}))(:[0-9]{1,4})*(/[a-zA-Z0-9\&%_\./-~-]*)?";

        $website = $this->getParams('website');
        $settingId = $this->getParams('settingId');
        $accountId = $this->getAccountId();

        if (!ereg($checkUrl, $website['url'])) {
            throw new InvalidParameterException(['url'=>Yii::t('helpDesk', 'url_format_wrong')]);
        }

        if (!empty($website['name']) && !empty($website['url'])) {
            $settingId = new \MongoId($settingId);
            $website = [
                'id'   => StringUtil::uuid(),
                'name' => $website['name'],
                'url'  => $website['url'],
                'code' => HelpDeskSetting::getCode($website['url'], $accountId)
            ];
            // Add a website into help desk setting
            $result = HelpDeskSetting::updateAll(
                ['$push' => ['websites' => $website]],
                ['_id' => $settingId]
            );

            if ($result) {
                return $website;
            }

            throw new ServerErrorHttpException('add website fail');
        }

        throw new BadRequestHttpException('parameters missing');
    }

    /**
     * Remove a website into help desk setting
     *
     * <b>Request Type: </b>PUT<br/>
     * <b>Request Endpoint: </b>http://{server-domain}/api/helpdesk/setting/remove-website<br/>
     * <b>Content-type: </b>Application/json<br/>
     * <b>Summary: </b>This api is for removing a website into help desk setting.<br/>
     *
     * <b>Request Example: </b>
     * <pre>
     *     {
     *          "settingId": '52d791327ae252f9149547cb',
     *          "websiteId": '52d791307ae252f9149547c9'
     *     }
     * </pre>
     */
    public function actionRemoveWebsite()
    {
        $settingId = $this->getParams('settingId');
        $websiteId = $this->getParams('websiteId');

        if (!empty($websiteId) && !empty($settingId)) {
            $settingId = new \MongoId($settingId);

            // Add a channel into help desk setting
            $result = HelpDeskSetting::updateAll(
                ['$pull' => ['websites' => ['id' => $websiteId]]],
                ['_id' => $settingId]
            );

            if ($result) {
                return $websiteId . '';
            }

            throw new ServerErrorHttpException('remove website fail');
        }

        throw new BadRequestHttpException('parameters missing');
    }

     /**
     * Authorize the wechatcp.
     */
    public function actionAuthorizeWechatCp()
    {
        $result = Yii::$app->weConnect->getPreOAuthCode(WECHAT_CP_HELPDESK_SUITE_ID, WECHAT_CORP_APP_ID);
        $preAuthCode = $result['preAuthCode'];

        if (empty($preAuthCode)) {
            throw new BadRequestHttpException('parameters missing');
        }

        $redirectUrl = DOMAIN . 'api/helpdesk/setting/authorize-wechat-cp-done';
        $redirectUrl = urlencode($redirectUrl);
        $targetUrl = WECHAT_CORP_DOMAIN . '/cgi-bin/loginpage?suite_id=' . WECHAT_CP_HELPDESK_SUITE_ID . "&pre_auth_code=$preAuthCode&redirect_uri=$redirectUrl";
        $this->redirect($targetUrl);
    }

    /**
     * Complete the authorization, then save the corp info.
     */
    public function actionAuthorizeWechatCpDone()
    {
        $accountId = $this->getAccountId();
        $authCode = $this->getQuery('auth_code');

        if (empty($authCode)) {
            throw new BadRequestHttpException('parameters missing');
        }

        $corpInfo = Yii::$app->weConnect->getCorpInfoByOAuth(WECHAT_CP_HELPDESK_SUITE_ID, $authCode);

        if (empty($corpInfo)) {
            throw new BadRequestHttpException('parameters missing');
        }

        $helpDeskSetting = HelpDeskSetting::getInstance($accountId);
        $wechatcp = array(
            'corpId' => $corpInfo['authCorpInfo']['id'],
            'corpName' => $corpInfo['authCorpInfo']['name'],
            'agentId' => $corpInfo['authAgentsInfo'][0]['agentId'],
            'agentName' => $corpInfo['authAgentsInfo'][0]['name']
        );
        $wechatcp = json_encode($wechatcp, JSON_UNESCAPED_UNICODE);
        $wechatcp = json_decode($wechatcp);
        $helpDeskSetting->wechatcp = $wechatcp;

        if ($helpDeskSetting->save(true, ['wechatcp'])) {
            $redirectUrl = DOMAIN . 'helpdesk/setting?active=2';
            $this->redirect($redirectUrl);
        } else {
            throw new ServerErrorHttpException('save wechatcp fail');
        }
    }

    /**
     * Cancel authorization of the wechatcp.
     */
    public function actionCancelAuthorization()
    {
        // TODO
        // 1. redirect to the weixin page under the specified suiteId.
        // 2. remove the wechatcp field in system DB.
        return null;
    }
}
