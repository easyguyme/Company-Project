<?php
namespace console\modules\product\controllers;

use Yii;
use yii\console\Controller;
use backend\models\Account;
use backend\modules\product\models\ProductCategory;
use MongoId;
use backend\modules\member\models\Member;
use backend\modules\product\models\CampaignLog;

/**
 * update product data script
 */
class UpdateDataController extends controller
{
    /**
     * update-stats-coupon-log-daily
     */
    public function actionUpdateStatsCouponLogDaily($startTime, $endTime)
    {
        $args = [
            'description' => 'Direct: update Stats of StatsCouponLogDaily',
            'startTime' => $startTime,
            'endTime' => $endTime,
        ];
        $jobId = Yii::$app->job->create('backend\modules\product\job\StatsCouponLogDailyUpdate', $args, null, null, false);
        echo $jobId . PHP_EOL;
    }

    /**
     * update the product category
     */
    public function actionUpdateCategory()
    {
        //get the info of account
        $accountInfos = Account::findAll(['enabledMods' => ['$all' => ['product']]]);

        if ($accountInfos) {
            foreach ($accountInfos as $accountInfo) {
                //update reservation group
                ProductCategory::updateAll(['$set' => ['type' => ProductCategory::RESERVATION]], ['name' => ProductCategory::RESERVATION_CATEGORY_NAME, 'accountId' => $accountInfo->_id]);
                // update product group
                ProductCategory::updateAll(['$set' => ['type' => ProductCategory::PRODUCT]], ['type' => ['$exists' => false], 'accountId' => $accountInfo->_id]);
            }
        }
        echo 'update category successful' . PHP_EOL;
    }

    /**
    * Update the tags of member who who joined the giving monthly
    * @param $campaignId string
    * @param $tag string "tag1, tag2, tag3, ..."
    * @param $accountId string
    *
    * @return boolean
    */
    public function actionUpdateMemberTagsMonthly($id, $tag, $accountId)
    {
        // verify if the parameter is empty
        if (empty($id) || empty($tag) || empty($accountId)) {
            echo 'Failed : missing params when update member`s tags' . PHP_EOL;
            echo 'id:' . $id . ' tag:' . $tag . ' accountId:' . $accountId . PHP_EOL;
            exit();
        }
        $tags = explode(',', $tag);
        // Verify if the tag length is less than or equal 5
        $tagsArray = [];
        foreach ($tags as $tagItem) {
            $tagLen = mb_strlen($tagItem, 'utf-8');
            if ($tagLen <= 0 || $tagLen > 30) {
                echo 'A max of 5 Chinese characters allowed.' . ' tag:' . $tagItem . PHP_EOL;
                exit();
            }
            $tagsArray[] = [
                'name' => $tagItem
            ];
        }
        $condition = [
            'campaignId' => new MongoId($id),
            'accountId' => new MongoId($accountId)
        ];
        $campaignLog = CampaignLog::findAll($condition);

        if (!empty($campaignLog)) {
            // add new tag to account`s tags
            $updateTagsToAccount['$addToSet'] = ['tags' => ['$each' => $tagsArray]];
            Account::updateAll($updateTagsToAccount, ['_id' => new MongoId($accountId)]);

            // get all the memberIds
            foreach ($campaignLog as $campaignLogItem) {
                if (!empty($campaignLogItem['member'])) {
                    // add tags for member
                    $conditions = [
                        '_id' => $campaignLogItem['member']['id'],
                        'accountId' => new MongoId($accountId)
                    ];

                    $updateTagsTomember['$addToSet'] = ['tags' => ['$each' => $tags]];
                    $updateMemberResult = Member::updateAll($updateTagsTomember, $conditions);

                    if (!$updateMemberResult) {
                        echo 'Failed : Failed to update member`s tags. member:' . $campaignLogItem['member']['id'] . ' tags:';
                        print_r($tags);
                    }
                }
            }
        } else {
            echo 'Nobody participated in this activaty' . PHP_EOL;
        }
        echo 'Update successfully' . PHP_EOL;
    }
}
