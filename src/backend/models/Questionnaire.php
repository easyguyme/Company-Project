<?php
namespace backend\models;

use backend\components\PlainModel;
use backend\utils\MongodbUtil;
use backend\exceptions\InvalidParameterException;
use yii\web\BadRequestHttpException;
use backend\models\Question;
use backend\utils\TimeUtil;
use Yii;

/**
 * Model class for questionnaire
 *
 * @property MongoId    $_id
 * @property string     $name
 * @property MongoDate  $startTime
 * @property MongoDate  $endTime
 * @property Object     $creator
 * @property string     $description
 * @property array      $questions
 * @property boolean    $isPublished
 * @property MongoId    $accountId
 * @property MongoDate  $createdAt
 * @author Rex Chen
 */
class Questionnaire extends PlainModel
{
    const STATUS_ON = 'on';
    const STATUS_OFF = 'off';

    public static function collectionName()
    {
        return 'questionnaire';
    }

    /**
     * Returns the list of all attribute names of questionnaire.
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
                'name', 'startTime', 'endTime', 'creator', 'description', 'questions', 'isPublished'
            ]
        );
    }

    public function safeAttributes()
    {
        return array_merge(
            parent::attributes(),
            [
                'name', 'startTime', 'endTime', 'creator', 'description', 'questions', 'isPublished'
            ]
        );
    }

    /**
     * Returns the list of all rules of questionnaire.
     * This method must be overridden by child classes to define available attributes.
     *
     * @return array list of rules.
     */
    public function rules()
    {
        return array_merge(
            parent::rules(),
            [
                [['name', 'startTime', 'endTime', 'isPublished'], 'required'],
                ['startTime', 'validateTime', 'on' => [self::SCENARIO_CREATE]],
                ['endTime', 'validateTime', 'on' => [self::SCENARIO_CREATE]],
            ]
        );
    }

    /**
     * The default implementation returns the names of the columns whose values have been populated into questionnaire.
     */
    public function fields()
    {
        return array_merge(
            parent::fields(),
            [
                'name',
                'startTime' => function () {
                    return MongodbUtil::MongoDate2String($this->startTime, 'Y-m-d H:i:s');
                },
                'endTime' => function () {
                    return MongodbUtil::MongoDate2String($this->endTime, 'Y-m-d H:i:s');
                },
                'creator' => function ($model) {
                    $creator = $model->creator;
                    $creator['id'] .= '';
                    return $creator;
                },
                'description',
                'questions' => function () {
                    return self::getByOrder($this->questions);
                },
                'isPublished',
                'createdAt' => function () {
                    return MongodbUtil::MongoDate2String($this->createdAt, 'Y-m-d H:i:s');
                },
            ]
        );
    }

    /**
    * Get question by order.
    * @param $question
    */
    public function getByOrder($question)
    {
        return Question::find()->where(['_id' => ['$in' => $question]])->orderBy(['order' => SORT_ASC])->all();
    }

    /**
    *   Varify if name is unique.
    */
    public static function isNameExist($name)
    {
        $result = self::getByName($name);
        if ($result != null) {
            throw new InvalidParameterException(Yii::t('content', 'question_exist_name'));
        }
    }

    /**
    * Get one questionnaire information.
    * @param $name string
    */
    public static function getByName($name)
    {
        return self::findOne(['name' => $name]);
    }

    /**
    * Get questionnaire by id.
    * @param $id MongoId
    */
    public static function getById($id)
    {
        return self::findByPk($id);
    }

    /**
    * Verify if time is true.
    */
    public function validateTime($attribute)
    {
        $time = $this->$attribute;

        if ($attribute == 'startTime') {
            $now = time();
            if ($time < $now) {
                throw new InvalidParameterException(['beginDatePicker' => \Yii::t('product', 'invalid_start_time')]);
            }
        } else if ($attribute == 'endTime') {
            if ($time <= MongodbUtil::MongoDate2TimeStamp($this->startTime)) {
                throw new InvalidParameterException(['endDatePicker' => \Yii::t('product', 'invalid_end_time')]);
            }
        }
    }

    /**
    * Get questionnaire messge by ids.
    * @param $ids string
    */
    public static function getByQuestionnaireIds($questionnireIds)
    {
        return self::findAll(['_id' => ['$in' => $questionnireIds]]);
    }
}
