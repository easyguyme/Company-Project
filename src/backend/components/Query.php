<?php

namespace backend\components;


/**
 * This is the base query class for aug-marketing
 * It is used to query mongodb.
 * @author Harry Sun
 *
 **/
class Query extends \yii\mongodb\Query
{
    /**
     * Define the status
     */
    const DELETED = true;
    const NOT_DELETED = false;

    /**
     * Rewrite init function and add isDelete condition
     */
    public function init()
    {
        parent::init();
        $this->andWhere(['isDeleted' => self::NOT_DELETED]);
    }
}