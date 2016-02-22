<?php
use yii\helpers\Html;
//use frontend\assets\AppAsset;

/* @var $this \yii\web\View */
/* @var $content string */

//AppAsset::register($this);
?>
<?php $this->beginPage() ?>
<!DOCTYPE html>
<html lang="<?= Yii::$app->language ?>">
<head>
    <meta charset="<?= Yii::$app->charset ?>"/>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="keywords" content="<?= Yii::$app->params['metaKeywords']?>">
    <meta name="description" content="<?= Yii::$app->params['metaDescription']?>">
    <link rel="canonical" href="https://www.quncrm.com/">
    <?= Html::csrfMetaTags() ?>
    <?php if(defined('KLP') && KLP) { ?>
        <title>聯合利華飲食策劃</title>
        <link rel="shortcut icon" href="/favicon.ico">
    <?php } else { ?>
        <title>群脉</title>
        <link rel="shortcut icon" href="/favicon.png">
    <?php } ?>
    <link href="/build/app.css?v=<?= Yii::$app->params['buildVersion'] ?>" rel="stylesheet">
    <?php $this->head() ?>
    <?= '<script> window.trackerLog={url:"' . Yii::$app->params['frontendTrackUrl'] . '",env:"' . Yii::$app->params['currentEnv'] . '"};</script>';?>
    <?= '<script> window.config={shortUrlDomain:"' . Yii::$app->params['shortUrlDomain'] . '"};</script>';?>
    <!--[if lte IE 9]>
    <script type="text/javascript" src="/vendor/bower/es5-shim/es5-shim.min.js"></script>
    <script type="text/javascript" src="/vendor/bower/html5shiv/dist/html5shiv.min.js"></script>
    <script type="text/javascript" src="/vendor/bower/respond/dest/respond.min.js"></script>
    <![endif]-->
</head>
<body>
    <?php $this->beginBody() ?>
    <?= $content ?>
    <?php $this->endBody() ?>
    <script src="//dn-tsbengine.qbox.me/engine.min.js"></script>
    <script src="/vendor/bower/requirejs/require.min.js"></script>
    <script src="/build/main.js?v=<?= Yii::$app->params['buildVersion'] ?>"></script>
</body>
</html>
<?php $this->endPage() ?>
