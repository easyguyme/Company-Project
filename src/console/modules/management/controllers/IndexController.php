<?php
namespace console\modules\management\controllers;

use Yii;
use ReflectionClass;
use yii\console\Controller;
use backend\utils\FileUtil;

/**
 * Ensure all collection indexes
 */
class IndexController extends Controller
{
    public function actionIndex()
    {
        $backendModuls = FileUtil::getModule('backend');
        if (!empty($backendModuls)) {
            foreach ($backendModuls as $module) {
                $reflectionClass = new ReflectionClass($module['class']);
                if ($reflectionClass->hasMethod('setCollectionIndex')) {
                    $models = call_user_func([$module['class'], 'setCollectionIndex']);
                    if (!empty($models)) {
                        foreach ($models as $model) {
                            if (!empty($model['class']) && !empty($model['indexes'])) {
                                static::createMongoIndexByConfig($model);
                                echo $model['class'] . ' Create mongo indexes successfully' . PHP_EOL;
                            }
                        }
                    }
                }
            }
        }
        echo 'Create mongo indexes over' . PHP_EOL;
    }

    public static function createMongoIndexByConfig($model)
    {
        $options = ['background' => true];

        $collection = call_user_func([$model['class'], 'getCollection']);
        // get mongo collection
        $mongoCollection = $collection->mongoCollection;
        // get all indexes info
        $indexInfos = $mongoCollection->getIndexInfo();
        $formattedIndexes = [];

        foreach ($model['indexes'] as $index) {
            $indexKeys = static::formatIndexKey($index['keys']);
            $formattedIndexes[] = $indexKeys;
            $index['options'] = array_merge($options, $index['options']);

            // find the exist index
            $existIndex = null;
            if (!empty($indexInfos)) {
                foreach ($indexInfos as $indexInfo) {
                    if (isset($indexInfo['key']) && $indexInfo['key'] === $indexKeys) {
                        $existIndex = $indexInfo;
                        break;
                    }
                    unset($indexInfo);
                }
            }

            if (!empty($existIndex)) {
                unset($existIndex['v'], $existIndex['key'], $existIndex['name'], $existIndex['ns']);
                if ($index['options'] != $existIndex) {
                    // if there are some difference, delete the index
                    $mongoCollection->deleteIndex($indexKeys);
                }
            }
            // ensure the index
            $mongoCollection->ensureIndex($indexKeys, $index['options']);
            unset($index, $indexKeys, $existIndex);
        }

        // add the default index for _id
        $formattedIndexes[] = ["_id" => 1];
        foreach ($indexInfos as $indexInfo) {
            if (!in_array($indexInfo['key'], $formattedIndexes, true)) {
                // delete index
                $mongoCollection->deleteIndex($indexInfo['key']);
            }
        }
        unset($collection, $mongoCollection, $indexInfos, $formattedIndexes, $model);
    }

    /**
     * Wether a array is assoc
     * @param  array  $arr
     * @return boolean
     */
    public static function isAssoc($arr)
    {
        return array_keys($arr) !== range(0, count($arr) - 1);
    }

    /**
     * Format index key from ['key1', 'key2'] to ['key1' => 1, 'key2' => 1]
     * @param  array $keys
     * @return array
     */
    public static function formatIndexKey($keys)
    {
        $indexKeys = [];
        if (!static::isAssoc($keys)) {
            // format the index keys
            foreach ($keys as $key => $value) {
                if (!is_string($key)) {
                    $indexKeys[$value] = 1;
                } else {
                    $indexKeys[$key] = $value;
                }
            }
            unset($key, $value);
        } else {
            $indexKeys = $keys;
        }
        return $indexKeys;
    }
}
