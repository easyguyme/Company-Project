<?php
namespace backend\models;

use Yii;
use MongoId;
use MongoDate;
use backend\components\PlainModel;
use backend\exceptions\InvalidParameterException;
use backend\exceptions\backend\exceptions;
use yii\web\BadRequestHttpException;
use backend\utils\MongodbUtil;

/**
 * Model class for questionnaireLog
 *
 * @property MongoId    $_id
 * @property MongoId    $questionnaireId
 * @property array      $user
 * @property array      $answers
 * @property MongoId    $accountId
 * @property MongoDate  $createdAt
 * @author Rex Chen
 */
class QuestionnaireLog extends PlainModel
{
    public static function collectionName()
    {
        return 'questionnaireLog';
    }

    /**
     * Returns the list of all attribute names of questionnaireLog.
     * This method must be overridden by child classes to define available attributes.
     * The parent's attributes function is:
     *
     * @return array list of attribute names.
     */
    public function attributes()
    {
        return array_merge(
            parent::attributes(),
            [
                'questionnaireId', 'user', 'answers'
            ]
        );
    }

    public function safeAttributes()
    {
        return array_merge(
            parent::attributes(),
            [
                'questionnaireId', 'user', 'answers'
            ]
        );
    }

    /**
     * Returns the list of all rules of questionnaireLog.
     * This method must be overridden by child classes to define available attributes.
     *
     * @return array list of rules.
     */
    public function rules()
    {
        return array_merge(
            parent::rules(),
            [
                ['answers', 'required'],
                ['answers', 'validateAnswers']
            ]
        );
    }

    public function validateAnswers($attribute)
    {
        if ($attribute !== 'answers') {
            return true;
        }
        $answers = $this->$attribute;
        $questionnaire = Questionnaire::findByPk($this->questionnaireId);

        $answeredQuestions = [];
        foreach ($answers as &$answer) {
            $answer['questionId'] = new MongoId($answer['questionId']);
            if (!isset($answer['questionId']) || !isset($answer['type']) || !isset($answer['value'])) {
                throw new BadRequestHttpException(Yii::t('content', 'invalid_answer'));
            }
            if (!in_array($answer['questionId'], $questionnaire->questions)) {
                throw new BadRequestHttpException(Yii::t('content', 'invalid_answer'));
            }
            if ($answer['type'] === Question::TYPE_CHECKBOX && !is_array($answer['value'])) {
                throw new BadRequestHttpException(Yii::t('content', 'invalid_answer'));
            }
            $answeredQuestions[] = $answer['questionId'];
        }

        //check if questionnaire has un answered question
        $noAnswerQuestions = array_diff($questionnaire->questions, $answeredQuestions);
        if (!empty($noAnswerQuestions)) {
            throw new InvalidParameterException(Yii::t('content', 'no_answer_error'));
        }
        $this->$attribute = $answers;
    }

    /**
     * The default implementation returns the names of the columns whose values have been populated into questionnaireLog.
     */
    public function fields()
    {
        return array_merge(
            parent::fields(),
            [
                'questionnaireId', 'user', 'answers'
            ]
        );
    }

    /**
     * Get questionnaire stats by date
     * @param string $date, '2015-08-12'
     * @return array
     *      @example
     *      [
     *          [
     *              '_id' => ['questionnaireId' => ObjectId('55dadf34d6f97f6f0d8b4570'), 'accountId' => ObjectId('55dadf34d6f97f6f0d8b4570')],
     *              'count' => 100
     *          ]
     *      ]
     * @author Rex Chen
     */
    public static function getStats($date)
    {
        $condition = ['createdAt' => ['$gte' => new MongoDate(strtotime($date)), '$lt' => new MongoDate(strtotime($date . ' +1 day'))]];
        $pipeline = [
            ['$match' => $condition],
            [
                '$group' => [
                    '_id' => ['questionnaireId' => '$questionnaireId', 'accountId' => '$accountId'],
                    'count' => ['$sum' => 1]
                ]
            ],
        ];
        return self::getCollection()->aggregate($pipeline);
    }

    /**
     * Get questionnaire answer stats by date
     * @param MongoId $questionnaireId
     * @param string $date, '2015-08-12'
     * @return array
     *      @example
     *      [
     *          [
     *              '_id' => ['questionId' => ObjectId('55dadf34d6f97f6f0d8b4570'), 'value' => 'male'],
     *              'count' => 100
     *          ]
     *      ]
     * @author Rex Chen
     */
    public static function getAnswerStats($questionnaireId, $date)
    {
        $condition = [
            'questionnaireId' => $questionnaireId,
            'createdAt' => [
                '$gte' => new MongoDate(strtotime($date)),
                '$lt' => new MongoDate(strtotime($date . ' +1 day'))
            ],
        ];
        //check box stats
        $pipeline = [
            ['$match' => $condition],
            ['$unwind' => '$answers'],
            ['$match' => ['answers.type' => Question::TYPE_CHECKBOX]],
            [
                '$project' => [
                    'questionId' => '$answers.questionId',
                    'questionType' => '$answers.type',
                    'answerValue' => '$answers.value',
                ]
            ],
            ['$unwind' => '$answerValue'],
            [
                '$group' => [
                    '_id' => ['questionId' => '$questionId', 'value' => '$answerValue'],
                    'count' => ['$sum' => 1]
                ]
            ],
        ];
        $checkboxStats = self::getCollection()->aggregate($pipeline);
        //radio stats
        //$match radio
        $pipeline[2]['$match'] = ['answers.type' => Question::TYPE_RADIO];
        //unset $unwind
        unset($pipeline[4]);
        $radioStats = self::getCollection()->aggregate(array_values($pipeline));
        return array_merge($checkboxStats, $radioStats);
    }

    /**
     * Get by questionnaireid and user
     * @param MongoId $questionnaireId
     * @param array $user
     */
    public static function getByQuestionnaireAndUser($questionnaireId, $user)
    {
        return self::findOne(['questionnaireId' => $questionnaireId, 'user.channelId' => $user['channelId'], 'user.openId' => $user['openId']]);
    }

    /**
     *
     * @param MongoId $questionnaireId
     * @param MongoId $questionId
     * @param int $page
     * @param int $perPage
     */
    public static function getAnswersByQuestionnaireId($questionnaireId, $questionId, $page, $perPage)
    {
        $condition = ['questionnaireId' => $questionnaireId];
        $pipeline = [
            ['$match' => $condition],
            ['$unwind' => '$answers'],
            ['$match' => ['answers.questionId' => $questionId]],
            ['$project' => ['name' => '$user.name', 'value' => '$answers.value', '_id' => 0]],
            ['$sort' => ['value' => 1]],
            ['$skip' => ($page - 1) * $perPage],
            ['$limit' => $perPage],
        ];
        return self::getCollection()->aggregate($pipeline);
    }

    /**
     * Get questionnaireLogs count by questionnaireId
     * @param MongoId $questionnaireId
     */
    public static function countByQuestionnaireId($questionnaireId)
    {
        return self::count(['questionnaireId' => $questionnaireId]);
    }

    /**
     * Get questionnaireLogs count by questionnaireId and questionId
     * @return int
     * @param questionnaireId, objectId
     * @param questionId, objectId
     */
    public static function countByQuestionnaireIdAndQuestionId($questionnaireId, $questionId)
    {
        return self::count(['questionnaireId' => $questionnaireId, 'answers.questionId' => $questionId]);
    }
}
