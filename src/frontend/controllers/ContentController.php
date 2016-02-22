<?php
namespace frontend\controllers;

use yii\web\Controller;
use yii\base\InvalidParamException;
use backend\models\Graphic;
use Yii;

/**
 * Content controller
 */
class ContentController extends Controller
{
    public $layout = 'graphic';

    /**
     * Render graphic page
     */
    public function actionGraphic($id)
    {
        $index = Yii::$app->request->getQueryParam('index', 0);

        $graphicId = new \MongoId($id);
        $graphic = Graphic::findByPk($graphicId);

        $graphicContentUrl = '';
        if (!empty($graphic) && !empty($graphic->articles[$index])) {
            $article = $graphic->articles[$index];
            !empty($article["contentUrl"]) && ($graphicContentUrl = $article["contentUrl"]);
        }

        $this->getView()->registerJsFile("/vendor/bower/jquery/dist/jquery.min.js");
        $this->getView()->registerJsFile("/vendor/bower/jquery-qrcode/jquery.qrcode.min.js");
        $this->getView()->registerJsFile("/build/webapp/msite/graphic/index.js");
        return $this->render('graphic', array('url' => $graphicContentUrl));
    }
}
