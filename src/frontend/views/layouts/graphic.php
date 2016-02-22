<?php use yii\helpers\Html; ?>
<?php $this->beginPage() ?>
<!DOCTYPE html>
<html lang="<?= Yii::$app->language ?>">
<head>
    <meta charset="<?= Yii::$app->charset ?>"/>
    <meta name="keywords" content="<?= Yii::$app->params['metaKeywords']?>">
    <meta name="description" content="<?= Yii::$app->params['metaDescription']?>">
    <link rel="canonical" href="https://www.quncrm.com/">
    <?= Html::csrfMetaTags() ?>
    <title>群脉</title>
    <link rel="shortcut icon" href="/favicon.png">
    <link rel="stylesheet" href="/build/webapp/msite/graphic/app.css">
</head>
<body>
    <?php $this->beginBody() ?>
    <?= $content ?>
    <?php $this->endBody() ?>
</body>
</html>
<?php $this->endPage() ?>
