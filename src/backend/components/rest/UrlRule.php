<?php

namespace backend\components\rest;

class UrlRule extends \yii\rest\UrlRule
{
    public $tokens = [
        '{id}' => '<id:[\\d\\w,]*>',
    ];
}