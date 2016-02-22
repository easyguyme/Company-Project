<?php
namespace backend\models;

use Yii;
use MongoDate;
use backend\components\PlainModel;
use backend\utils\LogUtil;
use backend\utils\MongodbUtil;
use backend\utils\TimeUtil;
use yii\web\BadRequestHttpException;
use backend\modules\member\models\Member;
use backend\modules\member\models\MemberProperty;

/**
 * Model class for MessageTemplate
 *
 * @property MongoId   $_id
 * @property MongoId   $accountId
 * @property string    $name
 * @property boolean   $useWebhook
 * @property Array     $weChat {templateId, templateContent}
 * @property Array     $email {title, content}
 * @property Array     $mobile {message}
 * @property MongoDate $updatedAt
 * @property MongoDate $createdAt
 */
class MessageTemplate extends PlainModel
{
    const REDEMPTION_TITLE = 'redemption_template';
    const PROMOTIONCODE_TITLE = 'promotioncode_template';
    const MESSAGE_TBODY_REGEX = '/<tbody>([\S\s]+?(.*?)[\s\S]+?)<\/tbody>/';
    const RECORD_COUNT_TITLE = '%quantity%';
    const RECORD_PRODUCT_NAME_TITLE = '%product%';
    const RECORD_PRICE_TITLE = '%price%';
    const RECORD_TOTAL_PRICE_TITLE = '%totalPrice%';

    const REDEMPTION_SUBJECT = "积分兑换商品成功通知";
    const PROMOCODE_SUBJECT = "产品码兑换成功通知";
    const REDEMPTION_MOBILE_TEMPLATE = "【群脉CRM】尊敬的客户，您好！您已经成功兑换商品'%product%'，感谢您的合作。";
    const PROMOCODE_MOBILE_TEMPLATE = "【群脉CRM】尊敬的%username%，您的产品码已兑换成功！";

    const REDEMPTION_TYPE = 'redemption';
    const PROMOCODE_TYPE = 'promocode';

    const STAFF_MOBILE_MESSAGE = '【群脉CRM】群脉管理员已经为您创建店员账号，请使用此手机号登录群脉移动POS 客户端激活您的账号！';
    const STAFF_TITLE = 'staff_template';

    const MOBILE_PRODUCT_SPECIAL_PARAM = '%product%x%quantity%';

    /**
     * Declares the name of the Mongo collection associated with MessageTemplate.
     * @return string the collection name
     */
    public static function collectionName()
    {
        return 'messageTemplate';
    }

    public function attributes()
    {
        return array_merge(
            parent::attributes(),
            ['name','weChat','email','mobile', 'useWebhook', 'updatedAt']
        );
    }

    public function safeAttributes()
    {
        return array_merge(
            parent::attributes(),
            ['name','weChat','email','mobile', 'useWebhook', 'updatedAt']
        );
    }

    /**
    * Returns the list of all rules of MessageTemplate.
    * This method must be overridden by child classes to define available attributes.
    *
    * @return array list of rules.
    */
    public function rules()
    {
        return array_merge(
            parent::rules(),
            ['useWebhook', 'default', 'value'=> false]
        );
    }

    /**
    * The default implementation returns the names of the columns whose values have been populated into MessageTemplate.
    */
    public function fields()
    {
        return array_merge(
            parent::fields(),
            [
                'id' => function ($model) {
                    return (string)$model->_id;
                },
                'name', 'weChat', 'email', 'mobile', 'useWebhook',
                'updatedAt' => function ($model) {
                    return MongodbUtil::MongoDate2String($model->updatedAt);
                }
            ]
        );
    }

    /**
     * create a default template for create staff
     */
    public static function createStaffTemplate($accountId)
    {
        $data[] = [
            'accountId' => $accountId,
            'name' => self::STAFF_TITLE,
            'mobile' => ['message' => self::STAFF_MOBILE_MESSAGE],
            'createdAt' => new MongoDate(),
            'updatedAt' => new MongoDate(),
        ];
        MessageTemplate::batchInsert($data);
    }

    /**
     * create a default template for redeem and promotioncode
     */
    public static function createDefaultTemplate($accountId)
    {
        $datas = self::getDefaultTemplateData($accountId);
        MessageTemplate::batchInsert($datas);
    }

