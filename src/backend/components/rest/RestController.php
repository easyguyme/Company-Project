<?php
namespace backend\components\rest;

use yii\rest\ActiveController;
use backend\behaviors\ControllerBehavior;
use yii\web\HttpException;

/**
 * Rest controller
 */
class RestController extends ActiveController
{
    /**
     * serialize the response in format of json
     *
     */
    public $serializer = [
        'class' => 'yii\rest\Serializer',
        'collectionEnvelope' => 'items',
    ];

    /**
     * Initializer of the controller
     *
     **/
    public function init()
    {
        parent::init();
    }

    /**
     * Specify the defatult actions for restful web service
     *
     */
    public function actions()
    {
        $actions = parent::actions();

        $actions['index']['class'] = 'backend\components\rest\IndexAction';
        $actions['create']['class'] = 'backend\components\rest\CreateAction';
        $actions['update']['class'] = 'backend\components\rest\UpdateAction';
        $actions['delete']['class'] = 'backend\components\rest\DeleteAction';
        $actions['view']['class'] = 'backend\components\rest\ViewAction';

        return $actions;
    }

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
     * There is a similar fix in backend\components\Controller.php
     */
    public function afterAction($action, $result)
    {
        $result = parent::afterAction($action, $result);

        $pageFixActions = ['member'];
        if (('index' === $action->id || in_array($action->id, $pageFixActions)) && isset($result['_meta']['currentPage'])) {
            $result['_meta']['currentPage'] ++;
        }

        return $result;
    }
}
