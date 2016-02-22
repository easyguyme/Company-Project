# Extension Service Documentation

## Example
You can use extension service like following:
```php
use Yii;
use MongoId;

$service = Yii::$app->service;
$service->accountId = new MongoId("55811f4e2736e795108b456b");
$tags = $service->tag->all();
```

or

```php
use Yii;
use MongoId;

$accountId = new MongoId("55811f4e2736e795108b456b");
$tags = Yii::$app->service->setAccountId($accountId)->tag->all();
```

or

```php
use Yii;
use MongoId;

$accountId = new MongoId("55811f4e2736e795108b456b");
$service = Yii::$app->service->setAccountId($accountId);
$service->tag->create(['new tag']);
$tags = $service->tag->all();
```

## Extension Service Interface

All extension service interface based on accountId. The following examples use `$service` as `Yii::$app->service`;

### Tag

#### Get all tags

- Example

    $service->tag->all();

- Parameters

    | name | type | description | required | example |
    |------|-----|-------|---------|-------|

- Return

    | name | type | description | example |
    |------|-----|-------|-------|
    | tags | array | all tags |[['name' => 'tag1'], ['name' => 'tag2']]|

#### Create tags

- Example

    $service->tag->create(['tag3', 'tag4']);

- Parameters

    | name | type | description | required | example |
    |------|-----|-------|---------|-------|
    | tags | array | the tags need to create | Yes |['tag3', 'tag4']|

- Return

    | name | type | description | example |
    |------|-----|-------|-------|
    | result | boolean | wether create tags success | true |

### channel

#### get channel info by channel id

- Example

    $service->channel->getById($channelId, $one);

- Parameters

    | name | type | description | required | example |
    |------|-----|-------|---------|-------|
    | channelId | array | channelID | Yes |['55ffaccae4b021332764eec2', '55ffaccae4b021332764e444']|
    | one | boolean | return data number | No | true or false(default value is true) |

- Return

    | name | type | description | example |
    |------|-----|-------|-------|
    | result | array | channel info | as below |

```php
单个example:
{
    "id": "55ffacbfd6f97f576c8b4570",
    "channelId": "55ffaccae4b021332764eec2",
    "origin": "wechat",
    "name": "Sean",
    "type": "SERVICE_AUTH_ACCOUNT",
    "status": "enable",
    "isTest": true
}
多个example:
[
    {
        "id": "55ffacbfd6f97f576c8b4570",
        "channelId": "55ffaccae4b021332764eec2",
        "origin": "wechat",
        "name": "Sean",
        "type": "SERVICE_AUTH_ACCOUNT",
        "status": "enable",
        "isTest": true
    },
    {
        "id": "55ffacbfd6f97f576c8b4570",
        "channelId": "55ffaccae4b021332764eec2",
        "origin": "wechat",
        "name": "Sean",
        "type": "SERVICE_AUTH_ACCOUNT",
        "status": "enable",
        "isTest": true
    }
]
```

### url

#### base oauth

- Example

    $url = $service->url->baseOAuth('54d9c155e4b0abe717853ee1', 'http:://www.baidu.com?myparams=params');
    click this $url in mobile wechat client, will redirect to 'http:://www.baidu.com?myparams=params&channelId=54d9c155e4b0abe717853ee1&origin=wechat&openId=****'

- Parameters

    | name | type | description | required | example |
    |------|-----|-------|---------|-------|
    | channelId | string | channelId | Yes | 54d9c155e4b0abe717853ee1 |
    | redirect | string | redirect url | Yes | http:://www.baidu.com |

- Return

    | name | type | description | example |
    |------|-----|-------|-------|
    | url | string | oauth url | http://wm.com/api/mobile/base-oauth?channelId=552621b9e4b00231bde18bdb&redirect=http%3A%2F%2Fwww.baidu.com |

#### member personal

- Example

    $url = $service->url->memberPersonal('54d9c155e4b0abe717854567');
    click this $url, will redirect to member personal page, please make sure you have oauthed before click this url

