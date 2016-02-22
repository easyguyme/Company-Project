<?php
namespace backend\modules\helpdesk\controllers;

use backend\modules\helpdesk\models\SelfHelpDeskSetting;
use Yii;
use yii\web\BadRequestHttpException;
use backend\exceptions\InvaildParameterException;
use MongoId;

class SelfSettingController extends BaseController
{
    public $modelClass = 'backend\modules\helpdesk\models\SelfHelpDeskSetting';

    const SELF_SETTING_PREFIX = 'selfHelpDesk-';

    public function actions()
    {
        $actions =parent::actions();
        unset($actions['create'], $actions['update'], $actions['index']);
        return $actions;
    }

    public function actionIndex()
    {
        $accountId = $this->getAccountId();
        $redis = Yii::$app->cache->redis;
        $settings = $redis->get(self::SELF_SETTING_PREFIX . $accountId);
        if (!empty($settings)) {
            return unserialize($settings);
        }
        return null;
    }

    public function actionCreate()
    {
        $accountId = $this->getAccountId();
        $params = $this->getParams();
        $redis = Yii::$app->cache->redis;
        if (empty($params['settings'])) {
            // Delete setting
            $redis->del(self::SELF_SETTING_PREFIX . $accountId);
            return ['status' => 'ok'];
        }
        if (empty($params['accountId'])) {
            $params['accountId'] = $accountId;
        }
        $params['isPublished'] = false;
        $redis->set(self::SELF_SETTING_PREFIX . $accountId, serialize($params));
        return ['status' => 'ok'];
    }

    public function actionPublish()
    {
        $accountId = $this->getAccountId();
        $redis = Yii::$app->cache->redis;
        $settings = $redis->get(self::SELF_SETTING_PREFIX . $accountId);
        $selfHelpDeskSetting = SelfHelpDeskSetting::findOne(['accountId' => new MongoId($accountId)]);
        if (!empty($settings)) {
            $settings = unserialize($settings);
            $settings['isPublished'] = true;
            $redis->set(self::SELF_SETTING_PREFIX . $accountId, serialize($settings));
            if (empty($selfHelpDeskSetting)) {
                $selfHelpDeskSetting = new SelfHelpDeskSetting();
            }
            $selfHelpDeskSetting['settings'] = $settings['settings'];
            $selfHelpDeskSetting['accountId'] = $settings   ['accountId'];
            if ($selfHelpDeskSetting->save()) {
                return ['status' => 'ok'];
            } else {
                return ['status' => 'failed'];
            }
        } else {
            if (!empty($selfHelpDeskSetting)) {
                $selfHelpDeskSetting->delete();
            }
            return ['status' => 'ok'];
        }
    }
}
