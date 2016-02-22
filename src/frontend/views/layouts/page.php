<?php

use yii\helpers\Html;

?>
<?php $this->beginPage() ?>
<!DOCTYPE html>
<html lang="<?= Yii::$app->language ?>">
<head>
    <?php $page = $this->params['page']; ?>
    <?php $pageRGBColor = $this->params['pageRGBColor']; ?>
    <meta charset="<?= Yii::$app->charset ?>"/>
    <meta content="yes" name="apple-mobile-web-app-capable" />
    <meta content="yes" name="apple-touch-fullscreen" />
    <meta content="telephone=no,email=no" name="format-detection" />
    <meta name="viewport" content="width=device-width,maximum-scale=1,user-scalable=no">
    <meta name="keywords" content="<?= Yii::$app->params['metaKeywords']?>">
    <meta name="description" content="<?= Yii::$app->params['metaDescription']?>">
    <link rel="canonical" href="https://www.quncrm.com/">
    <?= Html::csrfMetaTags() ?>
    <title><?= isset($page->title) ? $page->title : '群脉' ; ?></title>
    <link rel="shortcut icon" href="/favicon.png">
    <!-- <link rel="stylesheet" href="/vendor/bower/lib.flexible/flexible.css"> -->
    <link rel="stylesheet" href="/vendor/bower/photoswipe/dist/photoswipe-3.0.5.css">
    <link rel="stylesheet" href="/build/webapp/msite/page/app.css">
    <style>
        .m-color {color: <?= $page->color; ?>!important;}
        .m-color:visited {color: <?= $page->color; ?>!important;}
        .m-color:hover {color: <?= $page->color; ?>!important;}
        .m-bgcolor {background-color: <?= $page->color; ?>!important;}
        .m-border-color {border-color: <?= $page->color; ?>!important;}
        .m-pic-title-bgcolor {background-color: <?= 'RGBA(' . $pageRGBColor . ', 0.5)'; ?>!important;}
        .m-tab-title-bgcolor {background-color: <?= 'RGBA(' . $pageRGBColor . ', 0.1)'; ?>!important;}
        html, body, #cpt-wrap {
            width: 100%;
            height: 100%;
        }
        /*Page related styles*/
        .m-page-title {
            text-align: center;
            font-size: 0.56rem;
            height: 1.375rem;
            line-height: 1.375rem;
            color: #fff;
        }
        .m-radio {
            background-image: url("/images/microsite/radiobutton.png");
        }
        .real-radio:checked + .m-radio {
            background-image: url("/images/microsite/radiobutton_check_<?= substr($page->color, 1); ?>.png");
        }
        .m-checkbox {
            background-image: url("/images/microsite/checkbox.png");
        }
        .real-checkbox:checked + .m-checkbox {
            background-image: url("/images/microsite/checkbox_check_<?= substr($page->color, 1); ?>.png");
        }
    </style>
</head>
<body>
    <?php $this->beginBody() ?>
    <div id="cpt-wrap">
        <?= $content ?>
    </div>
    <?php $this->endBody() ?>
</body>
</html>
<?php $this->endPage() ?>
