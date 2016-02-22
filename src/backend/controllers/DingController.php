<?php
namespace backend\controllers;

use Yii;
use MongoId;
use backend\components\Controller;
use yii\web\BadRequestHttpException;
use backend\models\DingUser;

class DingController extends Controller
{
    public function actionUser()
    {
        $params = $this->getParams();
        if (empty($params['suiteKey']) || empty($params['corpId']) || empty($params['appId']) || empty($params['code'])) {
            throw new BadRequestHttpException(Yii::t('common', 'parameters_missing'));
        }
        $token = Yii::$app->ddJsSdk->getToken($params['suiteKey'], $params['corpId'], $params['appId']);
        $user = Yii::$app->ddConnect->getUserByCode($token['accesstoken'], $params['code']);

        $accountId = new MongoId($token['accountId']);
        $dingUser = DingUser::getByCorpIdAndDingUserId($accountId, $params['corpId'], $user['userid']);
        if (empty($dingUser)) {
            $userInfo = Yii::$app->ddConnect->getUserById($token['accesstoken'], $user['userid']);
            $dingUser = new DingUser;
            $dingUser->corpId = $params['corpId'];
            $dingUser->dingUserId = $userInfo['userid'];
            $dingUser->name = $userInfo['name'];
            $dingUser->avatar = $userInfo['avatar'];
            $dingUser->mobile = empty($userInfo['mobile']) ? '' : $userInfo['mobile'];
            $dingUser->email = empty($userInfo['email']) ? '' : $userInfo['email'];
            $dingUser->openId = empty($userInfo['openId']) ? '' : $userInfo['openId'];
            $dingUser->accountId = $accountId;
            $dingUser->save();
        }

        return ['dingUserId' => (string) $dingUser->_id];
    }
}
