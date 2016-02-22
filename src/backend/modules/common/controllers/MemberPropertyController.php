<?php
namespace backend\modules\common\controllers;

use Yii;
use yii\helpers\Url;
use yii\helpers\Json;
use yii\web\ServerErrorHttpException;
use yii\web\BadRequestHttpException;
use backend\modules\member\models\MemberProperty;
use backend\modules\member\models\Member;
use backend\modules\member\models\ScoreRule;
use backend\exceptions\InvalidParameterException;
use backend\components\rest\RestController;

class MemberPropertyController extends RestController
{
    public $modelClass = "backend\modules\member\models\MemberProperty";

    public function actions()
    {
        $actions = parent::actions();
        unset($actions['create'], $actions['update'], $actions['delete']);
        return $actions;
    }
}
