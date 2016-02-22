<?php
namespace backend\modules\member\job;

use Yii;
use backend\models\Account;
use backend\models\StatsMemberDaily as ModelStatsMemberDaily;
use backend\modules\member\models\Member;
use backend\modules\resque\components\ResqueUtil;
use backend\utils\TimeUtil;
use backend\models\Channel;
use backend\components\resque\SchedulerJob;

/**
* Job for StatsMemberDaily
*/
class StatsMemberDaily extends SchedulerJob
{
    /**
     * @args {"description": "Delay: Stats of StatsMemberDaily", "runNextJob": true}
     * @author Rex Chen
     */
    public function perform()
    {
        $args = $this->args;

        $date = empty($args['date']) ? '' : $args['date'];
        $datetime = TimeUtil::getDatetime($date);
        $dateStr = date('Y-m-d', $datetime);

        if (!empty(WECONNECT_DOMAIN)) {
            $channelNameMap = $this->_getChannelNameMap();
        }

        $start = new \MongoDate($datetime);
        $end = new \MongoDate(strtotime('+1 day', $datetime));
        $memberStats = Member::getNewMemberStats($start, $end);

        $rowStats = [];
        foreach ($memberStats as $stats) {
            $accountId = $stats['_id']['accountId'];
            $origin = $stats['_id']['origin'];
            $socialAccountId = $stats['_id']['socialAccountId'];
            $originName = empty($channelNameMap[$socialAccountId]) ? '' : $channelNameMap[$socialAccountId];
            $total = $stats['total'];

            $statsMember = ModelStatsMemberDaily::getByDateAndOriginInfo($dateStr, $origin, $originName, $accountId);
            if (!empty($statsMember)) {
                $statsMember->total = $total;
                try {
                    $statsMember->save(true, ['total']);
                } catch (Exception $e) {
                    ResqueUtil::log(['Update StatsMemberDaily error' => $e->getMessage(), 'StatsMemberDaily' => $statsMember]);
                    continue;
                }
            } else {
                $rowStats[] = [
                    'date' => $dateStr,
                    'origin' => $origin,
                    'originName' => $originName,
                    'total' => $total,
                    'accountId' => $accountId
                ];
            }
        }
        ModelStatsMemberDaily::batchInsert($rowStats);

        return true;
    }

    /**
     * Get channel_id -> name map
     * @return array
     * @example ['54dbfc44e4b09d7f7799e96d' => 'hankliu62', '54d9c475e4b0abe717853ee6' => '群游汇']
     */
    private function _getChannelNameMap()
    {
        $channelNameMap = [];
        $channels = Channel::findAll([]);
        foreach ($channels as $channel) {
            $channelNameMap[$channel->channelId] = $channel->name;
        }

        return $channelNameMap;
    }

    public function tearDown()
    {
        parent::tearDown();

        $args = $this->args;
        if (!empty($args['runNextJob'])) {
            $args = [];
            $args['description'] = 'Direct: Stats of StatsMemberMonthly';
            Yii::$app->job->create('backend\modules\member\job\StatsMemberMonthly', $args);
        }
    }
}