- Parameters

    | name | type | description | required | example |
    |------|-----|-------|---------|-------|
    | memberId | string | memberId | Yes | 54d9c155e4b0abe717854567 |
    | redirectUrl | string | redirect to this url with "memberId={memberId}" in query | No | http://www.baidu.com |

- Return

    | name | type | description | example |
    |------|-----|-------|-------|
    | url | string | member personal page | http://wm.com/mobile/member/personal?memberId=56021feed6f97f20658b4568 |

#### member bind

- Example

    $url = $service->url->memberBind('54d9c155e4b0abe717853ee1');
    click this $url, It will redirect to member bind page.

- Parameters

    | name | type | description | required | example |
    |------|-----|-------|---------|-------|
    | channelId | string | channelId | Yes | 54d9c155e4b0abe717853ee1 |
    | redirectUrl | string | redirect to this url witn "quncrm_member={memberId}" in query after bind | No | http://www.baidu.com |

- Return

    | name | type | description | example |
    |------|-----|-------|-------|
    | url | string | url oauth to member bind | http://wm.com/api/mobile/member?channelId=54d9c155e4b0abe717853ee1 |

### Member

#### Search member by conditions

- Example

    $service->member->search(['tags' => ['a']])

- Parameters

    | name | type | description | required | example |
    |------|-----|-------|---------|-------|
    | conditions | array | conditions | Yes | ['tags' => ['a']] |
    | page | int | page number, default value is 1 | No | 1 |
    | pageSize | int | page size, default value is 20 | No | 2 |

- Return

    | name | type | description | example |
    |------|-----|-------|-------|
    | result | array | member list, order by createdAt desc | as below |

```php
example:
{
    "items": [
        {
            "id": "561c716bd6f97f1a2c8b4568",
            "socials": [],
            "card": {...},
            "createdAt": "2015-10-13 10:50:19",
            "socialAccount": {
                "id": null,
                "origin": "portal",
                "name": "",
                "type": "",
                "status": ""
            },
            "socialMember": null,
            "properties": [
                {
                    "id": "5518bfacd6f97f41048b456f",
                    "name": "tel",
                    "value": "13021123165"
                },
                {
                    "id": "558b5f5ad6f97f03218b4569",
                    "name": "Operat",
                    "value": "Free Trade"
                },
                {
                    "id": "5593a0ebd6f97fd24a8b4567",
                    "name": "pro",
                    "value": [
                        "a"
                    ]
                },
                {
                    "id": "5518bfacd6f97f41048b4572",
                    "name": "email",
                    "value": "asd@ads.com"
                },
                {
                    "id": "5518bfacd6f97f41048b4571",
                    "name": "birthday",
                    "value": 1444320000000
                },
                {
                    "id": "5518bfacd6f97f41048b4570",
                    "name": "gender",
                    "value": "male"
                }
            ],
            "cardProvideTime": "2015-10-13 10:50:19",
            "cardExpired": 0,
            "avatar": "/images/management/image_hover_default_avatar.png",
            "location": {
                "country": "",
                "province": "",
                "city": "",
                "detail": ""
            },
            "tags": [
                "a"
            ],
            "score": 0,
            "remarks": null,
            "cardNumber": "10025027",
            "unionId": null,
            "totalScore": 0,
            "cardExpiredAt": "",
            "birth": 1009,
            "openId": null,
            "qrcodeViewed": false,
            "totalScoreAfterZeroed": 0,
            "isDisabled": false
        }
    ],
    "_meta": {
        "totalCount": 10,
        "pageCount": 10,
        "currentPage": 2,
        "perPage": 1
    }
}
```

#### Search member by tags

- Example

    $service->member->searchByTags(['a'])

- Parameters

    | name | type | description | required | example |
    |------|-----|-------|---------|-------|
    | tags | array | conditions | Yes | ['a', 'b'] |
    | page | int | page number, default value is 1 | No | 1 |
    | pageSize | int | page size, default value is 20 | No | 2 |

- Return

    | name | type | description | example |
    |------|-----|-------|-------|
    | result | array | member list, order by createdAt desc | as below |

