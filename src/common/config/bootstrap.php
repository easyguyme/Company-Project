<?php
require_once "config.php";
Yii::setAlias('common', dirname(__DIR__));
Yii::setAlias('root', dirname(dirname(__DIR__)));
Yii::setAlias('frontend', dirname(dirname(__DIR__)) . '/frontend');
Yii::setAlias('backend', dirname(dirname(__DIR__)) . '/backend');
Yii::setAlias('webapp', dirname(dirname(__DIR__)) . '/webapp');
Yii::setAlias('console', dirname(dirname(__DIR__)) . '/console');
