<?php
namespace console\modules\helpdesk\controllers;

use yii\console\Controller;
use backend\modules\helpdesk\models\Issue;
use backend\modules\helpdesk\models\IssueActivity;
use backend\modules\helpdesk\models\IssueUser;

/**
 * Provide issue-related operations, including data migration.
 **/
class IssueController extends Controller
{
    /**
     * Update schemas of collection issue and issueActivity.
     */
    public function actionMigrate()
    {
        echo "Begin to update collection issue\n";
        // Update collection issue
        $issues = Issue::find()->all();
        foreach ($issues as &$issue) {
            $creator = $issue->creator;
            if (!($creator instanceof \mongoId)) {
                $issue->creator = new \MongoId($creator['id']);
            }
            if (!isset($issue->origin)) {
                $issue->origin = IssueUser::HELPDESK;
            }
            if (!empty($issue->assignee)) {
                $assignee = $issue->assignee;
                if (!($assignee instanceof \MongoId)) {
                    $issue->assignee = new \MongoId($assignee['id']);
                }
            }
            $issue->update();
        }
        echo "Successfully updated collection issue.\n";

        echo "Begin to update collection issueActivity\n";
        // Update collection issueActivity
        $issueActivities = IssueActivity::find()->all();
        foreach ($issueActivities as $activity) {
            $creator = $activity->creator;
            if (!($creator instanceof \mongoId)) {
                $activity->creator = new \MongoId($creator['id']);
            }
            $activity->save();
        }
        echo "Successfully updated collection issueActivity.\n";
        echo "Complete migration.\n";
    }
}
