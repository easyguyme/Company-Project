<?php
namespace backend\models;

use backend\components\PlainModel;

/**
 * Model class for statsQuestionnaireDaily
 *
 * @property MongoId    $_id
 * @property MongoId    $questionnaireId
 * @property int        $count
 * @property string     $date
 * @property MongoId    $accountId
 * @property MongoDate  $createdAt
 * @author Rex Chen
 */
class StatsQuestionnaireDaily extends PlainModel
{
    public static function collectionName()
    {
        return 'statsQuestionnaireDaily';
    }

    /**
     * Returns the list of all attribute names of statsQuestionnaireDaily.
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
                'questionnaireId', 'count', 'date'
            ]
        );
    }

    public function safeAttributes()
    {
        return array_merge(
            parent::attributes(),
            [
                'questionnaireId', 'count', 'date'
            ]
        );
    }

    /**
     * Returns the list of all rules of statsQuestionnaireDaily.
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
     * The default implementation returns the names of the columns whose values have been populated into statsQuestionnaireDaily.
     */
    public function fields()
    {
        return array_merge(
            parent::fields(),
            [
                'questionnaireId' => function () {
                    return (string) $this->questionnaireId;
                },
                'count', 'date'
            ]
        );
    }

    /**
     * Get daily stats
     * @param MongoId $questionnaireId
     * @param string $startDate
     * @param string $endDate
     */
    public static function getByQuestionnaireIdAndDate($questionnaireId, $startDate, $endDate)
    {
        $condition = [
            'questionnaireId' => $questionnaireId,
            'date' => ['$gte' => $startDate, '$lte' => $endDate]
        ];
        return self::findAll($condition);
    }

    /**
     * Get by questionnaire and date
     * @param MongoId $accountId
     * @param MongoId $questionnaireId
     * @param string $date
     * @return array
     */
    public static function getByQuestionnaireAndDate($accountId, $questionnaireId, $date)
    {
        return self::findOne(['accountId' => $accountId, 'questionnaireId' => $questionnaireId, 'date' => $date]);
    }
}
