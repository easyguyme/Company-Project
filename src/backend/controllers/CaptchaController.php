<?php
namespace backend\controllers;

use Yii;
use backend\components\Controller;
use backend\utils\StringUtil;
use Gregwar\Captcha\CaptchaBuilder;

class CaptchaController extends Controller
{
    public function actionIndex()
    {
        $codeId = (string) new \MongoId();
        $code = StringUtil::rndString(4, 1);
        $builder = new CaptchaBuilder($code);
        $builder->build(160, 50);
        $cache = Yii::$app->cache;
        $duration = Yii::$app->params['img_captcha_availab_time'];
        $cache->set($codeId, $code, $duration);

        return ['message' => 'OK', 'data' => $builder->inline(), 'codeId' => $codeId];
    }
}
