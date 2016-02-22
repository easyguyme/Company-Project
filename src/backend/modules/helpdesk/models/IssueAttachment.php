<?php
namespace backend\modules\helpdesk\models;

use backend\components\PlainModel;
use Yii;

/**
 * Model class for attachment.
 *
 * The followings are the available columns in collection 'attachment':
 * @property MongoId $_id
 * @property string $name
 * @property string $type
 * @property string $size
 * @property string $url
 * @property MongoDate $createdAt
 */
class IssueAttachment extends PlainModel
{
    private static $_instance;
    /**
     * Declares the name of the Mongo collection associated with attachment.
     * @return string the collection name
     */
    public static function collectionName()
    {
        return 'issueAttachment';
    }

    public function attributes()
    {
        return array_merge(
            parent::attributes(),
            ['name', 'type', 'size', 'url', 'format']
        );
    }

    public function safeAttributes()
    {
        return array_merge(
            parent::attributes(),
            ['name', 'type', 'size', 'url', 'format']
        );
    }

    public function rules()
    {
        return array_merge(
            parent::rules(),
            [
                [['name', 'type', 'url'], 'required'],
            ]
        );
    }

    public function fields()
    {
        return array_merge(
            parent::fields(),
            ['name', 'type', 'size', 'url', 'format']
        );
    }
}
