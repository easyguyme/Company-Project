<?php
namespace backend\behaviors;

use backend\behaviors\BaseBehavior;
use backend\modules\member\models\Member;
use backend\modules\product\models\Campaign;
use backend\models\Account;

class TagBehavior extends BaseBehavior
{
    public function renameTag($accountId, $name, $newName)
    {
        //update account tags
        Account::updateAll(['$set' => ['tags.$.name' => $newName]], ['_id' => $accountId, 'tags.name' => $name]);
        Member::renameTag($accountId, $name, $newName);
        Campaign::renameTag($accountId, $name, $newName);
        $data = [
            'type'=> 'tag_renamed',
            'account_id'=> $accountId,
            'old_name'=> $name,
            'new_name'=> $newName
        ];
        $this->notifyModules($data);
    }

    public function deleteTag($accountId, $name)
    {
        Account::updateAll(['$pull' => ['tags' => ['name' => $name]]], ['_id' => $accountId]);
        Member::updateAll(['$pull' => ['tags' => $name]], ['accountId' => $accountId, 'tags' => $name]);
        Campaign::updateAll(['$pull' => ['tags' => $name]], ['accountId' => $accountId, 'tags' => $name]);
        $data = [
            'type'=> 'tag_deleted',
            'account_id'=> $accountId,
            'name'=> $name,
        ];
        $this->notifyModules($data);
    }
}
