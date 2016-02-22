<?php
namespace backend\components\mail;

use Yii;
use yii\base\Component;
use yii\base\Controller;
use backend\models\ServiceSetting;

/**
 * Send email with SendCloud use curl
 * @see http://sendcloud.sohu.com/
 * @example
 * $data = [
 *     'api_user' => 'postmaster@xxxx.sendcloud.org',
 *     'api_key' => 'xxxxxxxxxxxxxxxx',
 *     'from' => 'sendcloud@sendcloud.org',
 *     'fromname' => 'SendCloud',
 *     'to' => 'to1@domain.com;to2@domain.com',
 *     'subject' => 'Sendcloud php webapi example',
 *     'html' => "<html><head></head><body><p>欢迎使用<a href=\'http://sendcloud.sohu.com\'>SendCloud</a></p></body></html>"
 * ];
 * @author Harry Sun
 **/
class Mailer extends Component
{

    /**
     * @var string SendCloud api user
     */
    public $api_user;

    /**
     * @var string SendCloud api key
     */
    public $api_key;

    /**
     * @var string email sender
     */
    public $from;

    /**
     * @var string email sender name
     */
    public $fromname;

    /**
     * @var string email preview page
     */
    private $_view;

    /**
     * @var string email errors
     */
    public $errors;

    /**
     * Send email with SendCloud
     * @param  string $to      example like:'to1@domain.com;to2@domain.com'
     * @param  string $subject email subject
     * @param mixed $accountId, Account mongo ID or account ID string.
     * @param  string $html    email template
     * @return boolean
     */
    public function sendMail($to, $subject, $accountId, $html = '')
    {
        if (is_string($accountId)) {
            $accountId = new \MongoId($accountId);
        }
        $apiUser = $this->api_user;
        $apiKey = $this->api_key;
        $setting = ServiceSetting::findByAccountId($accountId);
        if (!empty($setting) && !empty($setting->email)) {
            $apiUser = $setting->email['apiUser'];
            $apiKey = $setting->email['apiKey'];
        }
        $ch = curl_init();
        $data = [
            'api_user' => $apiUser,
            'api_key' => $apiKey,
            'from' => $this->from,
            'fromname' => $this->fromname,
            'to' => $to,
            'subject' => $subject,
            'html' => empty($html) ? $this->_view : $html,
        ];

        curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($ch, CURLOPT_URL, 'https://sendcloud.sohu.com/webapi/mail.send.xml');
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        //klp proxy
        if (defined('KLP') && KLP) {
            curl_setopt($ch, CURLOPT_PROXY, 'sgsgprxs000.unileverservices.com');
            curl_setopt($ch, CURLOPT_PROXYPORT, 3128);
        }

        $result = curl_exec($ch);

        if ($result === false) {
            $this->errors = curl_error($ch);
        }

        curl_close($ch);
        return $result;
    }

    /**
     * Displays an e-mail in preview mode.
     * @param string $view the view name. Please refer to [[render()]] on how to specify a view name. example: '//mail/register', the view file in backend backend/views folder.
     * @param array $vars the parameters (name-value pairs) that should be made available in the view. example: ['name' => 'harry', 'link' => 'http://wm.com/XXXX'].
     * @param string $layout example: '//layouts/email', the view file in backend backend/layouts folder.
     */
    public function setView($view, $vars = array(), $layout = null)
    {
        // Get default controller
        $controller = Yii::$app->controller;

        if (empty($controller)) {
            $controller = new Controller('site', Yii::$app->module);
        }

        $body = $controller->renderPartial($view, $vars);

        if ($layout === null) {
            $this->_view = $body;
        } else {
            // Render the layout file with content
            $this->_view = $controller->renderPartial($layout, array('content' => $body));
        }
    }
}
