<?php
use yii\helpers\Html;
use frontend\assets\AppAsset;

/* @var $this \yii\web\View */
/* @var $content string */

AppAsset::register($this);
?>
<?php $this->beginPage() ?>
<!DOCTYPE html>
<html lang="<?= Yii::$app->language ?>" class="gray-bg chat-screem-height">
<head>
    <meta charset="<?= Yii::$app->charset ?>"/>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="keywords" content="<?= Yii::$app->params['metaKeywords']?>">
    <meta name="description" content="<?= Yii::$app->params['metaDescription']?>">
    <link rel="canonical" href="https://www.quncrm.com/">
    <?= Html::csrfMetaTags() ?>
    <title translate="chat_title"></title>
    <link rel="shortcut icon" href="/favicon.png">
    <?php $this->head() ?>
    <?= '<script> window.trackerLog={url:"' . Yii::$app->params['frontendTrackUrl'] . '",env:"' . Yii::$app->params['currentEnv'] . '"};</script>';?>
    <link rel="stylesheet" href="/build/chat/app.css">
    <script src="/vendor/bower/tuisongbao/engine.min.js"></script>
</head>
<body class="chat-screem-height">
    <?php $this->beginBody() ?>
    <?= $content ?>
    <?php $this->endBody() ?>
    <script data-main="/build/chat/main" src="/vendor/bower/requirejs/require.min.js"></script>
</body>
</html>
<?php $this->endPage() ?>
