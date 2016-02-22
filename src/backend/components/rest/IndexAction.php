<?php
namespace backend\components\rest;

use Yii;
use MongoId;
use backend\components\ActiveDataProvider;
use yii\helpers\Json;
use backend\utils\StringUtil;
use backend\models\Token;
use backend\components\PlainModel;
use backend\components\BaseControllerTrait;

/**
 * Class for default action for GET request
 *
 * Use it in url query like the followings:
 * //Order by name asc and email is harrysun@augmentum.com.cn
 * ?orderby={"name":"asc"}&where={"email":"harrysun@augmentum.com.cn"}
 * //Order by name desc
 * ?orderby=name
 *
 * @author Harry Sun
 **/
class IndexAction extends \yii\rest\IndexAction
{
    use BaseControllerTrait;

    /**
     * Prepares the data provider that should return the requested collection of the models.
     * @return ActiveDataProvider
     */
    protected function prepareDataProvider()
    {
        if ($this->prepareDataProvider !== null) {
            return call_user_func($this->prepareDataProvider, $this);
        }

        /* @var $modelClass \yii\db\BaseActiveRecord */
        $modelClass = $this->modelClass;
        $query = $modelClass::find();
        $whereCondition = [
            'accountId' => $this->getAccountId()
        ];
        if (!(new $modelClass instanceof PlainModel)) {
            $whereCondition['isDeleted'] = $modelClass::NOT_DELETED;
        }

        //Transform orderBy condition to mongodb query format
        //Only parse orderBy parameter below
        $orderBy = $modelClass::normalizeOrderBy(Yii::$app->request->get());
        $query->orderBy($orderBy);

        if ($where = Yii::$app->request->get('where')) {
            $where = $this->transformWhereCondition($modelClass, $where);
            $whereCondition = array_merge($whereCondition, $where);
        }

        if ($search = Yii::$app->request->get('search')) {
            $where = $this->transformSearch($search);
            $whereCondition = array_merge($whereCondition, $where);
        }

        $query->where($whereCondition);

        $unlimited = Yii::$app->request->get('unlimited', false);
        if ($unlimited) {
            return ['items' => $query->all()];
        }
        return new ActiveDataProvider(['query' => $query]);
    }

    /**
     * Prefix mongodb query operator keyword with $ simbol
     * @param  array $condition original query condition without $ simbol
     * @return array query condition with $ simbol if match keywords
     */
    protected function transformFieldQuery($condition)
    {
        if (is_array($condition)) {
            $newCondition = [];
            foreach ($condition as $key => $value) {
                //Check if starts with query operator
                if (in_array($key, $keys)) {
                    $key = '$' . $key;
                }
                $newCondition[$key] = $value;
            }
            $condition = $newCondition;
        }
        return $condition;
    }

    /**
     * Transform the request where field value to mongodb query format
     * Transform keys returned from model conver2MongoId method or _id key related value to mongoId
     * Prefix mongodb query operator keyword with $ simbol
     * @param  string $modelClass model class attached to RestController class
     * @param  array  $where      query condition got from frontend where field
     * @return array transformed query condition for mongodb
     */
    protected function transformWhereCondition($modelClass, $where)
    {
        $keys = ['lt', 'lte', 'gt', 'gte', 'ne', 'in', 'nin', 'all'];
        $where = Json::decode($where, true);
        $newWhere = [];
        //A method called conver2MongoId can be defined in model to
        //indicate which field in where condition should be coverted to mongoId
        $conver2MongoIds = [];
        if (method_exists($modelClass, 'conver2MongoId')) {
            $conver2MongoIds = $modelClass::conver2MongoId();
        }
        foreach ($where as $index => $condition) {
            if ($index == '_id' || in_array($index, $conver2MongoIds)) {
                $condition = $this->ensureMongoId($condition);
            }
            if ($index == 'or') {
                $index = '$or';
                //TODO: parse fields in or condition
            } else {
                $condition = $this->transformFieldQuery($condition);
            }
            $newWhere[$index] = $condition;
        }
        return $newWhere;
    }

    /**
     * Transform search condition to mongodb query format, espcially for fuzzy query (contain mode)
     * @param  array $search original query fuzzy value
     * @return array mongodb query format with regular expression
     */
    protected function transformSearch($search)
    {
        $where = [];
        $search = Json::decode($search, true);
        foreach ($search as $key => $value) {
            $value = trim($value);
            $value = StringUtil::regStrFormat($value);
            $where[$key] = new \MongoRegex("/$value/i");
        }
        return $where;
    }

    /**
     * Converts given value into [[MongoId]] instance.
     * If array given, each element of it will be processed.
     * @param mixed $rawId raw id(s).
     * @return array|\MongoId normalized id(s).
     */
    protected function ensureMongoId($rawId)
    {
        if (is_array($rawId)) {
            $result = [];
            foreach ($rawId as $key => $value) {
                $result[$key] = $this->ensureMongoId($value);
            }
            return $result;

        } elseif (is_object($rawId) && $rawId instanceof MongoId) {
            //Return mongoId directly
            return $rawId;
        }
        if (MongoId::isValid($rawId)) {
            //The rawId must be a string
            $mongoId = new MongoId($rawId);
        } else {
            $mongoId = $rawId;
        }

        return $mongoId;
    }
}
