<?php
namespace backend\modules\channel\modules\offlineStore\controllers;

use backend\components\Controller;
use backend\models\Store;
use backend\utils\StringUtil;
use yii\web\BadRequestHttpException;
use Yii;

class QrcodeController extends Controller
{
    /**
     * qrcode store type
     */
    const QRCODE_TYPE_STORE = 'STORE';

    /**
     * Create a store qrcode
     *
     * <b>Request Type</b>: POST<br/><br/>
     * <b>Request Endpoint</b>:http://{server-domain}/api/offlinestore/qrcodes<br/><br/>
     * <b>Response Content-type</b>: application/json<br/><br/>
     * <b>Summary</b>: This api is used to create store qrcode.
     * <br/><br/>
     *
     * <b>Request Params</b>:<br/>
     *     wechatId: string<br/>
     *     weiboId: string<br/>
     *     storeId: string<br/>
     *     msgType: TEXT or NEWS<br/>
     *     content: string|array If msgType is TEXT, it's a string. If msgType is NEWS, it's an array<br/>
     *     <br/><br/>
     *
     * <b>Response Params:</b><br/>
     *     array, json array to querie qrcode detail information<br/>
     *     <br/><br/>
     *
     * <br/><br/>
     *
     * <b>Response Example</b>:<br/>
     * <pre>
     * {
     *     wechat: {
     *         "id": "5473ffe7db7c7c2f0bee5c71",
     *         "accountId": "5473ffe7db7c7c2f0bee5c71",
     *         "replyMessage": {
     *             "msgType": "NEWS",
     *             "articles": [
     *                 {
     *                     "title": "没有",
     *                     "description": "",
     *                     "url": "http://vincenthou.qiniudn.com/94b6e2756acb030f6f76f690.jpg",
     *                     "content": "<p>哈哈哈哈</p>"
     *                  }
     *              ]
     *          },
     *          "sceneId": 1,
     *          "name": "这只是一个测试",
     *          "type": "EVENT",
     *          "description": "招聘一些人才为公司，以备用",
     *          "ticket": "gQFh8DoAAAAAAAAAASxodHRwOi8vd2VpeGluLnFxLmNvbS9xL3BrampCdVBtVlprc25JVDFHR1RQAAIErHaXVAMEAAAAAA==",
     *          "scanCount": 0,
     *          "subscribeCount": 0,
     *          "createTime": 1421113098304,
     *          "imageUrl": "https://mp.weixin.qq.com/cgi-bin/showqrcode?ticket=gQFh8DoAAAAAAAAAASxodHRwOi8vd2VpeGluLnFxLmNvbS9xL3BrampCdVBtVlprc25JVDFHR1RQAAIErHaXVAMEAAAAAA=="
     *      },
     *      weibo: {
     *          "id": "5473ffe7db7c7c2f0bee5c71",
     *          "accountId": "5473ffe7db7c7c2f0bee5c71",
     *          "replyMessage": {
     *              "msgType": "NEWS",
     *              "articles": [
     *                  {
     *                      "title": "没有",
     *                      "description": "",
     *                      "url": "http://vincenthou.qiniudn.com/94b6e2756acb030f6f76f690.jpg",
     *                      "content": "<p>哈哈哈哈</p>"
     *                  }
     *             ]
     *          },
     *          "sceneId": 1,
     *          "name": "这只是一个测试",
     *          "type": "EVENT",
     *          "description": "招聘一些人才为公司，以备用",
     *          "ticket": "gQFh8DoAAAAAAAAAASxodHRwOi8vd2VpeGluLnFxLmNvbS9xL3BrampCdVBtVlprc25JVDFHR1RQAAIErHaXVAMEAAAAAA==",
     *          "scanCount": 0,
     *          "subscribeCount": 0,
     *          "createTime": 1421113098304,
     *          "imageUrl": "https://mp.weixin.qq.com/cgi-bin/showqrcode?ticket=gQFh8DoAAAAAAAAAASxodHRwOi8vd2VpeGluLnFxLmNvbS9xL3BrampCdVBtVlprc25JVDFHR1RQAAIErHaXVAMEAAAAAA=="
     *      }
     * }
     * </pre>
     */
    public function actionCreate()
    {
        $qrcode = $this->getParams();

        if ((empty($qrcode['wechatId']) && empty($qrcode['weiboId']) && empty($qrcode['alipayId'])) || empty($qrcode['storeId'])) {
            throw new BadRequestHttpException(Yii::t('common', 'parameters_missing'));
        }

        $result = ['wechat' => [], 'weibo' => [], 'alipay' => []];
        $storeId = new \MongoId($qrcode['storeId']);
        $store = Store::findByPk($storeId);
        $qrcode['name'] = $store->name . StringUtil::uuid();
        $qrcode['type'] = self::QRCODE_TYPE_STORE;

        if (!empty($qrcode['wechatId'])) {
            $wechatId = $qrcode['wechatId'];
        }

        if (!empty($qrcode['weiboId'])) {
            $weiboId = $qrcode['weiboId'];
        }

        if (!empty($qrcode['alipayId'])) {
            $alipayId = $qrcode['alipayId'];
        }

        unset($qrcode['wechatId'], $qrcode['weiboId'], $qrcode['storeId'], $qrcode['alipayId']);

        if (!empty($wechatId)) {
            $result['wechat'] = \Yii::$app->weConnect->createQrcode($wechatId, $qrcode);

            if (!empty($result['wechat']) && isset($result['wechat']['id']) && $result['wechat']['imageUrl']) {
                $store->wechat = [
                    'channelId' => $wechatId,
                    'qrcodeId' => $result['wechat']['id'],
                    'qrcode' => $result['wechat']['imageUrl'],
                ];
            }
        }

        if (!empty($weiboId)) {
            $result['weibo'] = \Yii::$app->weConnect->createQrcode($weiboId, $qrcode);

            if (!empty($result['weibo']) && isset($result['weibo']['id']) && $result['weibo']['imageUrl']) {
                $store->weibo = [
                    'channelId' => $weiboId,
                    'qrcodeId' => $result['weibo']['id'],
                    'qrcode' => $result['weibo']['imageUrl'],
                ];
            }
        }

        if (!empty($alipayId)) {
            $result['alipay'] = \Yii::$app->weConnect->createQrcode($alipayId, $qrcode);

            if (!empty($result['alipay']) && isset($result['alipay']['id']) && $result['alipay']['imageUrl']) {
                $store->alipay = [
                    'channelId' => $alipayId,
                    'qrcodeId' => $result['alipay']['id'],
                    'qrcode' => $result['alipay']['imageUrl'],
                ];
            }
        }

        $store->save();

        return $result;
    }

