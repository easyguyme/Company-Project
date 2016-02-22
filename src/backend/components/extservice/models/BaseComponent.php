<?php
namespace backend\components\extservice\models;

use yii\base\Component;
use yii\rest\Serializer;
use yii\data\Pagination;
use backend\components\ActiveDataProvider;

/**
 * Base component for extension service
 * @author Harry Sun
 */
class BaseComponent extends Component
{
    /**
     * The instances for BaseComponent
     * @var array
     */
    protected static $_instances;

    /**
     * Instance accountId
     * @var MongoId
     */
    public $accountId;

    /**
     * Get a instance
     * @return BaseComponent
     */
    public static function getInstance($accountId)
    {
        $className = get_called_class();
        if (empty(static::$_instances[$className])) {
            static::$_instances[$className] = new static();
        }
        static::$_instances[$className]->accountId = $accountId;

        return static::$_instances[$className];
    }

    public function formatPageResult(ActiveDataProvider $dataProvider, $page, $pageSize)
    {
        $pagination = new Pagination([
            'page' => $page - 1,//fix yii2.0 bug
            'pageSize' => $pageSize,
        ]);

        $dataProvider->setPagination($pagination);
        $serializer = new Serializer(['collectionEnvelope' => 'items']);
        $result = $serializer->serialize($dataProvider);
        unset($result['_links']);
        $result['_meta']['currentPage']++;//fix yii2.0 bug
        return $result;
    }
}