```php
example:
{
    "items": [
        {
            "id": "561c716bd6f97f1a2c8b4568",
            "socials": [],
            "card": {...},
            "createdAt": "2015-10-13 10:50:19",
            "socialAccount": {
                "id": null,
                "origin": "portal",
                "name": "",
                "type": "",
                "status": ""
            },
            "socialMember": null,
            "properties": [
                {
                    "id": "5518bfacd6f97f41048b456f",
                    "name": "tel",
                    "value": "13021123165"
                },
                {
                    "id": "558b5f5ad6f97f03218b4569",
                    "name": "Operat",
                    "value": "Free Trade"
                },
                {
                    "id": "5593a0ebd6f97fd24a8b4567",
                    "name": "pro",
                    "value": [
                        "a"
                    ]
                },
                {
                    "id": "5518bfacd6f97f41048b4572",
                    "name": "email",
                    "value": "asd@ads.com"
                },
                {
                    "id": "5518bfacd6f97f41048b4571",
                    "name": "birthday",
                    "value": 1444320000000
                },
                {
                    "id": "5518bfacd6f97f41048b4570",
                    "name": "gender",
                    "value": "male"
                }
            ],
            "cardProvideTime": "2015-10-13 10:50:19",
            "cardExpired": 0,
            "avatar": "/images/management/image_hover_default_avatar.png",
            "location": {
                "country": "",
                "province": "",
                "city": "",
                "detail": ""
            },
            "tags": [
                "a"
            ],
            "score": 0,
            "remarks": null,
            "cardNumber": "10025027",
            "unionId": null,
            "totalScore": 0,
            "cardExpiredAt": "",
            "birth": 1009,
            "openId": null,
            "qrcodeViewed": false,
            "totalScoreAfterZeroed": 0,
            "isDisabled": false
        }
    ],
    "_meta": {
        "totalCount": 10,
        "pageCount": 10,
        "currentPage": 2,
        "perPage": 1
    }
}
```

#### Get member info by openId or memberId

- Example

    $service->member->one($condition);

- Parameters

    | name | type | description | required | example |
    |------|-----|-------|---------|-------|
    | condition | array | use as a condition to search member info,only suport openId or memberId,if array have openId and openId as key,only suport openId | Yes | ['openId' => 'drJzDjjGGVj6AR9kxD-cbILOH'] or ['memberId' => '5600d3b1d6f97f3a228b47d6'] |

- Return

    | name | type | description | example |
    |------|-----|-------|-------|
    | result | array | member info(may be is a empty array) | as below |

```php
example:
{
    "id": "5600d3b1d6f97f3a228b47d6",
    "socials": [],
    "card": {
        "id": "55c18aecd6f97fb4178b4571",
        "name": "默认会员卡",
        "poster": "/images/mobile/membercard.png",
        "fontColor": "#fff",
        "privilege": "<p>9折消费折扣</p><ul><li>全年消费享有正价商品9折优惠<li></ul><p>生日礼及寿星折扣</p><ul><li>生日當月可憑會員卡到全省門市領取精美生日好禮,且享有壽星200元商品抵用券或8折生日優惠.</li><li>8折優惠適用於正價商品，不得與會員折扣合併使用，生日禮將不定期更換</li></ul>",
        "condition": {
            "minScore": 0,
            "maxScore": 100
        },
        "usageGuide": "使用时向工作人员出示本会员卡即可，最终解释权归本品牌所有",
        "isEnabled": true,
        "isDefault": true,
        "isAutoUpgrade": null,
        "scoreResetDate": null,
        "provideCount": 1,
        "createdAt": "2015-08-05 12:02:52",
        "updatedAt": "2015-08-05 12:02:52"
    },
    "createdAt": "2015-09-22 12:06:09",
    "socialAccount": {
        "id": "55ffaccae4b021332764eec2",
        "origin": "wechat",
        "name": "Sean",
        "type": "SERVICE_AUTH_ACCOUNT",
        "status": "enable"
    },
    "socialMember": null,
    "properties": [
        {
            "id": "55c18aecd6f97fb4178b456c",
            "name": "name",
            "value": "xan"
        },
        {
            "id": "55c18aecd6f97fb4178b456d",
            "name": "tel",
            "value": "13799558651"
        },
        {
            "id": "55c18aecd6f97fb4178b456e",
            "name": "gender",
            "value": "male"
        }
    ],
    "cardProvideTime": "2015-09-22 12:06:09",
    "cardExpired": 0,
    "avatar": "http://wx.qlogo.cn/mmopen/vJghazITJsoO72zUcBryxXlymys0k5W0XfjJdxQLianVY2CDjXuCrBCulkomxricWCicYbdWWKfibtyEhHjYpfPUVc7ic3U7C8dh4/0",
    "location": {
        "city": "上海",
        "province": "上海",
        "country": "中国"
    },
    "tags": null,
    "score": 0,
    "remarks": null,
    "cardNumber": "10000014",
    "unionId": "",
    "totalScore": null,
    "cardExpiredAt": "",
    "birth": null,
    "openId": "o5JzDjjGGVj6AR9kxD-cbILOHtDQ",
    "qrcodeViewed": null,
    "totalScoreAfterZeroed": null,
    "isDisabled": null
}
```

