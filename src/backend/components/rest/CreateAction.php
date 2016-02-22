<?php
namespace backend\components\rest;

use Yii;
use yii\helpers\Url;
use yii\web\ServerErrorHttpException;
use backend\models\Token;
use backend\components\BaseModel;
use backend\components\BaseControllerTrait;

/**
 * Class for default action for POST request
 * @author Devin Jin
 *
 **/

class CreateAction extends \yii\rest\CreateAction
{
    use BaseControllerTrait;

    public function run()
    {
        if ($this->checkAccess) {
            call_user_func($this->checkAccess, $this->id);
        }

        /* @var $model \yii\db\ActiveRecord */
        $model = new $this->modelClass([
            'scenario' => BaseModel::SCENARIO_CREATE,
        ]);

        $body = Yii::$app->request->getRawBody();
        $attributes = json_decode($body, true);
        $token = $this->getAccessToken();
        $accountId = $this->getAccountId();
        $attributes['accountId'] = $accountId;
        $model->load($attributes, '');

        if ($model->save()) {
            $response = Yii::$app->getResponse();
            $response->setStatusCode(201);
            $id = implode(',', array_values($model->getPrimaryKey(true)));
            $response->getHeaders()->set('Location', Url::toRoute([$this->viewAction, 'id' => $id], true));
        } elseif (!$model->hasErrors()) {
            throw new ServerErrorHttpException('Failed to create the object for unknown reason.');
        }

        return $model;
    }
}
