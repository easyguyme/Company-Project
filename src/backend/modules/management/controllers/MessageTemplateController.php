<?php
namespace backend\modules\management\controllers;

use Yii;
use backend\models\MessageTemplate;
use yii\web\BadRequestHttpException;
use backend\components\rest\RestController;

class MessageTemplateController extends RestController
{
    public $modelClass = 'backend\models\MessageTemplate';

    public function actions()
    {
        $actions = parent::actions();
        unset($actions['index'], $actions['create'], $actions['update'], $actions['delete']);
        return $actions;
    }

    /**
     * get the name of template
     */
    public function actionIndex()
    {
        $accountId = $this->getAccountId();
        return MessageTemplate::findAll(['accountId' => $accountId]);
    }

    /**
     * update the template
     */
    public function actionUpdate($id)
    {
        $params = $this->getParams();
        if (empty($params['data'])) {
            throw new BadRequestHttpException('missing params');
        }

        unset($params['data']['name'], $params['data']['id']);
        $params['data']['updatedAt'] = new \MongoDate();
        MessageTemplate::updateAll($params['data'], ['_id' => new \MongoId($id)]);
    }
}