#### Reward score

- Example

    $service->member->rewardScore($memberIds, $score, $description = '');

- Parameters

    | name | type | description | required | example |
    |------|-----|-------|---------|-------|
    | memberIds | array | MongoId list | Yes | [ObjectId('560a48edd6f97f58048b4567')] |
    | score | int | Must gather than 0 | Yes | 12 |
    | description | string | description | No | reward |

- Return

    | name | type | description | example |
    |------|-----|-------|-------|
    | result | bool | weather reward success | true |

#### get member info by member id

- Example

    $service->member->getByIds($memberIds);

- Parameters

    | name | type | description | required | example |
    |------|-----|-------|---------|-------|
    | memberIds | array | MongoId list | Yes | [ObjectId('55c18ac0d6f97f2f7c8b4567'),ObjectId('54d9c155e4b0abe717853ee1')] |

- Return

    | name | type | description | example |
    |------|-----|-------|-------|
    | result | array | member info(may be is a empty array) | as below |

```php
example:
[
    {
        "id": "5600d3b1d6f97f3a228b47d6",
        "socials": [],
        "card": {
            "id": "55c18aecd6f97fb4178b4571",
            "name": "默认会员卡",
            "poster": "/images/mobile/membercard.png",
            "fontColor": "#fff",
            "privilege": "<p>9折消费折扣</p><ul><li>全年消费享有正价商品9折优惠<li></ul><p>生日礼及寿星折扣</p><ul><li>生日當月可憑會員卡到全省門市領取精美生日好禮,且享有壽星200元商品抵用券或8折生日優惠.</li><li>8折優惠適用於正價商品，不得與會員折扣合併使用，生日禮將不定期更換</li></ul>",
            "condition": {
                "minScore": 0,
                "maxScore": 100
            },
            "usageGuide": "使用时向工作人员出示本会员卡即可，最终解释权归本品牌所有",
            "isEnabled": true,
            "isDefault": true,
            "isAutoUpgrade": null,
            "scoreResetDate": null,
            "provideCount": 1,
            "createdAt": "2015-08-05 12:02:52",
            "updatedAt": "2015-08-05 12:02:52"
        },
        "createdAt": "2015-09-22 12:06:09",
        "socialAccount": {
            "id": "55ffaccae4b021332764eec2",
            "origin": "wechat",
            "name": "Sean",
            "type": "SERVICE_AUTH_ACCOUNT",
            "status": "enable"
        },
        "socialMember": null,
        "properties": [
            {
                "id": "55c18aecd6f97fb4178b456c",
                "name": "name",
                "value": "xan"
            },
            {
                "id": "55c18aecd6f97fb4178b456d",
                "name": "tel",
                "value": "13799558651"
            },
            {
                "id": "55c18aecd6f97fb4178b456e",
                "name": "gender",
                "value": "male"
            }
        ],
        "cardProvideTime": "2015-09-22 12:06:09",
        "cardExpired": 0,
        "avatar": "http://wx.qlogo.cn/mmopen/vJghazITJsoO72zUcBryxXlymys0k5W0XfjJdxQLianVY2CDjXuCrBCulkomxricWCicYbdWWKfibtyEhHjYpfPUVc7ic3U7C8dh4/0",
        "location": {
            "city": "上海",
            "province": "上海",
            "country": "中国"
        },
        "tags": null,
        "score": 0,
        "remarks": null,
        "cardNumber": "10000014",
        "unionId": "",
        "totalScore": null,
        "cardExpiredAt": "",
        "birth": null,
        "openId": "o5JzDjjGGVj6AR9kxD-cbILOHtDQ",
        "qrcodeViewed": null,
        "totalScoreAfterZeroed": null,
        "isDisabled": null
    },
    {
        "id": "560a48edd6f97f58048b4567",
        "socials": [],
        "card": {
            "id": "55c18aecd6f97fb4178b4571",
            "name": "默认会员卡",
            "poster": "/images/mobile/membercard.png",
            "fontColor": "#fff",
            "privilege": "<p>9折消费折扣</p><ul><li>全年消费享有正价商品9折优惠<li></ul><p>生日礼及寿星折扣</p><ul><li>生日當月可憑會員卡到全省門市領取精美生日好禮,且享有壽星200元商品抵用券或8折生日優惠.</li><li>8折優惠適用於正價商品，不得與會員折扣合併使用，生日禮將不定期更換</li></ul>",
            "condition": {
                "minScore": 0,
                "maxScore": 100
            },
            "usageGuide": "使用时向工作人员出示本会员卡即可，最终解释权归本品牌所有",
            "isEnabled": true,
            "isDefault": true,
            "isAutoUpgrade": null,
            "scoreResetDate": null,
            "provideCount": 1,
            "createdAt": "2015-08-05 12:02:52",
            "updatedAt": "2015-08-05 12:02:52"
        },
        "createdAt": "2015-09-22 12:06:09",
        "socialAccount": {
            "id": "55ffaccae4b021332764eec2",
            "origin": "wechat",
            "name": "Sean",
            "type": "SERVICE_AUTH_ACCOUNT",
            "status": "enable"
        },
        "socialMember": null,
        "properties": [
            {
                "id": "55c18aecd6f97fb4178b456c",
                "name": "name",
                "value": "xan"
            },
            {
                "id": "55c18aecd6f97fb4178b456d",
                "name": "tel",
                "value": "13799558651"
            },
            {
                "id": "55c18aecd6f97fb4178b456e",
                "name": "gender",
                "value": "male"
            }
        ],
        "cardProvideTime": "2015-09-22 12:06:09",
        "cardExpired": 0,
        "avatar": "http://wx.qlogo.cn/mmopen/vJghazITJsoO72zUcBryxXlymys0k5W0XfjJdxQLianVY2CDjXuCrBCulkomxricWCicYbdWWKfibtyEhHjYpfPUVc7ic3U7C8dh4/0",
        "location": {
            "city": "上海",
            "province": "上海",
            "country": "中国"
        },
        "tags": null,
        "score": 0,
        "remarks": null,
        "cardNumber": "10000014",
        "unionId": "",
        "totalScore": null,
        "cardExpiredAt": "",
        "birth": null,
        "openId": "o5JzDjjGGVj6AR9kxD-cbILOHtDQ",
        "qrcodeViewed": null,
        "totalScoreAfterZeroed": null,
        "isDisabled": null
    }
]

```

