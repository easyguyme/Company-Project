<?php
use yii\helpers\Html;
use yii\helpers\Url;
?>
<?php $this->beginPage() ?>
<!DOCTYPE html>
<html lang="<?= Yii::$app->language ?>">
<head>
    <meta charset="<?= Yii::$app->charset ?>"/>
    <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
    <meta name="keywords" content="<?= Yii::$app->params['metaKeywords']?>">
    <meta name="description" content="<?= Yii::$app->params['metaDescription']?>">
    <link rel="canonical" href="https://www.quncrm.com/">
    <?= Html::csrfMetaTags() ?>
    <?php if(defined('KLP') && KLP) { ?>
        <title>聯合利華飲食策劃</title>
        <link rel="shortcut icon" href="/favicon.ico">
    <?php } else { ?>
        <title>群脉SCRM—为您定制的社会化客户管理软件</title>
        <link rel="shortcut icon" href="/favicon.png">
    <?php } ?>
    <?php $this->head() ?>
    <link rel="stylesheet" type="text/css" media="all" href="/build/landing/css/app.css" />
    <?php if (Yii::$app->params['currentEnv'] == 'prod') {?>
        <script>
            //Google GA
            (function(i,s,o,g,r,a,m){i['GoogleAnalyticsObject']=r;i[r]=i[r]||function(){
            (i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),
            m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)
            })(window,document,'script','//www.google-analytics.com/analytics.js','ga');
            ga('create', 'UA-66878725-1', 'auto');
            ga('send', 'pageview');
        </script>
    <?php }?>
</head>
<body>
    <div class="header">
        <div class="container pure-g" id="menu">
            <div class="pure-u-1 pure-u-md-5-24 pure-u-lg-4-24">
                <div class="pure-menu pure-menu-horizontal">
                    <a class="pure-menu-heading" href="<?=Yii::$app->homeUrl ?>"></a>
                    <a href="#" class="custom-toggle" <?php if (Yii::$app->request->getQueryParam('from') === 'wechat') { echo 'hidden'; } ?> id="toggle">
                        <s class="bar"></s>
                        <s class="bar"></s>
                        <s class="bar"></s>
                    </a>
                </div>
            </div>
            <div class="pure-u-1 pure-u-md-11-24 pure-u-lg-14-24">
                <div class="pure-menu pure-menu-horizontal pure-menu-middle custom-can-transform">
                    <ul class="pure-menu-list">
                        <li class="pure-menu-item <?php if (Yii::$app->request->getPathInfo() == '') { echo 'pure-menu-selected'; } ?>">
                            <a href="<?= Url::toRoute('/') ?>" class="pure-menu-link">首页</a>
                        </li>
                        <li class="pure-menu-item <?php if (Yii::$app->request->getPathInfo() == 'site/feature') { echo 'pure-menu-selected'; } ?>">
                            <a href="<?= Url::toRoute('/site/feature') ?>" class="pure-menu-link">功能</a>
                        </li>
                        <li class="pure-menu-item <?php if (Yii::$app->request->getPathInfo() == 'site/case') { echo 'pure-menu-selected'; } ?>">
                            <a href="<?= Url::toRoute('/site/case') ?>" class="pure-menu-link">案例</a>
                        </li>
                        <li class="pure-menu-item helpdesk-consultation">
                            <a class="pure-menu-link">在线咨询</a>
                        </li>
                        <hr class="pure-menu-hr">
                        <!-- hr replace the border of #loginLink -->
                    </ul>
                </div>
            </div>
            <div class="pure-u-1 pure-u-md-8-24 pure-u-lg-6-24">
                <div class="pure-menu pure-menu-horizontal pure-menu-right pure-menu-operations custom-can-transform">
                    <ul class="pure-menu-list">
                        <li class="pure-menu-item" id="useLink">
                            <a href="/site/signup" class="pure-button button-default button-register <?php if (Yii::$app->request->getPathInfo() == '') { echo 'hidden'; } ?>">注册</a>
                        </li>
                        <li class="pure-menu-item" id="loginLink">
                            <a href="/site/login" class="pure-button button-custom">登录</a>
                        </li>
                        <li class="pure-menu-item">
                            <a href="/site/redirect" id="avatarLink">
                                <img class="avatar">
                                <span class="avatar-name fs16"></span>
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
    <?php $this->beginBody() ?>
    <?= $content ?>
    <?php $this->endBody() ?>
    <div class="footer" <?php if (Yii::$app->request->getQueryParam('from') === 'wechat') { echo 'hidden'; } ?>>
        <!-- fixed right sidebar -->
        <div class="extra-wrapper">
            <button class="button-online-consultation helpdesk-consultation"></button>
            <button class="helpdesk-feedback button-online-feedback"></button>
            <button class="button-phone button-extra">
                <div class="phone-content button-extra-content"><img src="/build/landing/images/onlineconsultant_phone_displaycontent.png"></div>
            </button>
            <button class="button-qrcode button-extra">
                <div class="qrcode-content button-extra-content button-extra-qrcode"><img src="/build/landing/images/onlineconsultant_qrcode_displaycontent.png"></div>
            </button>
            <button class="button-backtotop button-extra"></button>
        </div>

        <div class="real-content" <?php if (Yii::$app->request->getUrl() == '/') { echo 'hidden'; } ?>>
            <div class="pure-g">
                <div class="footer-logo pure-u-1 pure-u-md-4-24"></div>
                <div class="pure-u-md-1-24">
                    <div class="vertical-line"></div>
                </div>
                <div class="contact-list pure-u-1 pure-u-md-10-24">
                    <div class="contact-list-item">
                        <div class="item-type">
                            <i class="mail-inquiry"></i>
                            <span>邮件垂询</span>
                        </div>
                        <div class="item-info item-email">
                            <a href="mailto:quncrm@augmentum.com">quncrm@augmentum.com</a>
                        </div>
                    </div>
                    <div class="contact-list-item">
                        <div class="item-type">
                            <i class="sales-hotline"></i>
                            <span>销售热线</span>
                        </div>
                        <div class="item-info">021-51314277 转 400</div>
                    </div>
                    <div class="contact-list-item official-website">
                        <div class="item-type item-type-noicon">群脉由群硕软件自主研发，欢迎访问群硕软件官网</div>
                        <div class="item-info">
                            <a href="http://www.augmentum.com.cn" target="_blank">http://www.augmentum.com.cn</a>
                        </div>
                    </div>
                </div>
                <div class="footer-qrcode pure-u-1 pure-u-md-9-24">
                    <img src="/build/landing/images/footer_qrcode.png" />
                    <div class="wechat-sweep">微信扫一扫</div>
                </div>
            </div>
            <div class="copyright text-right">
                <div>Copyright@<?= date('Y') ?>Augmentum, Inc. All Rights Reserved.</div>
                <div>沪ICP备11006368号-3</div>
            </div>
        </div>
    </div>
    <script src="/build/landing/script/app.js"></script>
    <?php if (Yii::$app->params['currentEnv'] == 'prod') {?>
        <script>
            //Baidu statistics
            var _hmt = _hmt || [];
            (function() {
              var hm = document.createElement("script");
              hm.src = "//hm.baidu.com/hm.js?c51eb81db78b27940d3b31bc915a6f99";
              var s = document.getElementsByTagName("script")[0];
              s.parentNode.insertBefore(hm, s);
            })();
            $(function(){
                $('.pure-menu-link').on('click', function(e){(typeof _hmt!='undefined')&&_hmt.push(['_trackPageview', e.target.href.replace(location.origin, '')])});
            })
        </script>
    <?php }?>
</body>
</html>
<?php $this->endPage() ?>
