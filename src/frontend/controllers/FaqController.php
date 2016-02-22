<?php
  namespace frontend\controllers;

  use yii\web\Controller;
  use Yii;
  use backend\utils\BrowserUtil;
  use backend\utils\UrlUtil;

class FaqController extends Controller
{
    public function actionIndex()
    {
        $accountId = Yii::$app->request->get('accountId');
        $category = Yii::$app->request->get('category');
        if (BrowserUtil::isMobileBrowser()) {
            return $this->redirect(UrlUtil::getDomain() . '/mobile/helpdesk/FAQ?accountId=' . $accountId . '&category=' . $category);
        }

        $this->layout = 'faq';
        $this->getView()->registerJsFile("/vendor/bower/jquery/dist/jquery.min.js");
        $this->getView()->registerJsFile("/vendor/bower/angular/angular.min.js");
        $this->getView()->registerJsFile("/vendor/bower/angular-bindonce/bindonce.min.js");
        $this->getView()->registerJsFile("/build/modules/helpdesk/controllers/faq.js");
        return $this->render('faq', ['category' => $category, 'accountId' => $accountId]);
    }
}
