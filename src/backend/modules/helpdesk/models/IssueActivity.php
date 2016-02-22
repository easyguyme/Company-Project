<?php
namespace backend\modules\helpdesk\models;

use backend\components\PlainModel;
use backend\utils\MongodbUtil;
use Yii;
use yii\base\Event;

/**
 * Model class for issueActivity.
 *
 * The followings are the available columns in collection 'issueActivity':
 * @property MongoId $_id
 * @property MongoId, accountId
 * @property MongoDate $createdAt
 * @property MongoId $issueId
 * @property string $description
 * @property string $action
 * @property string $operator
 * @author byronzhang <byronzhang@augmentum.com.cn>
 */
class IssueActivity extends PlainModel
{
    // instance for issueActivity
    private static $_instance;

    const ACTION_CREATE = "create";
    const ACTION_CLAIM = "claim";
    const ACTION_RESOLVE = "resolve";
    const ACTION_CLOSE = "close";
    const ACTION_COMMENT = "comment";

    /**
     * Declares the name of the Mongo collection associated with issueActivity.
     * @return string the collection name
     */
    public static function collectionName()
    {
        return 'issueActivity';
    }

    public function attributes()
    {
        return array_merge(
            parent::attributes(),
            ['issueId', 'creator', 'action', 'description']
        );
    }

    public function safeAttributes()
    {
        return array_merge(
            parent::attributes(),
            ['issueId', 'creator', 'action', 'description']
        );
    }

    public function fields()
    {
        return array_merge(
            parent::fields(),
            ['creator', 'action', 'description',
            'createdAt' => function($model) {
                return MongodbUtil::MongoDate2msTimeStamp($model['createdAt']);
            },
            'issueId' => function($model) {
                return $model['issueId'] . '';
            }]
        );
    }

    public function getCreatorDetail()
    {
        if (!empty($this->origin) && $this->origin !== IssueUser::HELPDESK) {
            return $this->hasOne(IssueUser::className(), ['_id' => 'creator']);
        }

        return $this->hasOne(HelpDesk::className(), ['_id' => 'creator']);
    }
}
