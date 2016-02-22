<?php
namespace backend\modules\chat\controllers;

use backend\modules\chat\traits\ControllerTrait;
/**
 * Base rest controller for chat module
 * @author Harry Sun
 */
class RestController extends \backend\components\rest\RestController
{
    use ControllerTrait;
}