    /**
     * Update a store qrcode
     *
     * <b>Request Type</b>: PUT<br/><br/>
     * <b>Request Endpoint</b>:http://{server-domain}/api/offlinestore/qrcode/update<br/><br/>
     * <b>Response Content-type</b>: application/json<br/><br/>
     * <b>Summary</b>: This api is used for update a store qrcode
     * <br/><br/>
     *
     * <b>Request Params</b>:<br/>
     *     storeId: string<br/>
     *     msgType: TEXT or NEWS<br/>
     *     content: string|array If msgType is TEXT, it's a string. If msgType is NEWS, it's an array<br/>
     *     <br/><br/>
     *
     * <b>Response Params:</b><br/>
     *     array, json array to querie qrcode detail information<br/>
     *     <br/><br/>
     *
     * <br/><br/>
     *
     * <b>Response Example</b>:<br/>
     * <pre>
     * {
     *     wechat: {
     *         "id": "5473ffe7db7c7c2f0bee5c71",
     *         "accountId": "5473ffe7db7c7c2f0bee5c71",
     *         "replyMessage": {
     *             "msgType": "NEWS",
     *             "articles": [
     *                 {
     *                     "title": "没有",
     *                     "description": "",
     *                     "url": "http://vincenthou.qiniudn.com/94b6e2756acb030f6f76f690.jpg",
     *                     "content": "<p>哈哈哈哈</p>"
     *                  }
     *              ]
     *          },
     *          "sceneId": 1,
     *          "name": "这只是一个测试",
     *          "type": "EVENT",
     *          "description": "招聘一些人才为公司，以备用",
     *          "ticket": "gQFh8DoAAAAAAAAAASxodHRwOi8vd2VpeGluLnFxLmNvbS9xL3BrampCdVBtVlprc25JVDFHR1RQAAIErHaXVAMEAAAAAA==",
     *          "scanCount": 0,
     *          "subscribeCount": 0,
     *          "createTime": 1421113098304,
     *          "imageUrl": "https://mp.weixin.qq.com/cgi-bin/showqrcode?ticket=gQFh8DoAAAAAAAAAASxodHRwOi8vd2VpeGluLnFxLmNvbS9xL3BrampCdVBtVlprc25JVDFHR1RQAAIErHaXVAMEAAAAAA=="
     *      },
     *      weibo: {
     *          "id": "5473ffe7db7c7c2f0bee5c71",
     *          "accountId": "5473ffe7db7c7c2f0bee5c71",
     *          "replyMessage": {
     *              "msgType": "NEWS",
     *              "articles": [
     *                  {
     *                      "title": "没有",
     *                      "description": "",
     *                      "url": "http://vincenthou.qiniudn.com/94b6e2756acb030f6f76f690.jpg",
     *                      "content": "<p>哈哈哈哈</p>"
     *                  }
     *             ]
     *          },
     *          "sceneId": 1,
     *          "name": "这只是一个测试",
     *          "type": "EVENT",
     *          "description": "招聘一些人才为公司，以备用",
     *          "ticket": "gQFh8DoAAAAAAAAAASxodHRwOi8vd2VpeGluLnFxLmNvbS9xL3BrampCdVBtVlprc25JVDFHR1RQAAIErHaXVAMEAAAAAA==",
     *          "scanCount": 0,
     *          "subscribeCount": 0,
     *          "createTime": 1421113098304,
     *          "imageUrl": "https://mp.weixin.qq.com/cgi-bin/showqrcode?ticket=gQFh8DoAAAAAAAAAASxodHRwOi8vd2VpeGluLnFxLmNvbS9xL3BrampCdVBtVlprc25JVDFHR1RQAAIErHaXVAMEAAAAAA=="
     *      }
     * }
     * </pre>
     */
    public function actionUpdate()
    {
        $qrcode = $this->getParams();

        if (empty($qrcode['storeId'])) {
            throw new BadRequestHttpException(Yii::t('common', 'parameters_missing'));
        }

        $result = ['wechat' => [], 'weibo' => []];
        $storeId = new \MongoId($qrcode['storeId']);
        $store = Store::findByPk($storeId);
        $qrcode['name'] = $store->branchName . StringUtil::uuid();
        $qrcode['type'] = self::QRCODE_TYPE_STORE;
        unset($qrcode['storeId']);

        if (!empty($store->wechat['channelId'])) {
            $wechatId = $store->wechat['channelId'];
            $qrcodeId = $store->wechat['qrcodeId'];
            $result['wechat'] = Yii::$app->weConnect->updateQrcode($wechatId, $qrcodeId, $qrcode);
        }

        if (!empty($store->weibo['channelId'])) {
            $weiboId = $store->weibo['channelId'];
            $qrcodeId = $store->weibo['qrcodeId'];
            $result['weibo'] = Yii::$app->weConnect->updateQrcode($weiboId, $qrcodeId, $qrcode);
        }

        return $result;
    }

