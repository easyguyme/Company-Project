<?php
namespace console\modules\product\controllers;

use Yii;
use yii\console\Controller;
use backend\components\BaseModel;
use backend\models\Account;
use backend\models\MessageTemplate;

/**
 * Create default template
 */
class CreateTemplateController extends Controller
{
    public function actionIndex()
    {
        $types = [MessageTemplate::REDEMPTION_TITLE, MessageTemplate::PROMOTIONCODE_TITLE];

        //get the info of account
        $accountInfos = Account::findAll(['enabledMods' => ['$all' => ['product']]]);

        if ($accountInfos) {
            foreach ($accountInfos as $accountInfo) {
                foreach ($types as $name) {
                    $result = self::checkTemplateNotExists($accountInfo['_id'], $name);
                    if ($result) {
                        MessageTemplate::createDefaultTemplate($accountInfo['_id']);
                    }
                }
            }
        }
        echo 'create data successfully' . PHP_EOL;
    }

    /**
     * create staff template
     */
    public function actionCreateStaff()
    {
        //get the info of account
        $accountInfos = Account::findAll(['enabledMods' => ['$all' => ['product']]]);

        if ($accountInfos) {
            foreach ($accountInfos as $accountInfo) {
                $result = self::checkTemplateNotExists($accountInfo['_id'], MessageTemplate::STAFF_TITLE);
                if ($result) {
                    MessageTemplate::createStaffTemplate($accountInfo['_id']);
                }
            }
        }
        echo 'create staff template successfully' . PHP_EOL;
    }

    /**
     * delete muti staff
     */
    public function actionDeleteStaff()
    {
        $accountInfos = Account::findAll(['enabledMods' => ['$all' => ['product']]]);
        if ($accountInfos) {
            foreach ($accountInfos as $accountInfo) {
                $where = [
                    'accountId' => $accountInfo['_id'],
                    'name' => MessageTemplate::STAFF_TITLE,
                    'mobile' => ['message' => MessageTemplate::STAFF_MOBILE_MESSAGE]
                ];
                $number = MessageTemplate::count($where);
                if ($number >= 2) {
                    $template = MessageTemplate::findOne($where);
                    if (MessageTemplate::deleteAll(['_id' => $template->_id])) {
                        echo 'delete :' . $template->_id . '; accountId:' . $accountInfo['_id'] . PHP_EOL;
                    }
                }
            }
        }
        echo 'delete staff template successfully' . PHP_EOL;
    }

    /**
     * delete muti redemption template and promotion template(delete-product-template)
     */
    public function actionDeleteProductTemplate()
    {
        $accountInfos = Account::findAll(['enabledMods' => ['$all' => ['product']]]);
        if ($accountInfos) {
            $redemptionTemplate = $promotionTemplate = [];
            foreach ($accountInfos as $accountInfo) {
                if (empty($redemptionTemplate) || empty($promotionTemplate)) {
                    $datas = MessageTemplate::getDefaultTemplateData($accountInfo['_id']);
                    foreach ($datas as $data) {
                        if ($data['name'] == MessageTemplate::REDEMPTION_TITLE) {
                            $redemptionTemplate = [
                                'name' => $data['name'],
                                'weChat' => $data['weChat'],
                                'email' => $data['email'],
                                'mobile' => $data['mobile'],
                            ];
                        }

                        if ($data['name'] == MessageTemplate::PROMOTIONCODE_TITLE) {
                            $promotionTemplate = [
                                'name' => $data['name'],
                                'weChat' => $data['weChat'],
                                'email' => $data['email'],
                                'mobile' => $data['mobile'],
                            ];
                        }
                    }
                }
                $redemptionTemplate['accountId'] = $accountInfo['_id'];
                $promotionTemplate['accountId'] = $accountInfo['_id'];

                $redemptionDatas = MessageTemplate::findAll($redemptionTemplate);
                if (count($redemptionDatas) >= 2) {
                    MessageTemplate::deleteAll(['_id' => $redemptionDatas[0]->_id]);
                    echo 'delete redemptionTemplate id:' . $redemptionDatas[0]->_id . '; accountId:' . $accountInfo['_id'] . PHP_EOL;
                }
                $promotionDatas = MessageTemplate::findAll($promotionTemplate);
                if (count($promotionDatas) >= 2) {
                    MessageTemplate::deleteAll(['_id' => $promotionDatas[0]->_id]);
                    echo 'delete promotionTemplate id:' . $promotionDatas[0]->_id . '; accountId:' . $accountInfo['_id'] . PHP_EOL;
                }
            }
        }
        echo 'over' . PHP_EOL;
    }

