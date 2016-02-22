<?php
/**
 * Class for default action for DELETE request
 * @author Devin Jin
 *
 **/

namespace backend\components\rest;

use Yii;
use yii\web\ServerErrorHttpException;

class DeleteAction extends \yii\rest\DeleteAction
{
    public function run($id)
    {
        if ($this->checkAccess) {
            call_user_func($this->checkAccess, $this->id);
        }

        $idList = explode(',', $id);

        foreach ($idList as &$perId) {
            $perId = new \MongoId($perId);
        }

        $modelClass = $this->modelClass;

        if ($modelClass::deleteAll(['in', '_id', $idList]) == false) {
            throw new ServerErrorHttpException('Failed to delete the object for unknown reason.');
        }

        Yii::$app->getResponse()->setStatusCode(204);
    }
}
