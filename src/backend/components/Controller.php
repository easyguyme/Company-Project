<?php
/**
 * This is class file for Controller.
 * This is base controller for non-restful apis
 *
 * @author Devin Jin
 *
 */

namespace backend\components;

use backend\behaviors\ControllerBehavior;
use Yii;

class Controller extends \yii\rest\Controller
{
    /**
     * This method is used to valide the user's authority with token.
     * This method is invoked right before an action is executed.
     *
     * The method will trigger the [[EVENT_BEFORE_ACTION]] event. The return value of the method
     * will determine whether the action should continue to run.
     *
     * If you override this method, your code should look like the following:
     *
     * ```php
     * public function beforeAction($action)
     * {
     *     if (parent::beforeAction($action)) {
     *         // your custom code here
     *         return true;  // or false if needed
     *     } else {
     *         return false;
     *     }
     * }
     * ```
     *
     * @param Action $action the action to be executed.
     * @return boolean whether the action should continue to run.
     * @author Harry Sun
     */
    public function beforeAction($action)
    {
        if (parent::beforeAction($action)) {
            $this->attachBehavior('ControllerBehavior', new ControllerBehavior);
            $token = $this->getAccessToken();

            return $this->checkAuth($this->module, $token);
        }

        throw new HttpException(400, "Fail to resolve the action.");
    }

    /**
     * @inheritdoc
     * Fix Yii2 Bug #5665: The `currentPage` meta data in the RESTful result should be 1-based, similar to that in HTTP headers
     * There is a similar fix in backend\components\rest\RestController.php
     */
    public function afterAction($action, $result)
    {
        $result = parent::afterAction($action, $result);

        $fixActions = ['message-history'];
        if (in_array($action->id, $fixActions) && isset($result['_meta']['currentPage'])) {
            $result['_meta']['currentPage'] ++;
        }

        return $result;
    }

    /**
     * serialize the response in format of json
     *
     */
    public $serializer = [
        'class' => 'yii\rest\Serializer',
        'collectionEnvelope' => 'items',
    ];
}