### MemberProperty

#### Get member property by accountId

- Example

    $service->memberProperty->all($condition);

- Parameters

    | name | type | description | required | example |
    |------|-----|-------|---------|-------|
    | condition | boolean | this param suport:true,false,null;ture means to get default peoperties,false means to get custom properties,null means to get all properties | No(default value is null,you can do not pass this param) | true or false or null |

- Return

    | name | type | description | example |
    |------|-----|-------|-------|
    | result | array | member property(may be is a empty array) | as below |

```php
example:
[
    {
        "id": "55c18aecd6f97fb4178b456c",
        "order": 1,
        "name": "name",
        "options": null,
        "type": "input",
        "defaultValue": "",
        "isRequired": true,
        "isUnique": true,
        "isVisible": true,
        "isDefault": true,
        "propertyId": null
    },
    {
        "id": "55d2defcd6f97fa3238b4567",
        "order": 5,
        "name": "首選經銷商",
        "options": null,
        "type": "checkbox",
        "defaultValue": "",
        "isRequired": false,
        "isUnique": true,
        "isVisible": true,
        "isDefault": true,
        "propertyId": null
    }
]
```


#### bind member channel

- Example

    $service->member->bindChannel($id, $origin, $channelId = '', $openId = '');

- Parameters

    | name | type | description | required | example |
    |------|-----|-------|---------|-------|
    | id | mongoId | memberId | Yes | "54d9c155e4b0abe717853ee1" |
    | channelId | string | channelId | No | "54d9c155e4b0abe717853ee1" |
    | openId | string | openId | No | "54d9c155e4b0abe717853ee1" |
    | origin | string | available values: wechat, weibo, alipay, portal, app:android, app:ios, app:web, app:webview, others | Yes | "wechat" |

