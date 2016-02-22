<?php
namespace backend\modules\helpdesk\models;

use backend\components\BaseModel;
use backend\modules\helpdesk\models\IssueActivity;
use backend\modules\helpdesk\models\IssueAttachment;
use backend\modules\helpdesk\models\IssueUser;
use backend\modules\helpdesk\models\HelpDesk;
use backend\models\User;
use backend\utils\MongodbUtil;
use Yii;
use yii\helpers\ArrayHelper;
use yii\base\Event;

/**
 * Model class for issue.
 *
 * The followings are the available columns in collection 'issue':
 * @property MongoId $_id
 * @property MongoId $accountId
 * @property boolean $isDeleted
 * @property MongoDate $createdAt
 * @property MongoDate $updatedAt
 * @property string $title
 * @property string $description
 * @property string $status
 * @property array $creator
 * @property string $assignee
 * @property array $attachmentIds
 */
class Issue extends BaseModel
{
    // constants for issue statuses
    const STATUS_OPEN = "open";
    const STATUS_ASSIGNED = "assigned";
    const STATUS_RESOLVED = "resolved";
    const STATUS_CLOSED = "closed";

    // constants for channels
    const CHANNEL_ISSUE_PREFIX = "presence-wm-issue";

    // constants of events
    const EVENT_NEW_ISSUE = "new_issue";
    const EVENT_ISSUE_STATUS_CHANGED = "issue_status_changed";
    const EVENT_COMMENT_ISSUE = "comment_issue";

    // instance for issue
    private static $_instance;

    /**
     * Declares the name of the Mongo collection associated with issue.
     * @return string the collection name
     */
    public static function collectionName()
    {
        return 'issue';
    }

    public function attributes()
    {
        return array_merge(
            parent::attributes(),
            ['title', 'description', 'status', 'creator', 'assignee', 'attachmentIds', 'origin']
        );
    }

    public function safeAttributes()
    {
        return array_merge(
            parent::attributes(),
            ['title', 'description']
        );
    }

    public function rules()
    {
        return array_merge(
            parent::rules(),
            [
                [['title', 'description', 'creator'], 'required'],
            ]
        );
    }

    public function fields()
    {
        return array_merge(
            parent::fields(),
            ['title', 'description', 'status', 'creator', 'assignee',
            'createdAt' => function($model) {
                return MongodbUtil::MongoDate2msTimeStamp($model['createdAt']);
            },
            'isDeleted', 'origin',
            'hasAttachment' => function($model) {
                if (isset($model['attachmentIds']) && count($model['attachmentIds']) > 0) {
                    return true;
                }
                return false;
            }]
        );
    }

    public function getActivities()
    {
        return $this->hasMany(IssueActivity::className(), ['issueId' => '_id'])->orderBy(['createdAt' => SORT_ASC]);
    }

    public function getAttachments()
    {
        return $this->hasMany(IssueAttachment::className(), ['_id' => 'attachmentIds']);
    }

    public function getCreatorDetail()
    {
        if (!empty($this->origin) && $this->origin !== IssueUser::HELPDESK) {
            return $this->hasOne(IssueUser::className(), ['_id' => 'creator']);
        }

        return $this->hasOne(HelpDesk::className(), ['_id' => 'creator']);
    }

    public function getAssigneeDetail()
    {
        return $this->hasOne(HelpDesk::className(), ['_id' => 'assignee']);
    }

    public static function search($condition = [], $offset = 0, $limit = 100)
    {
        $query = self::find();
        $query->orderBy(['createdAt' => SORT_DESC]);
        $query->where($condition)->offset($offset)->limit($limit);
        return $query->all();
    }

    public static function count($condition = [])
    {
        $query = self::find();
        $query->where($condition);
        return $query->count();
    }
}
