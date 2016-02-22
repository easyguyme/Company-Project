<?php use yii\helpers\Html; ?>
<?php $this->beginPage() ?>
<!DOCTYPE html>
<html lang="<?= Yii::$app->language ?>">
<head>
    <meta charset="<?= Yii::$app->charset ?>"/>
    <meta name="viewport" content="width=device-width,initial-scale=1,maximum-scale=1,user-scalable=no">
    <meta name="keywords" content="<?= Yii::$app->params['metaKeywords']?>">
    <meta name="description" content="<?= Yii::$app->params['metaDescription']?>">
    <link rel="canonical" href="https://www.quncrm.com/">
    <?= Html::csrfMetaTags() ?>
    <title>群脉</title>
    <link rel="shortcut icon" href="/favicon.png">
    <link rel="stylesheet" href="/build/webapp/msite/article/app.css">
    <script src="/vendor/bower/zepto/zepto.min.js"></script>
</head>
<body>
    <?php $this->beginBody() ?>
    <?= $content ?>
    <?php $this->endBody() ?>
</body>
</html>
<?php $this->endPage() ?>
