<?php
namespace backend\modules\content\controllers;

use Yii;
use MongoId;
use backend\models\StatsQuestionnaireDaily;
use backend\components\Controller;
use yii\web\BadRequestHttpException;
use backend\utils\TimeUtil;
use backend\models\StatsQuestionnaireAnswerDaily;
use yii\helpers\ArrayHelper;
use backend\models\Question;
use backend\exceptions\InvalidParameterException;
use backend\models\QuestionnaireLog;
use backend\models\Questionnaire;

class StatsQuestionnaireController extends Controller
{
    /**
     * Return question daily stats
     * @param string $id, questionnaireId
     */
    public function actionView($id)
    {
        $params = $this->getQuery();
        if (!isset($params['startTime']) || !isset($params['endTime'])) {
            throw new BadRequestHttpException(Yii::t('common', 'parameters_missing'));
        }
        if ($params['startTime'] > $params['endTime']) {
            throw new BadRequestHttpException(Yii::t('common', 'data_error'));
        }
        //turn unix timestamp to string
        $startDateStr = TimeUtil::msTime2String($params['startTime'], 'Y-m-d');
        $endDateStr = TimeUtil::msTime2String($params['endTime'], 'Y-m-d');

        $stats = StatsQuestionnaireDaily::getByQuestionnaireIdAndDate(new \MongoId($id), $startDateStr, $endDateStr);
        $statsMap = ArrayHelper::map($stats, 'date', 'count');

        //format result
        $dates = [];
        $counts = [];
        $dateTime = strtotime($startDateStr);
        $endTime = strtotime($endDateStr);
        while ($dateTime <= $endTime) {
            $date = date('Y-m-d', $dateTime);
            $dates[] = $date;
            $counts[] = empty($statsMap[$date]) ? 0 : $statsMap[$date];
            $dateTime = strtotime('+1 day', $dateTime);
        }

        return ['date' => $dates, 'count' => $counts];
    }

    /**
     * Get question option answer's stats info
     * @throws BadRequestHttpException
     * @return array, [{"option": "Yes", "count": 12}]
     */
    public function actionAnswers()
    {
        $params = $this->getQuery();
        if (empty($params['questionId'])) {
            throw new BadRequestHttpException(Yii::t('common', 'parameters_missing'));
        }

        $question = Question::findByPk(new MongoId($params['questionId']));
        if (empty($question) || $question->type === Question::TYPE_INPUT) {
            throw new InvalidParameterException(Yii::t('content', 'invalid_question'));
        }

        //turn unix timestamp to string
        $startDateStr = isset($params['startTime']) ? TimeUtil::msTime2String($params['startTime'], 'Y-m-d') : null;
        $endDateStr = isset($params['endTime']) ? TimeUtil::msTime2String($params['endTime'], 'Y-m-d') : null;

        $stats = StatsQuestionnaireAnswerDaily::getQuestionOptionStats(new MongoId($params['questionId']), $startDateStr, $endDateStr);
        $statsMap = ArrayHelper::map($stats, 'option', 'count');

        $options = [];
        $count = [];
        foreach ($question->options as $option) {
            $options[] = $option['content'];
            $count[] = empty($statsMap[$option['content']]) ? 0 : $statsMap[$option['content']];
        }

        return ['options' => $options, 'count' => $count];
    }

    /**
     * Get all answers by quesrionnaire and questionId
     */
    public function actionQuestionAnswers()
    {
        $params = $this->getQuery();
        if (empty($params['questionnaireId']) && empty($params['questionId'])) {
            throw new BadRequestHttpException(Yii::t('common', 'parameters_missing'));
        }
        $questionnaireId = new MongoId($params['questionnaireId']);
        $questionId = new MongoId($params['questionId']);
        $page = intval($this->getQuery('page', 1));
        $perPage = intval($this->getQuery('per-page', 20));

        $items = QuestionnaireLog::getAnswersByQuestionnaireId($questionnaireId, $questionId, $page, $perPage);
        foreach ($items as &$item) {
            $item['name'] = empty($item['name']) ? '' :$item['name'];
        }
        $totalCount = QuestionnaireLog::countByQuestionnaireId($questionnaireId);
        $meta = [
            'totalCount' => $totalCount,
            'pageCount' => ceil($totalCount / $perPage),
            'currentPage' => $page,
            'perPage' => $perPage
        ];
        return ['items' => $items, '_meta' => $meta];
    }
}
