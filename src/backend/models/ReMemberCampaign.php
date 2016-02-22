<?php
namespace backend\models;

use backend\components\PlainModel;

/**
 * This is the reMemberCampaign model class for aug-marketing
 *
 * The followings are the available columns in collection 'reMemberCampaign':
 * @property MongoId    $_id
 * @property MongoId    $memberId
 * @property MongoId    $campaignId
 * @property int        $usedTimes
 * @property MongoId    $accountId
 * @property MongoDate  $createdAt
 * @author Harry Sun
 **/
class ReMemberCampaign extends PlainModel
{
    /**
     * Declares the name of the Mongo collection associated with reMemberCampaign.
     * @return string the collection name
     */
    public static function collectionName()
    {
        return 'reMemberCampaign';
    }

    /**
     * Returns the list of all attribute names of reMemberCampaign.
     * @return array list of attribute names.
     */
    public function attributes()
    {
        return array_merge(
            parent::attributes(),
            ['memberId', 'campaignId', 'usedTimes']
        );
    }

    public function safeAttributes()
    {
        return array_merge(
            parent::attributes(),
            ['memberId', 'campaignId', 'usedTimes']
        );
    }

    /**
     * Returns the list of all rules of admin.
     * This method must be overridden by child classes to define available attributes.
     *
     * @return array list of rules.
     */
    public function rules()
    {
        return array_merge(
            parent::rules(),
            [
                ['usedTimes', 'default', 'value' => 0],
            ]
        );
    }
}
