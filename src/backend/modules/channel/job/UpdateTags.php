<?php
namespace backend\modules\channel\job;

use Yii;
use backend\modules\resque\components\ResqueUtil;

/**
* Job for ClassJob
*/
class UpdateTags
{
    public function setUp()
    {
    # Set up environment for this job
    }

    public function perform()
    {
        # Run task
        $args = $this->args;
        ResqueUtil::log(['info' => 'update tags', 'args' => $args]);

        $channelId = $args['channelId'];
        $query = $args['query'];
        $tags = $args['tags'];
        $query['pageSize'] = 20;
        $query['pageNum'] = 1;

        $raw = Yii::$app->weConnect->getFollowers($channelId, $query);

        while (!empty($raw['results'])) {
            $followerIds = [];
            foreach ($raw['results'] as $followers) {
                $followerIds[] = $followers['id'];
            }
            if (empty($args['add']) || !$args['add']) {
                $result = Yii::$app->weConnect->removeTags($channelId, $followerIds, $tags);
            } else {
                $result = Yii::$app->weConnect->addTagsToFollowers($channelId, $followerIds, $tags);
            }

            if (!$result) {
                ResqueUtil::log(['error' => 'add tags', 'error followers' => $followerIds]);
                return false;
            }
            $query['pageNum']++;
            $raw = Yii::$app->weConnect->getFollowers($channelId, $query);
        }

        return true;
    }

    public function tearDown()
    {
    # Remove environment for this job
    }
}
