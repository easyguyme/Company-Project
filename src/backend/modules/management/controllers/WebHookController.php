<?php
namespace backend\modules\management\controllers;

use Yii;
use backend\components\rest\RestController;
use yii\web\ServerErrorHttpException;
use backend\models\WebHook;

class WebHookController extends RestController
{
    public $modelClass = 'backend\models\WebHook';

    public function actions()
    {
        $actions = parent::actions();
        unset($actions['delete']);
        return $actions;
    }

    public function actionDelete($id)
    {
        $webhook = WebHook::findByPK(new \MongoId($id));
        if ($webhook->delete() == false) {
            throw new ServerErrorHttpException('Failed to delete webhook for unknown reason.');
        }
        Yii::$app->getResponse()->setStatusCode(204);
    }
}
