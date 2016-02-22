<?php
namespace backend\modules\content\job;

use yii\base\Exception;
use backend\models\StatsQuestionnaireDaily as ModelStatsQuestionnaireDaily;
use backend\models\QuestionnaireLog;
use backend\utils\TimeUtil;
use backend\components\resque\SchedulerJob;
use backend\utils\LogUtil;

class StatsQuestionnaireDaily extends SchedulerJob
{
    /**
     * @args {"description": "Delay: Stats of questionnaire"}
     */
    public function perform()
    {
        $args = $this->args;
        //Get date from args or today
        $date = empty($args['date']) ? '' : $args['date'];
        $datetime = TimeUtil::getDatetime($date);
        $dateStr = date('Y-m-d', $datetime);
        $stats = QuestionnaireLog::getStats($dateStr);
        $statsRows = [];
        foreach ($stats as $stat) {
            $questionnaireId = $stat['_id']['questionnaireId'];
            $accountId = $stat['_id']['accountId'];
            //if $dailyStats exists, update it; else , create
            $dailyStats = ModelStatsQuestionnaireDaily::getByQuestionnaireAndDate($accountId, $questionnaireId, $dateStr);
            if (empty($dailyStats)) {
                $statsRows[] = [
                    'accountId' => $accountId,
                    'questionnaireId' => $questionnaireId,
                    'date' => $dateStr,
                    'count' => $stat['count']
                ];
            } else {
                $dailyStats->count = $stat['count'];
                //catch exception to avoid block batch insert
                try {
                    $dailyStats->save(true, ['count']);
                } catch (Exception $e) {
                    LogUtil::error(['Update StatsQuestionnaireDaily error' => $e->getMessage(), 'StatsQuestionnaireDaily' => $dailyStats]);
                    continue;
                }
            }
        }
        ModelStatsQuestionnaireDaily::batchInsert($statsRows);

        return true;
    }
}
