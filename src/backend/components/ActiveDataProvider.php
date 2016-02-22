<?php
namespace backend\components;

class ActiveDataProvider extends \yii\data\ActiveDataProvider
{
    /**
     * Sets the pagination for this data provider.
     * @param array|Pagination|boolean $value the pagination to be used by this data provider.
     * This can be one of the following:
     *
     * - a configuration array for creating the pagination object. The "class" element defaults
     *   to 'yii\data\Pagination'
     * - an instance of [[Pagination]] or its subclass
     * - false, if pagination needs to be disabled.
     *
     * @throws InvalidParamException
     */
    public function setPagination($value)
    {
        if (is_array($value) && !isset($value['pageSizeLimit'])) {
            $value['pageSizeLimit'] = [1, 100];
        }
        parent::setPagination($value);
    }
}
