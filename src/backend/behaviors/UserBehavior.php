<?php
namespace backend\behaviors;

use yii\base\Behavior;
use backend\modules\microsite\models\Page;

class UserBehavior extends Behavior
{
    /**
     * Update page creator name.
     * @param  \MongoId $userId
     * @param  string $name
     */
    public function updatePageCreator(\MongoId $userId, $name)
    {
        Page::updateAll(['$set' => ['creator.name' => $name]], ['creator.id' => $userId]);
    }

    /**
     * Update creator when update user name
     * @param  \MongoId $userId
     * @param  string $name
     */
    public function updateCreator(\MongoId $userId, $name)
    {
        $this->updatePageCreator($userId, $name);
    }
}
