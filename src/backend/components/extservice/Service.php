<?php
namespace backend\components\extservice;

use Exception;
use MongoId;
use yii\base\Component;

/**
 * A component to service extension
 * @author Harry Sun
 */
class Service extends Component
{
    /**
     * Record the accountId
     * @var MongoId
     */
    public $accountId = null;

    /**
     * Set the account id
     * @param MongoId $accountId
     */
    public function setAccountId(MongoId $accountId)
    {
        $this->accountId = $accountId;
        return $this;
    }

    /**
     * Rewrite the __get to get extension model instance
     * @param  string $name
     */
    public function __get($name)
    {
        if (isset($this->$name)) {
            return $this->$name;
        }

        if (empty($this->accountId) || !MongoId::isValid($this->accountId)) {
            throw new Exception('Please set the accountId and make sure it\'s a MongoId.');
        }

        $name = __NAMESPACE__ . '\models\\' . ucfirst($name);
        return call_user_func([$name, 'getInstance'], $this->accountId);
    }
}
