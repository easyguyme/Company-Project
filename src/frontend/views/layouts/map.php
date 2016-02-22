<?php
use yii\helpers\Html;
use frontend\assets\AppAsset;

AppAsset::register($this);
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
    <link rel="stylesheet" href="/build/app.css">
    <link rel="stylesheet" href="/build/webapp/map/app.css">
    <script type="text/javascript" src="http://api.map.baidu.com/api?v=2.0&ak=4RUuVxiI5kIZOcfIqAWXpFVC"></script>
    <script src="/vendor/bower/zepto/zepto.min.js"></script>
    <title>搜索定位</title>
</head>
<body>
    <?php $this->beginBody() ?>
    <?= $content ?>
    <?php $this->endBody() ?>
</body>
</html>
<?php $this->endPage() ?>