    /**
     * View a store qrcode detail
     *
     * <b>Request Type</b>: GET<br/><br/>
     * <b>Request Endpoint</b>:http://{server-domain}/api/offlinestore/qrcode/view?storeId=551362dbc0d0803ad7995b66<br/><br/>
     * <b>Response Content-type</b>: application/json<br/><br/>
     * <b>Summary</b>: This api is used to view qrcode detail.
     * <br/><br/>
     *
     * <b>Request Params</b>:<br/>
     *     channelId: string<br/>
     *     <br/><br/>
     *
     * <b>Response Params:</b><br/>
     *     array, json array to querie qrcode detail information<br/>
     *     <br/><br/>
     *
     * <br/><br/>
     *
     * <b>Response Example</b>:<br/>
     * <pre>
     * {
     *      "msgType": "NEWS",
     *      "content": {
     *          "articles": [
     *              {
     *                  "title": "没有",
     *                  "description": "",
     *                  "url": "http://vincenthou.qiniudn.com/94b6e2756acb030f6f76f690.jpg",
     *                  "content": "<p>哈哈哈哈</p>"
     *              }
     *          ]
     *      }
     * }
     * </pre>
     */
    public function actionView()
    {
        $qrcode = $this->getQuery();

        if (empty($qrcode['storeId'])) {
            throw new BadRequestHttpException(Yii::t('common', 'parameters_missing'));
        }

        $storeId = new \MongoId($qrcode['storeId']);
        $store = Store::findByPk($storeId);

        if (!empty($store->wechat['channelId'])) {
            $channelId = $store->wechat['channelId'];
            $qrcodeId = $store->wechat['qrcodeId'];
        }
        if (!empty($store->weibo['channelId'])) {
            $channelId = $store->weibo['channelId'];
            $qrcodeId = $store->weibo['qrcodeId'];
        }

        if (!empty($channelId) && !empty($qrcodeId)) {
            $qrcode = Yii::$app->weConnect->getQrcode($channelId, $qrcodeId);

            if (!empty($qrcode['accountId'])) {
                return ['msgType' => $qrcode['msgType'], 'content' => $qrcode['content']];
            }
        }
    }
}