    public static function checkTemplateNotExists($accountId, $name)
    {
        $where = ['accountId' => $accountId, 'name' => $name];
        $result = MessageTemplate::findOne($where);
        if (empty($result)) {
            return true;
        } else {
            return false;
        }
    }

     /**
     * get multi template account id(staff_template, redemption_template, promotioncode_template)
     */
    public function actionGetAccountId($name)
    {
        $names = [
            MessageTemplate::STAFF_TITLE,
            MessageTemplate::REDEMPTION_TITLE,
            MessageTemplate::PROMOTIONCODE_TITLE
        ];
        if (!in_array($name, $names)) {
            echo 'only suport this type :' . implode(',', $names) . PHP_EOL;
            return;
        }
        echo 'search message template:' . $name . PHP_EOL;
        $accountInfos = Account::findAll(['enabledMods' => ['$all' => ['product']]]);

        if ($accountInfos) {
            switch ($name) {
                case MessageTemplate::STAFF_TITLE:
                    foreach ($accountInfos as $accountInfo) {
                        $where = [
                            'accountId' => $accountInfo['_id'],
                            'name' => MessageTemplate::STAFF_TITLE,
                            'mobile' => ['message' => MessageTemplate::STAFF_MOBILE_MESSAGE]
                        ];
                        $number = MessageTemplate::count($where);
                        if ($number >= 2) {
                            echo 'AccountId :' . $accountInfo['_id'] . PHP_EOL;
                        }
                    }
                    break;

                case MessageTemplate::REDEMPTION_TITLE:
                    $redemptionTemplate = [];
                    foreach ($accountInfos as $accountInfo) {
                        if (empty($redemptionTemplate)) {
                            $datas = MessageTemplate::getDefaultTemplateData($accountInfo['_id']);
                            foreach ($datas as $data) {
                                if ($data['name'] == MessageTemplate::REDEMPTION_TITLE) {
                                    $redemptionTemplate = [
                                        'name' => $data['name'],
                                        'weChat' => $data['weChat'],
                                        'email' => $data['email'],
                                        'mobile' => $data['mobile'],
                                    ];
                                }
                            }
                        }
                        $redemptionTemplate['accountId'] = $accountInfo['_id'];
                        $redemptionDatas = MessageTemplate::findAll($redemptionTemplate);
                        if (count($redemptionDatas) >= 2) {
                            echo 'AccountId :' . $accountInfo['_id'] . PHP_EOL;
                        }
                    }
                    break;

                case MessageTemplate::PROMOTIONCODE_TITLE:
                    $promotionTemplate = [];
                    foreach ($accountInfos as $accountInfo) {
                        if (empty($promotionTemplate)) {
                            $datas = MessageTemplate::getDefaultTemplateData($accountInfo['_id']);
                            foreach ($datas as $data) {
                                if ($data['name'] == MessageTemplate::PROMOTIONCODE_TITLE) {
                                    $promotionTemplate = [
                                        'name' => $data['name'],
                                        'weChat' => $data['weChat'],
                                        'email' => $data['email'],
                                        'mobile' => $data['mobile'],
                                    ];
                                }
                            }
                        }
                        $promotionTemplate['accountId'] = $accountInfo['_id'];
                        $promotionDatas = MessageTemplate::findAll($promotionTemplate);
                        if (count($promotionDatas) >= 2) {
                            echo 'AccountId :' .$accountInfo['_id'] . PHP_EOL;
                        }
                    }
                    break;
            }
        }
    }
}
