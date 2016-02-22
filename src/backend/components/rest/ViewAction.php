<?php
/**
 * Class for default action for DELETE request
 * @author Devin Jin
 *
 **/

namespace backend\components\rest;

use Yii;
use yii\web\ServerErrorHttpException;

class ViewAction extends \yii\rest\ViewAction
{
    public function run($id)
    {
        return parent::run($id);
    }
}