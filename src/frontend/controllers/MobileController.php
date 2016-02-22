<?php
namespace frontend\controllers;

use Yii;
use MongoId;
use yii\web\Controller;
use yii\base\InvalidParamException;
use backend\utils\BrowserUtil;
use backend\models\Goods;
use yii\web\View;
use backend\models\Token;
use backend\modules\member\models\MemberLogs;
use backend\utils\LogUtil;
use backend\models\DingUser;
use yii\web\Cookie;
use backend\modules\member\models\Member;
use backend\utils\UrlUtil;

/**
 * Site controller
 */
class MobileController extends Controller
{
    const NOT_FOUND_PAGE_PATH = '/site/missing';
    const VENDOR_PATH = '/vendor/bower/';
    const BUILD_PATH = '/build/webapp/';

    public $layout = 'mobile';

    /**
     * Render member module mobile pages
     */
    public function actionMember($page)
    {
        if ($page === 'personal') {
            $this->registerBodyJsFile(self::VENDOR_PATH . 'hammer.js/hammer.min.js');
        }
        if ($page === 'personal' || $page === 'score') {
            $this->registerBodyJsFile(self::VENDOR_PATH . 'moment/min/moment.min.js');
        }
        if ($page === 'coupon') {
            $this->registerBodyJsFile(self::VENDOR_PATH . 'Swiper/dist/js/swiper.min.js');
            $this->view->registerCssFile(self::VENDOR_PATH . 'Swiper/dist/css/swiper.css');
        }
        $baiduJs = 'var _hmt = _hmt || [];
                    (function() {
                      var hm = document.createElement("script");
                      hm.src = "//hm.baidu.com/hm.js?c51eb81db78b27940d3b31bc915a6f99";
                      var s = document.getElementsByTagName("script")[0];
                      s.parentNode.insertBefore(hm, s);
                    })();';
        $this->view->registerJs($baiduJs, View::POS_END);
        return $this->renderPage($page);
    }

    public function actionCommon($page)
    {
        $version = Yii::$app->params['buildVersion'];
        $this->view->registerCssFile(self::BUILD_PATH . 'common/app.css');
        $data = null;
        $pages = ['attention', 'remind', 'wbattention'];
        if (in_array($page, $pages)) {
            $this->registerBodyJsFile(self::BUILD_PATH . "common.js?v=$version");
            $this->registerBodyJsFile(self::VENDOR_PATH . 'zepto/zepto.min.js');
            $this->registerBodyJsFile(self::BUILD_PATH . "common/$page/index.js");
            if ($page === 'wbattention') {
                $channelId = Yii::$app->request->get('channelId');
                if (empty($channelId)) {
                    return $this->redirect('/mobile/common/404');
                }
                $channel = Yii::$app->weConnect->getAccount($channelId);
                if (empty($channel) || empty($channel['appId'])) {
                    return $this->redirect('/mobile/common/404');
                }

                $data = ['channel' => $channel];
            }
        }

        if (!empty($data)) {
            return $this->render('common/' . $page, $data);
        } else {
            return $this->render('common/' . $page);
        }
    }

    /**
     * Render product module mobile pages
     */
    public function actionProduct($page)
    {
        if ($page === 'detail') {
            $goodsId = Yii::$app->request->get('goodsId');
            Goods::updateAll(['$inc' => ['clicks' => 1]], ['_id' => new \MongoId($goodsId)]);
        }
        if ($page === 'list' || $page === 'detail') {
            $this->registerBodyJsFile(self::VENDOR_PATH . 'Swiper/dist/js/swiper.min.js');
        }
        $js = '$($(".mb-breadcrumb-back").click(function(){window.history.back()}))';
        $this->view->registerJs($js, View::POS_END);
        $params = Yii::$app->request->get();
        $isPreview = !empty($params['preview']);
        return $this->renderPage($page, false, !$isPreview, !$isPreview);
    }

    /**
     * Render campaign related mobile pages
     */
    public function actionCampaign($page)
    {
        return $this->renderPage($page);
    }

    public function actionFeedback($page)
    {
        $actionName = $this->action->id;
        $this->registerCommonResource($page);
        $params = Yii::$app->request->get();
        $follower = Yii::$app->weConnect->getFollowerByOriginId($params['openId'], $params['channelId']);
        $nickname = '';
        if (!empty($follower)) {
            $nickname = $follower['nickname'];
        }
        $accessToken = $this->getAccessToken();
        return $this->render($actionName . '/' . $page, ['nickname' => $nickname, 'token' => $accessToken]);
    }

    public function actionHelpdesk($page)
    {
        $actionName = $this->action->id;
        $this->registerCommonResource($page);
        return $this->render($actionName . '/' . $page);
    }

    public function actionReservation($page)
    {
        return $this->renderPage($page, true);
    }

    public function actionDingding($page)
    {
        //check dingUser enableAction
        if ($page === 'index') {
            $dingUserId = Yii::$app->request->get('dingUserId');
            $dingUser = DingUser::findByPk(new MongoId($dingUserId));
            if (empty($dingUser) || !in_array(DingUser::ACTION_MOBILE_POS, $dingUser->enableActions)) {
                return $this->redirect('/mobile/common/dd403');
            } else {
                $token = Token::createForMobile($dingUser->accountId);
                $this->setAccessToken($token['accessToken']);
            }
        }
        return $this->renderPage($page, true, false, false, true);
    }

    public function actionContent($page)
    {
        // check member by id
        if ($page === 'member') {
            $memberId = Yii::$app->request->get('memberId');
            $member = Member::findByPk(new MongoId($memberId));
            if (empty($member)) {
                return $this->redirect('/mobile/common/dd403');
            }
        }
        return $this->renderPage($page, true, false, false, false);
    }

