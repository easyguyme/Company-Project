<?php
namespace backend\models;

use backend\components\PlainModel;

/**
 * Model class for statsQuestionnaireAnswerDaily
 *
 * @property MongoId    $_id
 * @property MongoId    $questionId
 * @property array      $stats
 * @property string     $date
 * @property MongoId    $accountId
 * @property MongoDate  $createdAt
 * @author Rex Chen
 */
class StatsQuestionnaireAnswerDaily extends PlainModel
{
    public static function collectionName()
    {
        return 'statsQuestionnaireAnswerDaily';
    }

    /**
     * Returns the list of all attribute names of statsQuestionnaireAnswerDaily.
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
                'questionId', 'stats', 'date'
            ]
        );
    }

    public function safeAttributes()
    {
        return array_merge(
            parent::attributes(),
            [
                'questionId', 'stats', 'date'
            ]
        );
    }

    /**
     * Returns the list of all rules of statsQuestionnaireAnswerDaily.
     * This method must be overridden by child classes to define available attributes.
     *
     * @return array list of rules.
     */
    public function rules()
    {
        return array_merge(
            parent::rules(),
            [
            ]
        );
    }

    /**
     * The default implementation returns the names of the columns whose values have been populated into statsQuestionnaireAnswerDaily.
     */
    public function fields()
    {
        return array_merge(
            parent::fields(),
            [
                'questionId', 'stats', 'date'
            ]
        );
    }

    /**
     * Get question option stats
     * @param MongoId $questionId
     * @param string $startDate, '2015-08-24'
     * @param string $endDate, '2015-08-24'
     * @return array, [{"option": "Yes", "count": 12}]
     */
    public static function getQuestionOptionStats($questionId, $startDate = '', $endDate = '')
    {
        $condition = ['questionId' => $questionId];
        if (!empty($startDate)) {
            $condition['date']['$gte'] = $startDate;
        }
        if (!empty($endDate)) {
            $condition['date']['$lte'] = $endDate;
        }

        $pipeline = [
            ['$match' => $condition],
            ['$unwind' => '$stats'],
            [
                '$group' => [
                    '_id' => '$stats.option',
                    'count' => ['$sum' => '$stats.count']
                ]
            ],
            ['$project' => ['option' => '$_id', '_id' => 0, 'count' => 1]]
        ];
        return self::getCollection()->aggregate($pipeline);
    }

    /**
     * Get by questionId and date
     * @param MongoId $questionId
     * @param string $date, '2015-08-26'
     */
    public static function getByQuestionIdAndDate($questionId, $date)
    {
        return self::findOne(['questionId' => $questionId, 'date' => $date]);
    }
}