- Return

    | name | type | description | example |
    |------|-----|-------|-------|
    | result | array | the result of bind channel | as below |

```php
example:
[
    {
        "message": "ok",
        "data": {
            "id": "5689f4df025f88b0418b4567",
            "card": {
                "id": "566ff68a025f8890268b457a",
                "name": "默认会员卡",
                "poster": "/images/mobile/membercard.png",
                "fontColor": "#fff",
                "privilege": "<p>9折消费折扣</p><ul><li>全年消费享有正价商品9折优惠<li></ul><p>生日礼及寿星折扣</p><ul><li>生日當月可憑會員卡到全省門市領取精美生日好禮,且享有壽星200元商品抵用券或8折生日優惠.</li><li>8折優惠適用於正價商品，不得與會員折扣合併使用，生日禮將不定期更換</li></ul>",
                "condition": {
                    "minScore": 0,
                    "maxScore": 100
                },
                "usageGuide": "使用时向工作人员出示本会员卡即可，最终解释权归本品牌所有",
                "isEnabled": true,
                "isDefault": true,
                "isAutoUpgrade": true,
                "scoreResetDate": null,
                "provideCount": 1,
                "createdAt": "2015-12-15 19:16:26",
                "updatedAt": "2015-12-15 19:16:26"
            },
            "createdAt": "2016-01-04 12:28:15",
            "socialAccount": {
                "id": null,
                "origin": "portal",
                "name": "",
                "type": "",
                "status": ""
            },
            "properties": [
                {
                    "id": "566ff68a025f8890268b4576",
                    "name": "tel",
                    "value": "23789451"
                },
                {
                    "id": "566ff68a025f8890268b4575",
                    "name": "name",
                    "value": "33311"
                }
            ],
            "tags": [
                ""
            ],
            "score": 0,
            "cardNumber": "10000066",
            "totalScoreAfterZeroed": 0,
            "isDisabled": false
        }
    }
]
```

### captcha

#### record captcha

- Example

    $service->captcha->record('13027785897', '1234', '127.0.0.1');

- Parameters

    | name | type | description | required | example |
    |------|-----|-------|---------|-------|
    | mobile | string | mobile | Yes | '13027785897' |
    | code | string | captcha code | Yes | '1234' |
    | userIp | string | user ip address | Yes | '127.0.0.1' |

- Return

    | name | type | description | example |
    |------|-----|-------|-------|
    | - | bool | save result | true |

#### get latest captcha by mobile

- Example

    $service->captcha->getLastestByMobile('13027785897');

- Parameters

    | name | type | description | required | example |
    |------|-----|-------|---------|-------|
    | mobile | string | mobile | Yes | '13027785897' |