    private function renderPage($page, $useRiot = false, $injectWechatJS = true, $socialWebviewOnly = true, $injectDingdingJS = false)
    {
        $actionName = $this->action->id;
        $params = Yii::$app->request->get();
        $this->registerCommonResource($page, $useRiot, $injectWechatJS, $injectDingdingJS);
        $logObj = json_encode([
                    'url' => Yii::$app->params['frontendTrackUrl'],
                    'env' => Yii::$app->params['currentEnv']
                  ]);
        $trackerJs = "window.trackerLog=$logObj;";
        $this->view->registerJs($trackerJs, View::POS_HEAD);
        if ($injectWechatJS) {
            $sdk = Yii::$app->wechatSdk;
            $sdk->refererUrl = $sdk->refererDomain . substr(Yii::$app->request->getUrl(), 1);
            $signPackage = json_encode($sdk->getSignPackage());
            $js = "var options=$signPackage, page='$page';";
            $this->view->registerJs($js, View::POS_HEAD);
            if (empty($params['debug'])) {
                $this->registerBodyJsFile(self::BUILD_PATH . 'handlewechat.js?v=' . Yii::$app->params['buildVersion']);
            }
        }
        if ($injectDingdingJS) {
            $currentUrl = UrlUtil::getDomain() . Yii::$app->request->getUrl();
            $suiteKey = $params['suiteKey'];
            $corpId = $params['corpid'];
            $appId = $params['appId'];
            if (empty($suiteKey) || empty($corpId) || empty($appId)) {
                return $this->redirect('/mobile/common/dd403');
            }
            $ddsdk = Yii::$app->ddJsSdk;
            $ddsignPackage = json_encode($ddsdk->getConfig($suiteKey, $corpId, $appId, $currentUrl));
            $ddjs = "var ddoptions=$ddsignPackage;";
            $this->view->registerJs($ddjs, View::POS_HEAD);
            if (empty($params['debug'])) {
                $this->registerBodyJsFile(self::BUILD_PATH . 'handledingding.js?v=' . Yii::$app->params['buildVersion']);
            }
        }
        $socialWebviewOnly = $socialWebviewOnly && empty($params['debug']);
        if ($socialWebviewOnly && !BrowserUtil::isWeixinBrowser() && !BrowserUtil::isWeiboBrower() && !BrowserUtil::isAliBrower()) {
            $this->view->js = null;
            $this->view->jsFiles = null;
            $this->view->cssFiles = null;
            $this->view->registerCssFile(self::BUILD_PATH . 'common/app.css');
            return $this->render('common/error');
        } else {
            //Member active record
            $accessToken = $this->getAccessToken();
            if (!empty($params['memberId']) && !empty($accessToken)) {
                $memberId = new \MongoId($params['memberId']);
                $accountId = Token::getAccountId($accessToken);
                MemberLogs::record($memberId, $accountId, MemberLogs::OPERATION_VIEWED);
            }
            return $this->render($actionName . '/' . $page);
        }
    }

    private function registerCommonResource($page, $useRiot = false, $injectWechatJS = true, $injectDingdingJS = false)
    {
        $version = Yii::$app->params['buildVersion'];
        $actionName = $this->action->id;
        $externals = [];
        $venderFiles = ['zepto/zepto.min.js'];
        $customFiles = ['common.js'];
        if ($useRiot) {
            $this->view->params['page'] = $page;
            $venderFiles[] = 'lib.flexible/flexible.js';
            $externals[] = self::VENDOR_PATH . 'riot/riot.min.js';
            //Inject components css file
            $this->view->registerCssFile(self::BUILD_PATH . "components/app.css");
        }
        $params = Yii::$app->request->get();
        if (empty($params['debug'])) {
            $venderFiles[] = 'alogs/alog.min.js';
            $customFiles[] = 'tracker.js';
            if ($injectDingdingJS) {
                $externals[] = '//g.alicdn.com/ilw/ding/0.5.1/scripts/dingtalk.js';
            }
            if ($injectWechatJS) {
                $externals = ['//res.wx.qq.com/open/js/jweixin-1.0.0.js', '//tpm.oneapm.com/static/js/bw-loader.js'];
            }
        }
        //Inject CSS file for page
        $this->view->registerCssFile(self::BUILD_PATH . "$actionName/app.css?v=$version");
        //Inject common vendor JS
        foreach ($externals as $file) {
            $this->registerHeadJsFile($file);
        }
        foreach ($venderFiles as $file) {
            $this->registerBodyJsFile(self::VENDOR_PATH . $file);
        }
        foreach ($customFiles as $file) {
            $this->registerBodyJsFile(self::BUILD_PATH . $file . "?v=$version");
        }
        //Inject page js file
        $this->registerBodyJsFile(self::BUILD_PATH . $actionName . "/$page/index.js?v=$version");
    }

    private function registerBodyJsFile($file)
    {
        $this->view->registerJsFile($file, ['position' => View::POS_END]);
    }

    private function registerHeadJsFile($file)
    {
        $this->view->registerJsFile($file, ['position' => View::POS_HEAD]);
    }

    private function getAccessToken()
    {
        $cookies = Yii::$app->request->cookies;
        $token = '';
        if (($cookie = $cookies->get('accesstoken')) !== null) {
            $token = $cookie->value;
        }
        return $token;
    }

    private function setAccessToken($accessToken)
    {
        $cookies = Yii::$app->response->cookies;
        $cookies->add(new Cookie(['name' => 'accesstoken', 'value' => $accessToken, 'expire' => time() + Token::EXPIRE_TIME]));
    }
}
