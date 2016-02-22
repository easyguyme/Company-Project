<?php
namespace backend\components\extservice\models;

use MongoId;
use backend\modules\member\models\Member;
use backend\modules\member\models\MemberProperty as ModelMemberProperty;

/**
 * MemberProperty for extension
 */
class MemberProperty extends BaseComponent
{
    /**
     * @return array or false
     * @param $accountId, string
     * @param $isDefault, boolean,null means to get all properties,true means to get default property,false means to get properties whitch member custom properties
     */
    public function all($isDefault = null)
    {
        if (true === $isDefault) {
            $where = [
                'accountId' => $this->accountId,
                'isDefault' => true,
            ];
        } else if (false === $isDefault) {
            $where = [
                'accountId' => $this->accountId,
                'isDefault' => false,
            ];
        } else {
            $where['accountId'] = $this->accountId;
        }
        $memberProperty = ModelMemberProperty::findAll($where);
        if (empty($memberProperty)) {
            return [];
        } else {
            return $memberProperty;
        }
    }
}