    public static function getDefaultTemplateData($accountId)
    {
        $vars = [
            'username' => '%username%',
            'gender' => '%gender%',
            'email' => '%email%',
            'phone' => '%phone%',
            'birthday' => '%birthday%',
            'product' => '%product%',
            'quantity' => '%quantity%',
            'price' => '%price%',
            'total' => '%total%',
            'datas' => [['quantity' => '%quantity%', 'productName' => '%product%', 'price' => '%price%']],
            'totalScore' => '%totalScore%',
        ];

        $controller = Yii::$app->controller;

        $datas = [];
        //default to create zh_cn
        $names = [
            'redemption' => [
                'file' => 'simpleRedemption',
                'name' => self::REDEMPTION_TITLE,
                'title' => self::REDEMPTION_SUBJECT,
                'message' => self::REDEMPTION_MOBILE_TEMPLATE,
            ],
            'promocode' => [
                'file' => 'simplePromocode',
                'name' => self::PROMOTIONCODE_TITLE,
                'title' => self::PROMOCODE_SUBJECT,
                'message' => self::PROMOCODE_MOBILE_TEMPLATE,
            ]
        ];
        foreach ($names as $file => $name) {
            $view = '@backend/views/mail/' . $name['file'];
            $body = $controller->renderPartial($view, $vars);
            $datas[] = [
                'name' => $name['name'],
                'weChat' => ['templateId' => ''],
                'email' => ['title' => $name['title'], 'content' => $body],
                'mobile' => ['message' => $name['message']],
                'accountId' => $accountId,
                'createdAt' => new MongoDate(),
                'updatedAt' => new MongoDate(),
            ];
        }

        return $datas;
    }

    /**
     * @param $name,string, template name
     * @param $accountId, MongoId
     * @param $mobileParams, array,
     * @param $specialPrams, array,it is used to replace some special params,its key is to replace to value
     */
    public static function getMobileTemplate($name, $accountId, $mobileParams, $specialParams = [])
    {
        $where = ['name' => $name, 'accountId' => $accountId];
        $template = MessageTemplate::findOne($where);

        $mobile = '';
        if (!empty($template->mobile['message'])) {
            //get key from $mobileParams and replace mobile template
            $mobile = $template->mobile['message'];
        } else {
            if ($name == self::REDEMPTION_TITLE) {
                $mobile = self::REDEMPTION_MOBILE_TEMPLATE;
            } else {
                $mobile = self::PROMOCODE_MOBILE_TEMPLATE;
            }
        }

        //deal with the param thar called %product%x%quantity% before every param be replaced
        if (!empty($specialParams)) {
            $specialKeys = array_keys($specialParams);
            $specialValues = array_values($specialParams);
            $mobile = str_replace($specialKeys, $specialValues, $mobile);
        }

        $mobileKeys = array_keys($mobileParams);
        $mobileValues = array_values($mobileParams);
        foreach ($mobileKeys as &$mobileKey) {
            $mobileKey = '%' . $mobileKey . '%';
        }
        $mobile = str_replace($mobileKeys, $mobileValues, $mobile);

        return $mobile;
    }

    /**
     * @param $name,string, template name
     * @param $accountId, MongoId
     * @param $emailParams, array,variables for email template;must have key:datas => to show data
     * @param $mobileParams, array,
     */
    public static function getEmailTemplate($name, $accountId, $emailParams)
    {
        $where = ['name' => $name, 'accountId' => $accountId];
        $template = MessageTemplate::findOne($where);
        $emailContent = '';
        if (!empty($template['email']['content'])) {
            //get key from $emailParams and replace email template
            $emailContent = $template['email']['content'];

            if (isset($emailParams['datas'])) {
                $emailContent = self::replaceData($emailContent, $emailParams['datas']);
                unset($emailParams['datas']);
            }
            $emailKeys = array_keys($emailParams);
            $emailValues = array_values($emailParams);
            foreach ($emailKeys as $index => $key) {
                $emailContent = str_replace('%' . $key . '%', $emailValues[$index], $emailContent);
            }
        }
        return $emailContent;
    }

    /**
     * repeat the data to create a list
     * @param $templateContent, string, email template
     * @param $data, array, the array must have this key:quantity, product, price
     */
    public static function replaceData($templateContent, $datas)
    {
        preg_match_all(self::MESSAGE_TBODY_REGEX, $templateContent, $matches);

        $replaceTemplace = '';
        //have content need to repeat
        if (isset($matches[1])) {
            foreach ($matches[1] as $match) {
                if (false !== stripos($match, self::RECORD_PRICE_TITLE)
                    || false !== stripos($match, self::RECORD_COUNT_TITLE)
                    || false !== stripos($match, self::RECORD_PRODUCT_NAME_TITLE)) {
                    $replaceTemplace = $match;
                    $replace = '';
                    foreach ($datas as $data) {
                        $replace .= str_replace(
                            [self::RECORD_COUNT_TITLE, self::RECORD_PRODUCT_NAME_TITLE, self::RECORD_PRICE_TITLE, self::RECORD_TOTAL_PRICE_TITLE],
                            [$data['quantity'], $data['productName'], $data['point'], $data['totalPoint']],
                            $replaceTemplace
                        );
                    }
                    $templateContent = str_replace($replaceTemplace, $replace, $templateContent);
                }
            }
        }
        return $templateContent;
    }

