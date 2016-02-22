<?php
namespace frontend\controllers;

use Yii;
use yii\web\Controller;

/*
 * Map Controller
 */
class MapController extends Controller {

    const STORE_MAP_PATH = '/map/index';
    const MAP_SCRIPT_PATH = '/build/webapp/map/';
    const MICROSITE_MAP_PATH = '/map/site';
    const MAP_LAYOUT = 'map';

    public function actionStore()
    {
        $this->layout = self::MAP_LAYOUT;
        $actionView = $this->getView();
        $actionView->registerJsFile(self::MAP_SCRIPT_PATH . 'store/index.js');
        return $this->render(self::STORE_MAP_PATH);
    }

    public function actionMicrosite()
    {
        $this->layout = self::MAP_LAYOUT;
        $actionView = $this->getView();
        $actionView->registerJsFile(self::MAP_SCRIPT_PATH . 'microsite/index.js');
        return $this->render(self::MICROSITE_MAP_PATH);
    }
}
