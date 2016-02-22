<?php
/**
 * Class for default action for POST request
 * @author Devin Jin
 *
 **/

namespace backend\components\rest;

use Yii;
use backend\components\BaseModel;
use yii\web\ServerErrorHttpException;

class UpdateAction extends \yii\rest\UpdateAction
{
    public function run($id)
    {
        //transfer the id from string to MongoId
        $id = new \MongoId($id);
        $model = $this->findModel($id);

        if ($this->checkAccess) {
            call_user_func($this->checkAccess, $this->id, $model);
        }

        $model->scenario = BaseModel::SCENARIO_UPDATE;
        $model->load(json_decode(Yii::$app->getRequest()->getRawBody(), true), '');

        if ($model->save() === false && !$model->hasErrors()) {
            throw new ServerErrorHttpException('Failed to update the object for unknown reason.');
        }

        $model->_id .= '';
        return $model;
    }
}
