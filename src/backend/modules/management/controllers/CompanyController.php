<?php
namespace backend\modules\management\controllers;

use backend\modules\channel\controllers\BaseController;
use backend\models\Account;
use yii\web\ServerErrorHttpException;
use backend\models\Captcha;
use backend\exceptions\InvalidParameterException;
use yii\web\BadRequestHttpException;
use backend\behaviors\CaptchaBehavior;

class CompanyController extends BaseController
{

    /**
     * Get company info
     *
     * <b>Request Type</b>: POST<br/><br/>
     * <b>Request Endpoint</b>:http://{server-domain}/management/company<br/><br/>
     * <b>Content-type</b>: application/json<br/><br/>
     * <b>Summary</b>: This api is used for billing account to get company info
     * <br/><br/>
     *
     * <b>Request Params</b>:<br/>
     *     <br/><br/>
     *
     * <b>Response Params:</b><br/>
     *     <br/><br/>
     *
     * <b>Response Example</b>:<br/>
     * <pre>
     *  {
     *      "id": "54b34b14e9c2fbe7268b4567",
     *      "comapny": 'Augmentum',
     *      "phone": '1302587456',
     *      "name": 'Hou'
     *  }
     * </pre>
     */
    public function actionIndex()
    {
        $accountId = $this->getAccountId();

        $account = Account::findByPk($accountId);

        if (!empty($account)) {
            return [
                'id' => $account->_id . '',
                'comapny' => $account->company,
                'phone' => $account->phone,
                'name' => $account->name,
                'helpdeskPhone' => $account->helpdeskPhone,
            ];
        }

        throw new ServerErrorHttpException('Failed to find company info');
    }


    /**
     * Update company info
     *
     * <b>Request Type</b>: PUT<br/><br/>
     * <b>Request Endpoint</b>:http://{server-domain}/management/company/{id}<br/><br/>
     * <b>Content-type</b>: application/json<br/><br/>
     * <b>Summary</b>: This api is used to update company info
     * <br/><br/>
     *
     * <b>Request Params</b>:<br/>
     *     captcha: string<br/>
     *     company: string<br/>
     *     name: string<br/>
     *     phone: string<br/>
     *     <br/><br/>
     *
     * <b>Response Params:</b><br/>
     *     <br/><br/>
     *
     * <b>Response Example</b>:<br/>
     * <pre>
     *  {
     *      'message' => 'OK';
     *  }
     * </pre>
     */
    public function actionUpdate($id)
    {
        $account = Account::findByPk(new \MongoId($id));

        $company = $this->getParams('company');
        $name = $this->getParams('name');
        $phone = $this->getParams('phone');
        $code = $this->getParams('captcha');
        $helpdeskPhone = $this->getParams('helpdeskPhone');

        if (empty($company) && empty($name) && empty($phone) && empty($helpdeskPhone)) {
            throw new BadRequestHttpException('Missing require params');
        }

        !empty($company) ? $account->company = $company : '';
        !empty($name) ? $account->name = $name : '';
        !empty($helpdeskPhone) ? $account->helpdeskPhone = $helpdeskPhone : '';

        if (!empty($phone)) {
            if (empty($code)) {
                throw new InvalidParameterException(['captcha' => \Yii::t('management', 'empty_captcha_error')]);
            }

            $this->attachBehavior('CaptchaBehavior', new CaptchaBehavior);
            $this->checkCaptcha($phone, $code);

            $account->phone = $phone;
        }

        if ($account->save()) {
            return ['message' => 'ok'];
        } else {
            throw new ServerErrorHttpException('Comapny save fail');
        }
    }
}
