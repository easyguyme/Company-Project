<?php
namespace backend\controllers;

use backend\components\Controller;
use backend\utils\LogUtil;

/**
 * Log controller
 */
class LogController extends Controller
{
    const PARAM_TYPE = 't';
    const FRONTEND = 'frontend';
    const ERROR_TYPE = 'err';
    const IMAGE_CONTENT_TYPE = 'Content-Type:image/png';

    public function actionFrontend()
    {
        $params = $this->getQuery();
        if (self::ERROR_TYPE === $params[self::PARAM_TYPE]) {
            LogUtil::error($params, self::FRONTEND);
        } else {
            LogUtil::info($params, self::FRONTEND);
        }
        header(self::IMAGE_CONTENT_TYPE);
    }
}
