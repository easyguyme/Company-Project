<?php
namespace backend\modules\management\controllers;

use Yii;
use yii\web\BadRequestHttpException;
use backend\components\rest\RestController;
use backend\models\User;
use backend\models\SensitiveOperation;

/**
 * Add the default sensitive operation
 **/
class SensitiveOperationController extends RestController
{
    public $modelClass = 'backend\models\SensitiveOperation';

    public function actions()
    {
        $actions = parent::actions();
        unset($actions['create'], $actions['delete']);
        return $actions;
    }

    /**
     * Get selected user list and unselected user list
     * @param  string $id sensitive operation id
     * @return array
     */
    public function actionListUser($id)
    {
        $operationId = new \MongoId($id);
        $sensitiveOperation = SensitiveOperation::findByPk($operationId);

        if (empty($sensitiveOperation)) {
            throw new BadRequestHttpException('Incorrect operation id');
        }

        $accountId = $this->getAccountId();
        // the common condition
        $condition = ['isActivated' => true, 'role' => User::ROLE_OPERATOR, 'accountId' => $accountId];
        // query the selected user
        $selectedUsers = User::findAll(array_merge($condition, ['_id' => ['$in' => $sensitiveOperation->users]]));
        // query the unselected user
        $unselectedUsers = User::findAll(array_merge($condition, ['_id' => ['$nin' => $sensitiveOperation->users]]));
        return ['selectedUsers' => $selectedUsers, 'unselectedUsers' => $unselectedUsers];
    }

    /**
     * Update the selected user in sensitive operation
     * @param  string $id sensitive operation id
     * @return boolean
     */
    public function actionSelectUser($id)
    {
        $id = new \MongoId($id);
        $users = [];
        $params = $this->getParams('users');
        if (!empty($params)) {
            // convert string id to mongo id
            foreach ($params as $userId) {
                $userId = new \MongoId($userId);
                array_push($users, $userId);
            }
        }
        // update the users of sensitive options
        $count = SensitiveOperation::updateAll(['users' => $users], ['_id' => $id]);
        return (boolean) $count;
    }
}