- Return

    | name | type | description | example |
    |------|-----|-------|-------|
    | _id | MongoId | captchaId | ObjectId("568f84ced6f97f7e6c8b456b") |
    | ip | string | user ip address | "127.0.0.1" |
    | mobile | string | mobile | "13027785897" |
    | code | string | code | "1234" |
    | isExpired | bool | is captcha expired | true |
    | createdAt | MongoDate | captcha send time | MongoDate("") |
```

### ScoreHistory

#### Query all score history by conditions

- Example

    $service->scoreHistory->all($conditions);

- Parameters

    | name | type | description | required | example |
    |------|-----|-------|---------|-------|
    | conditions | array | conditions | Yes | ["memberId":ObjectId("568b5e96137473cc168b45e2")] |

- Return

    | name | type | description | example |
    |------|-----|-------|-------|
    | result | array | score history(may be is a empty array) | as below |

```php
example:
[
    {
        "id": "569dfe6d3b69ef67ca5dde8d",
        "assigner": "rule_assignee",
        "increment": 200,
        "brief": "rule_assignee",
        "description": "perfect_information",
        "channel": {
            "id": "",
            "name": "",
            "origin": "portal"
        },
        "user": null,
        "createdAt": "2016-01-19 17:14:19"
    },
    {
        "id": "569edee8d6f97f4f198b456f",
        "assigner": "admin",
        "increment": 10,
        "brief": "admin_issue_score",
        "description": "",
        "channel": {
            "origin": "portal"
        },
        "user": {
            "id": "55bb296dd6f97f23688b456f",
            "name": "admin"
        },
        "createdAt": "2016-01-20 09:12:08"
    },
    {
        "id": "569edf20d6f97fea188b4568",
        "assigner": "exchange_goods",
        "increment": -3,
        "brief": "exchange_goods",
        "description": "乐事薯片(1)",
        "channel": {
            "origin": "portal"
        },
        "user": {
            "id": "55bb296dd6f97f23688b456f",
            "name": "admin"
        },
        "createdAt": "2016-01-20 09:13:04"
    }
]
```

#### Query member`s score history by conditions

- Example

    $service->scoreHistory->search($conditions, $page = 1, $pageSize = 10);

- Parameters

    | name | type | description | required | example |
    |------|-----|-------|---------|-------|
    | conditions | array | conditions | Yes | ["memberId":ObjectId("568b5e96137473cc168b45e2")] |
    | page | int | page number, default value is 1 | No | 1 |
    | pageSize | int | page size, default value is 10 | No | 10 |

- Return

    | name | type | description | example |
    |------|-----|-------|-------|
    | result | array | score history(may be is a empty array) | as below |

```php
example:
{
    "items": [
        {
            "id": "569dfe6d3b69ef67ca5dde8d",
            "assigner": "rule_assignee",
            "increment": 200,
            "brief": "rule_assignee",
            "description": "perfect_information",
            "channel": {
                "id": "",
                "name": "",
                "origin": "portal"
            },
            "user": null,
            "createdAt": "2016-01-19 17:14:19"
        },
        {
            "id": "569edee8d6f97f4f198b456f",
            "assigner": "admin",
            "increment": 10,
            "brief": "admin_issue_score",
            "description": "",
            "channel": {
                "origin": "portal"
            },
            "user": {
                "id": "55bb296dd6f97f23688b456f",
                "name": "admin"
            },
            "createdAt": "2016-01-20 09:12:08"
        },
        {
            "id": "569edf20d6f97fea188b4568",
            "assigner": "exchange_goods",
            "increment": -3,
            "brief": "exchange_goods",
            "description": "乐事薯片(1)",
            "channel": {
                "origin": "portal"
            },
            "user": {
                "id": "55bb296dd6f97f23688b456f",
                "name": "admin"
            },
            "createdAt": "2016-01-20 09:13:04"
        }
    ],
    "_meta": {
        "totalCount": 12,
        "pageCount": 1,
        "currentPage": 1,
        "perPage": 20
    }
}
```

#### Get the overview of the score history

- Example

    $service->scoreHistory->aggregate($pipeline);

