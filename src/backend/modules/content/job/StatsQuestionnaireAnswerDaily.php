<?php
namespace backend\modules\content\job;

use MongoId;
use yii\base\Exception;
use backend\models\QuestionnaireLog;
use backend\models\Questionnaire;
use backend\models\StatsQuestionnaireAnswerDaily as ModelStatsQuestionnaireAnswerDaily;
use backend\modules\resque\components\ResqueUtil;
use backend\utils\TimeUtil;
use backend\components\resque\SchedulerJob;

class StatsQuestionnaireAnswerDaily extends SchedulerJob
{
    /**
     * @args {"description": "Delay: Stats of questionnaire answer"}
     */
    public function perform()
    {
        $args = $this->args;
        //Get date from args or today
        $date = empty($args['date']) ? '' : $args['date'];
        $datetime = TimeUtil::getDatetime($date);
        $dateStr = date('Y-m-d', $datetime);

        //in case of too much data, get stats by questionnaire
        $skip = 0;
        $limit = 100;
        $query = Questionnaire::find()->orderBy(['_id' => SORT_ASC]);
        $query = $query->offset($skip)->limit($limit);
        $questionnaires = $query->all();

        while (!empty($questionnaires)) {
            $statsRows = [];
            foreach ($questionnaires as $questionnaire) {
                $stats = QuestionnaireLog::getAnswerStats($questionnaire->_id, $dateStr);

                //group stats by questionId
                $rows = [];
                foreach ($stats as $stat) {
                    $questionIdStr = (string) $stat['_id']['questionId'];
                    $optionValue = $stat['_id']['value'];
                    $rows[$questionIdStr][] = [
                        'option' => $optionValue,
                        'count' => $stat['count']
                    ];
                }

                //format stats data, and save it
                foreach ($rows as $questionIdStr => $answerStats) {
                    $questionId = new MongoId($questionIdStr);
                    $statsAnswerDaily = ModelStatsQuestionnaireAnswerDaily::getByQuestionIdAndDate($questionId, $dateStr);
                    if (empty($statsAnswerDaily)) {
                        $statsRows[] = [
                            'questionId' => $questionId,
                            'stats' => $answerStats,
                            'date' => $dateStr,
                            'accountId' => $questionnaire->accountId
                        ];
                    } else {
                        $statsAnswerDaily->stats = $answerStats;
                        try {
                            $statsAnswerDaily->save();
                        } catch (Exception $e) {
                            ResqueUtil::log(['Update StatsQuestionnaireAnswerDaily error' => $e->getMessage(), 'StatsQuestionnaireAnswerDaily' => $statsAnswerDaily]);
                            continue;
                        }
                    }
                }
            }
            ModelStatsQuestionnaireAnswerDaily::batchInsert($statsRows);
            $skip += $limit;
            $query = $query->offset($skip)->limit($limit);
            //free $questionnaires
            unset($questionnaires);
            $questionnaires = $query->all();
        }

        return true;
    }
}
