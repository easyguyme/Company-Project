<?php use yii\helpers\Html; ?>
<?php $this->beginPage() ?>
<!DOCTYPE html>
<html lang="<?= Yii::$app->language ?>">
<head>
    <meta charset="<?= Yii::$app->charset ?>"/>
    <meta name="viewport" content="width=device-width,initial-scale=1,maximum-scale=1,user-scalable=no">
    <meta name="keywords" content="<?= Yii::$app->params['metaKeywords']?>">
    <meta name="description" content="<?= Yii::$app->params['metaDescription']?>">
    <meta name="format-detection" content="telephone=no, email=no">
    <link rel="canonical" href="https://www.quncrm.com/">
    <?= Html::csrfMetaTags() ?>
    <title>群脉</title>
    <link rel="shortcut icon" href="/favicon.png">
    <!--start oneapm-->
    <script type='text/javascript'>window.BWEUM||(BWEUM={});BWEUM.info = {"stand":true,"agentType":"browser","agent":"tpm.oneapm.com/static/js/bw-send.js","beaconUrl":"tpm.oneapm.com/beacon","licenseKey":"je~26bRmmiNBn7Yf","applicationID":6756};</script>
    <!--end oneapm-->
    <?php $this->head() ?>
</head>
<?php $pageClazz = isset($this->params['page']) ? $this->params['page'] . '-page' : '' ; ?>
<body class="<?= isset($pageClazz) ? $pageClazz : '' ?>">
    <?php $this->beginBody() ?>
    <?= $content ?>
    <?php $this->endBody() ?>
</body>
</html>
<?php $this->endPage() ?>
