<?php
namespace backend\modules\management\controllers;

use Yii;
use yii\helpers\Url;
use yii\helpers\Json;
use yii\web\ServerErrorHttpException;
use yii\web\BadRequestHttpException;
use backend\modules\member\models\MemberProperty;
use backend\modules\member\models\Member;
use backend\modules\member\models\ScoreRule;
use backend\exceptions\InvalidParameterException;
use backend\components\rest\RestController;

class MemberPropertyController extends RestController
{
    public $modelClass = "backend\modules\member\models\MemberProperty";

    public function actions()
    {
        $actions = parent::actions();
        unset($actions['create'], $actions['update'], $actions['delete']);
        return $actions;
    }

    /**
     * Create a member property
     *
     * <b>Request Type: </b>POST<br/>
     * <b>Request Endpoint: </b>http://{{server-domain}}/api/common/member-propertys<br/>
     * <b>Content-type: </b>application/json<br/>
     * <b>Summary: </b>This api is for create a new member property.<br/>
     *
     * <b>Request Example:</b>
     * <pre>
     *{
     *   "order": 7,
     *   "name": "name",
     *  "type": "input",
     *   "defaultValue": "Devin Jin",
     *   "isRequired": true,
     *   "isUnique": true,
     *   "isVisible": true,
     *   "isDefault": true
     * }
     */
    public function actionCreate()
    {
        $accountId = $this->getAccountId();

        //can not exceed 100 properties for an account
        $propertiesCount = MemberProperty::count(['accountId' => $accountId]);

        if ($propertiesCount >= MemberProperty::MAX_COUNT) {
            throw new BadRequestHttpException(Yii::t('member', 'property_max_error'));
        }

        $params = $this->getParams();

        // get the max order property
        $condition = ['accountId' => $accountId, 'isDeleted' => MemberProperty::NOT_DELETED];
        $orderProperty = MemberProperty::find()->where($condition)->orderBy(['order' => SORT_DESC])->one();
        $order = 1;
        if (!empty($orderProperty)) {
            $order = $orderProperty['order'] + 1;
        }

        $property = new MemberProperty;
        $property->load($params, '');
        $property->order = $order;
        $property->accountId = $accountId;

        if ($property->save()) {
            //update all the members of the account
            Member::updateAll(['$push' => ['properties' => ['id' => $property->_id, 'name' => $property->name, 'value' => $property->defaultValue]]], ['accountId' => $accountId]);
            return $property;
        } else if ($property->hasErrors()) {
            throw new BadRequestHttpException(Json::encode($property->errors));
        }

        throw new ServerErrorHttpException('Failed to create the object for unknown reason.');
    }

    /**
     * Update the member property
     *
     * <b>Request Type:</b> PUT<br/>
     * <b>Request Endpoint:</b> http://{{server-domain}}/api/common/member-property/{{id}}
     * <b>Content-type:</b> application/json<br/>
     * <b>Summary</b> This api is for update member property<br/>
     *
     * <b>Request Parameters</b><br/>
     * <pre>
     *     {
     *         "order": 7,
     *         "name": "name123",
     *         "defaultValue": "Devin Jin 1",
     *         "isRequired": true,
     *         "isUnique": true,
     *         "isVisible": true
     *       }
     * </pre>
     */
    public function actionUpdate($id)
    {
        $propertyId = new \MongoId($id);
        $property = MemberProperty::findByPk($propertyId);
        $oldOptions = $property->options;
        $property->load($this->getParams(), '');

        if ($property->save()) {
            //update all the members of the account
            $modifier = ['$set' => ['properties.$.name' => $property->name]];

            if (($property->type == MemberProperty::TYPE_CHECKBOX || $property->type == MemberProperty::TYPE_RADIO) && $property->options != $oldOptions) {
                $modifier['$set']['properties.$.value'] = $property->defaultValue;
            }

            if (!$property->isVisible) {
                $scoreRuleModifier = ['$pull' => ['properties' => $property->_id]];
                $scoreRuleCondition = ['accountId' => $property->accountId, 'name' => ScoreRule::NAME_PERFECT_INFO, 'properties' => $property->_id];
                ScoreRule::updateAll($scoreRuleModifier, $scoreRuleCondition);
            }

            Member::updateAll($modifier, ['properties.id' => $propertyId]);
            return $property;
        } else if ($property->hasErrors()) {
            throw new BadRequestHttpException(Json::encode($property->errors));
        }

        throw new ServerErrorHttpException('Failed to save the object for unknown reason.');
    }

    /**
     * Delete member property
     * <b>Request Type: </b>DELETE<br/>
     * <b>Request Endpoint: </b>http://{{server-domain}}/api/common/member-property/{id}<br/>
     * <b>Summary</b> This api is for delete property for member.
     */
    public function actionDelete($id)
    {
        $accountId = $this->getAccountId();
        $propertyId = new \MongoId($id);

        if (MemberProperty::deleteByPk($propertyId, ['isDefault' => false])) {
            //update member
            Member::updateAll(['$pull' => ['properties' => ['id' => $propertyId]]], ['accountId' => $accountId]);
            //update scoreRule
            ScoreRule::updateAll(['$pull' => ['properties' => $propertyId]], ['accountId' => $accountId]);
            Yii::$app->getResponse()->setStatusCode(204);
            return;
        }

        throw new ServerErrorHttpException('Failed to delete the object for unknown reason.');
    }

    /**
     * Order member properties
     * <b>Request Type: </b>PUT<br/>
     * <b>Request Endpoint: </b>http://{{server-domain}}/api/common/member-property/order<br/>
     * <b>Summary</b> This api is for ordering property for member.
     * <b>Request Parameters</b><br/>
     *
     * <pre>
     *     {
     *         "54a8efe6b81374334e8b4567": 3,
     *         "54a8efe6b81374334e8b4568": 2,
     *         "54a8efe6b81374334e8b4569": 1,
     *         "54a8efe6b81374334e8b4570": 4,
     *       }
     * </pre>
     */
    public function actionOrder()
    {
        $orderMap = $this->getParams();

        foreach ($orderMap as $id => $order) {
            if (!MemberProperty::updateAll(['order' => $order], ['_id' => $id])) {
                throw new ServerErrorHttpException("Failed to save property order with id " . $id);
            }
        }

        Yii::$app->getResponse()->setStatusCode(204);
    }
}
