<?php
namespace backend\modules\store\controllers;

use Yii;
use MongoId;
use backend\models\Staff;
use yii\web\ServerErrorHttpException;
use backend\exceptions\InvalidParameterException;
use yii\web\BadRequestHttpException;
use backend\utils\MessageUtil;
use backend\utils\LogUtil;
use backend\components\rest\RestController;
use backend\utils\StringUtil;
use backend\components\Webhook;
use backend\modules\member\models\Member;
use backend\utils\MongodbUtil;

class StaffController extends RestController
{
    public $modelClass = 'backend\models\Staff';

    public function actions()
    {
        $actions = parent::actions();
        unset($actions['create'], $actions['update'], $actions['delete']);
        return $actions;
    }

    /**
     * when crate a staff successful,and send sms fail,we need to delete the staff
     */
    public function actionCreate()
    {
        $params = $this->getParams();

        if (empty($params['phone']) || empty($params['channel']['channelId']) || empty($params['badge']) || empty($params['storeId'])) {
            throw new BadRequestHttpException('params missing');
        }

        $accountId = $this->getAccountId();
        $params['accountId'] = $accountId;

        $existsEmpID = Staff::checkUnique($params['badge'], $accountId);
        if ($existsEmpID) {
            throw new InvalidParameterException(['badge' => Yii::t("store", "badge_exists")]);
        }

        $storeId = $params['storeId'];
        $params['storeId'] = new MongoId($storeId);
        if (false === Staff::checkPhone($params['storeId'], $params['phone'])) {
            throw new InvalidParameterException(['phone' => Yii::t("store", 'phone_exists')]);
        }
        $data = Staff::setQrcodeParam($params['channel']['channelId']);
        $params = array_merge($params, $data);
        $params['salt'] = StringUtil::rndString(6, 1);

        $staff = new Staff();
        $staff->load($params, '');

        $result = 'success';
        if ($staff->save()) {
            if (!empty($params['useWebhook'])) {
                $eventData = [
                    'type'=> Webhook::EVENT_STAFF_CREATED,
                    'store_id'=> $storeId,
                    'staff_id' => (string) $staff->_id,
                    'phone'=> $params['phone'],
                    'badge'=> $params['badge'],
                    'channel'=> [
                        'id'=> $params['channel']['channelId'],
                        'name'=> $params['channel']['channelName'],
                        'type'=> $params['channel']['channelType']
                    ],
                    'origin' => Member::PORTAL,
                    'account_id' => (string) $accountId,
                    'created_at' => MongodbUtil::MongoDate2String($staff->createdAt, \DateTime::ATOM)
                ];
                Yii::$app->webhook->triggerEvent($eventData);
            } else {
                //send mobile message
                $template = Staff::getMobileTemplate($accountId);
                $status = MessageUtil::sendMobileMessage($params['phone'], $template, $accountId);

                if (false === $status) {
                    $result = 'fail';
                    //delete the staff
                    Staff::getCollection()->remove(['_id' => $staff->_id]);
                    LogUtil::error(['message' => 'Faild to send message', 'template' => $template, 'params' => $params], 'staff');
                }
            }
        } else {
            throw new ServerErrorHttpException(Yii::t('store', 'fail_to_create'));
        }
        return ['result' => $result];
    }

    public function actionUpdate($id)
    {
        $params = $this->getParams();
        $accountId = $this->getAccountId();

        if (isset($params['isActivated'])) {
            $data['isActivated'] = $params['isActivated'];
        } else {
            $data = [
                'name' => empty($params['username']) ? '' : $params['username'],
                'gender' => empty($params['gender']) ? '' : $params['gender'],
                'birthday' => empty($params['birthday']) ? '' : $params['birthday'],
            ];
        }
        $where = ['_id' => new MongoId($id), 'accountId' => $accountId];
        if (Staff::updateAll($data, $where) && !empty($params['username'])) {
            //define a message to user when user subscribe the platform
            $staff = Staff::findOne($where);
            Staff::setQrcodeMessage($staff, $params['username']);
        }
    }

    public function actionDelete($id)
    {
        //delete staff info and delete staff qrcode
        $id = new MongoId($id);
        $staff = Staff::findByPk($id);
        if (!empty($staff->qrcodeId)) {
            Yii::$app->weConnect->deleteQrcode($staff->channel['channelId'], $staff->qrcodeId);
        }
        Staff::deleteAll(['_id' => $id]);
    }
}
