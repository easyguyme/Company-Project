<?php
namespace backend\utils;

use Yii;
use yii\web\BadRequestHttpException;

/**
 * Validate required parameters, if empty will throw BadRequestHttpException
 * @author Mike Wang
 */
class ValidatorUtil
{

    /**
     * Validate required parameters, if empty will throw BadRequestHttpException
     * @param  array  $data The source data contains keys need be check
     * @param  array  $requiredFields the required fields
     * @param  boolean $throwable  if true throw BadRequestHttpException
     * @return bool
     */
    public static function fieldsRequired($data, $requiredFields, $throwable = true)
    {
        if (!is_array($requiredFields)) {
            $requiredFields = [$requiredFields];
        }
        foreach ($requiredFields as $key => $value) {
            if (is_array($value)) {
                 if (!isset($data[$key]) ||
                    (empty($data[$key]) && !is_numeric($data[$key]) && !is_bool($data[$key]))
                   ) {
                    if ($throwable) {
                        throw new BadRequestHttpException(Yii::t('common', 'parameters_missing'));
                    }
                    return false;
                }
                if (!self::fieldsRequired($data[$key], $value)) {
                    return false;
                }
            } else {
                 if (!isset($data[$value]) ||
                    (empty($data[$value]) && !is_numeric($data[$value]) && !is_bool($data[$value]))) {
                    if ($throwable) {
                        throw new BadRequestHttpException(Yii::t('common', 'parameters_missing'));
                    }
                    return false;
                }
                if (is_array($data[$value])) {
                    if (!self::fieldsRequired($data[$value], array_keys($data[$value]))) {
                        return false;
                    }
                }
            }
        }
        return true;
    }
}
