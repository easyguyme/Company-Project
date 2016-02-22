<?php
namespace common\components;

use Yii;
use yii\base\Component;
use yii\helpers\Json;
use backend\models\Account;

/**
 * Deal with the channel menus
 * @author Harry Sun
 */
class ChannelMenu extends Component
{
    /**
     * Save all channel menus config
     * @var array
     */
    public $config = [];

    /**
     * Init the channel menus config
     * @param  string $channelId
     * @param  MongoId $accountId
     */
    protected function initConfig($channelId, $accountId)
    {
        $account = Account::findByPk($accountId);
        $modules = $account->enabledMods;
        $basePath = Yii::getAlias('@backend');

        if (!empty($modules)) {
            foreach ($modules as $module) {
                if (!empty($module)) {
                    $file = $basePath . '/modules/' . $module . '/config/channelMenu.php';
                    if (is_file($file)) {
                        $config = require($file);
                        if (is_array($config)) {
                            if ($this->_isNotAssoc($config)) {
                                $this->config = array_merge((array)$this->config, (array)$config);
                            } else {
                                $this->config[$module] = $config;
                            }
                        }
                    }
                }
            }
        }
    }

    /**
     * Whether array is assoc
     * @param $arr
     * @return bool
     */
    private static function _isNotAssoc($arr)
    {
        return array_keys($arr) === range(0, count($arr) - 1);
    }

    /**
     * Get all channel menus actions and configs
     * @param  string $channelId
     * @param  MongoId $accountId
     */
    public function getMenuActions($channelId, $accountId)
    {
        if (empty($this->config)) {
            $this->initConfig($channelId, $accountId);
        }

        $result = [];

        foreach ($this->config as $module => $config) {
            // Support the two news for different situation
            if (isset($config['newsCallback']) && is_callable($config['newsCallback']) && !empty($config['news'])) {
                $key = call_user_func($config['newsCallback'], $channelId, $accountId);
                $new = empty($config['news'][$key]) ? '' : $config['news'][$key];
                unset($config['newsCallback'], $config['news']);
                $config = array_merge($config, $new);
            }

            if (isset($config['dataCallback']) && is_callable($config['dataCallback'])) {
                $params = call_user_func($config['dataCallback'], $channelId, $accountId);
                // Support the news
                $isString = true;
                if (!is_string($config['content'])) {
                    $isString = false;
                    // Convert array to str for replacing
                    $config['content'] = Json::encode($config['content']);
                }
                foreach ($params as $key => $value) {
                    // Replace the params in content
                    $config['content'] = str_replace('{{' . $key . '}}', $value, $config['content']);
                }
                if (!$isString) {
                    $config['content'] = Json::decode($config['content']);
                }
            }
            // Use the module name as defaut keycode
            if (empty($config['keycode'])) {
                $config['keycode'] = strtoupper($module);
            }
            if (is_callable($config['isEnabled'])) {
                $config['isEnabled'] = (bool) call_user_func($config['isEnabled'], $channelId, $accountId);
            }
            unset($config['dataCallback']);

            $result[$config['keycode']] = $config;
        }

        return $result;
    }

    /**
     * Get all channel menus keycode
     * @param  string $channelId
     * @param  MongoId $accountId
     */
    public function getAllKeycode($channelId, $accountId)
    {
        if (empty($this->config)) {
            $this->initConfig($channelId, $accountId);
        }

        $result = [];

        foreach ($this->config as $module => $config) {
            $result[] = empty($config['keycode']) ? strtoupper($module) : $config['keycode'];
        }

        return $result;
    }

    /**
     * Get all channel menus keycode without content
     * @param  string $channelId
     * @param  MongoId $accountId
     */
    public function getMenuNoContentActions($channelId, $accountId)
    {
        if (empty($this->config)) {
            $this->initConfig($channelId, $accountId);
        }

        $result = [];

        foreach ($this->config as $module => $config) {
            if (!isset($config['content']) || empty($config['content']) || !$config['content']) {
                $result[] = empty($config['keycode']) ? strtoupper($module) : $config['keycode'];
            }
        }

        return $result;
    }
}
