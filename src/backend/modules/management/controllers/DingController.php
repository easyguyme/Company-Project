<?php
namespace backend\modules\management\controllers;

use Yii;
use backend\models\DingUser;
use yii\web\BadRequestHttpException;
use backend\components\rest\RestController;

class DingController extends RestController
{
    public $modelClass = 'backend\models\DingUser';

    public function actions()
    {
        $actions = parent::actions();
        unset($actions['create'], $actions['update'], $actions['delete']);
        return $actions;
    }

    public function actionDepartment()
    {
        $accountId = $this->getAccountId();
        $token = Yii::$app->weConnect->getDDTokenByQunAccountId((string) $accountId);
        $accesstoken = $token['token'];
        $departments = Yii::$app->ddConnect->getDepartment($accesstoken);
        return $departments['department'];
    }

    public function actionSyncUser()
    {
        $accountId = $this->getAccountId();
        $departmentId = $this->getParams('departmentId');
        if (empty($departmentId)) {
            throw new BadRequestHttpException(Yii::t('common', 'parameters_missing'));
        }
        $token = Yii::$app->weConnect->getDDTokenByQunAccountId((string) $accountId);
        $accesstoken = $token['token'];
        $corpId = $token['appInfo']['cropId'];
        $users = Yii::$app->ddConnect->getUsersByDepartment($accesstoken, $departmentId);
        $dingUsers = [];
        foreach ($users['userlist'] as $user) {
            $dingUser = DingUser::getByCorpIdAndDingUserId($accountId, $corpId, $user['userid']);
            if (empty($dingUser)) {
                $dingUsers[] = [
                    'corpId' => $corpId,
                    'dingUserId' => $user['userid'],
                    'name' => $user['name'],
                    'avatar' => $user['avatar'],
                    'mobile' => empty($user['mobile']) ? '' : $user['mobile'],
                    'email' => empty($user['email']) ? '' : $user['email'],
                    'openId' => empty($user['openId']) ? '' : $user['openId'],
                    'enableActions' => [],
                    'accountId' => $accountId,
                ];
            }
        }
        DingUser::batchInsert($dingUsers);
        return ['message' => 'OK', 'data' => null];
    }

    public function actionAuthorize()
    {
        $accountId = $this->getAccountId();
        $params = $this->getParams();
        $users = [];
        foreach ($params['users'] as $userId) {
            $users[] = new \MongoId($userId);
        }
        $count = DingUser::authorize($users, $params['authorities'], $accountId);
        return ['message' => 'OK', 'data' => ['updated' => $count]];
    }
}