      /**
     * get the vars
     */
    public static function getEmailVar($params)
    {
        if (empty($params['memberId']) || empty($params['type']) || empty($params['language'])) {
            throw new BadRequestHttpException('params missing');
        }

        $type = $params['type'];
        if (!in_array($type, [self::REDEMPTION_TYPE, self::PROMOCODE_TYPE])) {
            throw new BadRequestHttpException('type is invaild');
        }

        $params['memberId'] = new \MongoId($params['memberId']);
        $member = Member::getMemberInfo($params['memberId'], ['name', 'gender', 'email', 'tel', 'birthday']);
        if ('male' == $member['gender']) {
            $gender = '男';
        } else {
            $gender = '女';
        }

        //get the default template
        if ($params['language'] == 'zh_cn') {
            $view = '//mail/simple' . ucfirst($type);
        } else {
            $view = '//mail/traditional' . ucfirst($type);
        }
        $memberInfo = Member::findByPk($params['memberId']);
        $vars = [
            'address' => !empty($params['address']) ? $params['address'] : '',
            'pointBalance' => !isset($memberInfo->score) ? 0 : $memberInfo->score,
            'username' => $member['name'],
            'gender' => $gender,
            'email' => $member['email'],
            'phone' => $member['tel'],
            'birthday' => !empty($member['birthday']) ? date('Y-m-d', $member['birthday']/TimeUtil::MILLI_OF_SECONDS) : '',
        ];
        if ($type == self::REDEMPTION_TYPE) {
            $vars['datas'] = $params['data'];
            $vars['number'] = count($params['data']);
            $vars['amount'] = empty($params['usedScore']) ? 0 : $params['usedScore'];
        } else {
            $vars['total'] = empty($params['total']) ? 0 : $params['total'];
            $vars['totalScore'] = empty($params['totalScore']) ? 0 : $params['totalScore'];
        }
        //to suport member propertyId
        $properties = MemberProperty::getMemberProperty($memberInfo->accountId, $memberInfo->properties);
        $vars = array_merge($vars, $properties);
        return [$view, $vars];
    }

    public static function getMobileVar($params)
    {
        if (empty($params['memberId'])) {
            throw new BadRequestHttpException('missing params');
        }

        $type = $params['type'];
        if (!in_array($type, [self::REDEMPTION_TYPE, self::PROMOCODE_TYPE])) {
            throw new BadRequestHttpException('type is invaild');
        }

        $params['memberId'] = new \MongoId($params['memberId']);
        $member = Member::getMemberInfo($params['memberId'], ['name', 'gender', 'email', 'tel', 'birthday', 'score']);

        if ('male' == $member['gender']) {
            $gender = '男';
        } else {
            $gender = '女';
        }

        $product = $promoCode = '';
        if (!empty($params['data'])) {
            //create product list
            foreach ($params['data'] as $data) {
                if (isset($data['productName'])) {
                    $product .= $data['productName'] . ',';
                } else {
                    $promoCode = implode(',', $params['data']);
                    break;
                }
            }
            $product = rtrim($product, ',');
        }

        $memberInfo = Member::findByPk($params['memberId']);
        $vars = [
            'address' => !empty($params['address']) ? $params['address'] : '',
            'pointBalance' => !isset($memberInfo->score) ? 0 : $memberInfo->score,
            'product' => $product,
            'promoCode' => $promoCode,
            'username' => $member['name'],
            'gender' => $gender,
            'email' => $member['email'],
            'phone' => $member['tel'],
            'birthday' => !empty($member['birthday']) ? date('Y-m-d', $member['birthday']/TimeUtil::MILLI_OF_SECONDS) : '',
            'total' => empty($params['total']) ? 0 : $params['total'],
            'totalScore' => empty($params['totalScore']) ? 0 : $params['totalScore'],
            'number' => count($params['data']),
            'amount' => empty($params['usedScore']) ? 0 : $params['usedScore'],
        ];
        //to suport member propertyId
        $properties = MemberProperty::getMemberProperty($memberInfo->accountId, $memberInfo->properties);
        $vars = array_merge($vars, $properties);
        return $vars;
    }
    /**
     * get name base on the type from messageTemplate
     */
    public static function getTemplateName($type)
    {
        if ($type == self::REDEMPTION_TYPE) {
            $name = self::REDEMPTION_TITLE;
        } else {
            $name = self::PROMOTIONCODE_TITLE;
        }
        return $name;
    }

    /**
     * create the style for wechat template
     */
    public static function createWechatData($datas)
    {
        $result = [];
        if (!empty($datas)) {
            foreach ($datas as $key => $data) {
                $result[$key]['value'] = $data;
            }
        }
        return $result;
    }

    /**
     * get the the product info;example:商品名称x2,商品名称测试x1
     * @param mobileParams, array
     */
    public static function getProductList($mobileParams)
    {
        $key = self::MOBILE_PRODUCT_SPECIAL_PARAM;
        $msg = '';

        if (!empty($mobileParams['data'])) {
            foreach ($mobileParams['data'] as $data) {
                if (isset($data['productName']) && isset($data['quantity'])) {
                    $msg .= $data['productName'] . 'x' . $data['quantity'] . ',';
                }
            }
            $msg = rtrim($msg, ',');
        }

        return [$key => $msg];
    }
}
