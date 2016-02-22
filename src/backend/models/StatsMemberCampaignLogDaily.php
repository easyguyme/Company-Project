<?php
namespace backend\models;

use backend\components\PlainModel;
use backend\utils\MongodbUtil;

/**
 * Model class for statsMemberCampaignLogDaily.
 * Provide meta data for other campaign related statistics
 *
 * The followings are the available columns in collection 'statsMemberCampaignLogDaily':
 * @property MongoId $_id
 * @property String $memberId
 * @property String $memProperty
 * @property String $productId
 * @property int $code
 * @property String $year
 * @property String $quarter
 * @property String $month
 * @property ObjectId $accountId
 *
 **/

class StatsMemberCampaignLogDaily extends PlainModel
{
    /**
     * Declares the name of the Mongo collection associated with statsMemberCampaignLogDaily.
     * @return string the collection name
     */
    public static function collectionName()
    {
        return 'statsMemberCampaignLogDaily';
    }

    /**
     * Returns the list of all attribute names of statsMemberCampaignLogDaily.
     * This method must be overridden by child classes to define available attributes.
     * The parent's attributes function is:
     *
     * @return array list of attribute names.
     */
    public function attributes()
    {
        return array_merge(
            parent::attributes(),
            ['memberId', 'memProperty', 'productId', 'code', 'year', 'quarter', 'month']
        );
    }

    public function safeAttributes()
    {
        return array_merge(
            parent::attributes(),
            ['memberId', 'memProperty', 'productId', 'code', 'year', 'quarter', 'month']
        );
    }

    /**
     * Returns the list of all rules of statsMemberCampaignLogDaily.
     * This method must be overridden by child classes to define available attributes.
     *
     * @return array list of rules.
     */
    public function rules()
    {
        return array_merge(
            parent::rules(),
            [
                [['memberId', 'memProperty', 'productId', 'code', 'year', 'quarter', 'month'], 'required'],
            ]
        );
    }

    /**
     * The default implementation returns the names of the columns whose values have been populated into statsMemberCampaignLogDaily.
     */
    public function fields()
    {
        return array_merge(
            parent::fields(),
            [
                'memberId', 'memProperty', 'productId', 'code', 'year', 'quarter', 'month'
            ]
        );
    }

    public static function getPropAvgTradeQuarterly($accountId, $memPropertyId, $year, $quarter)
    {
        $condition = [
            'accountId' => $accountId,
            'year' => $year,
            'quarter' => $quarter
        ];
        $keys = ["memProperty.$memPropertyId" => true];
        $initial = ['avg' => 0, 'members' => ['count' => 0]];
        $reduce = 'function(doc, prev) {
                        if (!prev.members[doc.memberId]) {
                            var product = {"count":1};
                            product[doc.productId] = true;
                            prev.members[doc.memberId] = product;
                            prev.members["count"]++;
                        } else if (!prev.members[doc.memberId][doc.productId]) {
                            prev.members[doc.memberId][doc.productId] = true;
                            prev.members[doc.memberId]["count"]++;
                        }
                    }';
        $finalize = 'function(prev) {
                        var productCount = 0;
                        var memberCount = prev.members["count"];
                        delete prev.members.count;
                        for (var memberId in prev.members) {
                            var product = prev.members[memberId];
                            productCount += product["count"];
                        }
                        prev.avg = productCount / memberCount;
                        delete prev.members;
                    }';
        $options = [
            'condition' => $condition,
            'finalize' => $finalize
        ];
        return self::getCollection()->group($keys, $initial, $reduce, $options);
    }
}