- Parameters

    | name | type | description | required | example |
    |------|-----|-------|---------|-------|
    | pipeline | array | the pipeline document | Yes | [['$match' => ['createdAt' => ['$gt' => $today]]], ['$group' => ['_id' => '$memberId', 'totalScore' => ['$sum' =>'$increment']]]] |

- Return

    | name | type | description | example |
    |------|-----|-------|-------|
    | result | array | the overview of the score history | as below |

```php
example:
[
    {
        "_id": ObjectId("569e0ab58fd1252e84000002"),
        "totalScore": 10
    },
    {
        "_id": ObjectId("569e0ab58fd1252e84000002"),
        "totalScore": 100
    },
]
```

### ScoreRule

#### reward score by code

- Example

    $service->scoreRule->rewardByCode($memberId, $code, $origin, $channelId);

- Parameters

    | name | type | description | required | example |
    |------|-----|-------|---------|-------|
    | memberId | MongoId | member id | Yes | ObjectId("56728fb8d6f97fca648b4569") |
    | code | string | score rule code | Yes | "SHARE_REWARD" |
    | origin | string | reward origin | Yes | wechat, weibo, alipay, portal, app:ios, app:android, app:web, app:webview, others |
    | channelId | string | channelId, required when origin is wechat, weibo or alipay | No | "56728fb8d6f97fca648b4569" |

- Return

    | name | type | description | example |
    |------|-----|-------|-------|
    | result | bool | true: means success, false: means failed because of limit | true |

- Exception

    | Exception | description |
    |------|-----|
    | ServerErrorHttpException | reward failed unknow reason |
    | BadRequestHttpException | member not found |
    | BadRequestHttpException | score rule not found |
    | BadRequestHttpException | invalid origin |
    | BadRequestHttpException | invalid channelId |
    | BadRequestHttpException | invalid reward coupon |



#### update member properties

- Example

    $service->member->updateProperties($id, $properties = []);

- Parameters

    | name | type | description | required | example |
    |------|-----|-------|---------|-------|
    | id | string | member`s Id | Yes | "54d9c155e4b0abe717853ee1" |
    | properties | array | the properties of member | No | [{"id": "54d9c155e4b0abe717853ee1", "name": "name", "value": "lydiali"}, ...] |

- Return

    | name | type | description | example |
    |------|-----|-------|-------|
    | result | array | the result of updating properties | as below |

```php
example:
[
    {
        "message": "ok",
        "data": {
            "id": "5689f4df025f88b0418b4567",
            "card": {
                "id": "566ff68a025f8890268b457a",
                "name": "默认会员卡",
                "poster": "/images/mobile/membercard.png",
                "fontColor": "#fff",
                "privilege": "<p>9折消费折扣</p><ul><li>全年消费享有正价商品9折优惠<li></ul><p>生日礼及寿星折扣</p><ul><li>生日當月可憑會員卡到全省門市領取精美生日好禮,且享有壽星200元商品抵用券或8折生日優惠.</li><li>8折優惠適用於正價商品，不得與會員折扣合併使用，生日禮將不定期更換</li></ul>",
                "condition": {
                    "minScore": 0,
                    "maxScore": 100
                },
                "usageGuide": "使用时向工作人员出示本会员卡即可，最终解释权归本品牌所有",
                "isEnabled": true,
                "isDefault": true,
                "isAutoUpgrade": true,
                "scoreResetDate": null,
                "provideCount": 1,
                "createdAt": "2015-12-15 19:16:26",
                "updatedAt": "2015-12-15 19:16:26"
            },
            "createdAt": "2016-01-04 12:28:15",
            "socialAccount": {
                "id": null,
                "origin": "portal",
                "name": "",
                "type": "",
                "status": ""
            },
            "properties": [
                {
                    "id": "566ff68a025f8890268b4576",
                    "name": "tel",
                    "value": "23789451"
                },
                {
                    "id": "566ff68a025f8890268b4575",
                    "name": "name",
                    "value": "33311"
                }
            ],
            "tags": [
                ""
            ],
            "score": 0,
            "cardNumber": "10000066",
            "totalScoreAfterZeroed": 0,
            "isDisabled": false
        }
    }
]
```
