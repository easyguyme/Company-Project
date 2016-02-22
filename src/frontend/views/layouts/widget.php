<?php

use yii\helpers\Html;

?>
<?php $this->beginPage() ?>
<!DOCTYPE html>
<html lang="<?= Yii::$app->language ?>" style="font-size: 27.8px;">
<head>
    <?php $type = $this->params['type']; ?>
    <?php $color = $this->params['color']; ?>
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
    <title><?= isset($type) ? $type : '群脉组件' ; ?></title>
    <link rel="shortcut icon" href="/favicon.png">
    <!-- <link rel="stylesheet" href="/vendor/bower/lib.flexible/flexible.css"> -->
    <!--Only needed for photoswipe dependent widget-->
    <?php if (strpos($type, 'album') !== false) { ?>
        <link rel="stylesheet" href="/vendor/bower/photoswipe/dist/photoswipe-3.0.5.css">
    <?php
}
    ?>
    <link rel="stylesheet" href="/build/webapp/msite/page/app.css">
    <style>
        .m-color {color: <?= $color; ?>!important;}
        .m-color:visited {color: <?= $color; ?>!important;}
        .m-color:hover {color: <?= $color; ?>!important;}
        .m-bgcolor {background-color: <?= $color; ?>!important;}
        .m-border-color {border-color: <?= $color; ?>!important;}
        .m-pic-title-bgcolor {background-color: <?= 'RGBA(' . $pageRGBColor . ', 0.5)'; ?>!important;}
        .m-radio {
            background-image: url("/images/microsite/radiobutton.png");
        }
        .real-radio:checked + .m-radio {
            background-image: url("/images/microsite/radiobutton_check_<?= substr($color, 1); ?>.png");
        }
        .m-checkbox {
            background-image: url("/images/microsite/checkbox.png");
        }
        .real-checkbox:checked + .m-checkbox {
            background-image: url("/images/microsite/checkbox_check_<?= substr($color, 1); ?>.png");
        }
        <?php if (strpos($type, 'cover') !== false) { ?>
            html, body, #cpt-wrap {
                width: 100%;
                height: 100%;
            }
        <?php
}
        ?>
        html {
            overflow-y: hidden;
        }
    </style>
</head>
<body>
    <?php $this->beginBody() ?>
    <div id="cpt-wrap" data-type="<?= $type; ?>">
        <?= $content ?>
    </div>
    <?php $this->endBody() ?>
</body>
</html>
<?php $this->endPage() ?>
