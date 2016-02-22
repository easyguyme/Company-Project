<?php
namespace backend\modules\helpdesk\models;

use backend\components\PlainModel;
use Yii;

/**
 * Model class for issueUser.
 *
 * The followings are the available columns in collection 'issueUser':
 * @property MongoId $_id
 * @property MongoId $accountId
 * @property Array $location:{$country, $province, $city, $detail}
 * @property string $email
 * @property string $company
 * @property string $phone
 * @property string $name
 * @property string $openId
 * @property string $origin
 * @property string $avatar
 * @property string $channelId
 * @property MongoDate $createdAt
 */
class IssueUser extends PlainModel
{

    const HELPDESK = 'helpDesk';
    const VISITOR = 'visitor';

    /**
     * Declares the name of the Mongo collection associated with issueUser.
     * @return string the collection name
     */
    public static function collectionName()
    {
        return 'issueUser';
    }

    public function attributes()
    {
        return array_merge(
            parent::attributes(),
            ['location', 'email', 'phone', 'name', 'nickname', 'avatar', 'gender', 'language', 'company', 'openId', 'origin', 'channelId']
        );
    }

    public function safeAttributes()
    {
        return array_merge(
            parent::attributes(),
            ['name', 'email', 'phone', 'avatar', 'company']
        );
    }

    public function fields()
    {
        return array_merge(
            parent::fields(),
            ['email', 'phone', 'name', 'avatar', 'company', 'origin']
        );
    }

    public function rules()
    {
        return array_merge(
            parent::rules(),
            [
                [['name', 'phone', 'email'], 'required'],
                ['email', 'email'],
                ['avatar', 'default', 'value' => Yii::$app->params['defaultAvatar']],

            ]
        );
    }
}
