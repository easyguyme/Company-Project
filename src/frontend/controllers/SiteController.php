<?php
namespace frontend\controllers;

use Yii;
use yii\web\View;
use yii\web\Controller;
use yii\base\InvalidParamException;
use yii\web\BadRequestHttpException;
use yii\helpers\Json;
use backend\utils\LanguageUtil;

/**
 * Site controller
 */
class SiteController extends Controller
{
    /**
     * Define the type of statement and agreement
     */
    const AGREEMENT_DEFAULT = 'statement';
    const ANGULAR_INDEX = 'index';

    public function actionIndex($action)
    {
        $landingAction = ['landing', 'feature', 'case', 'signup', 'message', 'agreement'];
        $view = in_array($action, $landingAction) ? $action : 'index';
        if ($view !== self::ANGULAR_INDEX) {
            $this->layout = 'landing';
            return $this->renderPage($view, true);
        } else {
            Yii::$app->language = LanguageUtil::getBrowserLanguage();
            return $this->render($view);
        }
    }

    public function actionLanding()
    {
        $this->layout = 'landing';
        return $this->renderPage('landing', true);
    }

    /**
     * Render customer service chat page
     */
    public function actionChat()
    {
        Yii::$app->language  =  LanguageUtil::getBrowserLanguage();
        $this->layout = 'chat';
        return $this->render('chat');
    }

    private function renderPage($page, $isLanding = false)
    {
        $sdk = Yii::$app->wechatSdk;
        $sdk->refererUrl = $sdk->refererDomain . substr(Yii::$app->request->getUrl(), 1);
        $signPackage = Json::encode($sdk->getSignPackage());
        $helpdeskAccountId = HELPDESK_ACCOUNT_ID;
        $js = "var options=$signPackage, page='$page';";
        if ($isLanding) {
            $js = $js . "var helpdeskAccountId='$helpdeskAccountId';";
        }
        $this->view->registerJs($js, View::POS_END);
        $externals = ['//res.wx.qq.com/open/js/jweixin-1.0.0.js'];
        foreach ($externals as $file) {
            $this->view->registerJsFile($file, ['position' => View::POS_END]);
        }

        return $this->render($page);
    }
}
