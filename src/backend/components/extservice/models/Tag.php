<?php
namespace backend\components\extservice\models;

use backend\models\Account;

/**
 * Tag for extension
 * @author Harry Sun
 */
class Tag extends BaseComponent
{
    /**
     * Get all tags
     * @return array
     */
    public function all()
    {
        $tags = [];
        $account = Account::findOne(['_id' => $this->accountId]);

        if (!empty($account) && !empty($account['tags'])) {
            return $account['tags'];
        }

        return $tags;
    }

    /**
     * Create tags
     * @param  array $tags
     * @return boolean
     */
    public function create($tags)
    {
        if (empty($tags) || !is_array($tags)) {
            return false;
        }

        $addTags = [];
        foreach ($tags as $tagName) {
            $addTags[] = ['name' => $tagName];
        }

        return (boolean) Account::updateAll(['$addToSet' => ['tags' => ['$each' => $addTags]]], ['_id' => $this->accountId]);
    }
}
