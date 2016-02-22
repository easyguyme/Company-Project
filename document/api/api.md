# Get all campaign

- Request Method:
Get

- Request Endpoint:
http://{server-domain}/api/product/campaigns

- Request Parameters:

| Name | Type | Required | Example | Description |
| ---- | ---- | -------- | ------- | ----------- |
| accesstoken | string | Yes | 2b0f27f3-60e8-3763-c187-bd3dd6167e54 | 用户token（放在querystring中） |
| page | int | 第几页 | 2 | No 默认为1 |
| per-page | string | 每页条数 | 10 | No 默认为20 |
| search | string | No | {"name":"抢红包啦"} | 查找条件 |
| orderBy | string | No | {"name":"asc"} | 排序 |

- Request Example

```
http://dev.quncrm.com/api/product/campaigns
```

- Response Example

```
{
    "items": [
        {
            "id": "55360eded6f97f07048b4571",
            "name": "限时抽奖",
            "startTime": "2015-04-19 16:00:00",
            "endTime": "2015-05-19 16:00:00",
            "participantCount": 10,
            "limitTimes": 1,
            "promotion": {
                "type": "promotion_code",
                "data": [
                    {
                        "productId": "5530c501d6f97f6e038b4569",
                        "productName": "test"
                    }
                ],
                "gift": {
                    "type": "score",
                    "config": {
                        "method": "times",
                        "number": 2
                    }
                },
                "campaigns": "first",
                "tags": [],
                "channels": []
            },
            "promotionCodeCount": 0,
            "isActivated": true,
            "isExpired": false
        },
        {
            "id": "55360e26d6f97f07048b4570",
            "name": "限时抽奖1",
            "startTime": "2015-04-19 16:00:00",
            "endTime": "2015-05-19 16:00:00",
            "participantCount": 10,
            "limitTimes": 1,
            "promotion": {
                "type": "promotion_code",
                "gift": {
                    "type": "lottery",
                    "config": {
                        "method": "scale",
                        "prize": [
                            {
                                "name": "大白兔",
                                "number": 20
                            }
                        ]
                    }
                },
                "campaigns": "first",
                "tags": [],
                "channels": [],
                "data": []
            },
            "promotionCodeCount": 0,
            "isActivated": false,
            "isExpired": false
        }
    ],
    "_links": {
        "self": {
            "href": "http://wm.com/api/product/campaigns?accesstoken=87782eb2-7799-eeb2-cb35-5025648b370e&page=1"
        }
    },
    "_meta": {
        "totalCount": 2,
        "pageCount": 1,
        "currentPage": 1,
        "perPage": 20
    }
}
```

# Create campaign

- Request Method:
POST

- Request Endpoint:
http://{server-domain}/api/product/campaigns

- Request Parameters:

| Name | Type | Required | Example | Description |
| ---- | ---- | -------- | ------- | ----------- |
| accesstoken | String | Yes | 2b0f27f3-60e8-3763-c187-bd3dd6167e54 | 用户token（放在querystring中） |
| name | string | Yes | 2 | 活动名称 |
| startTime | string | Yes | 1429000112193 毫秒时间戳（下同）| 活动开始时间 |
| endTime | string | Yes | 1429000116193 毫秒时间戳（下同） | 活动结束时间 |
| participantCount | int | No | 100  | 参与人数 默认为不限(null) |
| limitTimes | int | No | 2  | 参与次数限制 默认为不限(null) |
| productIds | Array | 关联商品的Id | [551368a2137473e9438b4578, 551368a2137473e9438b4579]  | No 默认为[] |
| campaigns | string或array | Yes | [55135f6c137473e2438b4570,55135f6c137473e2438b4570] | 'unlimited', 'first' 或者 [campaignId] |
| tags | array | No | ["liu","chen"] | 默认为空数组 |
| channels | array | No | ["55135f6c137473e2438b4570","55135f6c137473e2438b4570"] | 默认为空数组 |
| gift | Array | Yes | [] | 赠品配置如下 |
| gift.type | string | Yes | score | 赠品类型, score或者lottery |
| gift.config | Array | Yes | 如下 | 赠品规则 |
| gift.config.method | string | No | "scale" | gift.type为score时为scale或者number gift.type为lottery时为times或score |
| gift.config.number | int | No | 100 | gift.type为score时必填 倍数或积分 |
| gift.config.prize | array | No | [] | 如下 |
| gift.config.prize.name | string | No | "泰迪熊一只" | gift.type为lottery时必填 奖品名 |
| gift.config.prize.number | int | No | 20 | gift.type为lottery时必填 奖品数目或中奖人数比例 |
| isActivated | boolean | Yes | true  | 是否启用 |

- Request Example

```
http://dev.quncrm.com/api/product/campaigns
```

- Response Example

```
{
    "id": "55360e26d6f97f07048b4570",
    "name": "限时抽奖1",
    "startTime": "2015-04-19 16:00:00",
    "endTime": "2015-05-19 16:00:00",
    "participantCount": 10,
    "limitTimes": 1,
    "promotion": {
        "type": "promotion_code",
        "gift": {
            "type": "lottery",
            "config": {
                "method": "scale",
                "prize": [
                    {
                        "name": "大白兔",
                        "number": 20
                    }
                ]
            }
        },
        "campaigns": "first",
        "tags": [],
        "channels": [],
        "data": []
    },
    "promotionCodeCount": 0,
    "isActivated": false,
    "isExpired": false
}
```

# Get campaign by id

- Request Method:
Get

- Request Endpoint:
http://{server-domain}/api/product/campaign/{campaign_id}

- Request Parameters:

| Name | Type | Required | Example | Description |
| ---- | ---- | -------- | ------- | ----------- |
| accesstoken | String | Yes | 2b0f27f3-60e8-3763-c187-bd3dd6167e54 | 用户token（放在querystring中） |

- Request Example

```
http://dev.quncrm.com/api/associations
```

- Response Example

```
{
    "id": "55360e26d6f97f07048b4570",
    "name": "限时抽奖1",
    "startTime": "2015-04-19 16:00:00",
    "endTime": "2015-05-19 16:00:00",
    "participantCount": 10,
    "limitTimes": 1,
    "promotion": {
        "type": "promotion_code",
        "gift": {
            "type": "lottery",
            "config": {
                "method": "scale",
                "prize": [
                    {
                        "name": "大白兔",
                        "number": 20
                    }
                ]
            }
        },
        "campaigns": "first",
        "tags": [],
        "channels": [],
        "data": []
    },
    "promotionCodeCount": 0,
    "isActivated": false,
    "isExpired": false
}
```

# Get campaign name by ids

- Request Method:
Get

- Request Endpoint:
http://{server-domain}/api/product/campaign/names

- Request Parameters:

| Name | Type | Required | Example | Description |
| ---- | ---- | -------- | ------- | ----------- |
| accesstoken | String | Yes | 2b0f27f3-60e8-3763-c187-bd3dd6167e54 | 用户token（放在querystring中） |
| campaignIds | String | Yes | campaignIds=552dcf7cd6f97f91748b4573,55189a7cd6f97f41048b4567 | campaign ids  |

- Request Example

```
http://dev.quncrm.com/api/product/campaign/names?accesstoken=2b0f27f3-60e8-3763-c187-bd3dd6167e54&campaignIds=552dcf7cd6f97f91748b4573,55189a7cd6f97f41048b4567
```

- Response Example

```
[
    "限时送积分",
    "限时抽奖"
]
```

# Get all campaigns

- Request Method:
Get

- Request Endpoint:
http://{server-domain}/api/product/campaign/all

- Request Parameters:

| Name | Type | Required | Example | Description |
| ---- | ---- | -------- | ------- | ----------- |
| accesstoken | String | Yes | 2b0f27f3-60e8-3763-c187-bd3dd6167e54 | 用户token（放在querystring中） |

- Request Example

```
http://dev.quncrm.com/api/product/campaign/all?accesstoken=2b0f27f3-60e8-3763-c187-bd3dd6167e54
```

- Response Example

```
[
    {
        "id": "55360e26d6f97f07048b4570",
        "name": "限时抽奖1",
        "startTime": "2015-04-19 16:00:00",
        "endTime": "2015-05-19 16:00:00",
        "participantCount": 10,
        "limitTimes": 1,
        "promotion": {
            "type": "promotion_code",
            "gift": {
                "type": "lottery",
                "config": {
                    "method": "scale",
                    "prize": [
                        {
                            "name": "大白兔",
                            "number": 20
                        }
                    ]
                }
            },
            "campaigns": "first",
            "tags": [],
            "channels": [],
            "data": []
        },
        "promotionCodeCount": 0,
        "isActivated": false,
        "isExpired": false
    },
    {
        "id": "55360eded6f97f07048b4571",
        "name": "限时抽奖",
        "startTime": "2015-04-19 16:00:00",
        "endTime": "2015-05-19 16:00:00",
        "participantCount": 10,
        "limitTimes": 1,
        "promotion": {
            "type": "promotion_code",
            "data": [
                {
                    "productId": "5530c501d6f97f6e038b4569",
                    "productName": "test"
                }
            ],
            "gift": {
                "type": "score",
                "config": {
                    "method": "times",
                    "number": 2
                }
            },
            "campaigns": "first",
            "tags": [],
            "channels": []
        },
        "promotionCodeCount": 0,
        "isActivated": true,
        "isExpired": false
    }
]
```

# Update campaign by id

- Request Method:
PUT

- Request Endpoint:
http://{server-domain}/api/product/campaign/{campaignId}

- Request Parameters:

| Name | Type | Required | Example | Description |
| ---- | ---- | -------- | ------- | ----------- |
| accesstoken | String | Yes | 2b0f27f3-60e8-3763-c187-bd3dd6167e54 | 用户token（放在querystring中） |
| name | string | Yes | 2 | 活动名称 |
| startTime | string | Yes | 1429000112193 毫秒时间戳（下同）| 活动开始时间 |
| endTime | string | Yes | 1429000116193 毫秒时间戳（下同） | 活动结束时间 |
| participantCount | int | No | 100  | 参与人数 默认为不限(null) |
| limitTimes | int | No | 2  | 参与次数限制 默认为不限(null) |
| productIds | Array | 关联商品的Id | [551368a2137473e9438b4578, 551368a2137473e9438b4579]  | No 默认为[] |
| campaigns | string或array | No | [55135f6c137473e2438b4570,55135f6c137473e2438b4570] | 'unlimited', 'first' 或者 [campaignId] |
| tags | array | No | ["liu","chen"] | tags |
| channels | array | No | ["55135f6c137473e2438b4570","55135f6c137473e2438b4570"] | channelId |
| gift | Array | Yes | [] | 赠品配置如下 |
| gift.type | string | Yes | score | 赠品类型, score或者lottery |
| gift.config | Array | Yes | 如下 | 赠品规则 |
| gift.config.method | string | No | "scale" | gift.type为score时为scale或者number gift.type为lottery时为times或score |
| gift.config.number | int | No | 100 | gift.type为score时必填 倍数或积分 |
| gift.config.prize | array | No | [] | 如下 |
| gift.config.prize.name | string | No | "泰迪熊一只" | gift.type为lottery时必填 奖品名 |
| gift.config.prize.number | int | No | 20 | gift.type为lottery时必填 奖品数目或中奖人数比例 |
| isActivated | boolean | Yes | true  | 是否启用 |

- Request Example

```
http://dev.quncrm.com/api/product/campaign/55135f6c137473e2438b4570

{
    "isActivated": false
}
```

- Response Example

```
{
    "id": "55360e26d6f97f07048b4570",
    "name": "限时抽奖1",
    "startTime": "2015-04-19 16:00:00",
    "endTime": "2015-05-19 16:00:00",
    "participantCount": 10,
    "limitTimes": 1,
    "promotion": {
        "type": "promotion_code",
        "gift": {
            "type": "lottery",
            "config": {
                "method": "scale",
                "prize": [
                    {
                        "name": "大白兔",
                        "number": 20
                    }
                ]
            }
        },
        "campaigns": "first",
        "tags": [],
        "channels": [],
        "data": []
    },
    "promotionCodeCount": 0,
    "isActivated": false,
    "isExpired": false
}
```
# get the info of product from campaign

- Request Method:
GET

- Request Endpoint:
http://{server-domain}/api/product/campaign/product-info

- Request Parameters:

| Name | Type | Required | Example | Description |
| ---- | ---- | -------- | ------- | ----------- |
| accesstoken | string | Yes | 2b0f27f3-60e8-3763-c187-bd3dd6167e54 | 用户token（放在querystring中） |
| pageSize | int | No | 10 | if yuo do not pass this param,and you can get the all products.if you pass this param,you must pass a param that is page|
| page | int | No | 10 | page number |


- Response Example


```
[
    {
        "id": "556d4446475df4fd7e8b4567",
        "sku": "1433224193579818",
        "name": "托尔斯泰",
        "pictures": [
            {
                "name": "webwxgeticon",
                "url": "http://vincenthou.qiniudn.com/d3e3534cccf39954127a769b.jpg",
                "size": "0.02"
            }
        ],
        "category": {
            "id": "55508421475df4fe518b4567",
            "name": "1",
            "properties": []
        },
        "intro": "<p>11</p>",
        "isAssigned": true,
        "codeNum": 5,
        "isSelected": false
    },
    {
        "id": "556d4446475df4fd7e8b4567",
        "sku": "1433224193579818",
        "name": "托尔斯泰",
        "pictures": [
            {
                "name": "webwxgeticon",
                "url": "http://vincenthou.qiniudn.com/d3e3534cccf39954127a769b.jpg",
                "size": "0.02"
            }
        ],
        "category": {
            "id": "55508421475df4fe518b4567",
            "name": "1",
            "properties": []
        },
        "intro": "<p>11</p>",
        "isAssigned": true,
        "codeNum": 5,
        "isSelected": false
    }
]
```

# qrcode indicator

- Request Method:
GET

- Request Endpoint:
http://{server-domain}/api/management/channel/qrcode/key-indicator

- Request Parameters:

| Name | Type | Required | Example | Description |
| ---- | ---- | -------- | ------- | ----------- |
| time | string | Yes | 1441691255162 | current time(millisecond) |
| channelId | string | Yes | "54fd0571e4b055a0030461fb" | channel id |
| qrcodeId | string | Yes |"55ecf0a1e4b023058bf92bde" | qrcode id |


- Request Example

```
http://wm.com/api/api/channel/qrcode/key-indicator?tmoffset=-8&time=1441691255162&channelId=54fd0571e4b055a0030461fb&qrcodeId=55ecf0a1e4b023058bf92bde

```

- Response Example

```
{
    "id": "55ee334032ecb827c6960e82",
    "qrcodeId": "55ecf0a1e4b023058bf92bde",
    "accountId": "54fd0571e4b055a0030461fb",
    "refDate": 1441555200000,
    "scan": 1,
    "subscribe": 1,
    "unsubscribe": 0,
    "register": 0,
    "totalScan": 1,
    "totalSubscribe": 1,
    "totalUnsubscribe": 1,
    "totalRegister": 0,
    "createTime": 1441674048202
}
```

# Trend of Scans & Followers

- Request Method:
GET

- Request Endpoint:
http://{server-domain}/api/management/channel/qrcode/time-series

- Request Parameters:

| Name | Type | Required | Example | Description |
| ---- | ---- | -------- | ------- | ----------- |
| channelId | string | Yes | "54fd0571e4b055a0030461fb" | channel id |
| qrcodeId | string | Yes |"55ecf0a1e4b023058bf92bde" | qrcode id |
| endDate | string | Yes | 1441555200000 | end time |
| startDate | string | Yes | 1441036800000 | start time |

- Request Example

```
http://wm.com/api/api/channel/qrcode/time-series?tmoffset=-8&time=1441691255183&channelId=54fd0571e4b055a0030461fb&endDate=1441555200000&qrcodeId=55ecf0a1e4b023058bf92bde&startDate=1441036800000

```

- Response Example

```
{
    "statDate": [
        "2015-09-07"
    ],
    "scan": [
        1
    ],
    "subscribe": [
        1
    ]
}
```

# Export trend of scans & followers

- Request Method:
GET

- Request Endpoint:
http://{server-domain}/api/channel/qrcode/export-qrcode-info

- Request Parameters:

| Name | Type | Required | Example | Description |
| ---- | ---- | -------- | ------- | ----------- |
| channelId | string | Yes | "54fd0571e4b055a0030461fb" | channel id |
| qrcodeId | string | Yes |"55ecf0a1e4b023058bf92bde" | qrcode id |
| endDate | string | Yes | 1441555200000 | end time |
| startDate | string | Yes | 1441036800000 | start time |

- Request Example

```
http://wm.com/api/channel/qrcode/export-qrcode-info?channelId=54fd0571e4b055a0030461fb&endDate=1441555200000&qrcodeId=55ecf0a1e4b023058bf92bde&startDate=1441036800000

```

- Response Example

```
{
    "result": "success",
    "message": "exporting file",
    "data": {
        "jobId": "44d89c05999e6f59d51980ac3c68f0b5",
        "key": "婴儿毛线帽_20150908_1441678595244369"
    }
}
```

# Init alipay

- Request Method:
POST

- Request Endpoint:
http://{server-domain}/api/management/channel/init-alipay

- Request Parameters:

| Name | Type | Required | Example | Description |
| ---- | ---- | -------- | ------- | ----------- |
| accesstoken | string | No | 2b0f27f3-60e8-3763-c187-bd3dd6167e54 | 用户token（放在querystring中） type为'updateCompanyInfo'和'exchange'时必须 |
| name | string | Yes | "爱骑行" | 帐号名 |
| appId | string | Yes | "2014102700014769" | 帐号appid |
| headImageUrl | string | Yes |"http://wx.qlogo.cn/mmopen/GPyw0pGicibl5Eda4GmSSbTguhjg9LZjumHmVjybjiaQXnE9XrXEts6ny9Uv4Fk6hOScWRDibq1fI0WOkSaAjaecNTict3n6EjJaC/0" | 头像 |
| Description | string | No | "爱骑行官方支付宝" | 描述 |
| id | string | No | 554972b8e4b050b441eb7625 | 账号id (编辑的时候必填) |

- Request Example

```
http://wm.com/api/management/channel/init-alipay?accesstoken=41bb1337-198d-2779-07fd-af34522b8021

{
    "appId": "2014102700014769",
    "name": "群游汇",
    "headImageUrl": "http://wx.qlogo.cn/mmopen/GPyw0pGicibl5Eda4GmSSbTguhjg9LZjumHmVjybjiaQXnE9XrXEts6ny9Uv4Fk6hOScWRDibq1fI0WOkSaAjaecNTict3n6EjJaC/0"
}
```

- Response Example

```
{
    "id":"554972b8e4b050b441eb7625",
    "appId":"2014102700014769",
    "name":"徒步去旅行",
    "channelAccount":"2014102700014769",
    "headImageUrl":"http://wx.qlogo.cn/mmopen/GPyw0pGicibl5Eda4GmSSbTguhjg9LZjumHmVjybjiaQXnE9XrXEts6ny9Uv4Fk6hOScWRDibq1fI0WOkSaAjaecNTict3n6EjJaC/0",
    "channel":"ALIPAY",
    "status":"ENABLE",
    "createTime":1430876856005,
    "accessStatus":"NON_CONNECT",// 三种状态(SUCCESS， NON_CONNECT, FAILED)
    "serviceUrl":"http://dev.wx.quncrm.com/alipay/2014102700014769",
    "publicKey":"MIGfMA0GCSqGSIb3DQEBAQUAA4GNADCBiQKBgQDVdgJxIGauzmzMgTSoWpuEeaTTFtubbZng79qhoRGViGX6aSiwWbkdrzAAeCDIyRB6TLLnf/6Lil9iKjgqPY2ljuqZZX2wkNsABMTqDfORfTe4agZ9vuMSq9IXrtqDWM2WK0t0Xr9AQw6l424AFA2hS3nJKf5W32wjL/P6sVO16wIDAQAB"
}
```

# Delete channel

- Request Method:
DELETE

- Request Endpoint:
http://{server-domain}/api/management/channel/{id}

- Request Parameters:

| Name | Type | Required | Example | Description |
| ---- | ---- | -------- | ------- | ----------- |
| accesstoken | string | No | 2b0f27f3-60e8-3763-c187-bd3dd6167e54 | 用户token（放在querystring中） type为'updateCompanyInfo'和'exchange'时必须 |
| type | string | Yes | "weibo" | 渠道类型(weibo/alipay) |
| weiboToken | string | No | "2.00kySP1C0KN_bW8ef46b1f167ZbDrD" | channelType为weibo时必填 |

- Request Example

```
http://wm.com/api/management/channel/55189a7cd6f97f41048b4568?tmoffset=-8&accesstoken=b32952e0-475b-3cc6-2f25-4458351bd2af

{
    "channelType":"weibo",
    "weiboToken":"2.00kySP1C0KN_bW8ef46b1f167ZbDrD"
}
```

- Response Example

```
{
}
```

# Get channels

- Request Method:
GET

- Request Endpoint:
http://{server-domain}/api/common/channels

- Request Parameters:

| Name | Type | Required | Example | Description |
| ---- | ---- | -------- | ------- | ----------- |
| accesstoken | string | Yes | 2b0f27f3-60e8-3763-c187-bd3dd6167e54 | 用户token（放在querystring中） type为'updateCompanyInfo'和'exchange'时必须 |

- Request Example

```
http://wm.com/api/common/channels?tmoffset=-8&accesstoken=b32952e0-475b-3cc6-2f25-4458351bd2af
```

- Response Example

```
{
    "wechat": [
        {
            "id": "54d9c155e4b0abe717853ee1",
            "appId": "wx2df5d7e4ce8a04ca",
            "name": "熊猫Baby",
            "channelAccount": "gh_b7f586690646",
            "headImageUrl": "http://wx.qlogo.cn/mmopen/GPyw0pGicibl5Eda4GmSSbTguhjg9LZjumHmVjybjiaQXnE9XrXEts6ny9Uv4Fk6hOScWRDibq1fI0WOkSaAjaecNTict3n6EjJaC/0",
            "channel": "WEIXIN",
            "status": "ENABLE",
            "createTime": 1423556949585,
            "appSecret": "d348e89e0db5536d5c1dade129e9a5f8",
            "token": "XF3E2FF34DFD4457FASAF34565FDA3562",
            "encodingAESKey": "1QW34RDFB567UI34DGT60OWSMFJKE432WASXLPO0I7R",
            "accountType": "SERVICE_AUTH_ACCOUNT",
            "customerServiceSettings": {
                "status": "ENABLE",
                "accessToken": "b7133549-1486-2e7f-cd4a-a9d7f1d985cc",
                "sessionExpiresIn": 600000
            },
            "serviceUrl": "http://dev.wx.quncrm.com/wechat/wx2df5d7e4ce8a04ca",
            "menuStatus": "UNPUBLISH"
        }
    ],
    "weibo": [
        {
            "id": "54f51cefe4b0c5896e262375",
            "appId": "2131440262",
            "name": "我只是来潜水的",
            "channelAccount": "2131440262",
            "headImageUrl": "http://tp3.sinaimg.cn/2131440262/50/5658132246/1",
            "channel": "WEIBO",
            "status": "ENABLE",
            "createTime": 1425349871347,
            "customerServiceSettings": {
                "status": "DISABLE",
                "accessToken": "72af7a50-ea30-6664-6e52-b3a0a0af7d79",
                "sessionExpiresIn": 600000
            },
            "weiboToken": "2.00kySP1C0aiipk40e3b36ee93_TBRE",
            "weiboTokenExpireTime": 1438282799000,
            "fansServiceToken": "2.00kySP1C0aiipk40e3b36ee93_TBRE",
            "accessStatus": "SUCCESS",
            "serviceUrl": "http://dev.wx.quncrm.com/weibo/2131440262",
            "menuStatus": "UNPUBLISH",
            "weiboAccessStatus": "SUCCESS",
            "appkey": "481734740"
        }
    ],
    "alipay": [
        {
            "id": "554972b8e4b050b441eb7625",
            "appId": "2014102700014769",
            "name": "徒步去旅行",
            "channelAccount": "2014102700014769",
            "headImageUrl": "http://wx.qlogo.cn/mmopen/GPyw0pGicibl5Eda4GmSSbTguhjg9LZjumHmVjybjiaQXnE9XrXEts6ny9Uv4Fk6hOScWRDibq1fI0WOkSaAjaecNTict3n6EjJaC/0",
            "channel": "ALIPAY",
            "status": "ENABLE",
            "createTime": 1430876856005,
            "accessStatus": "SUCCESS",
            "serviceUrl": "http://dev.wx.quncrm.com/alipay/2014102700014769",
            "publicKey": "MIGfMA0GCSqGSIb3DQEBAQUAA4GNADCBiQKBgQDVdgJxIGauzmzMgTSoWpuEeaTTFtubbZng79qhoRGViGX6aSiwWbkdrzAAeCDIyRB6TLLnf/6Lil9iKjgqPY2ljuqZZX2wkNsABMTqDfORfTe4agZ9vuMSq9IXrtqDWM2WK0t0Xr9AQw6l424AFA2hS3nJKf5W32wjL/P6sVO16wIDAQAB",
            "weiboAccessStatus": "SUCCESS"
        }
    ]
}
```

# export trade payments
- Request Method:
GET

- Request Endpoint:
```
http://{server-domain}/api/channel/trade-payment/export
```

- Request Parameters:

| Name | Type | Required | Example | Description |
| ---- | ---- | -------- | ------- | ----------- |
| orderBy | json array | No | {"shallCount":"desc"} or {"paymentTime":"desc"}| If not provided, the result will be ordered by createdAt(desc) as default |
| page | int | No | 1 | the current page |
| per-page | int | No | 10 | show how many records in every page |
| startTime | string | No | 1420041600000| the start of service time (时间戳) |
| endTime | string | No | 1435680000000| the end of service time (时间戳) |
| searchKey | string | No | P012345678 | the key of search |

- Request Example

```
http://wm.com/api/channel/trade-payment/export
```

- Response Example
```
{
    "result": "success",
    "message": "exporting file",
    "data": {
        "jobId": "44d89c05999e6f59d51980ac3c68f0b5",
        "key": "婴儿毛线帽_20150908_1441678595244369"
    }
}
```
# export trade refund
- Request Method:
GET

- Request Endpoint:

```
http://{server-domain}/api/channel/trade-refund/export
```

- Request Parameters:

| Name | Type | Required | Example | Description |
| ---- | ---- | -------- | ------- | ----------- |
| orderBy | json array | No | {"realCount":"desc"} or {"refundAt":"desc"}| If not provided, the result will be ordered by createdAt(desc) as default |
| page | int | No | 1 | the current page |
| per-page | int | No | 10 | show how many records in every page |
| startTime | string | No | 1420041600000| the start of service time (时间戳) |
| endTime | string | No | 1435680000000| the end of service time (时间戳) |
| searchKey | string | No | P012345678 | the key of search |

- Request Example

```
http://wm.com/api/channel/trade-refund/export
```

- Response Example

```
{
    "result": "success",
    "message": "exporting file",
    "data": {
        "jobId": "44d89c05999e6f59d51980ac3c68f0b5",
        "key": "婴儿毛线帽_20150908_1441678595244369"
    }
}
```

# get interact message

- Request Method:
GET

- Request Endpoint:
http://{server-domain}/api/channel/message/interact-message?channelId={channelId}&userId={userId}

- Request Parameters:

| Name | Type | Required | Example | Description |
| ---- | ---- | -------- | ------- | ----------- |
| accesstoken | string | Yes | 2b0f27f3-60e8-3763-c187-bd3dd6167e54 |用户token（放在querystring中） |
| channelId | string | Yes | 54d9c155e4b0abe717853ee1 |
| per-page | int | Yes | 20 |
| next | string | No | 1434611703383 | createTime


- Request Example

```
http://wm.com/api/channel/message/interact-message?tmoffset=-8&accesstoken=8f04b6c9-3625-7deb-8baa-c26f4f3b2e85&channelId=54d9c155e4b0abe717853ee1&userId=55b88461e4b053393cc59f1d&per-page=10&next=
```
- Response Example

```
{
    "code": 200,
    "message": "OK",
    "data": {
        "pageSize": 100,
        "pageNum": 1,
        "totalAmount": 192,
        "next": "1434611703383",
        "results": [
            {
                "id": "55b875c8e4b0a28cbdb7e7d6",
                "accountId": "54fd0571e4b055a0030461fb",
                "userId": "5551bfc7ddb753946833fa77",
                "msgType": "TEXT",
                "direction": "SEND",
                "keycode": "RESUBSCRIBE",
                "matchedRuleId": "555d87b0e4b05a574aac5f1a",
                "createTime": 1438152136450,
                "message": {
                    "fromUser": "gh_ea167ba0879b",
                    "toUser": "ojmADuPByNRg7YuUkJwdD75RJ7P0",
                    "msgType": "TEXT",
                    "content": "乌拉拉，乌拉拉，乌拉拉，欢迎您回家···",
                    "createTime": 1438152136450
                }
            },
            {
                "id": "55b6eff9e4b0a28cbdb7e4c9",
                "accountId": "54fd0571e4b055a0030461fb",
                "userId": "5551bfc7ddb753946833fa77",
                "msgType": "TEXT",
                "direction": "RECEIVE",
                "createTime": 1438052345000,
                "message": {
                    "fromUser": "ojmADuPByNRg7YuUkJwdD75RJ7P0",
                    "toUser": "gh_ea167ba0879b",
                    "msgType": "TEXT",
                    "content": "情怀",
                    "createTime": 1438052345000,
                    "messageId": "6176387791930943073"
                },
                "sender": {
                    "id": "5551bfc7ddb753946833fa77",
                    "accountId": "54fd0571e4b055a0030461fb",
                    "subscribed": true,
                    "originId": "ojmADuPByNRg7YuUkJwdD75RJ7P0",
                    "nickname": "八宝周",
                    "gender": "FEMALE",
                    "language": "en",
                    "city": "云阳",
                    "province": "重庆",
                    "country": "中国",
                    "headerImgUrl": "http://wx.qlogo.cn/mmopen/XzhF92tBcey5paCtNenjwuYSQ49Yua8RRk40QA6GdFnRrEvBMc2ZaWH5v44NjbUfTsYKTM808yxY4wllfaEr0w/0",
                    "subscribeTime": 1438152135000,
                    "unionId": "o8nGSjuIOcI-Gg3MzlK5fWRemqIc",
                    "massSendUsageCount": 39,
                    "tags": [
                        "银卡会员"
                    ],
                    "subscribeSource": "qrscene_131",
                    "firstSubscribeSource": "other",
                    "firstSubscribeTime": 1430363800000,
                    "interactMessageCount": 95,
                    "lastInteractMessageTime": 1438052345924,
                    "lastInteractEventTime": 1438152136400,
                    "userCounts": null,
                    "createTime": 1431420871548,
                    "unsubscribeTime": null,
                    "member": false
                }
            },
            {
                "id": "55b1e3b6e4b0fcecc53d4029",
                "accountId": "54fd0571e4b055a0030461fb",
                "userId": "5551bfc7ddb753946833fa77",
                "msgType": "TEXT",
                "direction": "SEND",
                "matchedRuleId": "55b1e3aae4b0fcecc53d4027",
                "createTime": 1437721526520,
                "message": {
                    "fromUser": "gh_ea167ba0879b",
                    "toUser": "ojmADuPByNRg7YuUkJwdD75RJ7P0",
                    "msgType": "TEXT",
                    "content": "白阿比",
                    "createTime": 1437721526520
                }
            },
            {
                "id": "55b1e3b6e4b0fcecc53d4028",
                "accountId": "54fd0571e4b055a0030461fb",
                "userId": "5551bfc7ddb753946833fa77",
                "msgType": "TEXT",
                "direction": "RECEIVE",
                "keycode": "小白猫",
                "matchedRuleId": "55b1e3aae4b0fcecc53d4027",
                "createTime": 1437721525000,
                "message": {
                    "fromUser": "ojmADuPByNRg7YuUkJwdD75RJ7P0",
                    "toUser": "gh_ea167ba0879b",
                    "msgType": "TEXT",
                    "content": "小白猫",
                    "createTime": 1437721525000,
                    "messageId": "6174966930850001809"
                },
                "sender": {
                    "id": "5551bfc7ddb753946833fa77",
                    "accountId": "54fd0571e4b055a0030461fb",
                    "subscribed": true,
                    "originId": "ojmADuPByNRg7YuUkJwdD75RJ7P0",
                    "nickname": "八宝周",
                    "gender": "FEMALE",
                    "language": "en",
                    "city": "云阳",
                    "province": "重庆",
                    "country": "中国",
                    "headerImgUrl": "http://wx.qlogo.cn/mmopen/XzhF92tBcey5paCtNenjwuYSQ49Yua8RRk40QA6GdFnRrEvBMc2ZaWH5v44NjbUfTsYKTM808yxY4wllfaEr0w/0",
                    "subscribeTime": 1438152135000,
                    "unionId": "o8nGSjuIOcI-Gg3MzlK5fWRemqIc",
                    "massSendUsageCount": 39,
                    "tags": [
                        "银卡会员"
                    ],
                    "subscribeSource": "qrscene_131",
                    "firstSubscribeSource": "other",
                    "firstSubscribeTime": 1430363800000,
                    "interactMessageCount": 95,
                    "lastInteractMessageTime": 1438052345924,
                    "lastInteractEventTime": 1438152136400,
                    "userCounts": null,
                    "createTime": 1431420871548,
                    "unsubscribeTime": null,
                    "member": false
                }
            }
        }
    ]
}
```

# Get job status

- Request Method:
GET

- Request Endpoint:
http://{server-domain}/api/common/job/status

- Request Parameters:

| Name | Type | Required | Example | Description |
| ---- | ---- | -------- | ------- | ----------- |
| accesstoken | string | No | 2b0f27f3-60e8-3763-c187-bd3dd6167e54 | 用户token（放在querystring中） type为'updateCompanyInfo'和'exchange'时必须 |
| data | array | Yes | {['jobId':xx,'key':xx],['jobId':xx1,'key':xx1]} |  |


- Request Example

```
http://wm.com/api/common/job/status?accesstoken=41bb1337-198d-2779-07fd-af34522b8021&data={['jobId':xx,'key':xx],['jobId':xx1,'key':xx1]}
```

- Response Example

```
{
    "message": "OK",
    "data" : [
        ["jobId":xx, "url":"xx", "status": "xx"]
        ["jobId":xx, "url":"xx", "status": "xx"]
        ]
}
```

# get channel info by channelId

- Request Method:
GET

- Request Endpoint:
http://{server-domain}/api/common/channel/get-channel-info

- Request Parameters:

| Name | Type | Required | Example | Description |
| ---- | ---- | -------- | ------- | ----------- |
| accesstoken | string | yes | 2b0f27f3-60e8-3763-c187-bd3dd6167e54 | |
| channelId | string | yes | 234567767 |  |

- Request Example

```
http://wm.com/api/common/channel/get-channel-info?tmoffset=-8&accesstoken=92717035-cc5e-b5ea-b698-495fcb3697db
```

- Response Example

```
[
    {
        "id": "54d9c155e4b0abe717853ee1",
        "appId": "wx2df5d7e4ce8a04ca",
        "name": "熊猫Baby",
        "channelAccount": "gh_b7f586690646",
        "headImageUrl": "http://wx.qlogo.cn/mmopen/GPyw0pGicibl5Eda4GmSSbTguhjg9LZjumHmVjybjiaQXnE9XrXEts6ny9Uv4Fk6hOScWRDibq1fI0WOkSaAjaecNTict3n6EjJaC/0",
        "channel": "WEIXIN",
        "status": "ENABLE",
        "createTime": 1423556949585,
        "appSecret": "d348e89e0db5536d5c1dade129e9a5f8",
        "token": "XF3E2FF34DFD4457FASAF34565FDA3562",
        "encodingAESKey": "1QW34RDFB567UI34DGT60OWSMFJKE432WASXLPO0I7R",
        "accountType": "SERVICE_AUTH_ACCOUNT",
        "customerServiceSettings": {
            "status": "ENABLE",
            "accessToken": "94df164d-ce4d-e204-5d6b-4549111bcb11",
            "sessionExpiresIn": 600000
        },
        "serviceUrl": "http://dev.wx.quncrm.com/wechat/wx2df5d7e4ce8a04ca",
        "menuStatus": "UNPUBLISH"
    }
]
```

# Get tag stats list

- Request Method:
GET

- Request Endpoint:
http://{server-domain}/api/common/tags

- Request Parameters:

| Name | Type | Required | Example | Description |
| ---- | ---- | -------- | ------- | ----------- |
| accesstoken | string | No | 2b0f27f3-60e8-3763-c187-bd3dd6167e54 | 用户token（放在querystring中） type为'updateCompanyInfo'和'exchange'时必须 |
| per-page | int | no | 20 | Page size. Default value is 20 |
| page | int | no | 1 | Page Number. Default value is 1 |

- Request Example

```
http://wm.com/api/common/tags?tmoffset=-8&accesstoken=679a9eab-5032-1a19-8812-946b033db829
```

- Response Example

```
{
    "items": [
        {
            "name": "银牌会员",
            "followerCount": 5,
            "memberCount": 3
        }
    ],
    "_meta": {
        "totalCount": 1,
        "pageCount": 1,
        "currentPage": 1,
        "perPage": 20
    }
}
```

# Remove tag by name

- Request Method:
PUT

- Request Endpoint:
http://{server-domain}/api/common/tag/remove

- Request Parameters:

| Name | Type | Required | Example | Description |
| ---- | ---- | -------- | ------- | ----------- |
| accesstoken | string | Yes | 2b0f27f3-60e8-3763-c187-bd3dd6167e54 | 用户token（放在querystring中） type为'updateCompanyInfo'和'exchange'时必须 |
| name | string | Yes | "银牌会员" | 标签名 |

- Request Example

```
http://wm.com/api/common/tag/remove?tmoffset=-8&accesstoken=679a9eab-5032-1a19-8812-946b033db829

{
    "name": "银牌会员"
}
```

- Response Example

```
{
    "message": "OK",
    "date": null
}
```

# Rename tag by name

- Request Method:
PUT

- Request Endpoint:
http://{server-domain}/api/common/tag/rename

- Request Parameters:

| Name | Type | Required | Example | Description |
| ---- | ---- | -------- | ------- | ----------- |
| accesstoken | string | Yes | 2b0f27f3-60e8-3763-c187-bd3dd6167e54 | 用户token（放在querystring中） type为'updateCompanyInfo'和'exchange'时必须 |
| name | string | Yes | "银牌会员" | 标签名 |
| newName | string | Yes | "金牌会员" | 新标签名 |

- Request Example

```
http://wm.com/api/common/tag/rename?tmoffset=-8&accesstoken=679a9eab-5032-1a19-8812-946b033db829

{
    "name": "银牌会员",
    "newName": "金牌会员"
}
```

- Response Example

```
{
    "message": "OK",
    "date": null
}
```

# Generate qiniu token

- Endpoint

    **GET** /api/qiniu-token/generate

- Parameters

    | name | type | description | required |
    |------|-----|-------|---------|
    | accesstoken | String | 用户token（放在querystring中） | Yes |

- Response body

    ```json
    {
        token: "QK5YJSJHDKQmlXQq5W4RQqNDTOr5RPCPiVbTqoW-:pjO6D0imUQ-B7JOtcg9ldIfE6IY=:eyJzY29wZSI6InZpbmNlbnRob3UiLCJkZWFkbGluZSI6MTQxNzc0OTUyNn0=",
        bucket: "vincenthou",
        domain: "http://vincenthou.qiniudn.com",
        uploadDomain: "http://upload.qiniu.com"
    }
    ```

# Query questionnaire.

- Request Method:
GET

- Request Endpoint:
http://{server-domain}/api/content/questionnaires?orderBy={"createdAt":"desc"}&page=1&per-page=10&fields=id,name,startTime,endTime,creator,createdAt,isPublished&where={"isAutoUpgrade":true}

- Request Parameters:

| Name | Type | Required | Example | Description |
| ---- | ---- | -------- | ------- | ----------- |
| accesstoken | string | Yes | 2b0f27f3-60e8-3763-c187-bd3dd6167e54 |用户token（放在querystring中） |
| orderBy | json array | No | {"createdAt":"desc"} or {"startTime":"desc"} or {"endTime":"desc"} | If not provided, the result will be ordered by createdAt(desc) as default |
| page | int | No | 1 | the current page |
| per-page | int | No | 10 | show how many records in every page |
| where | string | No | {"isPublished":true}| the condition of search |

- Request Example

```
http://wm.com/api/content/questionnaires?orderBy={"createdAt":"desc"}&page=1&per-page=10&fields=id,name,startTime,endTime,creator,createdAt,isPublished
```
- Response Example

```
{
    "items": [
        {
            "id": "55d6cb8be9c2fb022c8b4579",
            "name": "name",
            "startTime": "2015-04-14 16:28:32",
            "endTime": "2015-04-14 16:28:36",
            "creator": {
                "id": "54c58ea2e9c2fb51048b4569",
                "name": "name"
            },
            "isPublished": false,
            "createdAt": "2015-08-21 14:56:11"
        }
    ],
    "_links": {
        "self": {
            "href": "http://wm.com/api/content/questionnaires?fields=id%2Cname%2CstartTime%2CendTime%2Ccreator%2CcreatedAt%2CisPublished&page=1"
        }
    },
    "_meta": {
        "totalCount": 1,
        "pageCount": 1,
        "currentPage": 1,
        "perPage": 20
    }
}
```

# Create new questionnaires

- Request Method:
POST

- Request Endpoint:
http://{server-domain}/api/content/questionnaires

- Request Parameters:

| Name | Type | Required | Example | Description |
| ---- | ---- | -------- | ------- | ----------- |
| name | string | Yes | "questionnaire name" | the name of questionnaire |
| startTime | string | Yes    | "1429000112193 毫秒时间戳"   | the start time of questionnaire |
| endTime | string | Yes | "1429000112193 毫秒时间戳" | the end time of questionnaire |
| description | string | No | "<p>good</p>" | description for questionnaire |
| question | Array | No | [{"title": "math","type": "radio","order": 0,"options": [{"icon": "support","content": "A option" },{"icon": "support","content": "B option"}]},{"type": "input","order": 1,"title": "This is a problem"}] | the question of questionnaires |
| isPublished | boolean | false | the status of published |

- Request Example

```
{
    "name": "name",
    "startTime": "1429000112193",
    "endTime": "1429000116193",
    "description": "good",
    "question": [
        {
            "title": "math",
            "type": "radio",
            "order": 0,
            "options": [
                {
                    "icon": "support",
                    "content": "A option"
                },
                {
                    "icon": "support",
                    "content": "B option"
                }
            ]
        },
        {
            "type": "input",
            "order": 1,
            "title": "This is a problem"
        }
    ],
    "isPublished": false
}
```

- Response Example

```
{
    "message": "OK",
    "data": ""
}
```

# Get questionnaire's questions

- Request Method:
GET

- Request Endpoint:
http://{server-domain}/api/content/questionnaire/question-names

- Request Parameters:

| Name | Type | Required | Example | Description |
| ---- | ---- | -------- | ------- | ----------- |
| questionnaireId | string | Yes | "55d6cb8be9c2fb022c8b4579" | 问卷Id |

- Request Example

```
http://wm.com/api/content/questionnaire/question-names?questionnaireId=55d6cb8be9c2fb022c8b4579
```
- Response Example

```
[
    {
        "id": "55d6cb8be9c2fb022c8b457b",
        "title":"Do you like dog"
    },
    {
        "id": "55d6cb8be9c2fb022c8c457b",
        "title":"Do you like cat"
    },
    {
        "id": "55d6cb8be9c2ab022c8b457b",
        "title":"Do you like bird"
    }
]
```


# Delete questionnaire

- Request Method
DELETE

- Request Endpoint
http://{server-domain}/api/content/questionnaire/{product_id_list}

- Request Example
```
http://{server-domain}/api/content/questionnaire/54bcb51cdb4c0e7c3d8b4569,54bcb51cdb4c0e7c3d8b4569,54bcb51cdb4c0e7c3d8b4569
```

# View questionnaire by id for phone.

- Request Method:
GET

- Request Endpoint:
http://{server-domain}/api/questionnaire/{phoneId}

- Request Parameters:

| Name | Type | Required | Example | Description |
| ---- | ---- | -------- | ------- | ----------- |
| id | string | Yes | 111,333 | ObjectId |
| channelId | string | No | 54d9c155e4b0abe717853ee1 | ObjectId |
| openId | string | No | 54d9c155e4b0abe717853ee1 | ObjectId |

- Request Example

```
http://wm.com/api/questionnaire/55d6cb8be9c2fb022c8b4579?channelId=54d9c155e4b0abe717853ee1&openId=54d9c155e4b0abe717853ee1tmoffset=-8&time=1440137373042
```

- Response Example

```
{
    "id": "55d6cb8be9c2fb022c8b4579",
    "name": "name",
    "startTime": "2015-04-14 16:28:32",
    "endTime": "2015-04-14 16:28:36",
    "creator": {
        "id": "54c58ea2e9c2fb51048b4569",
        "name": "name"
    },
    "description": "good",
    "questions": [
        {
            "id": "55d6cb8be9c2fb022c8b4576",
            "title": "math",
            "type": "radio",
            "order": 0,
            "options": [
                {
                    "icon": "support",
                    "content": "A option"
                },
                {
                    "icon": "support",
                    "content": "B option"
                }
            ],
            "createdAt": "2015-08-21 14:56:11"
        },
        {
            "id": "55d6cb8be9c2fb022c8b4577",
            "title": "This is a problem",
            "order": 1,
            "type": "input",
            "options": [],
            "createdAt": "2015-08-21 14:56:11"
        }
    ],
    "isPublished": false,
    "createdAt": "2015-08-21 14:56:11",
    "answerTime": "2015-08-26 10:28:55",
    "isAnswered": false
}
```

# View questionnaire by id.

- Request Method:
GET

- Request Endpoint:
http://{server-domain}/api/content/questionnaire/{questionnaireId}

- Request Parameters:

| Name | Type | Required | Example | Description |
| ---- | ---- | -------- | ------- | ----------- |
| id | string | Yes | 111,333 | ObjectId |

- Request Example

```
http://wm.com/api/content/questionnaire/55d6cb8be9c2fb022c8b4579?tmoffset=-8&time=1440137373042
```

- Response Example

```
{
    "id": "55dd2467e9c2fb5b1c8b4569",
    "name": "test",
    "startTime": "2015-08-26 10:27:20",
    "endTime": "2015-08-27 10:27:22",
    "creator": {
        "id": "54dbfef7e9c2fb92108b456b",
        "name": "LydiaLi91"
    },
    "description": "<p>wwer</p>",
    "questions": [
        {
            "id": "55dd2466e9c2fb5b1c8b4568",
            "title": "11111",
            "type": "checkbox",
            "options": [
                {
                    "icon": "support",
                    "content": "111111"
                },
                {
                    "icon": "support",
                    "content": "222222"
                },
                {
                    "icon": "support",
                    "content": "333333"
                }
            ],
            "createdAt": "2015-08-26 10:28:54",
            "order": 0
        }
    ],
    "isPublished": false,
    "createdAt": "2015-08-26 10:28:55",
}
```

# Update new question

- Request Method:
PUT

- Request Endpoint:

```
http://{server-domain}/api/content/questionnaire/{questionnaireId}
```

- Request Parameters:

| Name | Type | Required | Example | Description |
| ---- | ---- | -------- | ------- | ----------- |
| name | string | Yes | "questionnaire name" | the name of questionnaire |
| startTime | string | Yes    | "1429000112193 毫秒时间戳"   | the start time of questionnaire |
| endTime | string | Yes | "1429000112193 毫秒时间戳" | the end time of questionnaire |
| description | string | No | "<p>good</p>" | description for questionnaire |
| question | Array | No | [{"id": "55d6cb8be9c2fb022c8b4577","title": "math","type": "radio","order": 0,"options": [{"icon": "support","content": "A option"},{"icon": "support","content": "B option"}]},{"id": "55d6cb8be9c2fb022c8b4577","type": "input","title": "This is a problem","order": 1}] | the question of questionnaires |
| isPublished | boolean | Yes | the status of published |

- Request Example

```
{
    "userCount": 7,
    "name": "name",
    "startTime": "1429000112193",
    "endTime": "1429000116193",
    "description": "good",
    "question": [
        {
            "id": "55d6cb8be9c2fb022c8b4577",
            "title": "math",
            "type": "radio",
            "order": 0,
            "options": [
                {
                    "icon": "support",
                    "content": "A option",
                    "count": 6
                },
                {
                    "icon": "support",
                    "content": "B option",
                    "count": 1
                }
            ]
        },
        {
            "id": "55d6cb8be9c2fb022c8b4577",
            "type": "input",
            "title": "This is a problem",
            "order": 1
        }
    ],
    "isPublished": false
}
```

- Response Example

```
{
    "message": "OK",
    "data": ""
}
```


# Stats questionnaire

- Request Method
GET

- Request Endpoint
http://{server-domain}/api/content/stats-questionnaire/{questionnaireId}

- Request Parameters:

| Name | Type | Required | Example | Description |
| ---- | ---- | -------- | ------- | ----------- |
| startTime | array | Yes | 1440412374740 | 开始时间（毫秒时间戳） |
| endTime | array | Yes | 1440412374940 | 结束时间（毫秒时间戳） |

- Request Example

```
http://wm.com/api/content/stats-questionnaire/55c346a0d6f97fa92f8b4567?tmoffset=-8
```

- Response Example

```
{
    "date": [
        "2015-08-29",
        "2015-08-30",
        "2015-08-31",
        "2015-09-01"
    ],
    "count": [
        2,
        0,
        0,
        0
    ]
}
```


# Answer questionnaire

- Request Method
POST

- Request Endpoint
http://{server-domain}/api/questionnaire/answer

- Request Parameters:

| Name | Type | Required | Example | Description |
| ---- | ---- | -------- | ------- | ----------- |
| questionnaireId | string | Yes | ["54d9c165e450abe717853671"] | 问卷Id |
| user | array | No | {"channelId": "...","openId": "...","origin": "..."} | 回答人的信息 |
| answers | array | Yes | [{"questionId": "...","type": "...","value": "..."}] | 答卷 |

- Request Example

```
http://wm.com/api/content/questionnaire-log?tmoffset=-8

{
    "questionnaireId": "54d9c165e450abe717853671",
    "user": {
        "channelId": "54d9c155e4b0abe717853ee1",
        "openId": "oC9Aes9vuisNRmC4ZNdIXY1lb_rk",
        "origin": "wechat"
    },
    "answers": [
        {
            "questionId": "54d9c155e4b0abe717853671",
            "type": "radio",
            "value": "Yes"
        }
    ]
}
```

- Response Example

```
```


# Answers stats

- Request Method
POST

- Request Endpoint
http://{server-domain}/api/content/stats-questionnaire/answers

- Request Parameters:

| Name | Type | Required | Example | Description |
| ---- | ---- | -------- | ------- | ----------- |
| questionId | string | Yes | ["54d9c165e450abe717853671"] | 问题Id |
| startTime | array | No | 1440412374740 | 开始时间（毫秒时间戳） |
| endTime | array | No | 1440412374940 | 结束时间（毫秒时间戳） |

- Request Example

```
http://wm.com/api/content/stats-questionnaire/answers?tmoffset=-8

```

- Response Example

```
{
    "options": [
        "a",
        "b",
        "c"
    ],
    "count": [
        0,
        0,
        0
    ]
}
```

# Get all answers by questionId

- Request Method
POST

- Request Endpoint
http://{server-domain}/api/content/stats-questionnaire/question-answers

- Request Parameters:

| Name | Type | Required | Example | Description |
| ---- | ---- | -------- | ------- | ----------- |
| questionnaireId | string | Yes | ["54d9c165e450abe717854671"] | 问卷Id |
| questionId | string | Yes | ["54d9c165e450abe717853671"] | 问题Id |

- Request Example

```
http://wm.com/api/content/stats-questionnaire/question-answers?tmoffset=-8

```

- Response Example

```
{
    "items": [
        {
            "name":"",
            "value":"很开心"
        },
        {
            "name":"王小丫",
            "value":"不开心"
        }
    ],
    "_meta": {
        "totalCount": 2,
        "pageCount": 1,
        "currentPage": 1,
        "perPage": 20
    }
}
```


# get coupon list

- Request Method
GET

- Request Endpoint
http://{server-domain}/api/product/coupons

- Request Parameters:

| Name | Type | Required | Example | Description |
| ---- | ---- | -------- | ------- | ----------- |
| title | string | No | ["优惠卷名称"] |  |
| orderby | jsonArray | No | {"total":"asc"} |  |
| unexpired | int | No | 4515424513455 | 毫秒 |
| unlimited |  boolean | No | false | when the value is true,the response data is diffrent from response example |

- Request Example

```
http://wm.com/api/product/coupons

```

- Response Example

```
{
    "items": [
        {
            "id": "55f0eee3d6f97ff2708b4567",
            "type": "cash",
            "title": "代金卷",
            "total": 410,
            "limit": 1,
            "time": {
                "type": "relative",
                "beginTime": "today",
                "endTime": 10
            },
            "url": "代金卷",
            "picUrl": "http://vincenthou.qiniudn.com/601513a18f9da8dcde0a52c0.jpg",
            "tip": "操作提示",
            "description": "优惠详情",
            "usageNote": "使用须知",
            "phone": "12458654215",
            "storeType": "specify",
            "stores": [
                {
                    "id": "55f0ff2dd6f97f036f8b4567",
                    "name": "上海测试11",
                    "branchName": "名店测试",
                    "address": "天津市河东区鲁山道街道11号",
                    "phone": "1233455666",
                }
            ],
            "qrcodes": [],
            "discountAmount": 8.5,
            "discountCondition": 100,
            "reductionAmount": 100
        },
        {
            "id": "55f0ee86d6f97f6e708b4569",
            "type": "cash",
            "title": "代金卷",
            "total": 10,
            "limit": 1,
            "time": {
                "type": "relative",
                "beginTime": "day",
                "endTime": 30
            },
            "url": "http://vincenthou.qiniudn.com",
            "picUrl": "http://vincenthou.qiniudn.com/601513a18f9da8dcde0a52c0.jpg",
            "tip": "操作提示",
            "description": "优惠详情",
            "usageNote": "使用须知",
            "phone": "12458654215",
            "storeType": "specify",
            "stores": [
                {
                    "id": "55c454d7d6f97f0b048b4569",
                    "name": "北京门店1",
                    "branchName": "门店测试",
                    "address": "天津市河东区上杭路街道55号",
                    "phone": "1233455666"
                }
            ],
            "qrcodes": [
                {
                    "id": "55f264f8d6f97f19478b4570",
                    "origin": "wechat",
                    "channelName": "熊猫Baby",
                    "channelId": "54d9c155e4b0abe717853ee1",
                    "url": "http://vincenthou.qiniudn.com/55f264f8d6f97f19478b4570.png"
                }
            ],
            "discountAmount": 8.5,
            "discountCondition": 100,
            "reductionAmount": 100
        }
    ],
    "_links": {
        "self": {
            "href": "http://wm.com/api/product/coupons?page=1"
        }
    },
    "_meta": {
        "totalCount": 2,
        "pageCount": 1,
        "currentPage": 1,
        "perPage": 20
    }
}
```

# create coupon

- Request Method
POST

- Request Endpoint
http://{server-domain}/api/product/coupons

- Request Parameters:

| Name | Type | Required | Example | Description |
| ---- | ---- | -------- | ------- | ----------- |
| type | string | Yes | cash |  |
| title | string | Yes | "代金卷" |  |
| total | int | Yes | 100 |  |
| limit | int | Yes | 1 |  |
| tip | string | No | "提示" |  |
| time | array | Yes | {"type":"absolute","beginTime":"2011122222","endTime":"55552221"} |  |
| picUrl | string | Yes | http://vincenthou.qiniudn.com/601513a18f9da8 |  |
| url | string | No | http://vincenthou.qiniudn.com |  |
| description | string | No | 优惠详情 |  |
| usageNote | string | No | 使用须知 |  |
| phone | string | Yes | 13254565213 |  |
| storeType | string | Yes | all | all or specify |
| stores | array | No | [{"id":"0ed6f97f1b048b456a","name":"门店1","branchName":"分店名","address":"上海","phone":"1233455666"}] |  |
| discountAmount | float | No | 5.2 |  |
| discountCondition | int | No | 55 |  |
| reductionAmount | int | No | 100 |  |

```
http://wm.com/api/product/coupons

```

- Response Example

```
{
    "id": "55f0ee86d6f97f6e708b4569",
    "type": "cash",
    "title": "代金卷",
    "total": 10,
    "limit": 1,
    "time": {
        "type": "relative",
        "beginTime": "day",
        "endTime": 30
    },
    "url": "http://vincenthou.qiniudn.com",
    "picUrl": "http://vincenthou.qiniudn.com/601513a18f9da8dcde0a52c0.jpg",
    "tip": "操作提示",
    "description": "优惠详情",
    "usageNote": "使用须知",
    "phone": "12458654215",
    "storeType": "specify",
    "stores": [
        {
            "id": "55c454d7d6f97f0b048b4569",
            "name": "北京门店1",
            "branchName": "门店测试",
            "address": "天津市河东区上杭路街道55号",
            "phone": "1233455666",
        }
    ],
    "qrcodes": [
        {
            "id": "55f264f8d6f97f19478b4570",
            "origin": "wechat",
            "channelName": "熊猫Baby",
            "channelId": "54d9c155e4b0abe717853ee1",
            "url": "http://vincenthou.qiniudn.com/55f264f8d6f97f19478b4570.png"
        }
    ],
    "discountAmount": 8.5,
    "discountCondition": 100,
    "reductionAmount": 100
}
```
# Update the coupon

- Request Method:
PUT

- Request Endpoint:
http://{server-domain}/api/product/coupon/{couponId}

- Request Parameters:

| Name | Type | Required | Example | Description |
| ---- | ---- | -------- | ------- | ----------- |
| total | int | No | 10 |  |
| url | string | No | "http://www.baidu.com" | |
| time | array | No | {"type":"absolute","beginTime":"2011122222","endTime":"55552221"} |  |

- Request Example

```
{
    "total": 10,
    "url": "http://www.baidu.com",
    "time": {
        "type": "absolute",
        "beginTime": "55552221",
        "endTime":"2011122222",
    }
}
```

# View coupon detail

- Request Method
GET

- Request Endpoint
http://{server-domain}/api/product/coupon/{couponId}

- Request Example
```
http://wm.com/api/product/coupon/54bcb51cdb4c0e7c3d8b4569
```

- Response Example
```
   {
    "id": "55f0ee86d6f97f6e708b4569",
    "type": "cash",
    "title": "代金卷",
    "total": 10,
    "limit": 1,
    "time": {
        "type": "relative",
        "beginTime": "day",
        "endTime": 30
    },
    "url": "http://vincenthou.qiniudn.com",
    "picUrl": "http://vincenthou.qiniudn.com/601513a18f9da8dcde0a52c0.jpg",
    "tip": "操作提示",
    "description": "优惠详情",
    "usageNote": "使用须知",
    "phone": "12458654215",
    "storeType": "specify",
    "stores": [
        {
            "id": "55c454d7d6f97f0b048b4569",
            "name": "北京门店1",
            "branchName": "门店测试",
            "address": "天津市河东区上杭路街道55号",
            "phone": "1233455666",
        }
    ],
    "qrcodes": [
        {
            "id": "55f264f8d6f97f19478b4570",
            "origin": "wechat",
            "channelName": "熊猫Baby",
            "channelId": "54d9c155e4b0abe717853ee1",
            "url": "http://vincenthou.qiniudn.com/55f264f8d6f97f19478b4570.png"
        }
    ],
    "discountAmount": 8.5,
    "discountCondition": 100,
    "reductionAmount": 100
}
```
# create coupon qrcode and update coupon qrcode

- Request Method:
POST

- Request Endpoint:
http://{server-domain}/api/product/coupon/qrcode

- Request Parameters:

| Name | Type | Required | Example | Description |
| ---- | ---- | -------- | ------- | ----------- |
| channels | array | Yes | ["54d9c155e4b0abe717853ee1"] |  |
| couponId | string | Yes | 54d9c165e450abe71785467 |  |

- Request Example

```
{
    "channels": [
            "54d9c155e4b0abe717853ee1"
    ],
    "couponId": "55f0eee3d6f97ff2708b4567"
}
```

- Response Example

```
{
    "wechat": [
        {
            "id": "55f2681ed6f97f1e478b456d",
            "origin": "wechat",
            "channelName": "熊猫Baby",
            "fileName": "55f2681ed6f97f1e478b456d.png",
            "channelId": "54d9c155e4b0abe717853ee1",
            "url": "http://vincenthou.qiniudn.com/55f2681ed6f97f1e478b456d.png"
        }
    ]
}
```

# View membershipDiscount.

- Request Method:
GET

- Request Endpoint:
http://{server-domain}/api/product/membership-discount/{membershiDiscountId}

- Request Parameters:

| Name | Type | Required | Example | Description |
| ---- | ---- | -------- | ------- | ----------- |

- Request Example

```
http://wm.com/api/product/membership-discount/55dd2467e9c2fb5b1c8b4569
```

- Response Example

```
{
    "id": "55f7d824e9c2fb810b8b456d",
    "qrcode": {
        "_id": "55f7d824e9c2fb810b8b456c",
        "url": "http://vincenthou.qiniudn.com/55f7d824e9c2fb810b8b456c.png"
    },
    "coupon": {
        "id": "55f7d1e1e9c2fb3f048b4567",
        "title": "coupon",
        "picUrl": "http://vincenthou.qiniudn.com/d056cc4e45a4181bb2a6c19e.jpg",
        "startTime": "2015-09-15 00:00:00",
        "endTime": "2015-09-17 00:00:00",
        "status": "unused"
    },
    "createdAt": "2015-09-15 16:34:44"
}
```


# Query membershipDiscounts.

- Request Method:
GET

- Request Endpoint:
http://{server-domain}/api/product/membership-discounts

- Request Parameters:

| Name | Type | Required | Example | Description |
| ---- | ---- | -------- | ------- | ----------- |
| memberId | string | Yes | 556e94968fd1256189000008 | memberId |
| status | string | No  | "unused"(默认为未使用状态) | the status of coupon |

- Request Example

```
http://wm.com/api/product/membership-discounts?memberId=55a20fd4e9c2fb1b1a8b4567&status=unused
```

- Response Example

```
{
    "items": [
        {
            "id": "55f6a448475df4e34d8b4582",
            "qrcode": {
                "_id": "55f6a447475df4e34d8b4581",
                "url": "http://vincenthou.qiniudn.com/55f6a447475df4e34d8b4581.png"
            },
            "coupon": {
                "id": "55f6a424475df413308b456a",
                "title": "test",
                "picUrl": "http://vincenthou.qiniudn.com/f407045b043d816952482e8a.jpg",
                "startTime": "2015-09-14 18:37:08",
                "endTime": "2015-09-17 18:37:09",
                "status": "unused"
            },
            "createdAt": "2015-09-14 18:41:12"
        },
        {
            "id": "55f6a44a475df413308b456d",
            "qrcode": {
                "_id": "55f6a449475df413308b456c",
                "url": "http://vincenthou.qiniudn.com/55f6a449475df413308b456c.png"
            },
            "coupon": {
                "id": "55f6a424475df413308b456a",
                "title": "test",
                "picUrl": "http://vincenthou.qiniudn.com/f407045b043d816952482e8a.jpg",
                "startTime": "2015-09-14 18:37:08",
                "endTime": "2015-09-17 18:37:09",
                "status": "unused"
            },
            "createdAt": "2015-09-14 18:41:14"
        }
    ],
    "_links": {
        "self": {
            "href": "http://wm.com/api/product/membership-discount/55a74649475df458558b4567?tmoffset=-8&status=unused&page=1&per-page=10"
        },
        "next": {
            "href": "http://wm.com/api/product/membership-discount/55a74649475df458558b4567?tmoffset=-8&status=unused&page=2&per-page=10"
        },
        "last": {
            "href": "http://wm.com/api/product/membership-discount/55a74649475df458558b4567?tmoffset=-8&status=unused&page=4&per-page=10"
        }
    },
    "_meta": {
        "totalCount": 31,
        "pageCount": 4,
        "currentPage": 0,
        "perPage": 10
    }
}

```

# Delete membership discount

- Request Method
DELETE

- Request Endpoint
http://{server-domain}/api/product/membership-discount/{membershipDiscountId}

- Request Example
```
http://wm.com/api/product/membership-discount/54bcb51cdb4c0e7c3d8b4569
```

# Stats coupon

- Request Method
GET

- Request Endpoint
http://{server-domain}/api/product/coupon-log/stats-coupon/{couponId}

- Request Parameters:

| Name | Type | Required | Example | Description |
| ---- | ---- | -------- | ------- | ----------- |
| startTime | array | Yes | 1440412374740 | 开始时间（毫秒时间戳） |
| endTime | array | Yes | 1440412374940 | 结束时间（毫秒时间戳） |

- Request Example

```
http://wm.com/api/product/coupon-log/stats-coupon/55c346a0d6f97fa92f8b4567?tmoffset=-8
```

- Response Example

```
{
    "date": [
        "2015-08-29",
        "2015-08-30",
        "2015-08-31",
        "2015-09-01"
    ],
    "count": {
        "recievedNum": [
            2,
            0,
            0,
            0
        ],
        "redeemedNum": [
            2,
            0,
            0,
            0
        ]
    }
}
```

# Stats the total of receivedNum, redeemedNum and deletedNum for conpon.

- Request Method
GET

- Request Endpoint
http://{server-domain}/api/product/coupon-log/stats-total-coupon/{couponId}

- Request Parameters:

| Name | Type | Required | Example | Description |
| ---- | ---- | -------- | ------- | ----------- |

- Request Example

```
http://wm.com/api/product/coupon-log/stats-total-coupon/55c346a0d6f97fa92f8b4567?tmoffset=-8
```

- Response Example

```
{
    "recievedTotal": "1444",
    "redeemedTotal": "213",
    "deletedTotal": "31"
}
```



# View coupon recieved(领取),redeemed(核销),deleted(删除) records

- Request Method
GET

- Request Endpoint

```
http://{server-domain}/api/product/coupon-logs
```

- Request Parameters:

| Name | Type | Required | Example | Description |
| ---- | ---- | -------- | ------- | ----------- |
| status | string | Yes | received | recieved(领取),redeemed(核销),deleted(删除)|
| startTime | string | No | 1440412374740 | 开始时间（毫秒时间戳） |
| endTime | string | No | 1440412374940 | 结束时间（毫秒时间戳） |
| searchKey | string | No | mike | 会员名称, 手机号, 优惠券名称 |
| per-page | int | no | 20 | Page size. Default value is 20 |
| page | int | no | 1 | Page Number. Default value is 1 |

- Request Example

```
http://wm.com/api/product/coupon-logs?status=recieved&searchKey=mike
```

- Response Example

```
{
    "items": [
        {
            "id": "55efb2e8971374151a8b4567",
            "couponId ": "55efb2e8971374151a8b4567",
            "type": "coupon",
            "title": '优惠卷名称',
            "status": 'recieved',
            "member": {
                "id": "55efb2e8971374151a8b4567",
                "name": "会员名称"
                "phone": "13345267867"
            },
            store {
                "id "  :  "55efb2e8971374151a8b4567",
                "name" :  "门店名称"
            },
            "total" : 10,
            "operationTime ": "2015-08-26 10:28:54",
            "createdAt": "2015-08-26 10:28:54",
        },
        {
            "id": "55efb2e8971374151a8b4567",
            "couponId ": "55efb2e8971374151a8b4567",
            "type": "coupon",
            "title": '优惠卷名称',
            "status": 'recieved',
            "member": {
                "id": "55efb2e8971374151a8b4567",
                "name": "会员名称"
                "phone": "13345267867"
            },
            store {
                "id "  :  "55efb2e8971374151a8b4567",
                "name" :  "门店名称"
            },
            "total" : 10,
            "operationTime ": "2015-08-26 10:28:54",
            "createdAt": "2015-08-26 10:28:54",
        }
    ],
    "_links": {
        "self": {
            "href": "http://wm.com/api/product/coupon-logs?page=1&status=recieved&searchKey=mike&per-page=2"
        },
        "next": {
            "href": "http://wm.com/api/product/coupon-logs?page=2&status=recieved&searchKey=mike&per-page=2"
        },
        "last": {
            "href": "http://wm.com/api/product/coupon-logs?page=2&status=recieved&searchKey=mike&per-page=2"
        }
    },
    "_meta": {
        "totalCount": 2,
        "pageCount": 2,
        "currentPage": 0,
        "perPage": 2
    }
}
```
# receive coupon through oauth

- Request method
GET

- Request Endpoint

```
http://{server-domain}/api/membership-discount/received-coupon
```


- Request Parameters:

| Name | Type | Required | Example | Description |
| ---- | ---- | -------- | ------- | ----------- |
| memberId | string | Yes | 55efb2e8971374151a8b4567 |  |
| couponId | string | Yes | 55efb2e8971374151a8b4567 |  |

- Request Example

```
http://{domain-server}/api/product/membership-discount/received-coupon?couponId=55efb2e8971374151a8b4567&memberId=55efb2e8971374151a8b4567
```


# Upsert Ding User

- Request Method:
POST

- Request Endpoint:
http://{server-domain}/api/ding/user

- Request Parameters:

| Name | Type | Required | Example | Description |
| ---- | ---- | -------- | ------- | ----------- |
| suiteKey | string | Yes | "1441691255162" | suiteKey |
| corpId | string | Yes | "54fd0571e4b055a0030461fb" | corpId |
| appId | string | Yes | "55ecf0a1e4b023058bf92bde" | appId |
| code | string | Yes | "08eec63bf03b36f2961d0588673f0f3e" | code |

- Request Example

```
http://wm.com/api/ding/user?corpid=dinge531cb7e7e12933b&suiteKey={suiteKey}&appId={appId}
```

- Response Example

```
{
    "dingUserId": "5638553dd6f97f616b8b4567"
}
```

# Get DingDing department

- Request Method:
GET

- Request Endpoint:
http://{server-domain}/api/management/ding/department

- Request Parameters:

| Name | Type | Required | Example | Description |
| ---- | ---- | -------- | ------- | ----------- |

- Request Example

```
http://wm.com/api/management/ding/department
```

- Response Example

```
[
    {
        "id": 1,
        "name": "Rex"
    }
]
```

# Sync dingding department user

- Request Method:
POST

- Request Endpoint:
http://{server-domain}/api/management/ding/sync-user

- Request Parameters:

| Name | Type | Required | Example | Description |
| ---- | ---- | -------- | ------- | ----------- |
| departmentId | string | Yes | 1 | departmentId |

- Request Example

```
http://wm.com/api/management/ding/sync-user

{
  "departmentId": 1
}
```

- Response Example

```
{
    "message": "OK",
    "data": null
}
```

# Get ding User list

- Request Method:
GET

- Request Endpoint:
http://{server-domain}/api/management/ding/user

- Request Parameters:

| Name | Type | Required | Example | Description |
| ---- | ---- | -------- | ------- | ----------- |

- Request Example

```
http://wm.com/api/management/ding/user
```

- Response Example

```
{
    "items": [
        {
            "id": "563ad0a8d6f97f253e8b4568",
            "corpId": "dinge531cb7e7e12933b",
            "dingId": "01446945196569",
            "name": "vincenthou",
            "avatar": null,
            "mobile": null,
            "email": null,
            "openId": null,
            "enableActions": []
        },
        {
            "id": "563ad0a8d6f97f253e8b4567",
            "corpId": "dinge531cb7e7e12933b",
            "dingId": "manager7746",
            "name": "Rex",
            "avatar": null,
            "mobile": null,
            "email": null,
            "openId": null,
            "enableActions": []
        }
    ],
    "_links": {
        "self": {
            "href": "http://wm.com/api/management/ding/user?page=1"
        }
    },
    "_meta": {
        "totalCount": 2,
        "pageCount": 1,
        "currentPage": 0,
        "perPage": 20
    }
}
```

# Authorize ding user with spefied authority

- Request Method:
PUT

- Request Endpoint:
http://{server-domain}/api/management/ding/authorize

- Request Parameters:

| Name | Type | Required | Example | Description |
| ---- | ---- | -------- | ------- | ----------- |
| departmentId | string | Yes | 1 | departmentId |

- Request Example

```
http://wm.com/api/management/ding/authorize

{
  "users": [
    "563ef977b81374cd398b4568"
  ],
  "authorities": [
    "mobile_pos"
  ]
}
```

- Response Example

```
{
    "message": "OK",
    "data": [
        "updated": 1
    ]
}
```


# Get follower property by openId

- Request Method:
GET

- Request Endpoint:
http://{server-domain}/api/channel/follower/property

- Request Parameters:

| Name | Type | Required | Example | Description |
| ---- | ---- | -------- | ------- | ----------- |
| openId | string | Yes | oC9Aes0P6F5o0e_Cgd3CPv4B0IwI | openId |

- Request Example

```
http://wm.com/api/channel/follower/property?openId=oC9Aes0P6F5o0e_Cgd3CPv4B0IwI
```

- Response Example

```
[
    {
        "id": "5518bfacd6f97f41048b456f",
        "name": "tel",
        "value": "13021123153"
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
        "id": "5518bfacd6f97f41048b4571",
        "name": "birthday",
        "value": 1443110400000
    },
    {
        "id": "5518bfacd6f97f41048b4570",
        "name": "gender",
        "value": "male"
    }
]
```

# Update goods by id

- Request Method:
PUT

- Request Endpoint:
http://{server-domain}/api/product/goods/update/{goodsId}

- Request Parameters:

| Name | Type | Required | Example | Description |
| ---- | ---- | -------- | ------- | ----------- |
| accesstoken | string | Yes | 2b0f27f3-60e8-3763-c187-bd3dd6167e54 | 用户token（放在querystring中） |
| pictures | array | No | ["/pictures/head.png"] | 图片 |
| score | int | No | 10 | 积分 |
| total | int | No | 10| 数量 |
| status | string | No | "on" | 商品上下架状态on或者off |
| onSaleTime | timeStamp | No | 1430806313141 | 商品上下架状态 |
| receiveModes | array | Yes | [self,express] |
| addresses | array | Yes | [] | default value is empty array |

- Request Example

```
http://dev.quncrm.com/api/product/goods/551b40f6d6f97f7f098b4569
```

- Response Example

```
{
    "id": "55374623d6f97f7d7e8b4569",
    "productId": "55374623d6f97f7d7e8b4567",
    "pictures": [
        "http://vincenthou.qiniudn.com/82ef08e377852edfbcd2d6a3.jpg",
        "http://vincenthou.qiniudn.com/82ef08e377852edfbcd2d6a3.jpg",
        "http://vincenthou.qiniudn.com/82ef08e377852edfbcd2d6a3.jpg"
    ],
    "score": 10,
    "total": 10,
    "usedCount": 10,
    "status": "on",
    "onSaleTime": "2015-5-1 10:00:00",
    "url": "http://www.***.com",
    "order": 100,
    "receiveModes":['self']
    "addresses": [
        {
            "address":"自提地址名称",
            "location" {
                "province":"省"
                "city":"市"
                "district":"县/区"
                "detail":"详细地址（不包括省市地区）"
            },
            "phone":"14245157867"
        }
    ]
}
```

# Get goods by id

- Request Method:
GET

- Request Endpoint:
http://{server-domain}/api/product/goods/view/{goodsId}

- Request Parameters:

| Name | Type | Required | Example | Description |
| ---- | ---- | -------- | ------- | ----------- |
| accesstoken | string | Yes | 2b0f27f3-60e8-3763-c187-bd3dd6167e54 | 用户token（放在querystring中） |

- Request Example

```
http://dev.quncrm.com/api/product/goods/551b40f6d6f97f7f098b4569
```

- Response Example

```
{
    "id": "554ac94ed6f97f233d8b4572",
    "productId": "55374623d6f97f7d7e8b4567",
    "pictures": [
        "http://ww4.sinaimg.cn/bmiddle/6106a4f0gw1eo94gprrhaj20dw0agdhe.jpg",
        "http://ww4.sinaimg.cn/bmiddle/6106a4f0gw1eo94gprrhaj20dw0agdhe.jpg"
    ],
    "score": 100,
    "total": 20,
    "usedCount": 1,
    "status": "on",
    "onSaleTime": "2015-05-08 09:56",
    "url": "http://u.augmarketing.cn/BP1y",
    "order": 10,
    "categoryName": "asd",
    "clicks": "1",
    "receiveModes":['self']
    "addresses": [
        {
            "address":"自提地址名称",
            "location" {
                "province":"省"
                "city":"市"
                "district":"县/区"
                "detail":"详细地址（不包括省市地区）"
            },
            "phone":"14245157867"
        }
    ]
}
```

# update the goods status

- Request Method:
PUT
- Request Endpoint:
```
http://wm.com/api/product/goods/update-goods-status
```

- Request Parameters:

| Name | Type | Required | Example | Description |
| ---- | ---- | -------- | ------- | ----------- |
| accesstoken | string | Yes | 2b0f27f3-60e8-3763-c187-bd3dd6167e54 | 用户token（放在querystring中） |
| operation | string | Yes | order(排序) or on(上架) or off(下架) | describe the operation |
| id | json array | Yes | {"2b0f27f3":"order","2b0f27f3":"order"} | ID:orderID |
| orderBy | json | Yes | {'order':'asc'} |  |
| onSaleTime |  string | no | timestamp | if the operation is on, the value must be passed |

- Response Example:

```
{
    "items": [
        {
            "id": "55363362d6f97f06048b4572",
            "productId": "5530c520d6f97f47658b456a",
            "pictures": ['http://wm.com/api/mall','http://wm.com/api/mall'],
            "score": 1000,
            "total": 10,
            "status": true,
            "onSaleTime": "2015年10月1号",
            "url": "XXXXXXXXXXXX",
            "order":100,
        },
        {
            "id": "55363362d6f97f06048b4572",
            "productId": "5530c520d6f97f47658b456a",
            "pictures": ['http://wm.com/api/mall','http://wm.com/api/mall'],
            "score": 1000,
            "total": 10,
            "status": true,
            "onSaleTime": "2015年10月1号",
            "url": "XXXXXXXXXXXX",
            "order":100,
        }
    ]
}
```

# Exchange goods

- Request Method:
PUT
- Request Endpoint:
```
http://wm.com/api/product/goods/exchange
```

- Request Parameters:

| Name | Type | Required | Example | Description |
| ---- | ---- | -------- | ------- | ----------- |
| accesstoken | string | Yes | 2b0f27f3-60e8-3763-c187-bd3dd6167e54 | 用户token（放在querystring中） |
| goodsId | string | Yes | 55363362d6f97f06048b4572 | 商品id |
| memberId | json array | Yes | 55363362d6f97f06048b4576 | 会员id |
| channelId | json | Yes | 55363362d6f97f06048b4572 | 渠道channelId |
| phone |  string | no | "13027785897" | 电话号码 |
| captcha |  string | no | "123456" | 验证码 |
| count |  int | no | 1 | 兑换数量 |
| address | string | Yes | 上海市浦东新区 | 送货地址或自提地址名称 |
| receiveMode | string | Yes | self或express | 配送方式 |

- Request Example

```
http://dev.cp.augmarketing.cn/api/product/goods/exchange?accesstoken=3ca28e36-c949-5378-4edb-15aff6a5223d

{
    "goodsId": "554ac94ed6f97f233d8b4572",
    "memberId": "5524ecf2d6f97f90318b457e",
    "channelId": "54d9c155e4b0abe717853ee1",
    "phone": "13027785897",
    "captcha": "941734",
    "count": 1，
    "address": "上海市浦东新区",
    "receiveMode": "self"
}
```

- Response Example:

```
{
    "message": "OK",
    "data": null
}
```

# Offline exchange goods

- Request Method:
PUT
- Request Endpoint:
```
http://wm.com/api/product/goods/offline-exchange
```

- Request Parameters:

| Name | Type | Required | Example | Description |
| ---- | ---- | -------- | ------- | ----------- |
| accesstoken | string | Yes | 2b0f27f3-60e8-3763-c187-bd3dd6167e54 | 用户token（放在querystring中） |
| memberId | string | Yes | 55363362d6f97f06048b4576 | 会员id |
| goods | array | Yes | [{"id":"55363362d6f97f06048b4578", "count":10}] | 兑换数量 |
| address | string | Yes | 上海市浦东新区 | 送货地址 |

- Request Example

```
http://dev.cp.augmarketing.cn/api/product/goods/offline-exchange?accesstoken=3ca28e36-c949-5378-4edb-15aff6a5223d

{
    "memberId": "55652c94d6f97f1a1f8b4579",
    "goods":[
        {
          "id": "554ac94ed6f97f233d8b4572",
          "count": 1
        },
        {
          "id": "55598c4bd6f97f07048b4568",
          "count": 2
        },
        {
          "id": "55505540d6f97f11048b456b",
          "count": 2
        }
    ],
    "address": "上海市浦东新区"
}
```

# Get goods list

- Request Method:
GET

- Request Endpoint:

    http://{server-domain}/api/product/goods/index

- Request Parameters:

| Name | Type | Required | Example | Description |
| ---- | ---- | -------- | ------- | ----------- |
| category | string | no | 54b5c1f8db4c0eea6e8b4569, 54b5c1f8db4c0eea6e8b4569 | If not provided, all the product will be listed |
| orderBy | json array | no | {"createdAt":"desc"} | If not provided, the result will be ordered by createdAt(desc) as default |
| searchKey | string | no | "zsho" | keyword |
| page | int | 1 | the current page |
| per-page | int | 10 | show how many records in every page |
| status | string | on or off or redeem | the goods status |

- Request Example

```
http://dev.cp.augmarketing.cn/api/product/products?accesstoken=3ca28e36-c949-5378-4edb-15aff6a5223d&orderBy={"createdAt":"desc"}&category=54b5c1f8db4c0eea6e8b4569,54b5c1f8db4c0eea6e8b4569&page=1&per-page=10&status=on
```

- Response Example

```
{
    "items": [
        {
            "id": "54b62e4edb4c0eae048b4574",
            "productId":"54b62e4edb4c0eae048b4574",
            "sku":"5555555555"
            "productName": "test",
            "pictures":[
                  "http://ww4.sinaimg.cn/bmiddle/6106a4f0gw1eo94gprrhaj20dw0agdhe.jpg","url":  "http://ww4.sinaimg.cn/bmiddle/6106a4f0gw1eo94gprrhaj20dw0agdhe.jpg"
            ],
            "categoryName": "www",
            "status":"on",
            "score":10,
            "total":10,
            "usedCount":1,
            "onSaleTime":2015-01-14 16:52:30,
            "order":10,
            "createdAt": "2015-01-14 16:52:30",
            "receiveModes":['self','express']
            "addresses": [
                {
                    "address":"自提地址名称",
                    "location" {
                        "province":"省"
                        "city":"市"
                        "district":"县/区"
                        "detail":"详细地址（不包括省市地区）"
                    },
                    "phone":"14245157867"
                }
            ]
        }
    ],
    "_links": {
        "self": {
            "href": "http://dev.cp.augmarketing.cn/api/product/proucct?accesstoken=3ca28e36-c949-5378-4edb-15aff6a5223d&orderBy=%7B%22createdAt%22%3A%22desc%22%7D&category=%5B%2254b5c1f8db4c0eea6e8b4569%22%5D&page=1&per-page=10"
        }
    },
    "_meta": {
        "totalCount": 1,
        "pageCount": 1,
        "currentPage": 1,
        "perPage": 20
    }
}
```

# create goods

- Request Method:
POST

    Request Endpoint:

     http://{server-domain}/api/product/goods/create

- Request Parameters:

| Name | Type | Required | Example | Description |
| ---- | ---- | -------- | ------- | ----------- |
| goods | array | Yes | [{"productId":"54b5c1f8db4c0eea6e8b4569","categoryId":"8db4c0eea6e8b4569","score":10,"total":10}] |  |

- Reponse Example:

```
[
    'items':
       {
        "id": "54b62e4edb4c0eae048b4574",
        "productId":"54b62e4edb4c0eae048b4574",
        "sku":"5555555555"
        "productName": "test",
        "pictures":null,
        "categoryName": "xxx",
        "status":"off"
        "score":10,
        "total":10,
        "usedCount":0,
        "onSaleTime":'',
        "order":100,
        "createdAt": "2015-01-14 16:52:30",
      },
      {
        "id": "54b62e4edb4c0eae048b4574",
        "productId":"54b62e4edb4c0eae048b4574",
        "sku":"5555555555"
        "productName": "test",
        "pictures":null,
        "categoryName": "xxx",
        "status":"off"
        "score":10,
        "total":10,
        "usedCount":0,
        "onSaleTime":'',
        "order":100,
        "createdAt": "2015-01-14 16:52:30",
      }
]
```

# delete the goods

- Request Method:
DELETE

- Request Endpoint:

    http://{server-domain}/api/product/goods/{article_id_list}

- Request Example:
```
http://{server-domain}/api/product/goods/54bcb51cdb4c0e7c3d8b4569,54bcb51cdb4c0e7c3d8b4569,54bcb51cdb4c0e7c3d8b4569
```

# Get all goodsExchangeLog

- Request Method:
GET

- Request Endpoint:
http://{server-domain}/api/product/goods-exchange-logs

- Request Parameters:

| Name | Type | Required | Example | Description |
| ---- | ---- | -------- | ------- | ----------- |
| accesstoken | string | Yes | 2b0f27f3-60e8-3763-c187-bd3dd6167e54 | 用户token（放在querystring中） |
| key | string | No | “红茶” | 查找关键词 |
| startTime | timeStamp | No | 1430807279235 | 兑换时间范围开始时间 |
| endTime | timeStamp | No | 1430807289235 | 兑换时间范围开始时间 |
| usedScoreMin | int | No | 100 | 消耗积分范围 |
| usedScoreMax | int | No | 200 | 消耗积分范围 |
| memberId | string | No | 55374623d6f97f7d7e8b4567 | 会员id |
| channelId | string | No | 55374623d6f97f7d7e8b4567 | 兑换渠道id |
| isDelivered | boolean | No | true or false | if user select all, you don't need to pass this param |
| receiveMode | string | No | self or express | receive mode |
| orderBy | string | No | {"createdAt":"asc"} | 排序 |
| page | int | No | 2 | No  页数默认为1 |
| per-page | string | No | 10 | No 每页条数默认为20 |

- Request Example

```
http://wm.com/api/product/goods-exchange-logs?tmoffset=-8&accesstoken=5ed61f22-b8ec-8d4b-900f-68b5a111df37&page=1&per-page=20
```

- Response Example

```
{
    "items": [
        {
            "id": "558230bad6f97fd53c8b4568",
            "goods": [
                {
                    "id": "5576c119d6f97fc7778b4567",
                    "productId": "55753c71d6f97fe3338b4567",
                    "sku": "1433746522779750",
                    "picture": "http://****",
                    "productName": "友陈肉松饼",
                    "count": 1
                },
                {
                    "id": "5576c291d6f97fcf338b4567",
                    "productId": "5576c25ed6f97fe84c8b4567",
                    "sku": "1433846341601811",
                    "picture": "http://****",
                    "productName": "乐事薯片",
                    "count": 2
                }
            ],
            "memberId": "55796c85d6f97f462c8b456d",
            "createdAt": "2015-06-18 10:45:14",
            "memberName": "刘金海 Hardy",
            "telephone": "13027785897",
            "usedScore": 1,
            "count": 3,
            "usedFrom": {
                "id": "",
                "type": "offline",
                "name": "offline_exchange"
            },
            "exceptedScore": 50,
            "address": "上海市浦东新区",
            "isDelivered" : true,
            "receiveMode" : "self"
        }
    ],
    "_links": {
        "self": {
            "href": "http://wm.com/api/product/goods-exchange-logs?tmoffset=-8&accesstoken=5da91224-9a6a-1011-8e37-b14add65cff8&page=1&per-page=10"
        }
    },
    "_meta": {
        "totalCount": 1,
        "pageCount": 1,
        "currentPage": 1,
        "perPage": 10
    }
}
```
# update goodsExchangeLog status

- Request Method:
POST

- Request Endpoint:
http://{server-domain}/api/product/goods-exchange-log/ship

- Request Parameters:

| Name | Type | Required | Example | Description |
| ---- | ---- | -------- | ------- | ----------- |
| accesstoken | string | Yes | 2b0f27f3-60e8-3763-c187-bd3dd6167e54 | 用户token（放在querystring中） |
| id | string | Yes | 5551577fd6f97f2f3b8b4569 | log id |

- Request Example

```
http://wm.com/api/product/goods-exchange-log/ship
```

# Get goodsExchangeLog by memberId

- Request Method:
POST

- Request Endpoint:
http://{server-domain}/api/product/goods-exchange-log/member/{memberId}

- Request Parameters:

| Name | Type | Required | Example | Description |
| ---- | ---- | -------- | ------- | ----------- |
| accesstoken | string | Yes | 2b0f27f3-60e8-3763-c187-bd3dd6167e54 | 用户token（放在querystring中） |
| channelId | string | No | 5551577fd6f97f2f3b8b4569 | 兑换渠道id |

- Request Example

```
http://wm.com/api/product/goods-exchange-log/member/5551577fd6f97f2f3b8b4569?tmoffset=-8&accesstoken=5ed61f22-b8ec-8d4b-900f-68b5a111df37&page=1&per-page=20
```

- Response Example

```
{
    "items": [
        {
            "id": "558289ced6f97f5f7e8b4568",
            "goods": [
                {
                    "id": "5576c119d6f97fc7778b4567",
                    "productId": "55753c71d6f97fe3338b4567",
                    "sku": "1433746522779750",
                    "picture": "http://vincenthou.qiniudn.com/52bb59a2fb73449030ac384a.jpg",
                    "productName": "友陈肉松饼",
                    "count": 2
                }
            ],
            "memberId": "55796c85d6f97f462c8b456d",
            "createdAt": "2015-06-18 17:05:18",
            "memberName": "刘金海 Hardy",
            "telephone": "13027785897",
            "usedScore": 20,
            "count": 2,
            "usedFrom": {
                "id": "54d9c155e4b0abe717853ee1",
                "type": "wechat",
                "name": "熊猫Baby"
            },
            "exceptedScore": 20,
            "address": "上海市浦东新区"
        }
    ],
    "_links": {
        "self": {
            "href": "http://wm.com/api/product/goods-exchange-log/member/55796c85d6f97f462c8b456d?tmoffset=-8&accesstoken=5da91224-9a6a-1011-8e37-b14add65cff8&page=1&per-page=10"
        }
    },
    "_meta": {
        "totalCount": 1,
        "pageCount": 1,
        "currentPage": 1,
        "perPage": 10
    }
}
```

# Remove goodsExchangeLog

- Request Method:
DELETE

- Request Endpoint:
http://{server-domain}/api/product/goods-exchange-log/{goodExchangeLogId}

- Request Parameters:

| Name | Type | Required | Example | Description |
| ---- | ---- | -------- | ------- | ----------- |
| accesstoken | string | Yes | 2b0f27f3-60e8-3763-c187-bd3dd6167e54 | 用户token（放在querystring中） |

- Request Example:

```
http://wm.com/api/product/goods-exchange-log/5551577fd6f97f2f3b8b4569?tmoffset=-8&accesstoken=5ed61f22-b8ec-8d4b-900f-68b5a111df37
```

- Response Example:
```
```

# Create, update and delete SelfHelpDeskSetting.

- Request Method:
POST

- Request Endpoint
http://{server-domain}/api/helpdesk/self-settings

- Request Parameters:

| Name | Type | Required | Example | Description |
| ---- | ---- | -------- | ------- | ----------- |

- Request Example:

```
POST
http://wm.com/api/helpdesk/self-settings

{
    "settings" : {
        "content" : "回复数字: 1.　传真处理, 2. 账号密保服务, 3. 充值业务, 4.转人工服务",
        "type": "reply",
        "menus" : {
            "1" : {
                "content" : "回复数字　1. 绑定, 2. 解绑, 3. 返回上一级",
                "type": "reply",
                "menus" : {
                    "1" : {
                        "content" : "您已绑定成功",
                        "type": "reply"
                    },
                    "2" : {
                        "content" : "已为您解绑",
                        "type": "reply"
                    },
                    "3" : {
                        "content" : "返回上一级",
                        "type": "back"
                    }
                }
            },
            "2" : {
                "content" : "回复数字  1. 短信服务绑定　２.　短信服务解绑　３. 返回上一级",
                "type": "reply",
                "menus" : {
                    "1" : {
                        "content" : "短信服务绑定",
                        "type": "reply"
                    },
                    "2" : {
                        "content" : "信服务解绑",
                        "type": "reply"
                    },
                    "3" : {
                        "content" : "返回上一级",
                        "type": "back"
                    }
                }
            },
            "3" : {
                "content" : "回复数字  1. 密保绑定状态　2. 密保卡使用状态  3. 返回上一级",
                "type": "reply",
                "menus" : {
                    "1" : {
                        "content" : "密保绑定状态",
                        "type": "reply"
                    },
                    "2" : {
                        "content" : "密保绑定状态",
                        "type": "reply"
                    },
                    "3" : {
                        "content" : "返回上一级",
                        "type": "back"
                    }
                }
            },
            "4" : {
                "content" : "回复数字　接人工客服 ",
                "type": "connect"
            }
        }
    }
}
```

- Response Example
-
```
{
    "status" : "ok",
    "id" : "560a52d4d4336e39365044be"
}
```

# Get SelfHelpDeskSetting

- Request Method:
GET

- Request Endpoint
http://{server-domain}/api/helpdesk/self-settings

- Request Parameters:

| Name | Type | Required | Example | Description |
| ---- | ---- | -------- | ------- | ----------- |

- Request Example:

```
GET
http://wm.com/api/helpdesk/self-settings
```

-Response Example
```
{
    "id" : "560a52d4d4336e39365044be",
    "settings" : {
        "content" : "回复数字: 1.　传真处理, 2. 账号密保服务, 3. 充值业务, 4.转人工服务",
        "type": "reply",
        "menus" : {
            "1" : {
                "content" : "回复数字　1. 绑定, 2. 解绑, 3. 返回上一级",
                "type": "reply",
                "menus" : {
                    "1" : {
                        "content" : "您已绑定成功",
                        "type": "reply"
                    },
                    "2" : {
                        "content" : "已为您解绑",
                        "type": "reply"
                    },
                    "3" : {
                        "content" : "返回上一级",
                        "type": "back"
                    }
                }
            },
            "2" : {
                "content" : "回复数字  1. 短信服务绑定　２.　短信服务解绑　３. 返回上一级",
                "type": "reply",
                "menus" : {
                    "1" : {
                        "content" : "短信服务绑定",
                        "type": "reply"
                    },
                    "2" : {
                        "content" : "信服务解绑",
                        "type": "reply"
                    },
                    "3" : {
                        "content" : "返回上一级",
                        "type": "back"
                    }
                }
            },
            "3" : {
                "content" : "回复数字  1. 密保绑定状态　2. 密保卡使用状态  3. 返回上一级",
                "type": "reply",
                "menus" : {
                    "1" : {
                        "content" : "密保绑定状态",
                        "type": "reply"
                    },
                    "2" : {
                        "content" : "密保绑定状态",
                        "type": "reply"
                    },
                    "3" : {
                        "content" : "返回上一级",
                        "type": "back"
                    }
                }
            },
            "4" : {
                "content" : "回复数字　接人工客服 ",
                "type": "connect"
            }
        }
    }
}
```


# Publish SelfHelpDeskSetting
- Request Method:
POST

- Request Endpoint
http://{server-domain}/api/helpdesk/self-setting/publish

- Request Parameters:

| Name | Type | Required | Example | Description |
| ---- | ---- | -------- | ------- | ----------- |

- Request Example:

```
POST
http://wm.com/api/helpdesk/self-setting/publish

```

- Response Example
-
```
{
    "status" : "ok"
}
```

# Get conversation statistics

- Request Method
GET

- Request Endpoint
http://{server-domain}/api/helpdesk/conversation/statistics

- Request Parameters

| Name | Type | Required | Example | Description |
| ---- | ---- | -------- | ------- | ----------- |
| startTime | int | No | 1421808140000 | The start time |
| endTime | int | No | 1421808140000 | the end time |

- Response Example

```
{
    "clientCount": 8,
    "conversationCount": 9,
    "clientMessageCount": 3,
    "statistics": {
        "categories": [
            "2015-01-21",
            "2015-01-23"
        ],
        "series": [
            {
                "name": "helpdesk_users_count",
                "data": [
                    4,
                    4
                ]
            },
            {
                "name": "helpdesk_sessions_count",
                "date": [
                    4,
                    5
                ]
            },
            {
                "name": "helpdesk_sent_message_count",
                "date": [
                    2,
                    2
                ]
            }
        ]
    }
}
```


# Refresh app private key

- Request Method:
PUT

- Request Endpoint:
http://{server-domain}/api/management/application/refresh/{applicationId}

- Request Parameters:

| Name | Type | Required | Example | Description |
| ---- | ---- | -------- | ------- | ----------- |
- Request Example

```
http://dev.cp.augmarketing.cn/api/management/application/refresh/54d028b22736e752508b4569
```

- Response Example

```
{
    "id": "54d028b22736e752508b4569",
    "name": "不宅人",
    "content" : "不宅人是一款徒步APP",
    "privateKey": "876e8645655s878645dsd6465214354735747s654"
}
```

# Refresh account access key and sercet key

- Request Method:
PUT

- Request Endpoint:
http://{server-domain}/api/management/application/refresh-key

- Request Parameters:

| Name | Type | Required | Example | Description |
| ---- | ---- | -------- | ------- | ----------- |
- Request Example

```
http://dev.cp.augmarketing.cn/api/management/application/refresh-key
```

- Response Example

```
{
    "accessKey": "87s2136e8645655s8"
    "secretKey": "876e8645234655234s878645d3243646547s653244",
    "keyCreatedAt": 1428459727
}
```

# get name of message template

- Request Method:
GET

- Request Endpoint:
http://{server-domain}/api/management/message-templates

- Request Parameters:

| Name | Type | Required | Example | Description |
| ---- | ---- | -------- | ------- | ----------- |
| accesstoken | string | yes | 2b0f27f3-60e8-3763-c1876167e54 | 用户token |


- Response Example

```
[
  {
        "id": "559b836f475df481788b4567",
        "name": "商品兑换模板",
        "weChat": {
            "templateId": "",
            "templateContent": ""
        },
        "email": {
            "title": "",
            "content": ""
        },
        "mobile": {
            "message": ""
        },
        "updatedAt": "2015-07-07 15:44:47"
    }
]
```

# update the message template

- Request Method:
PUT
- Request Endpoint:
http://{server-domain}/api/management/message-template/ID

- Request Parameters:

| Name | Type | Required | Example | Description |
| ---- | ---- | -------- | ------- | ----------- |
| accesstoken | string | yes | 2b0f27f3-60e8-3763-c187-bd3dd6167e54 | token |
| data | json | yes | {"id": "559b836f475df481788b4567","name": "商品兑换模板","weChat": {"templateId": "","templateContent": ""},"email": {"title": "","content": ""},"mobile": {"message": ""},"updatedAt": "2015-07-07 15:44:47"} | template for weChat |

- Response Example

```
{
        "id": "559b836f475df481788b4567",
        "name": "商品兑换模板",
        "weChat": {
            "templateId": "",
            "templateContent": ""
        },
        "email": {
            "title": "",
            "content": ""
        },
        "mobile": {
            "message": ""
        },
        "updatedAt": "2015-07-07 15:44:47"
}
```


## Wechat payment

### Open wechat payment.

- Request Method:
POST

- Request Endpoint:
http://{server-domain}/api/management/channel/open-wechat-payment

- Request Parameters:

| Name | Type | Required | Example | Description |
| ---- | ---- | -------- | ------- | ----------- |
| appId | string | Yes | "2014102700014769" | 渠道appId |
| sellerId | string | Yes | "10010000" | 微信商户ID / 卖家支付宝账户号 |
| apiKey | string | Yes | "asdadad" | API安全密钥 |
| p12Credential | File | No | "apiclient_cert.p12" | pkcs12格式 |
| pemCredential | File | No | "apiclient_cert.pem" | 证书pem格式 |
| pemCredentialKey | File | No | "apiclient_key.pem" | 证书密钥pem格式 |
| caCredential | File | No | "rootca.pem" | CA证书 |

- Request Example

```
http://wm.com/api/management/channel/open-wechat-payment

{
    "appId":"2014102700014769",
    "sellerId":"10010000",
    "apiKey":"20141027014769",
    "p12Credential":"apiclient_cert.p12",
    "pemCredential":"apiclient_cert.pem",
    "pemCredentialKey":"apiclient_key.pem",
    "caCredential":"rootca.pem"
}
```

- Response Example

```
{
    "authDirectory": "http://wxpay.weixin.qq.com/pub/jsapi",
}

```

### View the message of wechat payment.

- Request Method:
GET

- Request Endpoint:
http://{server-domain}/api/management/channel/wechat-payment_message

- Request Parameters:

| Name | Type | Required | Example | Description |
| ---- | ---- | -------- | ------- | ----------- |

- Request Example

```
http://wm.com/api/management/channel/wechat-payment-message
```

- Response Example

```
{
    "quncrmAccountId": "群脉账号ID",
    "channelType": "渠道类型",
    "appId": "微信公众号appid",
    "sellerId": "微信商户ID",
    "apiKey": "API安全密钥",
    "weixinPayment": "ENABLE",
    "alipayPayment": "DISABLE",
    "p12Credential": {
        "id": "证书文件ID",
        "name": "文件名",
        "type": "证书类型(P12_CREDENTIAL)"
    },
    "pemCredential": {
        "id": "证书文件ID",
        "name": "文件名",
        "type": "证书类型(PEM_CREDENTIAL)"
    },
    "pemCredentialKey": {
        "id": "证书文件ID",
        "name": "文件名",
        "type": "证书类型(PEM_CREDENTIAL_KEY)"
    },
    "caCredential": {
        "id": "证书文件ID",
        "name": "文件名",
        "type": "证书类型(CA_CREDENTIAL)"
    },
    "authDirectory": "http://wxpay.weixin.qq.com/pub/jsapi"
}
```

### Edit wechat payment.

- Request Method:
POST

- Request Endpoint:
http://{server-domain}/api/management/channel/edit-wechat-payment

- Request Parameters:

| Name | Type | Required | Example | Description |
| ---- | ---- | -------- | ------- | ----------- |
| appId | string | Yes | "2014102700014769" | 渠道appId |
| sellerId | string | Yes | "10010000" | 微信商户ID / 卖家支付宝账户号 |
| apiKey | string | Yes | "asdadad" | API安全密钥 |
| p12CredentialId | string | No | '2014102700014769' | 证书文件的ID, 修改时, 如果传回原ID说明当前文件没有改变 |
| p12Credential | File | No | "apiclient_cert.p12" | pkcs12格式 |
| pemCredentialId | string | No | '2014102700014769' | 证书文件的ID, 修改时, 如果传回原ID说明当前文件没有改变 |
| pemCredential | File | No | "apiclient_cert.pem" | 证书pem格式 |
| pemCredentialKeyId | string | No | '2014102700014769' | 证书文件的ID, 修改时, 如果传回原ID说明当前文件没有改变 |
| pemCredentialKey | File | No | "apiclient_key.pem" | 证书密钥pem格式 |
| caCredentialId | string | No | '2014102700014769' | 证书文件的ID, 修改时, 如果传回原ID说明当前文件没有改变 |
| caCredential | File | No | "rootca.pem" | CA证书 |

- Request Example

```
http://wm.com/api/management/channel/edit-wechat-payment

{
    "appId":"2014102700014769",
    "sellerId":"10010000",
    "apiKey":"20141027014769",
    "p12CredentialId":"20141027014769",
    "p12Credential":"apiclient_cert.p12",
    "pemCredentialId":"20141027014769",
    "pemCredential":"apiclient_cert.pem",
    "pemCredentialKey":"apiclient_key.pem",
    "pemCredentialKeyId":"20141027014769",
    "caCredentialId":"20141027014769",
    "caCredential":"rootca.pem"
}
```

- Response Example

```
{
    "code": 200,
    "message": "OK"
}

```

### Check Payment.

- Request Method:
POST

- Request Endpoint:
http://{server-domain}/api/management/channel/check-payment

- Request Parameters:

| Name | Type | Required | Example | Description |
| ---- | ---- | -------- | ------- | ----------- |
| price | string | Yes | "100" | 订单金额 |

- Request Example

```
http://wm.com/api/management/channel/check-payment

{
    "price":"100",
}
```

- Response Example

```
{
    "code": 200,
    "message": "OK",
    "data": {
        "channelType": "WECHAT / ALIPAY",
        "quncrmAccountId": "群脉账号",
        "tradeType": "支付类型 JSAPI(公众号支付)，NATIVE(扫码支付)",
        "sellerId":"微信商户ID / 卖家支付宝账户号",
        "buyerId":"买家微信openId / 买家支付宝账户号",
        "spbillCreateIp": "买家终端IP",
        "subject": "订单标题",
        "detail": "订单详情",
        "outTradeNo": "商户订单号",
        "tradeNo": "微信订单号 / 支付宝订单号",
        "totalFee": "订单金额",
        "createTime": "订单创建时间",
        "timeExpire": "订单过期时间",
        "extendsion": {
            "wechatAppId": "微信公众号ID",
            "prepayId": "微信预支付交易会话标识",
            "codeUrl": "二维码链接, tradeType为NATIVE是有返回，可将该参数值生成二维码展示出来进行扫码支付"
        },
        "tradeStatus": "订单状态",
        "tradeStateDesc": "交易状态描述",
        "failureCode": "微信/支付宝 错误代码",
        "failureMsg": "微信/支付宝 错误代码描述"
    }
}

```

### Check refund.

- Request Method:
POST

- Request Endpoint:
http://{server-domain}/api/management/channel/check-refund

- Request Parameters:

| Name | Type | Required | Example | Description |
| ---- | ---- | -------- | ------- | ----------- |
| outTradeNo | string | Yes | "T0001" | 商户订单号 |
| refundFee | string | Yes | "10" | 退款金额 |

- Request Example

```
http://wm.com/api/management/channel/check-refund

{
    "outTradeNo": "T0000",
    "refundFee": "10"
}
```

- Response Example

```
{
    "code": 200,
    "message": "OK"
}
```

# Get member by id

- Request Method:
GET

- Request Endpoint:
http://{server-domain}/api/member/member/{memberId}

- Request Parameters:

| Name | Type | Required | Example | Description |
| ---- | ---- | -------- | ------- | ----------- |
| accesstoken | string | No | 2b0f27f3-60e8-3763-c187-bd3dd6167e54 | 用户token（放在querystring中） type为'updateCompanyInfo'和'exchange'时必须 |

- Request Example

```
http://wm.com/api/member/member/55b1ab3ad6f97f5e668b4569?tmoffset=-8&accesstoken=07cf02ec-a1ef-758f-47fb-344f45258664
```

- Response Example

```
{
    "id": "55b1ab3ad6f97f5e668b4569",
    "card": {
        "id": "5518bfadd6f97f41048b4573",
        "name": "默认会员卡",
        "poster": "/images/mobile/membercard.png",
        "fontColor": "#fff",
        "privilege": "<p>9折消费折扣</p><ul><li><p>全年消费享有正价商品9折优惠</p></li><li><p><br/></p></li></ul><p>生日礼及寿星折扣</p><ul><li><p>生日當月可憑會員卡到全省門市領取精美生日好禮,且享有壽星200元商品抵用券或8折生日優惠.</p></li><li><p>8折優惠適用於正價商品，不得與會員折扣合併使用，生日禮將不定期更換</p></li></ul>",
        "condition": {
            "minScore": 50,
            "maxScore": 100
        },
        "usageGuide": "<p>使用时向工作人员出示本会员卡即可，最终解释权归本品牌所有</p>",
        "isEnabled": true,
        "isDefault": true,
        "scoreResetDate": null,
        "provideCount": 9,
        "createdAt": "2015-03-30 11:14:53",
        "updatedAt": "2015-05-20 16:40:35"
    },
    "createdAt": "2015-07-24 11:04:26",
    "socialAccount": {
        "id": "54d9c155e4b0abe717853ee1",
        "origin": "wechat",
        "name": "熊猫Baby",
        "type": "SERVICE_AUTH_ACCOUNT"
    },
    "socialMember": "Aaron Wang Test",
    "properties": [
        {
            "id": "5518bfacd6f97f41048b456e",
            "name": "name",
            "type": "input",
            "value": "Aaron Wang Test"
        },
        {
            "id": "5518bfacd6f97f41048b456f",
            "name": "tel",
            "type": "input",
            "value": "13027785897"
        },
        {
            "id": "5518bfacd6f97f41048b4570",
            "name": "gender",
            "type": "radio",
            "value": "unknown"
        }
    ],
    "accountId": "55189a7cd6f97f41048b4567",
    "cardProvideTime": "2015-07-24 11:04:26",
    "cardExpired": 0,
    "avatar": "http://wx.qlogo.cn/mmopen/GsBuGiaXuVMgdGH27mIX3A5zYfwEeVJtM6NYyLeBnQUIUJqHvFpAALIcLuxJicY53w2SaNL99ibRl8WUTr6ibTTvdIWeYX8ibWccY/0",
    "location": {
        "city": "浦东新区",
        "province": "上海",
        "country": "中国"
    },
    "tags": [],
    "score": 1,
    "remarks": null,
    "cardNumber": "10000082",
    "unionId": "",
    "totalScore": 1,
    "cardExpiredAt": "",
    "birth": null,
    "openId": "oC9Aes_BczVT_wk_DaREmajROhd0",
    "scoreProvideTime": "2015-07-24 11:04:26",
    "qrcodeUrl": "http://qiniu.qlogo.cn/mmopen/GsBuGiaXuVMgdGH27mIX3..."
}
```

# Export member

- Request Method:
GET

- Request Endpoint:
http://{server-domain}/api/member/member/export

- Request Parameters:

| Name | Type | Required | Example | Description |
| ---- | ---- | -------- | ------- | ----------- |
| accesstoken | string | No | 2b0f27f3-60e8-3763-c187-bd3dd6167e54 | 用户token（放在querystring中） type为'updateCompanyInfo'和'exchange'时必须 |

- Request Example

```
http://wm.com/api/member/member/export?accesstoken=e61798a3-66ec-77c1-091d-ce0439e96e77
```

- Response Example

```
{
    "result": "success/error",
    "data": {
        "jobId": "4e5d97c94375010847ac9cb8507faaea",
        "key": "member-1433382734-2079375604.csv"
    }
}
```

# Stats member signup summary

- Request Method:
GET

- Request Endpoint:
http://{server-domain}/api/member/stats/signup-summary

- Request Parameters:

| Name | Type | Required | Example | Description |
| ---- | ---- | -------- | ------- | ----------- |
| accesstoken | string | Yes | 2b0f27f3-60e8-3763-c187-bd3dd6167e54 | 用户token（放在querystring中） type为'updateCompanyInfo'和'exchange'时必须 |
| start | string | Yes | 1420041600000 | 时间戳 |
| end | string | Yes | 1435680000000 | 时间戳 |

- Request Example

```
http://wm.com/api/member/stats/signup-summary?accesstoken=92717035-cc5e-b5ea-b698-495fcb3697db&start=1420041600000&end=1435680000000
```

- Response Example

```
{
    "date": [
        "2015-04",
        "2015-05",
        "2015-06",
        "2015-07"
    ],
    "data": {
        "app:web": [
            0,
            0,
            1,
            0
        ],
        "wechat": [
            0,
            0,
            5,
            0
        ],
        "portal": [
            0,
            0,
            1,
            0
        ]
    }
}
```

# Stats member active tracking

- Request Method:
GET

- Request Endpoint:
http://{server-domain}/api/member/stats/active-tracking

- Request Parameters:

| Name | Type | Required | Example | Description |
| ---- | ---- | -------- | ------- | ----------- |
| accesstoken | string | Yes | 2b0f27f3-60e8-3763-c187-bd3dd6167e54 | 用户token（放在querystring中） type为'updateCompanyInfo'和'exchange'时必须 |
| year | string | Yes | 2015 | 年 |
| quarter | string | Yes | 2 | 季度 |

- Request Example

```
http://wm.com/api/member/stats/active-tracking?accesstoken=92717035-cc5e-b5ea-b698-495fcb3697db&year=2015&quarter=2
```

- Response Example

```
{
    "id": "558a9528d6f97f9e718b4567",
    "totalNew": 2, //新注册活跃会员数
    "totalActive": 2, //总活跃会员数
    "totalInactive": 19, //总不活跃会员数
    "year": 2015,
    "quarter": 2,
    "createdAt": "2015-06-24 19:31:52"
}
```

# Stats member engagement

- Request Method:
GET

- Request Endpoint:
http://{server-domain}/api/member/stats/engagement

- Request Parameters:

| Name | Type | Required | Example | Description |
| ---- | ---- | -------- | ------- | ----------- |
| accesstoken | string | Yes | 2b0f27f3-60e8-3763-c187-bd3dd6167e54 | 用户token（放在querystring中） type为'updateCompanyInfo'和'exchange'时必须 |
| year | string | Yes | 2015 | 年 |

- Request Example

```
http://wm.com/api/member/stats/engagement?accesstoken=92717035-cc5e-b5ea-b698-495fcb3697db&year=2015
```

- Response Example

```
{
    "date": [
        "Jan",
        "Feb",
        "Mar",
        "Apr",
        "May",
        "Jun",
        "Jul",
        "Aug",
        "Sep",
        "Oct",
        "Nov",
        "Dec"
    ],
    "data": [
        0,
        0,
        0,
        0,
        0,
        2,
        0,
        0,
        0,
        0,
        0,
        0
    ]
}
```

# Merge member

- Request Method:
POST

- Request Endpoint:
http://{server-domain}/api/member/member/merge

- Request Parameters:

| Name | Type | Required | Example | Description |
| ---- | ---- | -------- | ------- | ----------- |
| accesstoken | string | Yes | 2b0f27f3-60e8-3763-c187-bd3dd6167e54 | 用户token（放在querystring中） type为'updateCompanyInfo'和'exchange'时必须 |
| main | string | Yes | 54b5c1f8db4c0eea6e8b4569 | 主记录memberId |
| others | array | Yes | [54b5c1f8db4c0eea6e8b4568, 54b5c1f8db4c0eea6e8b4567] | 要删除的memberId |

- Request Example

```
wm.com/api/member/member/merge?accesstoken=4c39fa7d-44c7-ac74-e365-0522f3a6a542
```

- Response Example

```
{
    "message": "OK",
    "data": ""
}
```

# Export member signup summary

- Request Method:
GET

- Request Endpoint:
http://{server-domain}/api/member/stats/export-signup-summary

- Request Parameters:

| Name | Type | Required | Example | Description |
| ---- | ---- | -------- | ------- | ----------- |
| accesstoken | string | Yes | 2b0f27f3-60e8-3763-c187-bd3dd6167e54 | 用户token（放在querystring中） type为'updateCompanyInfo'和'exchange'时必须 |
| start | string | Yes | 1420041600000 | 时间戳 |
| end | string | Yes | 1435680000000 | 时间戳 |

- Request Example

```
http://wm.com/api/member/stats/export-signup-summary?accesstoken=6d662a2a-f743-9d1e-ecbd-cac445af0e04&start=1420070400000&end=1438387200000
```

- Response Example

```
{
    "message": "success",
    "data": {
        "jobId": "4541429d091d2ce687ef09e52cc33d3d",
        "key": "Signup Summary_20150707"
    }
}
```

# Export member active tracking

- Request Method:
GET

- Request Endpoint:
http://{server-domain}/api/member/stats/export-active-tracking

- Request Parameters:

| Name | Type | Required | Example | Description |
| ---- | ---- | -------- | ------- | ----------- |
| accesstoken | string | Yes | 2b0f27f3-60e8-3763-c187-bd3dd6167e54 | 用户token（放在querystring中） type为'updateCompanyInfo'和'exchange'时必须 |
| year | string | Yes | 2015 | 年 |
| quarter | string | Yes | 2 | 季度 |

- Request Example

```
http://wm.com/api/member/stats/export-active-tracking?accesstoken=6d662a2a-f743-9d1e-ecbd-cac445af0e04&year=2015&quarter=3
```

- Response Example

```
{
    "message": "success",
    "data": {
        "jobId": "a2a3f988c32ef1338da2000f83054ea9",
        "key": "Acct Tracking_20150707"
    }
}
```

# Export member engagement

- Request Method:
GET

- Request Endpoint:
http://{server-domain}/api/member/stats/export-engagement

- Request Parameters:

| Name | Type | Required | Example | Description |
| ---- | ---- | -------- | ------- | ----------- |
| accesstoken | string | Yes | 2b0f27f3-60e8-3763-c187-bd3dd6167e54 | 用户token（放在querystring中） type为'updateCompanyInfo'和'exchange'时必须 |
| year | string | Yes | 2015 | 年 |

- Request Example

```
http://wm.com/api/member/stats/export-engagement?accesstoken=6d662a2a-f743-9d1e-ecbd-cac445af0e04&year=2015
```

- Response Example

```
{
    "message": "success",
    "data": {
        "jobId": "132ae3ff30ee582ea6279f4897747415",
        "key": "Member Ship Engagement_20150707"
    }
}
```

# Check qrcode help

- Request Method:
POST

- Request Endpoint:
http://{server-domain}/api/member/member/check-qrcode-help

- Request Parameters:

| Name | Type | Required | Example | Description |
| ---- | ---- | -------- | ------- | ----------- |
| accesstoken | string | Yes | 2b0f27f3-60e8-3763-c187-bd3dd6167e54 | 用户token（放在querystring中） type为'updateCompanyInfo'和'exchange'时必须 |
| memberId | string | Yes | 54b5c1f8db4c0eea6e8b4568 | 会员Id |

- Request Example

```
http://wm.com/api/member/member/check-qrcode-help?tmoffset=-8&accesstoken=b32952e0-475b-3cc6-2f25-4458351bd2af
```

- Response Example

```
{
    "message": "OK",
    "data": ""
}
```

# Get member help desk record

- Request Method:
GET

- Request Endpoint:
http://{server-domain}/api/member/conversations

- Request Parameters:

| Name | Type | Required | Example | Description |
| ---- | ---- | -------- | ------- | ----------- |
| memberId | string | Yes | 54b5c1f8db4c0eea6e8b4568 | 会员Id |
| page | string | No | 1 | 默认为1 |
| per-page | string | No | 20 | 默认为20 |

- Request Example

```
http://wm.com/api/member/conversations?tmoffset=-8
```

- Response Example

```
{
    "items": [
        {
            "id": "5673afd7a94c454d6af885eb",
            "conversationId": "5670b8421837ba3cfbc2e99e",
            "desk": {
                "avatar": "/images/management/image_hover_default_avatar.png",
                "badge": "T04405",
                "email": "758302063@qq.com",
                "name": "iris",
                "id": "566e8287137473d6198b45d2"
            },
            "client": {
                "tags": [
                    "T0527",
                    "叶良辰"
                ],
                "channelInfo": {
                    "name": "群硕软件",
                    "type": "WEIBO"
                },
                "accountId": "54bf3b4c13747372268b4567",
                "channelId": "54feb1e7e4b0c154f48b7357",
                "source": "weibo",
                "openId": "ojmADuO45K-PutHOHWpdwrmrA3OU",
                "avatar": "http://tp2.sinaimg.cn/2760433581/50/40017422707/1",
                "nick": "魔杰_黑啤酒"
            },
            "startMessageId": 2,
            "endMessageId": 12,
            "lastChatTime": "2015-12-16 09:02:58",
            "createdAt": "2015-12-16 09:02:58"
        },
        {
            "id": "5670b840137473747e8b4574",
            "conversationId": "5670b8411837ba3cfbc2e99d",
            "desk": {
                "avatar": "https://dn-quncrm.qbox.me/a9df572c16aa06e62031df8a.jpg",
                "badge": "007",
                "email": "zzwcjd@163.com",
                "name": "王朗",
                "id": {
                    "$id": "566f6bfc13747330028b4567"
                }
            },
            "client": {
                "tags": [
                    "八宝",
                    "狐尔摩斯"
                ],
                "channelInfo": {
                    "name": "群硕测试2",
                    "type": "SERVICE_AUTH_ACCOUNT"
                },
                "accountId": "54bf3b4c13747372268b4567",
                "channelId": "54fd0571e4b055a0030461fb",
                "source": "wechat",
                "openId": "ojmADuO45K-PutHOHWpdwrmrA3OU",
                "avatar": "http://wx.qlogo.cn/mmopen/PiajxSqBRaEKlWxluAfFbwCmicicYL1QppiaAHu9M6wuwrnylP8IoJEXt6v5YZkeiawIt3pOJgVxria8dKpmXrOhfUNw/0",
                "nick": "Irisun"
            },
            "startMessageId": 2,
            "endMessageId": null,
            "lastChatTime": "2015-12-16 09:02:56",
            "createdAt": "2015-12-16 09:02:56"
        }
    ],
    "_links": {
        "self": {
            "href": "http://wm.com/api/member/conversations?tmoffset=-8&time=1450422749049&memberId=5672518fd6f97fa7628b4567&page=1&per-page=5"
        }
    },
    "_meta": {
        "totalCount": 2,
        "pageCount": 1,
        "currentPage": 1,
        "perPage": 5
    },
    "lastChatDate": "2015-12-17"
}
```

# Get member order stats

- Request Method:
GET

- Request Endpoint:
http://{server-domain}/api/member/order/stats

- Request Parameters:

| Name | Type | Required | Example | Description |
| ---- | ---- | -------- | ------- | ----------- |
| memberId | string | Yes | 54b5c1f8db4c0eea6e8b4568 | 会员Id |

- Request Example

```
http://wm.com/api/member/order/stats?tmoffset=-8
```

- Response Example

```
{
    "lastOperateTime": "2015-04-22",//最后购买时间
    "operateInterval": 30,//距离最后一次消费的天数
    "consumptionAmount": 120,//累计消费总额
    "consumptionAmountAvg": 180,//平均累计消费总额
    "recentConsumption": 11,//最近6个月购买次数
    "recentConsumptionAvg": 12,//最近6个月平局购买次数
    "consumption": 11,//该会员平均每次消费金额
    "consumptionAvg": 12,//总的平均每次消费金额
    "memberMaxConsumption": 11,//该会员单次最大消费额
    "maxConsumption": 12,//单次最大消费额
}
```

# Get member orders

- Request Method:
GET

- Request Endpoint:
http://{server-domain}/api/member/orders

- Request Parameters:

| Name | Type | Required | Example | Description |
| ---- | ---- | -------- | ------- | ----------- |
| expand | string | Yes | store | 请填写"store" |
| memberId | string | Yes | 54b5c1f8db4c0eea6e8b4568 | 会员Id |
| beginCreatedAt | int | No | 1441701159798 | 毫秒时间戳 |
| endCreatedAt | int | No | 1441701159798 | 毫秒时间戳 |
| page | string | No | 1 | 默认为1 |
| per-page | string | No | 20 | 默认为20 |

- Request Example

```
http://wm.com/api/member/orders?tmoffset=-8
```

- Response Example

```
{
    "items": [
        {
            "id": "55f91cd37c33067561ed0260",
            "storeId": "55250ea2d6f97fe4038b4568",
            "expectedPrice": "0.12",
            "totalPrice": "0.00",
            "staff": {
                "id": "55c1b54ad6f97f26528b4567",
                "name": ""
            },
            "consumer": {
                "id": "55ee86018fd1254278000001",
                "name": "apcavav",
                "phone": "1302778087",
                "avatar": ""
            },
            "storeGoods": [
                {
                    "id": "55b59665d6f97f050f8b4567",
                    "name": "乐事薯aa",
                    "sku": "1433846341601811",
                    "price": "0.12",
                    "count": 1,
                    "totalPrice": "0.12",
                    "pictures": [
                        "http://vincenthou.qiniudn.com/8e664c055899162b6bffaacd.jpg"
                    ]
                }
            ],
            "status": "pending",
            "payWay": "Alipay",
            "orderNumber": "201509164",
            "remark": "100 元优惠券",
            "operateTime": "0001-01-01 08:00:00",
            "createdAt": "2015-09-16 15:40:03",
            "store": {
                "id": "55250ea2d6f97fe4038b4568",
                "name": "香菜",
                "branchName": null,
                "type": null,
                "subtype": null,
                "telephone": "020-12345678",
                "location": {
                    "province": "",
                    "city": "",
                    "district": "",
                    "detail": "广东省广州市海珠区 "
                },
                "position": {
                    "latitude": 23.1008300781,
                    "longitude": 113.325248718
                },
                "image": null,
                "businessHours": null,
                "description": null,
                "wechat": {
                    "channelId": "54d9c155e4b0abe717853ee1",
                    "qrcodeId": "55250ec4e4b0e4b771cd10a6",
                    "qrcode": "https://mp.weixin.qq.com/cgi-bin/showqrcode?ticket=gQEF8DoAAAAAAAAAASxodHRwOi8vd2VpeGluLnFxLmNvbS9xL2xrZ3AzWXptZ3BuN0dMUXowbVRQAAIExA4lVQMEAAAAAA=="
                },
                "weibo": null,
                "alipay": null
            }
        }
    ],
    "_meta": {
        "totalCount": 2,
        "pageCount": 2,
        "currentPage": 1,
        "perPage": 1
    }
}
```

# create card

- Request Method:
POST

- Request Endpoint:
http://{server-domain}/api/member/cards

- Request Parameters:

| Name | Type | Required | Example | Description |
| ---- | ---- | -------- | ------- | ----------- |
| accesstoken | string | Yes | 2b0f27f3-60e8-3763-c187-bd3dd6167e54 | 用户token（放在querystring中） type为'updateCompanyInfo'和'exchange'时必须 |
| name | string | Yes | 金卡 | 卡名 |
| poster | string | Yes | http://wm.com/images/mobile/membercard.png | 背景图片 |
| fontColor | string | No | #ffffff | 字体颜色 |
| privilege | string | Yes | 8折优惠 | 特权 |
| condition | string | No | {"minScore": 0, "maxScore": 49} | 积分区间isAutoUpgrade为true时必填 |
| usageGuide | string | Yes | 刷刷刷 | 使用说明 |
| isAutoUpgrade | string | No | true | 是否可以自动升级 |
| scoreResetDate | string | Yes | {"month":1,"day":1} | 积分清0时间 |

- Request Example

```
http://wm.com/api/member/cards?tmoffset=-8&accesstoken=7ec0164d-a543-1a11-9747-a6afc678def6

{
    "name": "bbbbbbbbbb",
    "poster": "http://wm.com/images/mobile/membercard.png",
    "fontColor": "#ffffff",
    "privilege": "<p>bbbbbbbbbbbbbbb</p>",
    "condition": {
        "minScore": 0,
        "maxScore": 49
    },
    "usageGuide": "<p>bbbbbbbbbb</p>",
    "isAutoUpgrade": true,
    "scoreResetDate": null
}
```

- Response Example

```

```

# get member card list

- Request Method:
Get

- Request Endpoint:
http://{server-domain}/api/member/cards

- Request Parameters:

| Name | Type | Required | Example | Description |
| ---- | ---- | -------- | ------- | ----------- |
| accesstoken | string | Yes | 2b0f27f3-60e8-3763-c187-bd3dd6167e54 | 用户token（放在querystring中） type为'updateCompanyInfo'和'exchange'时必须 |
| where | string | No | {"isAutoUpgrade":true} | 查询条件 |
| orderBy | string | No | {"condition.minScore":"desc"} | 排序 |

- Request Example

```
http://wm.com/api/member/cards?tmoffset=-8&accesstoken=7ec0164d-a543-1a11-9747-a6afc678def6&where={"isAutoUpgrade":true}&orderBy={"condition.minScore":"desc"}
```

- Response Example

```
{
    "items": [
        {
            "id": "555c47ddd6f97f4e438b4568",
            "name": "金卡卡卡卡",
            "poster": "http://wm.com/images/mobile/membercard.png",
            "fontColor": "#ffffff",
            "privilege": "<p>什么都能干</p>",
            "condition": {
                "minScore": 101,
                "maxScore": 200
            },
            "usageGuide": "<p>我说的</p>",
            "isEnabled": true,
            "isDefault": false,
            "isAutoUpgrade": true,
            "scoreResetDate": null,
            "provideCount": 14,
            "createdAt": "2015-05-20 16:37:49",
            "updatedAt": "2015-05-20 16:37:49"
        },
        {
            "id": "5518bfadd6f97f41048b4573",
            "name": "默认会员卡",
            "poster": "/images/mobile/membercard.png",
            "fontColor": "#fff",
            "privilege": "<p>9折消费折扣</p><ul><li><p>全年消费享有正价商品9折优惠</p></li><li><p><br/></p></li></ul><p>生日礼及寿星折扣</p><ul><li><p>生日當月可憑會員卡到全省門市領取精美生日好禮,且享有壽星200元商品抵用券或8折生日優惠.</p></li><li><p>8折優惠適用於正價商品，不得與會員折扣合併使用，生日禮將不定期更換</p></li></ul>",
            "condition": {
                "minScore": 50,
                "maxScore": 100
            },
            "usageGuide": "<p>使用时向工作人员出示本会员卡即可，最终解释权归本品牌所有</p>",
            "isEnabled": true,
            "isDefault": true,
            "isAutoUpgrade": true,
            "scoreResetDate": null,
            "provideCount": 16,
            "createdAt": "2015-03-30 11:14:53",
            "updatedAt": "2015-05-20 16:40:35"
        },
        {
            "id": "55c326aed6f97f211a8b4568",
            "name": "bbbbbbbbbb",
            "poster": "http://wm.com/images/mobile/membercard.png",
            "fontColor": "#ffffff",
            "privilege": "<p>bbbbbbbbbbbbbbb</p>",
            "condition": {
                "minScore": 0,
                "maxScore": 49
            },
            "usageGuide": "<p>bbbbbbbbbb</p>",
            "isEnabled": true,
            "isDefault": false,
            "isAutoUpgrade": true,
            "scoreResetDate": {
                "month": 1,
                "day": 1
            },
            "provideCount": 0,
            "createdAt": "2015-08-06 17:19:41",
            "updatedAt": "2015-08-07 16:23:35"
        }
    ],
    "_links": {
        "self": {
            "href": "http://wm.com/api/member/cards?tmoffset=-8&accesstoken=7ec0164d-a543-1a11-9747-a6afc678def6&where=%7B%22isAutoUpgrade%22%3Atrue%7D&orderBy=%7B%22condition.minScore%22%3A%22desc%22%7D&page=1"
        }
    },
    "_meta": {
        "totalCount": 3,
        "pageCount": 1,
        "currentPage": 1,
        "perPage": 20
    }
}
```

# Set default card

- Request Method:
PUT

- Request Endpoint:
http://{server-domain}/api/member/card/set-default

- Request Parameters:

| Name | Type | Required | Example | Description |
| ---- | ---- | -------- | ------- | ----------- |
| accesstoken | string | Yes | 2b0f27f3-60e8-3763-c187-bd3dd6167e54 | 用户token（放在querystring中） type为'updateCompanyInfo'和'exchange'时必须 |
| id | string | Yes | 556e94968fd1256189000004 | 会员卡id |

- Request Example

```
http://wm.com/api/member/card/set-default?tmoffset=-8&accesstoken=7ec0164d-a543-1a11-9747-a6afc678def6

{
    "id": "556e94968fd1256189000004",
}
```

- Response Example

```
    ['message' => 'OK', 'data' => '']
```

# get the detail of score rule
- Request Method:
GET

- Request Endpoint:
http://{server-domain}/api/member/score-rule/{id}

- Request Parameters:

| Name | Type | Required | Example | Description |
| ---- | ---- | -------- | ------- | ----------- |
| expand | string | Yes | rewardType |  |

- Request Example:
```
http://{server-domain}/api/member/score-rule/5ed61f22-b8ec-8d4b-900f-68b5a111df37
```
- Response Example:
```
score:
{
    "id": "55c18aecd6f97fb4178b4569",
    "name": "perfect_information",
    "type": "time",
    "rewardType":"score"
    "score": 21,
    "triggerTime": "day",
    "description": "<p>2</p>",
    "isEnabled": true,
    "times": 0,
    "memberCount": 0,
    "properties": [],
    "startTime": "",
    "endTime": ""
}
coupon:
{
    "id": "55c18aecd6f97fb4178b4569",
    "name": "perfect_information",
    "type": "time",
    "rewardType":"coupon"
    "couponId": "55c18aecd6f97fb4178b4569",
    "triggerTime": "day",
    "description": "<p>2</p>",
    "isEnabled": true,
    "times": 0,
    "memberCount": 0,
    "properties": [],
    "startTime": "",
    "endTime": ""
}
```

# update score rule
- Request Method:
PUT

- Request Endpoint:
http://{server-domain}/api/member/score-rule/{id}

- Request Parameters:

| Name | Type | Required | Example | Description |
| ---- | ---- | -------- | ------- | ----------- |
| name | string | Yes | "birthday" |  |
| type | string | Yes | "time" or "event" |  |
| score | int | No | 1 |  |
| couponId | string | 55c18aecd6f97fb4178b456a |  |
| rewardType | strng | Yes | score |  |
| triggerTime | string | No | day or week or month |  |
| description | string | No | "describe" |
| isEnabled | boolean | No | true or false |  |
| times | int | No | 1 |  |
| memberCount | int | No | 1 |  |
| startTime | int | No | 1447742172000 |  |
| endTime | int | No | 1447742172000 |  |

- Request Example:
```
http://{server-domain}/api/member/score-rule/5ed61f22-b8ec-8d4b-900f-68b5a111df37
```
- Response Example:
```
{
    "id": "55c18aecd6f97fb4178b456a",
    "name": "birthday",
    "type": "time",
    "score": 1,
    "triggerTime": "week",
    "description": "<p>3<br/></p>",
    "isEnabled": false,
    "times": 0,
    "memberCount": 0,
    "couponId": null,
    "properties": [],
    "startTime": "",
    "endTime": "",
    "rewardType": "score"
}
```

# check the phone of member

- Request Method:
GET

- Request Endpoint:
http://{server-domain}/api/member/member/checkMember

- Request Parameters:

| Name | Type | Required | Example | Description |
| ---- | ---- | -------- | ------- | ----------- |
| accesstoken | string | Yes | 2b0f27f3-60e8-3763-c187-bd3dd6167e54 | 用户token（放在querystring中） |
| searchKey| string | yes | 12322121211 | |

- Request Example:
```
http://wm.com/api/member/member/checkMember?tmoffset=-8&accesstoken=5ed61f22-b8ec-8d4b-900f-68b5a111df37&searchkey=112222333
```
- Response Example:
```
{
    "id": "555411b8475df410738b4567",
    "card": {
        "id": "552503f9475df423548b4575",
        "name": "默认会员卡",
        "poster": "/images/mobile/membercard.png",
        "fontColor": "#fff",
        "privilege": "xxx",
        "condition": {
            "minScore": 0,
            "maxScore": 100
        },
        "usageGuide": "使用时向工作人员出示本会员卡即可，最终解释权归本品牌所有",
        "isEnabled": true,
        "isDefault": true,
        "provideCount": 1,
        "createdAt": "2015-04-08 18:33:29",
        "updatedAt": "2015-04-08 18:33:29"
    },
    "createdAt": "2015-05-14 11:08:40",
    "socialAccount": {
        "id": "54d9c155e4b0abe717853ee1",
        "name": "熊猫Baby",
        "type": "SERVICE_AUTH_ACCOUNT"
    },
    "socialMember": null,
    "properties": [
        {
            "id": "552503f9475df423548b4573",
            "type": "date",
            "name": "birthday",
            "value": 1431532800000
        }
    ],
    "accountId": "552502ed475df423548b4567",
    "cardProvideTime": "2015-05-21 09:35:11",
    "cardExpired": 0,
    "avatar": "/images/management/image_hover_default_avatar.png",
    "location": {
        "country": "中国",
        "province": "吉林",
        "city": "松原"
    },
    "tags": [],
    "score": 921,
    "remarks": null,
    "cardNumber": "10000001",
    "unionId": null,
    "totalScore": 0,
    "openIds": null,
    "cardExpiredAt": null,
    "birth": 513,
    "openId": null
}
```

# Get all channel.

- Request Method:
GET

- Request Endpoint:
http://{server-domain}/api/member/menu/channels

- Request Parameters:

| Name | Type | Required | Example | Description |
| ---- | ---- | -------- | ------- | ----------- |
| memberId | string | Yes | "55cc4fa4e4b03b1960e99bbc" | 会员Id |

- Request Example

```
http://wm.com/api/member/menu/channels?memberId=55cc4fa4e4b03b1960e99bbc
```

- Response Example

```
[
    {
        "id": "55ba03f6e9c2fbf3348b4567",
        "channelId": "54d9c155e4b0abe717853ee1",
        "origin": "wechat",
        "name": "熊猫Baby",
        "type": "SERVICE_AUTH_ACCOUNT",
        "status": "enable",
        "isTest": true,
        "openId": "oC9Aes9vuisNRmC4ZNdIXY1lb_rk",
        "memberId": "55a20fd4e9c2fb1b1a8b4567"
    },
    {
        "id": "55ba03f6e9c2fbf3348b4567",
        "channelId": "54d9c155e4b0abe717853ee1",
        "origin": "wechat",
        "name": "熊猫Baby",
        "type": "SERVICE_AUTH_ACCOUNT",
        "status": "enable",
        "isTest": true,
        "openId": "55a21063e9c2fb1a1a8b4567",
        "memberId": "55a20fd4e9c2fb1b1a8b4567"
    },
    {
        "id": "55ba03f6e9c2fbf3348b4567",
        "channelId": "54d9c155e4b0abe717853ee1",
        "origin": "wechat",
        "name": "熊猫Baby",
        "type": "SERVICE_AUTH_ACCOUNT",
        "status": "enable",
        "isTest": true,
        "openId": "55a21063e9c2fb1a1a8b4567",
        "memberId": "55a20fd4e9c2fb1b1a8b4567"
    }
]
```


# Get hits for menus.

- Request Method:
GET

- Request Endpoint:
http://{server-domain}/api/member/menu/stats-menus-hits

- Request Parameters:

| Name | Type | Required | Example | Description |
| ---- | ---- | -------- | ------- | ----------- |
| channelId | string | Yes | "55cc4fa4e4b03b1960e99bbc" | 渠道Id |
| openId | string | Yes | "55cc4fa4e4b03b1960e99bbc" | 会员社交账号Id |


- Request Example

```
http://wm.com/api/member/menu/stats-menus-hits?openId=ovPiJwR3IvaQmNkT0ZQqTWL9bpQQ&channelId=55cc4fa3e4b03b1960e99bb7
```

- Response Example

```
{
    "hitCount": 12334,
    "lastHitTime": "2015-08-05 17:05:41",
}
```


# Get menus list.

- Request Method:
GET

- Request Endpoint:
http://{server-domain}/api/member/menu/stats-menu-hits

- Request Parameters:
| Name | Type | Required | Example | Description |
| ---- | ---- | -------- | ------- | ----------- |
| channelId | string | Yes | "55cc4fa4e4b03b1960e99bbc" | 渠道Id |
| openId | string | Yes | "55cc4fa4e4b03b1960e99bbc" | 会员社交账号Id |
| page | int | No | 1 | the current page |
| per-page | int | No | 10 | show how many records in every page |
| orderby | string | No | {"hitCount":"desc"} | 排序 |

- Request Example

```
http://wm.com/api/member/menu/stats-menu-hits?channelId=55cc4fa4e4b03b1960e99bbc&openId=55cc4fa4e4b03b1960e99bbc&page=1&per-page=10&orderby={"hitCount":"desc"}
```

- Response Example

```
{
    "items": [
        {
            "id": "556e94968fd1256189000004",
            "channelId": "556e94968fd1256189000004",
            "content": "招聘1",
            "type" : "mainMenu",
            "hitCount": 20,
        },
        {
            "id": "556e94968fd1256189000004",
            "channelId": "556e94968fd1256189000004",
            "content": "招聘2",
            "type" : "subMenu",
            "hitCount": 20,
        },
        {
            "id": "556e94968fd1256189000004",
            "channelId": "556e94968fd1256189000004",
            "content": "招聘3",
            "type" : "subMenu",
            "hitCount": 20,
        }
    ],
    "_links": {
        "self": {
            "href": "http://dev.cp.augmarketing.cn/api/microsite/articles?accesstoken=3ca28e36-c949-5378-4edb-15aff6a5223d&orderBy=%7B%22createdAt%22%3A%22desc%22%7D&channels=%5B%2254b5c1f8db4c0eea6e8b4569%22%5D&page=1"
        }
    },
    "_meta": {
        "totalCount": 1,
        "pageCount": 1,
        "currentPage": 1,
        "perPage": 20
    }
}
```

# View menu.

- Request Method:
GET

- Request Endpoint:
http://{server-domain}/api/member/menu/stats-menu

- Request Parameters:

| Name | Type | Required | Example | Description |
| ---- | ---- | -------- | ------- | ----------- |
| menuId | string | Yes | 556e94968fd1256189000008 | menuId |
| channelId | string | Yes | "55cc4fa4e4b03b1960e99bbc" | 渠道Id |
| openId | string | Yes | "55cc4fa4e4b03b1960e99bbc" | 会员社交账号Id |
| page | int | No | 1 | the current page |
| per-page | int | No | 10 | show how many records in every page |
| orderby | string | No | {"hitCount":"desc"} or {"refDate":"desc"} | 排序 |

- Request Example

```
http://wm.com/api/member/menu/stats-menu?menuId=55d6d104e4b044af182620f5&openId=55cc50cfe4b03b1960e99bce&channelId=55cc50cfe4b03b1960e99bce&page=1&per-page=10&orderby={"hitCount":"desc"}
```

- Response Example

```
{
    "items": [
            {
                "refDate": 1439395200000,
                "hitCount": 9
            },
            {
                "refDate": 1439481600000,
                "hitCount": 24
            },
            {
                "refDate": 1439913600000,
                "hitCount": 1
            },
            {
                "refDate": 1440086400000,
                "hitCount": 8
            },
            {
                "refDate": 1439395200000,
                "hitCount": 8
            }
    ],
    "_meta": {
        "totalCount": 2,
        "pageCount": 1,
        "currentPage": "1",
        "perPage": "10"
    }
}
```

# Get count for interact messages.

- Request Method:
GET

- Request Endpoint:
http://{server-domain}/api/member/interact-message/stats-messages

- Request Parameters:

| Name | Type | Required | Example | Description |
| ---- | ---- | -------- | ------- | ----------- |
| channelId | string | Yes | "55cc4fa4e4b03b1960e99bbc" | 渠道Id |
| openId | string | Yes | "55cc4fa4e4b03b1960e99bbc" | 会员社交账号Id |

- Request Example

```
http://wm.com/api/member/interact-message/stats-messages?channelId=55cc4fa3e4b03b1960e99bb7&openId=ovPiJwR3IvaQmNkT0ZQqTWL9bpQQ
```

- Response Example

```
{
    "messageCount": 12334,
    "keyCount": 12,
    "lastInteractTime": "2015-08-05 17:05:41",
}
```

# Get interact messages list.

- Request Method:
GET

- Request Endpoint:
http://{server-domain}/api/member/interact-message/stats-message

- Request Parameters:

| Name | Type | Required | Example | Description |
| ---- | ---- | -------- | ------- | ----------- |
| channelId | string | Yes | "55cc4fa4e4b03b1960e99bbc" | 渠道Id |
| openId | string | Yes | "55cc4fa4e4b03b1960e99bbc" | 会员社交账号Id |
| page | int | No | 1 | the current page |
| per-page | int | No | 10 | show how many records in every page |
| ordering | string | DESC or ASC | No |

- Request Example

```
http://wm.com/api/member/interact-message/stats-message?channelId=55cc4fa3e4b03b1960e99bb7&openId=ovPiJwR3IvaQmNkT0ZQqTWL9bpQQ&page=1&per-page=10&ordering=desc
```

- Response Example

```
{
    "items": [
        {
            "id": "556e94968fd1256189000004",
            "channelId": "556e94968fd1256189000004",
            "keycode": "hello",
            "msgType": "TEXT",
            "message": "hi",
            "interactTime": "2015-08-05 17:05:41",
        },
        {
            "id": "556e94968fd1256189000004",
            "channelId": "556e94968fd1256189000004",
            "message": "hi",
            "msgType": "TEXT",
            "interactTime": "2015-08-05 17:05:41",
        }
    ],
    "_links": {
        "self": {
            "href": "http://dev.cp.augmarketing.cn/api/microsite/articles?accesstoken=3ca28e36-c949-5378-4edb-15aff6a5223d&orderBy=%7B%22createdAt%22%3A%22desc%22%7D&channels=%5B%2254b5c1f8db4c0eea6e8b4569%22%5D&page=1"
        }
    },
    "_meta": {
        "totalCount": 1,
        "pageCount": 1,
        "currentPage": 1,
        "perPage": 20
    }
}
```


# Get interact messages.

- Request Method:
GET

- Request Endpoint:
http://{server-domain}/api/member/interact-message/messages

- Request Parameters:

| Name | Type | Required | Example | Description |
| ---- | ---- | -------- | ------- | ----------- |
| channelId | string | Yes | "55cc4fa4e4b03b1960e99bbc" | 渠道Id |
| openId | string | Yes | "55cc4fa4e4b03b1960e99bbc" | 会员社交账号Id |
| page | int | No | 1 | the current page |
| per-page | int | No | 10 | show how many records in every page |

- Request Example

```
http://wm.com/api/member/interact-message/messages?channelId=55cc4fa3e4b03b1960e99bb7&openId=ovPiJwR3IvaQmNkT0ZQqTWL9bpQQ&page=1&per-page=10
```

- Response Example

```
{
    "item": [
        {
            "id": "55f623b8e4b094f7431306ee",
            "accountId": "55cc4fa3e4b03b1960e99bb7",
            "userId": "55ed5f34e4b061c1aeb869dd",
            "msgType": "TEXT",
            "direction": "RECEIVE",
            "keycode": "测试",
            "matchedRuleId": "55e6aa0ae4b06c2692378a61",
            "createTime": 1442194360652,
            "message": {
                "fromUser": "ovPiJwR3IvaQmNkT0ZQqTWL9bpQQ",
                "toUser": "gh_e29b4b82a4a9",
                "msgType": "TEXT",
                "content": "测试",
                "createTime": 1442194360652,
                "messageId": "6194177610884544454"
            },
            "sender": {
                "id": "55ed5f34e4b061c1aeb869dd",
                "accountId": "55cc4fa3e4b03b1960e99bb7",
                "subscribed": true,
                "originId": "ovPiJwR3IvaQmNkT0ZQqTWL9bpQQ",
                "nickname": "嗯哼",
                "gender": "MALE",
                "language": "zh_CN",
                "city": "南阳",
                "province": "河南",
                "country": "中国",
                "headerImgUrl": "http://wx.qlogo.cn/mmopen/Q3auHgzwzM7eMuaQaRiaa3mHtv2054NWg7cyosbu9FWqUPRiamnesYuWRqhIFEb8t8JDuWVjOLrOGHuueLO97Gf003TomlNmAtfEic7uNV6ibM0/0",
                "subscribeTime": 1441619763003,
                "unionId": null,
                "tags": [
                    "123",
                    "1234567890",
                    "TEST1"
                ],
                "userCounts": null,
                "profiles": {
                    "massSendUsageCount": null,
                    "subscribeSource": null,
                    "firstSubscribeSource": null,
                    "firstSubscribeTime": null,
                    "messages": {
                        "messageCount": null,
                        "hitKeywordCount": null,
                        "lastMessageTime": null
                    },
                    "events": {
                        "eventCount": null,
                        "lastEventTime": null
                    },
                    "menus": {
                        "hitCount": null,
                        "lastHitTime": null,
                        "items": []
                    }
                },
                "createTime": 1441619764613,
                "unsubscribeTime": null,
                "authorized": false
            }
        },
        {
            "id": "55f6236de4b094f7431306ea",
            "accountId": "55cc4fa3e4b03b1960e99bb7",
            "userId": "55ed5f34e4b061c1aeb869dd",
            "msgType": "TEXT",
            "direction": "RECEIVE",
            "matchedRuleId": "55cc4fa4e4b03b1960e99bbb",
            "createTime": 1442194285182,
            "message": {
                "fromUser": "ovPiJwR3IvaQmNkT0ZQqTWL9bpQQ",
                "toUser": "gh_e29b4b82a4a9",
                "msgType": "TEXT",
                "content": "测一测",
                "createTime": 1442194285182,
                "messageId": "6194177288761997238"
            },
            "sender": {
                "id": "55ed5f34e4b061c1aeb869dd",
                "accountId": "55cc4fa3e4b03b1960e99bb7",
                "subscribed": true,
                "originId": "ovPiJwR3IvaQmNkT0ZQqTWL9bpQQ",
                "nickname": "嗯哼",
                "gender": "MALE",
                "language": "zh_CN",
                "city": "南阳",
                "province": "河南",
                "country": "中国",
                "headerImgUrl": "http://wx.qlogo.cn/mmopen/Q3auHgzwzM7eMuaQaRiaa3mHtv2054NWg7cyosbu9FWqUPRiamnesYuWRqhIFEb8t8JDuWVjOLrOGHuueLO97Gf003TomlNmAtfEic7uNV6ibM0/0",
                "subscribeTime": 1441619763003,
                "unionId": null,
                "tags": [
                    "123",
                    "1234567890",
                    "TEST1"
                ],
                "userCounts": null,
                "profiles": {
                    "massSendUsageCount": null,
                    "subscribeSource": null,
                    "firstSubscribeSource": null,
                    "firstSubscribeTime": null,
                    "messages": {
                        "messageCount": null,
                        "hitKeywordCount": null,
                        "lastMessageTime": null
                    },
                    "events": {
                        "eventCount": null,
                        "lastEventTime": null
                    },
                    "menus": {
                        "hitCount": null,
                        "lastHitTime": null,
                        "items": []
                    }
                },
                "createTime": 1441619764613,
                "unsubscribeTime": null,
                "authorized": false
            }
        }
    ],
    "_links": {
        "self": {
            "href": "http://dev.cp.augmarketing.cn/api/microsite/articles?accesstoken=3ca28e36-c949-5378-4edb-15aff6a5223d&orderBy=%7B%22createdAt%22%3A%22desc%22%7D&channels=%5B%2254b5c1f8db4c0eea6e8b4569%22%5D&page=1"
        }
    },
    "_meta": {
        "totalCount": 1,
        "pageCount": 1,
        "currentPage": 1,
        "perPage": 20
    }
}
```

# Get article list

- Request Method:
GET

- Request Endpoint:
http://{server-domain}/api/microsite/articles

- Request Parameters:

| Name | Type | Required | Example | Description |
| ---- | ---- | -------- | ------- | ----------- |
| channels | json array | no | ["54b5c1f8db4c0eea6e8b4569", "54b5c1f8db4c0eea6e8b4569"] | If not provided, all the articles will be listed |
| orderBy | json array | no | {"createdAt":"desc"} | If not provided, the result will be ordered by createdAt(desc) as default |

- Request Example

```
http://dev.cp.augmarketing.cn/api/microsite/articles?accesstoken=3ca28e36-c949-5378-4edb-15aff6a5223d&orderBy={"createdAt":"desc"}&channels=["54b5c1f8db4c0eea6e8b4569"]
```

- Response Example

```
{
    "items": [
        {
            "id": "54b62e4edb4c0eae048b4574",
            "name": "test",
            "url": "http://www.baidu.com",
            "createdBy": "Devin",
            "picUrl": "http://ww4.sinaimg.cn/bmiddle/6106a4f0gw1eo94gprrhaj20dw0agdhe.jpg",
            "content": "abcabcabcabcabcabcabcabc",
            "fields": [],
            "createdAt": "2015-01-14 16:52:30",
            "channel": "54b5c1f8db4c0eea6e8b4569"
        }
    ],
    "_links": {
        "self": {
            "href": "http://dev.cp.augmarketing.cn/api/microsite/articles?accesstoken=3ca28e36-c949-5378-4edb-15aff6a5223d&orderBy=%7B%22createdAt%22%3A%22desc%22%7D&channels=%5B%2254b5c1f8db4c0eea6e8b4569%22%5D&page=1"
        }
    },
    "_meta": {
        "totalCount": 1,
        "pageCount": 1,
        "currentPage": 1,
        "perPage": 20
    }
}
```

# Create new article

- Request Method:
POST

- Request Endpoint:
http://{server-domain}/api/microsite/articles

- Request Parameters:

| Name | Type | Required | Example | Description |
| ---- | ---- | -------- | ------- | ----------- |
| name | string | Yes    | "abc"   | the name(or title) of the article |
| url | string | No | "http://www.baidu.com" | |
| picUrl | string | No | "/images/path/to/cover" | the url of the cover picture |
| content | string | Yes | "<p>This is the content</p>"| |
| fields | array | No | "fields": [{"name": "aaa","type": "input"},{"name": "bbb","type": "date"}] | The optional fields |
| channel | string | No | "54b5c1f8db4c0eea6e8b4569" | The channel id, empty for default channel |

- Request Example

```
{
    "name": "test",
    "url": "http://www.baidu.com",
    "picUrl": "http://ww4.sinaimg.cn/bmiddle/6106a4f0gw1eo94gprrhaj20dw0agdhe.jpg",
    "content": "abcabcabcabcabcabcabcabc",
    "fields": [
        {
            "name": "aaa",
            "type": "input"
        },
        {
            "name": "bbb",
            "type": "date"
        }
    ]
}
```

- Response Example

```
{
    "id": "54bcb51cdb4c0e7c3d8b4569",
    "name": "test",
    "url": "http://www.baidu.com",
    "createdBy": "Devin",
    "picUrl": "http://ww4.sinaimg.cn/bmiddle/6106a4f0gw1eo94gprrhaj20dw0agdhe.jpg",
    "content": "abcabcabcabcabcabcabcabc",
    "fields": [
        {
            "name": "aaa",
            "type": "input"
        },
        {
            "name": "bbb",
            "type": "date"
        }
    ],
    "createdAt": "2015-01-19 15:41:16",
    "channel": ""
}
```

# Update the acticle

- Request Method:
PUT

- Request Endpoint:
http://{server-domain}/api/microsite/article/{article_id}

- Request Parameters:

| Name | Type | Required | Example | Description |
| ---- | ---- | -------- | ------- | ----------- |
| name | string | Yes    | "abc"   | the name(or title) of the article |
| url | string | No | "http://www.baidu.com" | |
| picUrl | string | No | "/images/path/to/cover" | the url of the cover picture |
| content | string | Yes | "<p>This is the content</p>"| |
| fields | array | No | "fields": [{"name": "aaa","type": "input"},{"name": "bbb","type": "date"}] | The optional fields |
| channel | string | No | "54b5c1f8db4c0eea6e8b4569" | The channel id, empty for default channel |

- Request Example

```
{
    "name": "test",
    "url": "http://www.baidu.com",
    "picUrl": "http://ww4.sinaimg.cn/bmiddle/6106a4f0gw1eo94gprrhaj20dw0agdhe.jpg",
    "content": "abcabcabcabcabcabcabcabc",
    "fields": [
        {
            "name": "aaa",
            "type": "input"
        },
        {
            "name": "bbb",
            "type": "date"
        }
    ]
}
```

- Response Example

```
{
    "id": "54bcb51cdb4c0e7c3d8b4569",
    "name": "test",
    "url": "http://www.baidu.com",
    "createdBy": "Devin",
    "picUrl": "http://ww4.sinaimg.cn/bmiddle/6106a4f0gw1eo94gprrhaj20dw0agdhe.jpg",
    "content": "abcabcabcabcabcabcabcabc",
    "fields": [
        {
            "name": "aaa",
            "type": "input"
        },
        {
            "name": "bbb",
            "type": "date"
        }
    ],
    "createdAt": "2015-01-19 15:41:16",
    "channel": ""
}
```

# Delete article

- Request Method
DELETE

- Request Endpoint
http://{server-domain}/api/microsite/article/{article_id_list}

- Request Example
```
http://{server-domain}/api/microsite/article/54bcb51cdb4c0e7c3d8b4569,54bcb51cdb4c0e7c3d8b4569,54bcb51cdb4c0e7c3d8b4569
```

# Get article channel list

- Request Method:
GET

- Request Endpoint:
http://{server-domain}/api/microsite/article-channels

- Request Example

```
{
    "items": [
        {
            "id": "54b5c1f8db4c0eea6e8b4569",
            "name": "test",
            "fields": [
                {
                    "name": "aaa",
                    "type": "input"
                },
                {
                    "name": "bbb",
                    "type": "date"
                }
            ]
        }
    ],
    "_links": {
        "self": {
            "href": "http://dev.cp.augmarketing.cn/api/microsite/article-channels?accesstoken=8325d438-65a0-686c-59f0-9cd5205c7f7c&page=1"
        }
    },
    "_meta": {
        "totalCount": 1,
        "pageCount": 1,
        "currentPage": 1,
        "perPage": 20
    }
}
```

# Create article channel

- Request Method
GET

- Request Endpoint
http://{server-domain}/api/microsite/article-channels

- Request Parameters

| Name | Type | Required | Example | Description |
| ---- | ---- | -------- | ------- | ----------- |
| name | string | Yes | "abc"  | The name of the channel |
| fields | array | no | [{"name":"aaa", "type":"input"}, {"name":"bbb", "type":"date"}] | |

- Response Example

```
{
    "id": "54bccaf4db4c0e0b448b4569",
    "name": "test",
    "fields": [
        {
            "name": "aaa",
            "type": "input"
        },
        {
            "name": "bbb",
            "type": "date"
        }
    ]
}
```

# update article channel

- Request Method:
GET

- Request Endpoint:
http://{server-domain}/api/microsite/article-channel/{channel_id}

- Request Parameters54b5c801db4c0eae048b4571

| Name | Type | Required | Example | Description |
| ---- | ---- | -------- | ------- | ----------- |
| name | string | Yes | "abc"  | The name of the channel |
| fields | array | no | [{"id":"fdasfdsaf", "name":"aaa", "type":"input"}, {"name":"bbb", "type":"date"}] | If the field is exsisted, the id for the field is required, or it will be considered as new field |

- Response Example

```
{
    "id": "54bccaf4db4c0e0b448b4569",
    "name": "test",
    "fields": [
        {
            "id": "ffdsafvasvasdvsadvsv",
            "name": "aaa",
            "type": "input"
        },
        {
            "id": "fadsfadsfasvsadvasdv"
            "name": "bbb",
            "type": "date"
        }
    ]
}
```

# Delete Channel

- Request Method
DELETE

- Request Endpoint
http://{server-domain}/api/microsite/article-channels/{channel_id}

- Request Example
```
http://{server-domain}/api/microsite/article-channel/54bcb51cdb4c0e7c3d8b4569
```

# Get article&page list

- Request Method:
GET

- Request Endpoint:
http://{server-domain}/api/microsite/materials

- Request Parameters:

| Name | Type | Required | Example | Description |
| ---- | ---- | -------- | ------- | ----------- |
| accesstoken | string | No | 2b0f27f3-60e8-3763-c187-bd3dd6167e54 | 用户token（放在querystring中） type为'updateCompanyInfo'和'exchange'时必须 |
| per-page | string | No | 10 | 默认为10 |
| searchKey | string | No | "a" | 查找关键字 |
| timeFrom | string | No | 1428466348321 | 时间戳 |

- Request Example

```
http://wm.com/api/microsite/materials?tmoffset=-8&accesstoken=b4e4355b-9ec4-041a-77d2-e821fb96b8da&per-page=4
```

- Response Example

```
{
    "timeFrom": 1436429022171,
    "items": [
        {
            "id": "559e2c18d6f97f6e1e8b4569",
            "title": "page3",
            "url": "http://u.augmarketing.com/BPEK",
            "type": "page"
        },
        {
            "id": "559e2c04d6f97f892f8b4568",
            "title": "article4",
            "url": "http://u.augmarketing.com/BPEl",
            "type": "article"
        },
        {
            "id": "559e2af7d6f97fee3d8b456a",
            "title": "article3",
            "url": "http://u.augmarketing.com/BPEw",
            "type": "article"
        },
        {
            "id": "559e2aded6f97fee3d8b4569",
            "title": "article2",
            "url": "http://u.augmarketing.com/BPEJ",
            "type": "article"
        }
    ]
}
```

# Get article&page title by url

- Request Method:
GET

- Request Endpoint:
http://{server-domain}/api/microsite/title

- Request Parameters:

| Name | Type | Required | Example | Description |
| ---- | ---- | -------- | ------- | ----------- |
| accesstoken | string | No | 2b0f27f3-60e8-3763-c187-bd3dd6167e54 | 用户token（放在querystring中） type为'updateCompanyInfo'和'exchange'时必须 |
| url | string | No | "http%3A%2F%2Fu.augmarketing.com%2FBPEK" | urlencoded |

- Request Example

```
http://wm.com/api/microsite/material/title?tmoffset=-8&accesstoken=b4e4355b-9ec4-041a-77d2-e821fb96b8da&url=http%3A%2F%2Fu.augmarketing.com%2FBPEK
```

- Response Example

```
{
    "title": "page3"
}
```

# Send captcha

- Request Method:
POST

- Request Endpoint:
http://{server-domain}/api/mobile/send-captcha

- Request Parameters:

| Name | Type | Required | Example | Description |
| ---- | ---- | -------- | ------- | ----------- |
| accesstoken | string | No | 2b0f27f3-60e8-3763-c187-bd3dd6167e54 | 用户token（放在querystring中） type为'updateCompanyInfo'和'exchange'时必须 |
| type | string | Yes | 'bind' | 验证码的类型 'bind', 'signup','updateCompanyInfo'或'exchange' |
| mobile | string | Yes | '13027785456' | 手机号 |
| codeId | string | Yes | '2b0f27f360e83763c187bd3dd6167e54' | 图片验证码Id |
| code | string | Yes | "as2d" | 图片验证码 |
| openId | string | No | '2b0f27f360e83763c187bd3dd6167e54' | type为'bind'时必须 |
| channelId | string | No | '2b0f27f360e83763c187bd3dd6167e54' | type为'bind'时必须 |

- Request Example

```
http://dev.cp.augmarketing.cn/api/mobile/send-captcha
```

- Response Example

```
{
    "message": "OK",
    "data": ""
}
```
# redirect to detailed coupon throught oauth and receive coupon

- Request Method:
GET

- Request Endpoint:
http://{server-domain}/api/mobile/coupon

- Request Parameters:

| Name | Type | Required | Example | Description |
| ---- | ---- | -------- | ------- | ----------- |
| type | string | No | 'received' |  when you receive coupon,you need to make sure the value is received |
| channelId | string | Yes | '2b0f27f360e83763c187bd3dd6167e54' |  |
| couponId | string | Yes | '2b0f27f360e83763c187bd3dd6167e54' |  |

- Request Example

```
http://{domain-server}/api/mobile/coupon
```
# get store info  about coupon from mobile

- Request Method:
GET

- Request Endpoint:
http://{server-domain}/api/product/coupon/coupon-store

- Request Parameters:

| Name | Type | Required | Example | Description |
| ---- | ---- | -------- | ------- | ----------- |
| couponId | string | Yes | 54d9c155e4b0abe717853ee1 |  |

- Request Example

```
http://{server-domain}/api/product/coupon/coupon-store?couponId=55f662ff7baf7600dacba036
```

- Response Example

```
 {
    "id": "55f0eee3d6f97ff2708b4567",
    "type": "cash",
    "title": "代金卷",
    "total": 410,
    "limit": 1,
    "time": {
        "type": "relative",
        "beginTime": "today",
        "endTime": 10
    },
    "url": "代金卷",
    "picUrl": "http://vincenthou.qiniudn.com/601513a18f9da8dcde0a52c0.jpg",
    "tip": "操作提示",
    "description": "优惠详情",
    "usageNote": "使用须知",
    "phone": "12458654215",
    "storeType": "specify",
    "stores": [
        {
            "id": "55f0ff2dd6f97f036f8b4567",
            "name": "上海测试11",
            "branchName": "名店测试",
            "address": "天津市河东区鲁山道街道11号",
            "phone": "1233455666",
        }
    ],
    "qrcodes": [],
    "discountAmount": 8.5,
    "discountCondition": 100,
    "reductionAmount": 100
}
```
# Open coupon.

- Request Method:
GET

- Request Endpoint:
http://{server-domain}/api/mobile/open-coupon

- Request Parameters:

| Name | Type | Required | Example | Description |
| ---- | ---- | -------- | ------- | ----------- |
| couponId | string | Yes | 54d9c155e4b0abe717853ee1 | ObjectId |
| memberId | string | No | 54d9c155e4b0abe717853ee1 | ObjectId |

- Request Example

```
http://wm.com/api/mobile/open-coupon?couponId=55f662ff7baf7600dacba036&memberId=55a20fd4e9c2fb1b1a8b4567
```

- Response Example

```
{
    "id": "55f7d1e1e9c2fb3f048b4567",
    "type": "coupon",
    "title": "coupon",
    "total": 4,
    "limit": 2,
    "time": {
        "type": "relative",
        "beginTime": 0,
        "endTime": 2
    },
    "url": "",
    "picUrl": "http://vincenthou.qiniudn.com/d056cc4e45a4181bb2a6c19e.jpg",
    "tip": "011111111111",
    "description": "优惠详情",
    "usageNote": "使用须知",
    "phone": "13126565656",
    "storeType": "specify",
    "stores": [
        {
            "id": "55a75101e9c2fbbe1a8b4567",
            "name": "lydiali",
            "branchName": "",
            "address": "新疆维吾尔族自治区乌鲁木齐市乌鲁木齐县dfsdf",
            "phone": "13127972391"
        }
    ],
    "qrcodes": [],
    "discountAmount": null,
    "discountCondition": null,
    "reductionAmount": null,
    "isReceived": false,
    'message':"xxx"
}
```

# Get product list

- Request Method:
GET

- Request Endpoint:
http://{server-domain}/api/product/products

- Request Parameters:

| Name | Type | Required | Example | Description |
| ---- | ---- | -------- | ------- | ----------- |
| category | string | no | 54b5c1f8db4c0eea6e8b4569, 54b5c1f8db4c0eea6e8b4569 | If not provided, all the product will be listed |
| orderBy | json array | no | {"createdAt":"desc"} | If not provided, the result will be ordered by createdAt(desc) as default |
| searchKey | string | no | "zsho" | keyword |
| assigned | string | no | 1 | 1 or 0 |
| page | int | 1 | the current page |
| per-page | int | 10 | show how many records in every page |
| isAll | boolean | no | true or false | whether to get all products |
| storeId | string | no | 54b5c1f8db4c0eea6e8b4569 | 门店Id,会返回isStoreGoods字段 |
- Request Example

```
http://dev.cp.augmarketing.cn/api/product/products?accesstoken=3ca28e36-c949-5378-4edb-15aff6a5223d&orderBy={"createdAt":"desc"}&category=54b5c1f8db4c0eea6e8b4569,54b5c1f8db4c0eea6e8b4569&page=1&per-page=10
```

- Response Example

```
{
    "items": [
        {
            "id": "54b62e4edb4c0eae048b4574",
            "sku":"5555555555"
            "name": "test",
            "pictures":[
                {
                    "name": "图片名称",
                    "url":  "http://ww4.sinaimg.cn/bmiddle/6106a4f0gw1eo94gprrhaj20dw0agdhe.jpg",
                    "size": "10"
                },
                {
                    "name": "图片名称",
                    "url":  "http://ww4.sinaimg.cn/bmiddle/6106a4f0gw1eo94gprrhaj20dw0agdhe.jpg",
                    "size": "10"
                }
            ],
            "category": {
                "_id": "54b62e4edb4c0eae048b4574",
                "name": "类型的名称",
                "properties": [
                    {
                        "id": "111",
                        "name": "属性的名称",
                        "value": " "
                    }
                ]
            },
            "createdAt": "2015-01-14 16:52:30",
            "isStoreGoods": false //只有在传了storeId的时候会返回此字段
        }
    ],
    "_links": {
        "self": {
            "href": "http://dev.cp.augmarketing.cn/api/product/proucct?accesstoken=3ca28e36-c949-5378-4edb-15aff6a5223d&orderBy=%7B%22createdAt%22%3A%22desc%22%7D&category=%5B%2254b5c1f8db4c0eea6e8b4569%22%5D&page=1&per-page=10"
        }
    },
    "_meta": {
        "totalCount": 1,
        "pageCount": 1,
        "currentPage": 1,
        "perPage": 20
    }
}
```
# get productName by string that is maked up of id

- Request Method:
GET

- Request Endpoint:
http://{server-domain}/api/product/product/name

- Request Parameters:

| Name | Type | Required | Example | Description |
| ---- | ---- | -------- | ------- | ----------- |
| id | string | Yes | 111,333 |  |


- Response Example

```
{
    "id": "54b62e4edb4c0eae048b4574",
    "sku":"5555555555"
    "name": "test",
    "pictures":[
        {
            "name": "图片名称",
            "url":  "http://ww4.sinaimg.cn/bmiddle/6106a4f0gw1eo94gprrhaj20dw0agdhe.jpg",
            "size": "10"
        },
        {
            "name": "图片名称",
            "url":  "http://ww4.sinaimg.cn/bmiddle/6106a4f0gw1eo94gprrhaj20dw0agdhe.jpg",
            "size": "10"
        }
    ],
    "category": {
        "id": "54b62e4edb4c0eae048b4574",
        "name": "类型的名称",
        "properties": [
            {
                "id": "111",
                "name": "属性的名称",
                "value": "属性值"
            }
        ]
    },
    "intro": "xxxxxxxxxxx",
    "createdAt": "2015-01-14 16:52:30",
}
```

# Create new product

- Request Method:
POST

- Request Endpoint:
http://{server-domain}/api/product/products

- Request Parameters:

| Name | Type | Required | Example | Description |
| ---- | ---- | -------- | ------- | ----------- |
| sku | string | Yes | "112222333" | the product number |
| name | string | Yes    | "abc"   | the name(or title) of the product |
| pictures | array | No |  "pictures": [{"name": "aaa","url": "http://xxxx","size": 4567},{"name": "aaa","url": "http://xxxx","size": 4354}] | the unit of size is byte |
| category | array | Yes | "category": {"id":"1122","name":"xxx","properties":[{"id":"12","name":"title","value":"valuename"}]} | the category of the product |
| intro | string | No | 'this is a product' | the descption of the product |

- Request Example

```
{
    "sku": "1224444444",
    "name": "test",
    "pictures": [
        {"name": "aaa","url": "http://xxxx","size": 5},
        {"name": "aaa","url": "http://xxxx","size": 5}
    ],
    "category": [
        {
            "id":"54b62e4edb4c0eae048b4574",
            "name":"xxx",
            "properties":[
                {"id":"12","name":"title","value":"valuename"}
            ]
        }
    ],
    "intro": "xxxxxxxxxxx",
}
```

- Response Example

```
{
    "id": "54b62e4edb4c0eae048b4574",
    "sku":"5555555555"
    "name": "test",
    "pictures":[
        {
            "name": "图片名称",
            "url":  "http://ww4.sinaimg.cn/bmiddle/6106a4f0gw1eo94gprrhaj20dw0agdhe.jpg",
            "size": "10"
        },
        {
            "name": "图片名称",
            "url":  "http://ww4.sinaimg.cn/bmiddle/6106a4f0gw1eo94gprrhaj20dw0agdhe.jpg",
            "size": "10"
        }
    ],
    "category": {
        "id": "54b62e4edb4c0eae048b4574",
        "name": "类型的名称",
        "properties": [
            {
                "id": "111",
                "name": "属性的名称",
                "value": "属性值"
            }
        ]
    },
    "intro": "xxxxxxxxxxx",
    "createdAt": "2015-01-14 16:52:30",
}
```
# Update the product

- Request Method:
PUT

- Request Endpoint:
http://{server-domain}/api/product/product/{product_id}

- Request Parameters:

| Name | Type | Required | Example | Description |
| ---- | ---- | -------- | ------- | ----------- |
| sku | string | No | "123344" | the number of product |
| name | string | Yes    | "abc"   | the name(or title) of the product |
| pictures | array | No |  "pictures": [{"name": "aaa","url": "http://xxxx","size": 5},{"name": "aaa","url": "http://xxxx","size": 5}] | |
| category | array | Yes | "category": [{"_id":"1122","name":"xxx","properties":[{"id":"54b62e4edb4c0eae048b4584","name":"title","value":"valuename"}]}] | the category of the product |

- Request Example

```
{
    "sku": "12333",
    "name": "test",
    "pictures":[
        {
            "name": "图片名称",
            "url":  "http://ww4.sinaimg.cn/bmiddle/6106a4f0gw1eo94gprrhaj20dw0agdhe.jpg",
            "size": "10"
        },
        {
            "name": "图片名称",
            "url":  "http://ww4.sinaimg.cn/bmiddle/6106a4f0gw1eo94gprrhaj20dw0agdhe.jpg",
            "size": "10"
        }
    ],
    "category": {
        "id": "54b62e4edb4c0eae048b4574",
        "name": "类型的名称",
        "properties": [
            {
                "id": "54b62e4edb4c0eae048b4584",
                "name": "属性的名称",
                "value": "属性值"
            }
        ]
    },
    "intro": "xxxxxxxxxxx",
}
```

- Response Example

```
{
    "id": "54bcb51cdb4c0e7c3d8b4569",
    "sku": "13344",
    "name": "test",
    "pictures":[
        {
            "name": "图片名称",
            "url":  "http://ww4.sinaimg.cn/bmiddle/6106a4f0gw1eo94gprrhaj20dw0agdhe.jpg",
            "size": "10"
        },
        {
            "name": "图片名称",
            "url":  "http://ww4.sinaimg.cn/bmiddle/6106a4f0gw1eo94gprrhaj20dw0agdhe.jpg",
            "size": "10"
        }
    ],
    "category": {
        "id": "54b62e4edb4c0eae048b4574",
        "name": "类型的名称",
        "properties": [
            {
                "id": "54b62e4edb3b6eae048b4584",
                "name": "属性的名称",
                "value": "属性值"
            }
        ]
    },
    "createdAt": "2015-01-19 15:41:16",
    "intro": "xxxxxxxxxxx",
}
```
# Delete product

- Request Method
DELETE

- Request Endpoint
http://{server-domain}/api/product/product/{product_id_list}

- Request Example
```
http://{server-domain}/api/product/product/54bcb51cdb4c0e7c3d8b4569,54bcb51cdb4c0e7c3d8b4569,54bcb51cdb4c0e7c3d8b4569
```

# Get the category list from product

- Request Method:
GET

- Request Endpoint:
http://{server-domain}/api/product/product-categorys

- Request Example
```
http://dev.cp.augmarketing.cn/api/mail/product-categorys?accesstoken=3ca28e36-c949-5378-4edb-15aff6a5223d
```
- Response Example

```
{
    "items": [
        {
            "id": "54b5c1f8db4c0eea6e8b4569",
            "name": "test",
            "properties": [
                {
                    "id": "54b62e4edb3b6eae048b4584",
                    "order": 1,
                    "name": "属性的名称",
                    "type": "input",
                    "options": "",
                    "defaultValue": "test",
                    "isRequired": true,
                }
            ],
            'isDeletedCategory': true,
        },
        {
            "id": "54b5c1f8db4c0eea6e8b4569",
            "name": "test22",
            "properties": [
                {
                    "id": "54b62e4edb3b6eae048b4585",
                    "order": 2,
                    "name": "性别",
                    "type": "select",
                    "options": "男，女，保密",
                    "defaultValue": "男",
                    "isRequired": true,
                }
            ],
            'isDeletedCategory': false,
        }
    ]
}
```

# Create the category of product

- Request Method:
POST

- Request Endpoint:
http://{server-domain}/api/product/product-categorys

- Request Parameters:

| Name | Type | Required | Example | Description |
| ---- | ---- | -------- | ------- | ----------- |
| name | string | Yes    | "abc"   | the name(or title) of the product |

- Response Example

```
{
    "id": "54b5c1f8db4c0eea6e8b4569",
    "name": "test",
    "properties": []
}
```

# update the category of product

- Request Method:
PUT

- Request Endpoint:
http://{server-domain}/api/product/product-category/{category_id}

- Request Parameters

| Name | Type | Required | Example | Description |
| ---- | ---- | -------- | ------- | ----------- |
| name | string | Yes | "abc"  | The name of the channel |

- Response Example

```
{
    "id": "54b5c1f8db4c0eea6e8b4569",
    "name": "test",
    "properties": [
        {
            "id": 66,
            "order": 4,
            "name": "适合",
            "type": "radio",
            "options": ["合适，不合适"],
            "defaultValue": "合适",
            "isRequired": true,
        }，
        {
            "id": 67,
            "order": 5,
            "name": "颜色",
            "type": "input",
            "options": "",
            "defaultValue": "白色",
            "isRequired": false,
        }
    ]
    "createdAt": "2015-01-19 15:41:16",
}
```
# Delete category

- Request Method
DELETE

- Request Endpoint
http://{server-domain}/api/product/product-category/{channel_id}

- Request Example
```
http://{server-domain}/api/product/product-category/54bcb51cdb4c0e7c3d8b4569
```

# Create the proprty of category

- Request Method:
POST

- Request Endpoint:
http://{server-domain}/api/product/category-propertys/

- Request Parameters:

| Name | Type | Required | Example | Description |
| ---- | ---- | -------- | ------- | ----------- |
| categoryId | string | Yes | "552e070b475df4e2038b4574" | the category id |
| order | int | Yes    | 1   | the order of the category property |
| name | string | Yes    | "颜色"   | the name(or title) of the category property |
| type | string | Yes    | "radio"   | the type of the category property |
| options | array | No    | ["红色"，"绿色"] | |
| defaultValue | string | No | "红色" | |
| isRequired | boolean | No | true | the default value is false |

- Response Example

```
{
    "id":"552e070b438b4574uuid"
    "order": 1,
    "name": "产地",
    "type": "input",
    "options": "",
    "defaultValue": "上海",
    "isRequired": true,
}
```
# update the order of property

- Request Method

PUT

- Request Parameters

| Name | Type | Required | Example | Description |
| ---- | ---- | -------- | ------- | ----------- |
| order | string | No | {"order":{"60874b17-31f6-c9ea-bd42-382d68920bf6":1,"70624734-48bd-3766-8101-55d362be9b13":2}} | key is id and value is  the order |

- Request Endpoint:

http://{server-domain}/api/product/category-property/{category_id}

- Response Example

```
[
    "id":"5530d25913c09187498b4569",
    "properties": [
        {
            "name": "测试1",
            "type": "input",
            "defaultValue": "",
            "isRequired": true,
            "order": 1,
            "id": "60874b17-31f6-c9ea-bd42-382d68920bf6"
        },
        {
            "name": "测试2",
            "type": "date",
            "defaultValue": "",
            "isRequired": true,
            "order": 2,
            "id": "70624734-48bd-3766-8101-55d362be9b13"
        },
    ]
]
```

# update the property of category

- Request Method:
PUT

- Request Endpoint:
http://{server-domain}/api/product/category-property/{category_id}

- Request Parameters

| Name | Type | Required | Example | Description |
| ---- | ---- | -------- | ------- | ----------- |
| propertyId | string | Yes | "8325d438-65a0-686c-59f0-9cd5205c7f7"  |  the property id |
| name | string | Yes    | "颜色"   | the name(or title) of the category property |
| options | array | No    | ["红色"，"绿色"] | |
| defaultValue | string | No | "红色" | |
| isRequired | boolean | No | true | the default value is false |

- Response Example

```
{
    {
        "id": "8325d438-65a0-686c-59f0-9cd5205c7f7",
        "name": "合适",
        "options": ["合适", "不合适"],
        "defaultValue": "合适",
        "isRequired": true,
    }
}
```
# Delete property

- Request Method
DELETE

- Request Endpoint
```
http://{server-domain}/api/product/category-property/{category_id}
```
- Request Example
```
http://{server-domain}/api/product/category-property/54bcb51cdb4c0e7c3d8b4569
```
- Request Parameters

| Name | Type | Required | Example | Description |
| ---- | ---- | -------- | ------- | ----------- |
| propertyId | string | Yes | 8325d438-65a0-686c-59f0-9cd5205c7f7  |  the property id |


# Create the number of product

- Request Method:
GET

- Request Endpoint
```
http://{server-domain}/api/product/product/get-product-sku
```
- Response Example
```
{
    "number": 1234455,
}
```

# Check the promotionCode in the excel

- Request Method:
POST

- Request Endpoint:
http://{server-domain}/api/product/promotion-code/check-code

- Request Parameters:

| Name | Type | Required | Example | Description |
| ---- | ---- | -------- | ------- | ----------- |
| file | array | yes |   | file data |
| productId | string | yes | ss2234455 | product number |

- Response Example:
```
{'message' => 'OK', 'data' => ['token'=>'xxxxxxx','filename'=>'xxxxxx']}
```

# Import promotionCode

- Request Method:
POST

- Request Endpoint:
http://{server-domain}/api/product/promotion-codes

- Request Parameters:

| Name | Type | Required | Example | Description |
| ---- | ---- | -------- | ------- | ----------- |
| productId | Yes | Array | 552ca08b137473ec028b4568 | 商品Id |
| count | Yes | int | 10000 | 小于100000大于等于0 |
| filename | string | yes | bd3dd6167e5 | the file name |
| codeType | string | yes | generate or import | when generate code ,codeType is generate and upload file the codeType is import |
| import | boolean | yes | ture or false | decide to import the code |

- Response Example:
```
['message' => 'OK', 'data' => 'xxx'];
```

# Export promotionCode

- Request Method:
GET

- Request Endpoint:
```
http://{server-domain}/api/product/promotion-code/export
```
- Request Parameters:

| Name | Type | Required | Example | Description |
| ---- | ---- | -------- | ------- | ----------- |
| productId | string | Yes | 1244 |  |
| createdAt | int | Yes | 124567788 | timestamp |

- Request Example

```
http://dev.cp.augmarketing.cn/api/product/promotion-code/export
```

- Response Example

```
["result":"error","message":"xxx", "data":['jobId':'xx','key':'xx']]
["result":"success","message":key, 'data':[]]
```

# Get promotionCode history

- Request Method:
GET

- Request Endpoint:
http://{server-domain}/api/product/promotion-code/history

- Request Parameters:

| Name | Type | Required | Example | Description |
| ---- | ---- | -------- | ------- | ----------- |
| accesstoken | string | Yes | 2b0f27f3-60e8-3763-c187-bd3dd6167e54 | 用户token（放在querystring中） |
| productId | string | No 默认为1 | 5530c520d6f97f47658b456a | productId |

- Request Example

```
https://dev.quncrm.com/api/product/promotion-code/history?accesstoken=87782eb2-7799-eeb2-cb35-5025648b370e&productId=55374623d6f97f7d7e8b4567
```

- Response Example

```
[
    {
        "createdAt": "2015-04-22 07:02:43",
        "all": 10000,
        "rest": 9999,
        "used": 1,
        "timestamp": 1429657363
    }
]
```

# Delete promotionCode history

- Request Method:
POST

- Request Endpoint:
http://{server-domain}/api/product/promotion-code/del-history

- Request Parameters:

| Name | Type | Required | Example | Description |
| ---- | ---- | -------- | ------- | ----------- |
| accesstoken | string | Yes | 2b0f27f3-60e8-3763-c187-bd3dd6167e54 | 用户token（放在querystring中） |
| productId | string | Yes | 5530c520d6f97f47658b456a | productId |
| createdAt | timestamp | YES | 1429657363 | 时间戳创建时间 |

- Request Example

```
https://dev.quncrm.com/api/product/promotion-code/del-history?accesstoken=87782eb2-7799-eeb2-cb35-5025648b370e
```

- Response Example

```
{
    "message": "OK",
    "data": null
}
```

# exchange the promotion code
- Request Method:
    POST

- Request Endpoint:
    http://wm.com/api/product/promotion-code/exchange?accesstoken=62882071-8b3c-ad1b-3f85-0831f2246b87

- Request Parameters:

| Name | Type | Required | Example | Description |
| ---- | ---- | -------- | ------- | ----------- |
| code | string or array | Yes | 2b0f27f3-60e8,bd3dd6167e54 | how namy codes must be pass a array |
| memberId | string | Yes | 2b0f27f3 | member id |
| channelId | string | no | 2b0f27f3 or '' | channel id |
| exchangeTime | int | no | 155224745222 | time(毫秒) |

- Response Example:

success response
```
{
   "result":"success",
   "message":"success"
}
```
fail response
```
{
    "result":"error",
    "message":"code is invailid",
    'code':'xx'
}
```
# check the promotion code

 - Request Method
 GET
 - Request Endpoint:
 http://wm.com/api/product/promotion-code/check?accesstoken=62882071-8b3c-ad1b-3f85-0831f2246b87&coe=555411b8475df410738b4567,555411b8475df410738b4566

- Request Parameters:

| Name | Type | Required | Example | Description |
| ---- | ---- | -------- | ------- | ----------- |
| code | string | Yes | 2b0f27f3,bd3dd6167e54 | promotion code |

- Response Example:
```
success reponse:
{
    "data": {
        ['code':'TEST58','score':0,'status':'invaild','description':'无效'],
        ['code':'TEST59','score':0,'status':'expired','description':'过期']，
        ['code':'TEST60','score':0,'status':'redeemed','description':'已经兑换过了']
        ['code':'TEST62','score':10,'status':'vaild','description':'有效']，
        ['code':'TEST61','score':0,'status':'exceeded','description':'超过活动限制']
    }
}
fail response:
['result':'error','message':'xxx','code':'123344']
```
# clear the exchange the record in cache

- Request Method
 GET
 - Request Endpoint:
 http://wm.com/api/product/promotion-code/clear-exchange-record?accesstoken=62882071-8b3c-ad1b-3f85-0831f2246b87&memberId=555411b8475df410738b4566&code=555411b8475df410738b4567

- Request Parameters:

| Name | Type | Required | Example | Description |
| ---- | ---- | -------- | ------- | ----------- |
| code | string | No | 2b0f27f3 | promotion code |
| memberId | string | Yes | 555411b8475df410738b4566 | member id |

# analysic the promotion code
- Request Method:
GET

- Request Endpoint:

```
http://{server-domain}/api/product/promotion-cod-analysis/index?accesstoken=20dd7ac2-e24e-2a55-4290-a1cd18754f00
```
- Request Parameters:

| Name | Type | Required | Example | Description |
| ---- | ---- | -------- | ------- | ----------- |
| type | int or array | no | 1 | the type of analysis,if you pass a array,you will get data from the type in the array  |
| startDate | int | yes | 122344 |  |
| endDate | int | yes | 1332 |  |
| campaignId | int | yes | 1333 |  |

- Response Example:

```
{
    "1": {
        "categories": [
            "2015-07-09"
        ],
        "series": [
            {
                "name": "两个促销活动同时绑定一个商品",
                "data": [
                    1
                ]
            },
            {
                "name": "total_participate_title",
                "data": [
                    1
                ]
            }
        ]
    },
    "2": {
        "categories": [
            "2015-07-09",
            "2015-07-10",
            "2015-07-11",
            "2015-07-12",
            "2015-07-13",
            "2015-07-14",
            "2015-07-15"
        ],
        "series": [
            {
                "name": "两个促销活动同时绑定一个商品",
                "data": [
                    2,
                    2,
                    2,
                    2,
                    2,
                    2,
                    2
                ]
            }
        ]
    },
    "3": {
        "categories": [
            "2015-07-09"
        ],
        "series": [
            {
                "name": "两个促销活动同时绑定一个商品",
                "data": [
                    5
                ]
            }
        ]
    }
}
```

# export the promotion code log
- Request Method:
GET

- Request Endpoint:

```
http://{server-domain}/api/product/promotion-code-analysis/export
```
- Request Parameters:

| Name | Type | Required | Example | Description |
| ---- | ---- | -------- | ------- | ----------- |
| type | int | Yes | 1 | the type of analysis |
| startDate | int | yes | 122344 |  |
| endDate | int | yes | 1332 |  |
| campaignId | int | yes | 1333 |  |
| campaignName | string | yes | campaign name |  |

- Response Example:

```
{
    "result": "success",
    "message": "exporting file",
    "data": {
        "jobId": "44d89c05999e6f59d51980ac3c68f0b5",
        "key": "婴儿毛线帽_20150908_1441678595244369"
    }
}
```

# export the promotion code log for all campaign
- Request Method:
GET

- Request Endpoint:

```
http://{server-domain}/api/product/promotion-code-analysis/export-campaign-analysis
```
- Request Parameters:

| Name | Type | Required | Example | Description |
| ---- | ---- | -------- | ------- | ----------- |
| type | int | Yes | 1 | the type of analysis |
| startDate | int | yes | 122344 |  |
| endDate | int | yes | 1332 |  |

- Response Example:

```
{
    "result": "success",
    "message": "exporting file",
    "data": {
        "jobId": "44d89c05999e6f59d51980ac3c68f0b5",
        "key": "婴儿毛线帽_20150908_1441678595244369"
    }
}
```

# Check the resque

- Request Method:
GET

- Request Endpoint:

```
http://{server-domain}/api/product/promotion-code/get-status?accesstoken=20dd7ac2-e24e-2a55-4290-a1cd18754f00&token=7b905ae0c406cd6cec23b27bff472ef5
```
- Request Parameters:

| Name | Type | Required | Example | Description |
| ---- | ---- | -------- | ------- | ----------- |
| token | string | yes | 7b905ae0c406cd6cec23b27bff472ef5 | the rersqu id |
| productId | string | yes | 7b905ae0c406cd6cec23b27bff472ef5 | the product number |
| filename | string | yes | 7b905ae0c406cd6cec23b | upload file name |

- Response Example:

```
This will return the status of specified job by passing its token.

Resque_Job_Status::STATUS_WAITING = 1;

Resque_Job_Status::STATUS_RUNNING = 2;

Resque_Job_Status::STATUS_FAILED = 3;

Resque_Job_Status::STATUS_COMPLETE = 4;

wrong=-1;//user upload wrong product id

{'message' => 'OK', 'status' => 4, 'wrong' =>0, 'right'=> 100}
```

# Delete the promotion code resque

- Request Method:
GET

- Request Endpoint:
```
http://{server-domain}/api/product/promotion-code/clear-cache?accesstoken=20dd7ac2-e24e-2a55-4290-a1cd18754f00&filename=6cd6cec23&productId=7b905ae0c406cd6cec23b27bff472ef5
```
- Request Parameters:

| Name | Type | Required | Example | Description |
| ---- | ---- | -------- | ------- | ----------- |
| filename | string | yes | 6cd6cec23 | the filename |
| productId | string | yes | 7b905ae0c406cd6cec23b27bff472ef5 | the product number |

- Response Example:

```
{'message'=>'OK', 'data' => 'id'}

```

# generate email template for exchange score

 - Request Method
 post
 - Request Endpoint:
 http://wm.com/api/product/message/generate-email-template?accesstoken=62882071-8b3c-ad1b-3f85-0831f2246b87

- Request Parameters:

| Name | Type | Required | Example | Description |
| ---- | ---- | -------- | ------- | ----------- |
| memberId | string | Yes | 2b0f27f3,bd3dd6167e54 |  |
| data | array | yes | [{productName,sku,num,score},{productName,sku,num,score}] |  |
| exchangeScore | int | Yes | the number of score to exchange |
| type | string | promocode or redemption | yes | the type of template |

- Response Example:
```
success reponse:
{
   "template":"xxxxxxxxx"
}
```

# send email with exchange score

 - Request Method
 post
 - Request Endpoint:
 http://wm.com/api/product/message/send-redemption-email?accesstoken=62882071-8b3c-ad1b-3f85-0831f2246b87

- Request Parameters:

| Name | Type | Required | Example | Description |
| ---- | ---- | -------- | ------- | ----------- |
| memberId | string | Yes | 2b0f27f3,bd3dd6167e54 |  |
| template | string | yes | 10 |  |
| subject | string | Yes |  |

- Response Example:
```
true or false
```

# generate phone emplate for exchange score

 - Request Method
 post
 - Request Endpoint:
 http://wm.com/api/product/message/generate-mobile-template?accesstoken=62882071-8b3c-ad1b-3f85-0831f2246b87

- Request Parameters:

| Name | Type | Required | Example | Description |
| ---- | ---- | -------- | ------- | ----------- |
| memberId | string | Yes | 2b0f27f3,bd3dd6167e54 |  |
| type | string | Yes | promocode or redemption |  |

- Response Example:
```
success reponse:
{
   "template":"xxxxxxxxx"
}
```

# send phone message with exchange score

 - Request Method
 post
 - Request Endpoint:
 http://wm.com/api/product/message/send-redemption-message?accesstoken=62882071-8b3c-ad1b-3f85-0831f2246b87

- Request Parameters:

| Name | Type | Required | Example | Description |
| ---- | ---- | -------- | ------- | ----------- |
| memberId | string | Yes | 2b0f27f3,bd3dd6167e54 |  |
| message| string | yes | 10 |  |

- Response Example:
```
true or false
```

# get the campaignLog

- Request Method:
    GET

- Request Endpoint:
    http://wm.com/api/product/campaign-logs?accesstoken=62882071-8b3c-ad1b-3f85-0831f2246b87&memberId=555411b8475df410738b4567

- Request Parameters:

| Name | Type | Required | Example | Description |
| ---- | ---- | -------- | ------- | ----------- |
| accesstoken | string | Yes | 2b0f27f3-60e8-3763-c187-bd3dd6167e54 |  |
| memberId | string | No | 2b0f27f3 | member id |
| filter | string | No | 'score' or 'lotter' ... |   |

- Response Example:
```
{
    "items": [
        {
            "id": "555449c7475df4950c8b4569",
            "code": "23333332",
            "member": {
                "id": "555411b8475df410738b4567",
                "cardNumber": "10000001",
                "name": "xxx",
                "type": "score",
                "scoreAdded": "-11",
                "score": 10,
                "prize": "sss"
            },
            "usedFrom": {
                "id": "54d9c155e4b0abe717853ee1",
                "name": "熊猫Baby",
                "type": "weibo"
            },
            "usedTime": "2015-05-14 08:07:51"
        },
    ],
    "_links": {
        "self": {
            "href": "http://wm.com/api/product/campaign-logs?accesstoken=62882071-8b3c-ad1b-3f85-0831f2246b87&memberId=555411b8475df410738b4567&page=1"
        }
    },
    "_meta": {
        "totalCount": 4,
        "pageCount": 1,
        "currentPage": 1,
        "perPage": 20
    }
}
```

# get the total prize of promotion code

- Request Method:
    Get

- Request Endpoint:
    http://wm.com/api/product/campaign-log/total?accesstoken=62882071-8b3c-ad1b-3f85-0831f2246b87&memberId=555411b8475df410738b4567

- Request Parameters:

| Name | Type | Required | Example | Description |
| ---- | ---- | -------- | ------- | ----------- |
| accesstoken | string | Yes | 2b0f27f3-60e8-3763-c187-bd3dd6167e54 | |
| memberId | string | Yes | 2b0f27f3 | member id |

- Response Example

```
{
    "scoreNum": 1,
    "prizeNum": 2
}
```

# Get all productAssociation

- Request Method:
Get

- Request Endpoint:
http://{server-domain}/api/product/associations
- Request Parameters:

| Name | Type | Required | Example | Description |
| ---- | ---- | -------- | ------- | ----------- |
| accesstoken | string | Yes | 2b0f27f3-60e8-3763-c187-bd3dd6167e54 | 用户token（放在querystring中） |
| page | int | No 默认为1 | 2 | 第几页 |
| per-page | int | No 默认为20 | 10 | 每页条数 |
| orderBy | string | No | {"name":"asc"} | 排序 |
| searchKey | string | No | zxc | 查找 |
| giftType | array | No | score | "score" 或者 "lottery"结果包含没有赠品的关联 |

- Request Example

```
http://dev.quncrm.com/api/associations
```

- Response Example

```
{
    "items": [
        {
            "id": "55363362d6f97f06048b4572",
            "productId": "5530c520d6f97f47658b456a",
            "used": 0,
            "all": 1000,
            "rest": 0,
            "isAssociated": true,
            "productName": "test",
            "sku": "XXXXXXXXXXXX",
            "gift": {
                "type": "score",
                "config": {
                    "method": "times",
                    "number": 2
                }
            }
        }
    ],
    "_links": {
        "self": {
            "href": "http://wm.com/api/product/associations?tmoffset=-8&accesstoken=87782eb2-7799-eeb2-cb35-5025648b370e&page=1"
        }
    },
    "_meta": {
        "totalCount": 1,
        "pageCount": 1,
        "currentPage": 0,
        "perPage": 20
    }
}
```

# Create productAssociation

- Request Method:
POST

- Request Endpoint:
http://{server-domain}/api/product/associations

- Request Parameters:

| Name | Type | Required | Example | Description |
| ---- | ---- | -------- | ------- | ----------- |
| accesstoken | string | Yes | 2b0f27f3-60e8-3763-c187-bd3dd6167e54 | 用户token（放在querystring中） |
| productId | Yes | Array | 552ca08b137473ec028b4568 | 商品Id |
| count | Yes | int | 10000 | 小于100000大于等于0 |
| gift | Array | Yes | [] | 赠品配置如下 |
| gift.type | string | Yes | score | 赠品类型, score或者lottery |
| gift.config | Array | Yes | 如下 | 赠品规则 |
| gift.config.method | string | No | "scale" | gift.type为lottery时为scale或者number gift.type为score时为times或score |
| gift.config.number | int | No | 100 | gift.type为score时必填 倍数或积分 |
| gift.config.prize | array | No | [] | 如下 |
| gift.config.prize.name | string | No | "泰迪熊一只" | gift.type为lottery时必填 奖品名 |
| gift.config.prize.number | int | No | 20 | gift.type为lottery时必填 奖品数目或中奖人数比例 |
| filename | string | yes | bd3dd6167e5 | the file name |
| codeType | string | yes | generate or import | when generate code ,codeType is generate and upload file the codeType is import |
| import | boolean | yes | ture or false | decide to import the code |


- Request Example

```
http://dev.quncrm.com/api/product/associations

{
    "productId":"5530c520d6f97f47658b456a",
    "count": 1000,
    "gift": {
        "type": "score",
        "config": {
           "method": "times",
           "number": 2
        }
    }
}
```

- Response Example

```
{
    "message": "OK",
    "data": null
}
```

# Update productAssociation

- Request Method:
PUT

- Request Endpoint:
http://{server-domain}/api/product/association/{associationId}

- Request Parameters:

| Name | Type | Required | Example | Description |
| ---- | ---- | -------- | ------- | ----------- |
| accesstoken | string | Yes | 2b0f27f3-60e8-3763-c187-bd3dd6167e54 | 用户token（放在querystring中） |
| productId | Yes | Array | 552ca08b137473ec028b4568 | 商品Id |
| count | Yes | int | 10000 | 小于100000大于等于0 |
| gift | Array | Yes | [] | 赠品配置如下 |
| gift.type | string | Yes | score | 赠品类型, score或者lottery |
| gift.config | Array | Yes | 如下 | 赠品规则 |
| gift.config.method | string | No | "scale" | gift.type为score时为scale或者number gift.type为lottery时为times或score |
| gift.config.number | int | No | 100 | gift.type为score时必填 倍数或积分 |
| gift.config.prize | array | No | [] | 如下 |
| gift.config.prize.name | string | No | "泰迪熊一只" | gift.type为lottery时必填 奖品名 |
| gift.config.prize.number | int | No | 20 | gift.type为lottery时必填 奖品数目或中奖人数比例 |
| filename | string | yes | bd3dd6167e5 | the file name |
| codeType | string | yes | generate or import | when generate code ,codeType is generate and upload file the codeType is import |
| import | boolean | yes | ture or false | decide to import the code |
- Request Example

```
http://dev.quncrm.com/api/product/associations

{
    "productId":"5530c520d6f97f47658b456a",
    "count": 1000,
    "gift": {
        "type": "score",
        "config": {
           "method": "times",
           "number": 2
        }
    }
}
```

- Response Example

```
{
    "message": "OK",
    "data": null
}
```

# Delete productAssociation by Id

- Request Method:
DELETE

- Request Endpoint:
http://{server-domain}/api/product/association/{associationId}

- Request Parameters:

| Name | Type | Required | Example | Description |
| ---- | ---- | -------- | ------- | ----------- |
| accesstoken | string | Yes | 2b0f27f3-60e8-3763-c187-bd3dd6167e54 | 用户token（放在querystring中） |

- Request Example

```
http://dev.quncrm.com/api/product/association/552ca08b137473ec028b4568

```

- Response Example

```
{
    "message": "OK",
    "data": null
}
```

# Get Address

- Request Method:
GET

- Request Endpoint:
http://{server-domain}/api/product/goods-exchange-log/address/{productId}

- Request Parameters:

| Name | Type | Required | Example | Description |
| ---- | ---- | -------- | ------- | ----------- |
| accesstoken | string | Yes | 2b0f27f3-60e8-3763-c187-bd3dd6167e54 | 用户token（放在querystring中） |

- Request Example

```
http://wm.com/api/product/goods-exchange-log/address/55925bc58fd1250647000001?tmoffset=-8&accesstoken=761206f8-9de2-1a50-2cd9-78ed704a00f2
```

- Response Example

```
{
    "address": "中国湖北黄冈埃索达"
}
```

# Create a receive address

- Request Method:
POST

- Request Endpoint:
http://{server-domain}/api/product/receive-addresss

- Request Parameters:

| Name | Type | Required | Example | Description |
| ---- | ---- | -------- | ------- | ----------- |
| address | string | Yes | 上海浦东新区张江店 | address name |
| phone | string | Yes | 0699-78687888 | phone number |
| location  | object | Yes |  | the detail address |

- Request Example

```
POST

http://wm.com/api/product/receive-addresss

{
    "address":"上海浦东新区张江店",
    "phone": "0699-78687888",
    "location": {
        "province" : "上海市",
        "city" : "浦东新区",
        "district" : "张江镇",
        "detail" : "郭守敬路"
    }
}
```

- Response Example

```
{
    "id": "5620c3e6971374846e8b456d",
    "address":"上海浦东新区张江店",
    "phone": "0699-78687888",
    "location": {
        "province" : "上海市",
        "city" : "浦东新区",
        "district" : "张江镇",
        "detail" : "郭守敬路"
    }
}
```

# update a exist receive address

- Request Method:
PUT

- Request Endpoint:
http://{server-domain}/api/product/receive-address/{id}

- Request Parameters:

| Name | Type | Required | Example | Description |
| ---- | ---- | -------- | ------- | ----------- |
| address | string | Yes | 上海浦东新区张江店 | address name |
| phone | string | Yes | 0699-78687888 | phone number |
| location  | object | Yes |  | the detail address |

- Request Example

```
PUT

http://wm.com/api/product/receive-address/5620c3e6971374846e8b456d

{
    "address":"上海浦东新区张江店",
    "phone": "0699-78687888",
    "location": {
        "province" : "上海市",
        "city" : "浦东新区",
        "district" : "张江镇",
        "detail" : "郭守敬路"
    }
}
```

- Response Example

```
{
   "id": "5620c3e6971374846e8b456d",
    "address":"上海浦东新区张江店",
    "phone": "0699-78687888",
    "location": {
        "province" : "上海市",
        "city" : "浦东新区",
        "district" : "张江镇",
        "detail" : "郭守敬路"
    }
}
```

# delete a exist receive address

- Request Method:
DELETE

- Request Endpoint:
http://{server-domain}/api/product/receive-address/{id}

- Request Parameters:

| Name | Type | Required | Example | Description |
| ---- | ---- | -------- | ------- | ----------- |


- Request Example

```
DELETE

http://wm.com/api/product/receive-address/5620c3e6971374846e8b456d

```

- Response Example

```

```


# get a exist receive address

- Request Method:
GET

- Request Endpoint:
http://{server-domain}/api/product/receive-address/{id}

- Request Parameters:

| Name | Type | Required | Example | Description |
| ---- | ---- | -------- | ------- | ----------- |


- Request Example

```
GET

http://wm.com/api/product/receive-address/5620c3e6971374846e8b456d

```

- Response Example

```
{
    "id": "5620c3e6971374846e8b456d",
    "address":"上海浦东新区张江店",
    "phone": "0699-78687888",
    "location": {
        "province" : "上海市",
        "city" : "浦东新区",
        "district" : "张江镇",
        "detail" : "郭守敬路"
    }
}
```

# get receive address list

- Request Method:
GET

- Request Endpoint:
http://{server-domain}/api/product/receive-addresss

- Request Parameters:

| Name | Type | Required | Example | Description |
| ---- | ---- | -------- | ------- | ----------- |


- Request Example

```
GET

http://wm.com/api/product/receive-addresss?unlimited=1

```

- Response Example

```
{
    "items": [
        {
            "id": "562f40bc971374e2488b456e",
            "address": "安徽太湖",
            "location": {
                "province": "安徽省",
                "city": "安庆市",
                "district": "太湖县",
                "detail": "江塘"
            },
            "phone": "１８６１５４５０６２８"
        },
        {
            "id": "562f1a2397137431048b4568",
            "address": "上海浦东新区张江店",
            "location": {
                "province": "上海市",
                "city": "松江区",
                "district": "漕河泾",
                "detail": ""
            },
            "phone": "11111111111"
        }
    ]
}
```

# Get img captcha

- Request Method:
GET

- Request Endpoint:
http://{server-domain}/api/captcha/index

- Request Parameters:

| Name | Type | Required | Example | Description |
| ---- | ---- | -------- | ------- | ----------- |

- Request Example

```
http://wm.com/api/captcha/index
```

- Response Example

```
{
    "message": "OK",
    "data": "data:image/jpeg;base64,/9j/4AAQ...",
    "codeId": "55a74a29d6f97f98268b456f"
}
```

# Refresh Token

- Endpoint

    **GET** /api/site/refresh-token

- Parameters

    | name | type | description | required |
    |------|-----|-------|---------|
    | accesstoken | String | 用户token（放在querystring中） | Yes |

- Response body

    ```json
    {
        "accessToken":"f47a91a0-4984-85a2-897f-e528a07222a5"
    }
    ```

# Get store list

- Request Method:
GET

- Request Endpoint:
http://{server-domain}/api/channel/offlinestore/store/index

- Request Parameters:

| Name | Type | Required | Example | Description |
| ---- | ---- | -------- | ------- | ----------- |
| orderBy | json array | no | {"createdAt":"desc"} | If not provided, the result will be ordered by createdAt(desc) as default |
| per-page | int | no | 20 | Page size. Default value is 20 |
| page | int | no | 1 | Page Number. Default value is 1 |

- Request Example

```
http://dev.cp.augmarketing.cn/api/channel/offlinestore/store/index?accesstoken=3ca28e36-c949-5378-4edb-15aff6a5223d&orderBy={"createdAt":"desc"}&page=1&per-page=20
```

- Response Example

```
{
    "items": [
        {
            "id":               "54d2c22f2736e73d058b456b"，
            "name":             "某公司"，               //门店名
            "branchName":       "国贸店"，               //分店名
            "type":             "购物"，                 //门店服务类型
            "subtype":          "数码家电"，              //门店子服务类型
            "telephone"：       "021-8921222"，          //门店电话
            "location": {       //门店地理位置(自己host全国地理信息)
                province:   "上海"，
                city:       "上海"，
                district:   "浦东新区"，
                detail:     "郭守敬路598号"
            }，
            position:""，         //定位
            image:"http://vincenthou.qiniudn.com/a80d4d78d1536b0c1de91222.jpg"， //门店图片
            businessHours: "10:00-20:00"，
            description:""，
            wechat: {
                channelId:"54d9c155e4b0abe717853ee1"，
                qrcodeId:"54eeef15e4b0068a6eefbbbb"，
                qrcode:"https://mp.weixin.qq.com/cgi-bin/showqrcode?ticket=gQEl8ToAAAAAAAAAASxodHRwOi8vd2VpeGluLnFxLmNvbS9xL05VamU0Y1htWjVrZXpoY2RKV1RQAAIEh760VAMEAAAAAA=="
            }
            weibo: {
                channelId:"54f51cefe4b0c5896e262375"，
                qrcodeId:"54f66124e4b0bc118cb053af"，
                qrcode:"https://mp.weixin.qq.com/cgi-bin/showqrcode?ticket=gQFs8DoAAAAAAAAAASxodHRwOi8vd2VpeGluLnFxLmNvbS9xL2NVaXlza0htR3BsalhWTmZTV1RQAAIEhzKqVAMEAAAAAA=="
            }
        }
    ],
    "_links": {
        "self": {
            "href": "http://dev.cp.augmarketing.cn/api/offline-store/stores?accesstoken=3ca28e36-c949-5378-4edb-15aff6a5223d&orderBy=%7B%22createdAt%22%3A%22desc%22%7D&page=1&per-page=20"
        }
    },
    "_meta": {
        "totalCount": 1,
        "pageCount": 1,
        "currentPage": 1,
        "perPage": 20
    }
}
```

# Get a store info

- Request Method:
GET

- Request Endpoint:
http://{server-domain}/api/channel/offlinestore/store/view/{store_id}

- Request Parameters:

| Name | Type | Required | Example | Description |
| ---- | ---- | -------- | ------- | ----------- |

- Request Example

```
http://dev.cp.augmarketing.cn/api/channel/offlinestore/store/view/3ca28e36-c949-5378-4edb-15aff6a5223
```

- Response Example

```
{
    "id":               "54d2c22f2736e73d058b456b"，
    "name":             "某公司"，               //门店名
    "branchName":       "国贸店"，               //分店名
    "type":             "购物"，                 //门店服务类型
    "subtype":          "数码家电"，              //门店子服务类型
    "telephone"：       "021-8921222"，          //门店电话
    "location": {       //门店地理位置(自己host全国地理信息)
        "province":   "上海"，
        "city":       "上海"，
        "district":   "浦东新区"，
        "detail":     "郭守敬路598号"
    }，
    "position":""，         //定位
    "image":"http://vincenthou.qiniudn.com/a80d4d78d1536b0c1de91222.jpg"， //门店图片
    "businessHours": "10:00-20:00"，
    "description":""，
    "wechat": {
        "channelId":"54d9c155e4b0abe717853ee1"，
        "qrcodeId":"54eeef15e4b0068a6eefbbbb"，
        "qrcode":"https://mp.weixin.qq.com/cgi-bin/showqrcode?ticket=gQEl8ToAAAAAAAAAASxodHRwOi8vd2VpeGluLnFxLmNvbS9xL05VamU0Y1htWjVrZXpoY2RKV1RQAAIEh760VAMEAAAAAA=="
    }
    "weibo": {
        "channelId":"54f51cefe4b0c5896e262375"，
        "qrcodeId":"54f66124e4b0bc118cb053af"，
        "qrcode":"https://mp.weixin.qq.com/cgi-bin/showqrcode?ticket=gQFs8DoAAAAAAAAAASxodHRwOi8vd2VpeGluLnFxLmNvbS9xL2NVaXlza0htR3BsalhWTmZTV1RQAAIEhzKqVAMEAAAAAA=="
    }
}
```

# Get a store statistic

- Request Method:
GET

- Request Endpoint:
http://{server-domain}/api/channel/offlinestore/store/statistic

- Request Parameters:

| Name | Type | Required | Example | Description |
| ---- | ---- | -------- | ------- | ----------- |
| storeId | string | yes | 54d028b22736e752508b4569 | Store Id |

- Request Example

```
http://dev.cp.augmarketing.cn/api/channel/offlinestore/store/statistic?storeId=54d028b22736e752508b4569
```

- Response Example

```
{
    "wechat": {
        "scanNumber":22565,
        "followNumber":5565
    },
    "weibo": {
        "scanNumber":20565,
        "followNumber":5165
    }
}
```

# Get a store analysis data

- Request Method:
GET

- Request Endpoint:
http://{server-domain}/api/channel/offlinestore/store/analysis

- Request Parameters:

| Name | Type | Required | Example | Description |
| ---- | ---- | -------- | ------- | ----------- |
| storeId | string | yes | 54d028b22736e752508b4569 | Store Id |

- Request Example

```
http://dev.cp.augmarketing.cn/api/channel/offlinestore/store/analysis?storeId=54d028b22736e752508b4569
```

- Response Example

```
{
    "wechat": {
        "statDate": ["2015-01-01", "2015-01-02", "2015-01-03", "2015-01-04", "2015-01-05", "2015-01-06", "2015-01-07"],
        "scanNumber": [11, 15, 35, 89, 90, 80, 10],
        "followNumber": [13, 18, 34, 49, 30, 82, 20]
    },
    "weibo": {
        "statDate": ["2015-01-01", "2015-01-02", "2015-01-03", "2015-01-04", "2015-01-05", "2015-01-06", "2015-01-07"],
        "scanNumber": [11, 15, 35, 89, 90, 80, 10],
        "followNumber": [13, 18, 34, 49, 30, 82, 20]
    }
}
```

# Sync store data from wechat

- Request Method:
POST

- Request Endpoint:
http://{server-domain}/api/channel/offlinestore/store/sync

- Request Parameters:

| Name | Type | Required | Example | Description |
| ---- | ---- | -------- | ------- | ----------- |

- Request Example

```
http://dev.cp.augmarketing.cn/api/channel/offlinestore/store/sync
```

- Response Example

```
{
    "finished": false,
    "token": "ba1dfb1e2f20a938cbbe5accfd4a845d"
}
```

# Sync store data to wechat

- Request Method:
POST

- Request Endpoint:
http://{server-domain}/api/channel/offlinestore/store/push

- Request Parameters:

| Name | Type | Required | Example | Description |
| ---- | ---- | -------- | ------- | ----------- |
| channelIds | array | yes | ['54d028b22736e752508b4569'] | Channel id list |
| storeIds | array | yes | ['54d028b22736e752508b4569'] | Store id list |

- Request Example

```
http://dev.cp.augmarketing.cn/api/channel/offlinestore/store/push
```

- Response Example

```
{
    "finished": false,
    "token": "ba1dfb1e2f20a938cbbe5accfd4a845d"
}
```

# Check whether sync finish

- Request Method:
GET

- Request Endpoint:
http://{server-domain}/api/channel/offlinestore/store/check-sync

- Request Parameters:

| Name | Type | Required | Example | Description |
| ---- | ---- | -------- | ------- | ----------- |
| token | string | yes | 'ba1dfb1e2f20a938cbbe5accfd4a845d' | check aync token |
- Request Example

```
http://dev.cp.augmarketing.cn/api/channel/offlinestore/store/check-sync
```

- Response Example

* finished is false, means it's still running.
* finished is true, means it's complete.
* fail is true, means it's failed.

```
{
    "finished": false
}
```

# get the list of the staff

- Request Method:
GET

- Request Endpoint:
http://{server-domain}/api/store/staffs

- Request Parameters:

| Name | Type | Required | Example | Description |
| ---- | ---- | -------- | ------- | ----------- |
| accesstoken | string | yes | 2b0f27f3-60e8-3763-c187-bd3dd6167e54 | |
| where | json array | no | {"email":"harrysun@augmentum.com.cn"} |  |

- Request Example

```
http://wm.com/api/store/staffs?tmoffset=-8&accesstoken=92717035-cc5e-b5ea-b698-495fcb3697db
```

- Response Example

```
{
    "items": [
        {
            "id": "54b62e4edb4c0eae048b4574",
            "storeId":"54b62e4edb4c0eae048"
            "phone":"5555555555"
            "name": "test",
            "badge": "T45302",
            "gender":"male",
            "birthday":"5555555555"
            "channel":{
                "channelType":"服务号",
                "channelName":"测试号",
                "channelId":"渠道id"
            }
            "isEnabled":ENABLE,
            "updatedAt":"2015-01-14 16:52:30",
            'qrcodeUrl': "https://mp.weixin.qq.com/cgi-bin/showqrcode?ticket=gQG28DoAAAAAAAAAASxodHRwOi8vd2VpeGluLnFxLmNvbS9xL3EwaW9CeVhtRzVsaVZvbnhVMlRQAAIECLakVQMEAAAAAA==",
            "createdAt": "2015-01-14 16:52:30",
        },
        {
            "id": "54b62e4edb4c0eae048b4574",
            "storeId":"54b62e4edb4c0eae048"
            "phone":"5555555555"
            "name": "test",
            "badge": "T45302",
            "gender":"male",
            "birthday":"5555555555"
            "channel":{
                "channelType":"服务号",
                "channelName":"测试号",
                "channelId":"渠道id"
            }
            'qrcodeUrl': "https://mp.weixin.qq.com/cgi-bin/showqrcode?ticket=gQG28DoAAAAAAAAAASxodHRwOi8vd2VpeGluLnFxLmNvbS9xL3EwaW9CeVhtRzVsaVZvbnhVMlRQAAIECLakVQMEAAAAAA==",
            "isEnabled":ENABLE,
            "updatedAt":"2015-01-14 16:52:30",
            "createdAt": "2015-01-14 16:52:30",
        }
    ],
    "_links": {
        "self": {
            "href": "http://dev.cp.augmarketing.cn/api/store/staffs?accesstoken=3ca28e36-c949-5378-4edb-15aff6a5223d&orderBy=%7B%22createdAt%22%3A%22desc%22%7D&category=%5B%2254b5c1f8db4c0eea6e8b4569%22%5D&page=1&per-page=10"
        }
    },
    "_meta": {
        "totalCount": 1,
        "pageCount": 1,
        "currentPage": 1,
        "perPage": 20
    }
}
```

# create the staff

- Request Method:
POST

- Request Endpoint:
http://{server-domain}/api/store/staffs
- Request Parameters:

| Name | Type | Required | Example | Description |
| ---- | ---- | -------- | ------- | ----------- |
| accesstoken | string | yes | 2b0f27f3-60e8-3763-c187-bd3dd6167e54 | |
| phone | string | yes | 13444777 |  |
| channel | array | yes | {"channelType":"服务号","channelName":"渠道名称","channelId":"渠道id"} |  |
| badge | string | yes | t4011 |  |
| storeId | string | Yes | store id |


- Request Example

```
http://wm.com/api/store/staffs?tmoffset=-8&accesstoken=92717035-cc5e-b5ea-b698-495fcb3697db
```

- Response Example

```
{
   "result":"success or fail"
}
```

# update the staff

- Request Method:
PUT

- Request Endpoint:
http://{server-domain}/api/store/staff/{id}
- Request Parameters:

| Name | Type | Required | Example | Description |
| ---- | ---- | -------- | ------- | ----------- |
| accesstoken | string | yes | 2b0f27f3-60e8-3763-c187-bd3dd6167e54 | |
| username | string | yes | xiao |  |
| gender | string | yes | female |  |
| birthday | string | yes | 11111111111000 | 毫秒 |
| isEnabled | true | no | true |  |


- Request Example

```
http://wm.com/api/store/staff/bd3dd6167e54?tmoffset=-8&accesstoken=92717035-cc5e-b5ea-b698-495fcb3697db
```

# delete the staff

- Request Method:
DELETE

- Request Endpoint:
http://{server-domain}/api/store/staff/{id}
- Request Parameters:

| Name | Type | Required | Example | Description |
| ---- | ---- | -------- | ------- | ----------- |
| accesstoken | string | yes | 2b0f27f3-60e8-3763-c187-bd3dd6167e54 | |

- Request Example

```
http://wm.com/api/store/staff/bd3dd6167e54?tmoffset=-8&accesstoken=92717035-cc5e-b5ea-b698-495fcb3697db
```

# Get a store info

- Request Method:
GET

- Request Endpoint:
http://{server-domain}/api/store/store/{store_id}

- Request Parameters:

| Name | Type | Required | Example | Description |
| ---- | ---- | -------- | ------- | ----------- |

- Request Example

```
http://dev.cp.augmarketing.cn/api/store/store/5524739c137473c86c8b4567
```

- Response Example

```
{
    "id":               "54d2c22f2736e73d058b456b"，
    "name":             "某公司"，               //门店名
    "branchName":       "国贸店"，               //分店名
    "type":             "购物"，                 //门店服务类型
    "subtype":          "数码家电"，              //门店子服务类型
    "telephone"：       "021-8921222"，          //门店电话
    "location":         "上海浦东新区郭守敬路598号"，
    "position":""，         //定位
    "image":"http://vincenthou.qiniudn.com/a80d4d78d1536b0c1de91222.jpg"， //门店图片
    "businessHours": "10:00-20:00"，
    "description":""，
    "wechat": {
        "channelId":"54d9c155e4b0abe717853ee1"，
        "qrcodeId":"54eeef15e4b0068a6eefbbbb"，
        "qrcode":"https://mp.weixin.qq.com/cgi-bin/showqrcode?ticket=gQEl8ToAAAAAAAAAASxodHRwOi8vd2VpeGluLnFxLmNvbS9xL05VamU0Y1htWjVrZXpoY2RKV1RQAAIEh760VAMEAAAAAA=="
    },
    "weibo": {
        "channelId":"54f51cefe4b0c5896e262375"，
        "qrcodeId":"54f66124e4b0bc118cb053af"，
        "qrcode":"https://mp.weixin.qq.com/cgi-bin/showqrcode?ticket=gQFs8DoAAAAAAAAAASxodHRwOi8vd2VpeGluLnFxLmNvbS9xL2NVaXlza0htR3BsalhWTmZTV1RQAAIEhzKqVAMEAAAAAA=="
    },
    "storeGoods": {
        "total" : 300,
        "onSaleTotal": 260,
        "offSaleTotal": 40
    },
    "staff": {
        "total": 300,
        "onlineTotal": 240,
        "offlineTotal": 60
    }
}
```

# Get storeGoods list

- Request Method:
GET

- Request Endpoint:
http://{server-domain}/api/store/goods/index

- Request Parameters:

| Name | Type | Required | Example | Description |
| ---- | ---- | -------- | ------- | ----------- |
| accesstoken | string | Yes | 2b0f27f3-60e8-3763-c187-bd3dd6167e54 | 用户token（放在querystring中） |
| storeId | string | Yes | "55363362d6f97f06048b4576" | 门店id |
| searchKey | string | No | 'a' | 查找关键字 |
| categoryIds | string | No | "55363362d6f97f06048b4576,55363362d6f97f06048b457a" | 类别id |
| status | string | No | "on" | on或者off |
| saleTimeFrom |  string | no | "1436947027687" | 时间戳(毫秒) |
| saleTimeTo |  string | no | "1436947027687" | 时间戳(毫秒) |
| priceFrom |  string | no | "12.0" | 价格区间（小值） |
| priceTo |  string | no | "15.2" | 价格区间（大值） |

- Request Example

```
http://wm.com/api/store/goods/index?tmoffset=-8&accesstoken=081c2f43-62b9-2dfa-ace3-781d27c711ca
```

- Response Example

```
{
    "items": [
        {
            "id": "55a61053d6f97f7b3d8b4569",
            "productId": "5576c25ed6f97fe84c8b4567",
            "pictures": [
                "http://vincenthou.qiniudn.com/8e664c055899162b6bffaacd.jpg"
            ],
            "status": "off",
            "onSaleTime": "",
            "categoryName": "asd",
            "productName": "乐事薯片",
            "sku": "1433846341601811",
            "storeId": "55753c71d6f97fe3338b4567",
            "price": 12.00
            "offShelfTime": ""
        }
    ],
    "_links": {
        "self": {
            "href": "http://wm.com/api/store/goodss?tmoffset=-8&accesstoken=081c2f43-62b9-2dfa-ace3-781d27c711ca&page=1"
        }
    },
    "_meta": {
        "totalCount": 1,
        "pageCount": 1,
        "currentPage": 1,
        "perPage": 20
    }
}
```

# Create storeGoods

- Request Method:
POST

- Request Endpoint:
http://{server-domain}/api/store/goods/create

- Request Parameters:

| Name | Type | Required | Example | Description |
| ---- | ---- | -------- | ------- | ----------- |
| accesstoken | string | Yes | 2b0f27f3-60e8-3763-c187-bd3dd6167e54 | 用户token（放在querystring中） |
| goods | array | Yes | [{"productId": "5576c25ed6f97fe84c8b4567", "price": 12.00}] | Store goods |
| storeId | string | Yes | 5576c25ed6f97fe84c8b4567 | 门店Id |

- Request Example

```
http://wm.com/api/store/goods/create?tmoffset=-8&accesstoken=081c2f43-62b9-2dfa-ace3-781d27c711ca

{
    "storeId": "55a61053d6f97f7b3d8b4569",
    "goods":[
        {
            "productId": "5576c25ed6f97fe84c8b4567",
            "price":12.00
        }
     ]
}
```

- Response Example

```
{
    "message": "OK",
    "data": null
}
```

# Update storeGoods

- Request Method:
PUT

- Request Endpoint:
http://{server-domain}/api/store/goods/update/{storeGoodsId}

- Request Parameters:

| Name | Type | Required | Example | Description |
| ---- | ---- | -------- | ------- | ----------- |
| accesstoken | string | Yes | 2b0f27f3-60e8-3763-c187-bd3dd6167e54 | 用户token（放在querystring中） |
| status | string | Yes | on, off | 状态 |
| price | float | Yes | 12.00 | 商品价格 |
| pictures | array | No | ["http://vincenthou.qiniudn.com/52bb59a2fb73449030ac384a.jpg"] | 图片 |
| onSaleTime | string | No | 1436947027687 | 上架时间 |

- Request Example

```
http://wm.com/api/store/goods/create/55a61053d6f97f7b3d8b4569?tmoffset=-8&accesstoken=081c2f43-62b9-2dfa-ace3-781d27c711ca

{
    "pictures": [
        "http://vincenthou.qiniudn.com/52bb59a2fb73449030ac384a.jpg",
        "http://vincenthou.qiniudn.com/15cd5e41c3b574dc81ed1662.jpg"
    ],
    "status": "on",
    "price": 12.00
    "onSaleTime": "1436947027687"
}
```

- Response Example

```
{
    "id": "55a706c2d6f97f76508b4568",
    "storeId": "5576c25ed6f97fe84c8b4567",
    "productId": "5576c25ed6f97fe84c8b4567",
    "pictures": [
        "http://vincenthou.qiniudn.com/8e664c055899162b6bffaacd.jpg"
    ],
    "status": "on",
    "onSaleTime": "2015-07-17 09:21",
    "categoryName": "asd",
    "productName": "乐事薯片",
    "sku": "1433846341601811",
    "price": 12.00
    "offShelfTime": ""
}
```

# Delete storeGoods

- Request Method:
DELETE

- Request Endpoint:
http://{server-domain}/api/store/goods/delete/{storeGoods}

- Request Parameters:

| Name | Type | Required | Example | Description |
| ---- | ---- | -------- | ------- | ----------- |
| accesstoken | string | Yes | 2b0f27f3-60e8-3763-c187-bd3dd6167e54 | 用户token（放在querystring中） |

- Request Example

```
http://wm.com/api/store/goods/delete/55a61053d6f97f7b3d8b4569,55a61053d6f97f7b3d8b456a?tmoffset=-8&accesstoken=081c2f43-62b9-2dfa-ace3-781d27c711ca
```

- Response Example

```

```

# Get storeGoods by Id

- Request Method:
GET

- Request Endpoint:
http://{server-domain}/api/store/goods/view/{storeGoodsId}

- Request Parameters:

| Name | Type | Required | Example | Description |
| ---- | ---- | -------- | ------- | ----------- |
| accesstoken | string | Yes | 2b0f27f3-60e8-3763-c187-bd3dd6167e54 | 用户token（放在querystring中） |

- Request Example

```
http://wm.com/api/store/goods/view/55a6132fd6f97f7b3d8b456a?tmoffset=-8&accesstoken=081c2f43-62b9-2dfa-ace3-781d27c711ca
```

- Response Example

```
{
    "id": "55a6132fd6f97f7b3d8b456a",
    "productId": "55753c71d6f97fe3338b4567",
    "pictures": [
        "http://vincenthou.qiniudn.com/52bb59a2fb73449030ac384a.jpg",
        "http://vincenthou.qiniudn.com/15cd5e41c3b574dc81ed1662.jpg"
    ],
    "status": "off",
    "onSaleTime": "",
    "categoryName": "asd",
    "price": 12.00,
    "productName": "友陈肉松饼",
    "sku": "1433746522779750",
    "description": null,
    "storeId": "55753c71d6f97fe3338b4567",
    "offShelfTime": ""
}
```

# Sale storeGoods

- Request Method:
PUT

- Request Endpoint:
http://{server-domain}/api/store/goods/sale

- Request Parameters:

| Name | Type | Required | Example | Description |
| ---- | ---- | -------- | ------- | ----------- |
| accesstoken | string | Yes | 2b0f27f3-60e8-3763-c187-bd3dd6167e54 | 用户token（放在querystring中） |
| storeGoodsIds |  array | No | ["55a61053d6f97f7b3d8b4569"] | 商品Id |
| status |  string | Yes | on, off | 状态 |
| onSaleTime |  string | No | 1436947027687 | 上架时间 |

- Request Example

```
http://wm.com/api/store/goods/sale?tmoffset=-8&accesstoken=081c2f43-62b9-2dfa-ace3-781d27c711ca

{
    "storeGoodsIds": [
        "55a61053d6f97f7b3d8b4569"
    ],
    "status": "on",
    "onSaleTime": "1436947027687"
}
```

- Response Example

```
{
    "message": "OK",
    "data": null
}
```
# order info

- Request Method:
GET

- Request Endpoint:
http://{server-domain}/api/store/orders

- Request Parameters:

| Name | Type | Required | Example | Description |
| ---- | ---- | -------- | ------- | ----------- |
| accesstoken | string | Yes | 2b0f27f3-60e8-3763-c187-d6167e54 | 用户token |
| storeId | string | Yes | "55363362d6f97f06048b4576" | 门店id |
| orderNumber| string | No | 'a' | order number|
| status | string | No | 'finished,pending,canceled' | status of order,if you select all,you can not pass this param |
| beginCreatedAt | string | No | begin time |  |
| endCreatedAt | string | No | end time |  |
| minAmount | int | No | 11 |  |
| maxAmount | int | No | 22 |  |
| staff | string | no | '店员名称' |  |
| member | string | no | '会员名称或匿名名称' |  |

- Request Example

```
http://wm.com/api/store/orders?tmoffset=-8&accesstoken=081c2f43-62b9-2dfa-ace3-781d27c711ca
```

- Response Example

```
{
    "items": [
        {
            "id": "55b6e719475df4c51d8b4569",
            "storeId": "552b763b475df406048b4569",
            "totalPrice": "10.00",
            "expectedPrice": "10.00",
            "staff": {
                "id": "55b1e06e475df47a6f8b4567",
                "name": "测试账号"
            },
            "consumer": {
                "id": "55b19f53475df4f07b8b4567",
                "name": "会员信息",
                "phone": "13799228651",
                "avatar": "xxxx"
            },
            "storeGoods": [
                {
                    "id": "55b083cb475df49e568b4567",
                    "sku": "143805007379",
                    "pictures": ['xxx','yy'],
                    "price": "10.00",
                    "count": 1,
                    "totalPrice": "10.00"
                }
            ],
            "status": "finished",
            "payWay": null,
            "operateTime": "2015-07-28 10:21:13",
            "createdAt": "2015-07-28 10:21:13"
        }
    ],
    "_links": {
        "self": {
            "href": "http://wm.com/api/store/orders?tmoffset=-8&accesstoken=081c2f43-62b9-2dfa-ace3-781d27c711ca&page=1"
        }
    },
    "_meta": {
        "totalCount": 1,
        "pageCount": 1,
        "currentPage": 1,
        "perPage": 20
    }
}
```

# get order info by id

- Request Method:
GET

- Request Endpoint:
http://{server-domain}/api/store/order/view/{orderId}

- Request Parameters:

| Name | Type | Required | Example | Description |
| ---- | ---- | -------- | ------- | ----------- |
| accesstoken | string | Yes | 2b0f27f3-60e8-3763-c187-bd3dd6167e54 | 用户token（放在querystring中） |

- Request Example

```
http://wm.com/api/store/orders/view/55a6132fd6f97f7b3d8b456a?tmoffset=-8&accesstoken=081c2f43-62b9-2dfa-ace3-781d27c711ca
```

- Response Example

```
 {
    "id": "55b6e719475df4c51d8b4569",
    "storeId": "552b763b475df406048b4569",
    "totalPrice": "10.00",
    "expectedPrice": "10.00",
    "staff": {
        "id": "55b1e06e475df47a6f8b4567",
        "name": "测试账号"
    },
    "consumer": {
        "id": "55b19f53475df4f07b8b4567",
        "name": "会员信息",
        "phone": "13799228651",
        "avatar": "xxxx"
    },
    "storeGoods": [
        {
            "id": "55b083cb475df49e568b4567",
            "sku": "143805007379",
            "pictures": ['xxx','yy'],
            "price": "10.00",
            "count": 1,
            "totalPrice": "10.00"
        }
    ],
    "status": "finished",
    "payWay": null,
    "operateTime": "2015-07-28 10:21:13",
    "createdAt": "2015-07-28 10:21:13"
}
```
# update status of order

- Request Method:
PUT

- Request Endpoint:
http://{server-domain}/api/store/order/{orderId}

- Request Parameters:

| Name | Type | Required | Example | Description |
| ---- | ---- | -------- | ------- | ----------- |
| accesstoken | string | Yes | 2b0f27f3-60e8-3763-c187-bd3dd6167e54 | 用户token（放在querystring中） |
| status | string | Yes | finished, cancel | 状态 |

- Request Example

```
http://wm.com/api/store/order/2343455566?tmoffset=-8&accesstoken=081c2f43-62b9-2dfa-ace3-781d27c711ca
```

# Operator avg FT vs PULL

- Request Method:
GET

- Request Endpoint:
http://{server-domain}/api/uhkklp/stats-property/product-operator-avg

- Request Parameters:

| Name | Type | Required | Example | Description |
| ---- | ---- | -------- | ------- | ----------- |
| accesstoken | string | No | 2b0f27f3-60e8-3763-c187-bd3dd6167e54 | 用户token（放在querystring中） type为'updateCompanyInfo'和'exchange'时必须 |
| year | string | Yes | 2015 | 年 |

- Request Example

```
http://wm.com/api/uhkklp/stats-property/product-operator-avg?tmoffset=-8&accesstoken=92717035-cc5e-b5ea-b698-495fcb3697db&year=2015
```

- Response Example

```
{
    "date": [
        "Q1",
        "Q2",
        "Q3",
        "Q4"
    ],
    "data": {
        "Pull": [
            0,
            0.33,
            0,
            0
        ],
        "Free Trade": [
            0,
            1,
            0,
            0
        ]
    }
}
```

# Export Operator avg

- Request Method:
GET

- Request Endpoint:
http://{server-domain}/api/uhkklp/stats-property/export-product-operator-avg

- Request Parameters:

| Name | Type | Required | Example | Description |
| ---- | ---- | -------- | ------- | ----------- |
| accesstoken | string | No | 2b0f27f3-60e8-3763-c187-bd3dd6167e54 | 用户token（放在querystring中） type为'updateCompanyInfo'和'exchange'时必须 |
| year | string | Yes | 2015 | 年 |

- Request Example

```
http://wm.com/api/uhkklp/stats-property/export-product-operator-avg?accesstoken=6d662a2a-f743-9d1e-ecbd-cac445af0e04&year=2015
```

- Response Example

```
{
    "message": "success",
    "data": {
        "jobId": "07d4c94968a1ef0e34a41f0b08a06667",
        "key": "SKU per Operator_20150707"
    }
}
```

# KLP Channel Penetration in Volume

- Request Method:
GET

- Request Endpoint:
http://{server-domain}/api/uhkklp/stats-property/product-code

- Request Parameters:

| Name | Type | Required | Example | Description |
| ---- | ---- | -------- | ------- | ----------- |
| accesstoken | string | No | 2b0f27f3-60e8-3763-c187-bd3dd6167e54 | 用户token（放在querystring中） type为'updateCompanyInfo'和'exchange'时必须 |
| year | string | Yes | 2015 | 年 |
| quarter | string | Yes | 2 | 季度 |

- Request Example

```
http://wm.com/api/uhkklp/stats-property/product-code?tmoffset=-8&accesstoken=92717035-cc5e-b5ea-b698-495fcb3697db&year=2015&quarter=2
```

- Response Example

```
{
    "data": {
        "WR": 234,
        "T2": 1734,
        "Canteen": 1000,
        "Others": 232
    }
}
```

# export KLP Channel Penetration in Volume

- Request Method:
GET

- Request Endpoint:
http://{server-domain}/api/uhkklp/stats-property/export-product-code

- Request Parameters:

| Name | Type | Required | Example | Description |
| ---- | ---- | -------- | ------- | ----------- |
| accesstoken | string | No | 2b0f27f3-60e8-3763-c187-bd3dd6167e54 | 用户token（放在querystring中） type为'updateCompanyInfo'和'exchange'时必须 |
| year | string | Yes | 2015 | 年 |
| quarter | string | Yes | 2 | 季度 |

- Request Example

```
http://wm.com/api/uhkklp/stats-property/export-product-code?tmoffset=-8&accesstoken=92717035-cc5e-b5ea-b698-495fcb3697db&year=2015&quarter=2
```

- Response Example

```
{
    "message": "success",
    "data": {
        "jobId": "f354dbb56fe971ceaaf757674f43bf7e",
        "key": "KLP_Channel_Penetration_in_Volume_20150708"
    }
}
```

# KLP Channel Penetration in Acct

- Request Method:
GET

- Request Endpoint:
http://{server-domain}/api/uhkklp/stats-property/member-participant

- Request Parameters:

| Name | Type | Required | Example | Description |
| ---- | ---- | -------- | ------- | ----------- |
| accesstoken | string | No | 2b0f27f3-60e8-3763-c187-bd3dd6167e54 | 用户token（放在querystring中） type为'updateCompanyInfo'和'exchange'时必须 |
| year | string | Yes | 2015 | 年 |
| quarter | string | Yes | 2 | 季度 |

- Request Example

```
http://wm.com/api/uhkklp/stats-property/member-participant?tmoffset=-8&accesstoken=92717035-cc5e-b5ea-b698-495fcb3697db&year=2015&quarter=2
```

- Response Example

```
{
    "data": {
        "WR": 234,
        "T2": 1734,
        "Canteen": 1000,
        "Others": 232
    }
}
```

# export KLP Channel Penetration in Acct

- Request Method:
GET

- Request Endpoint:
http://{server-domain}/api/uhkklp/stats-property/export-member-participant

- Request Parameters:

| Name | Type | Required | Example | Description |
| ---- | ---- | -------- | ------- | ----------- |
| accesstoken | string | No | 2b0f27f3-60e8-3763-c187-bd3dd6167e54 | 用户token（放在querystring中） type为'updateCompanyInfo'和'exchange'时必须 |
| year | string | Yes | 2015 | 年 |
| quarter | string | Yes | 2 | 季度 |

- Request Example

```
http://wm.com/api/uhkklp/stats-property/export-member-participant?tmoffset=-8&accesstoken=92717035-cc5e-b5ea-b698-495fcb3697db&year=2015&quarter=2
```

- Response Example

```
{
    "message": "success",
    "data": {
        "jobId": "f354dbb56fe971ceaaf757674f43bf7e",
        "key": "KLP_Channel_Penetration_in_acct_20150708"
    }
}
```

# Promotion SKU Summary

- Request Method:
GET

- Request Endpoint:
http://{server-domain}/api/uhkklp/stats-promotion/product

- Request Parameters:

| Name | Type | Required | Example | Description |
| ---- | ---- | -------- | ------- | ----------- |
| accesstoken | string | No | 2b0f27f3-60e8-3763-c187-bd3dd6167e54 | 用户token（放在querystring中） type为'updateCompanyInfo'和'exchange'时必须 |
| year | string | Yes | 2015 | 年 |
| quarter | string | Yes | 2 | 季度 |

- Request Example

```
http://wm.com/api/uhkklp/stats-promotion/product?tmoffset=-8&accesstoken=92717035-cc5e-b5ea-b698-495fcb3697db&year=2015&quarter=2
```

- Response Example

```
{
    "products": [
        "WR",
        "T2",
        "Canteen",
        "Others"
    ],
    "data": [
        1243,
        2334,
        5423,
        34563
    ]
}
```

# export Promotion SKU Summary

- Request Method:
GET

- Request Endpoint:
http://{server-domain}/api/uhkklp/stats-promotion/export-product

- Request Parameters:

| Name | Type | Required | Example | Description |
| ---- | ---- | -------- | ------- | ----------- |
| accesstoken | string | No | 2b0f27f3-60e8-3763-c187-bd3dd6167e54 | 用户token（放在querystring中） type为'updateCompanyInfo'和'exchange'时必须 |
| year | string | Yes | 2015 | 年 |
| quarter | string | Yes | 2 | 季度 |

- Request Example

```
http://wm.com/api/uhkklp/stats-promotion/export-product?tmoffset=-8&accesstoken=92717035-cc5e-b5ea-b698-495fcb3697db&year=2015&quarter=2
```

- Response Example

```
{
    "message": "success",
    "data": {
        "jobId": "f354dbb56fe971ceaaf757674f43bf7e",
        "key": "Promotion_SKU_Summary_20150708"
    }
}
```

# FT and Pull Participant Summary

- Request Method:
GET

- Request Endpoint:
http://{server-domain}/api/uhkklp/stats-property/member-monthly

- Request Parameters:

| Name | Type | Required | Example | Description |
| ---- | ---- | -------- | ------- | ----------- |
| accesstoken | string | No | 2b0f27f3-60e8-3763-c187-bd3dd6167e54 | 用户token（放在querystring中） type为'updateCompanyInfo'和'exchange'时必须 |
| start | string | Yes | 1420041600000 | 时间戳 |
| end | string | Yes | 1435680000000 | 时间戳 |

- Request Example

```
http://wm.com/api/uhkklp//stats-property/member-monthly?tmoffset=-8&accesstoken=92717035-cc5e-b5ea-b698-495fcb3697db&start=1420041600000&end=1435680000000
```

- Response Example

```
{
    "data": {
        "Pull": [
            3,
            24
        ],
        "Free Trade": [
            0,
            0
        ]
    },
    "month": [
        "2015-05",
        "2015-06"
    ]
}
```

# Export FT and Pull Participant Summary

- Request Method:
GET

- Request Endpoint:
http://{server-domain}/api/uhkklp/stats-property/export-member-monthly

- Request Parameters:

| Name | Type | Required | Example | Description |
| ---- | ---- | -------- | ------- | ----------- |
| accesstoken | string | Yes | 2b0f27f3-60e8-3763-c187-bd3dd6167e54 | 用户token（放在querystring中） type为'updateCompanyInfo'和'exchange'时必须 |
| start | string | Yes | 1420041600000 | 时间戳 |
| end | string | Yes | 1435680000000 | 时间戳 |

- Request Example

```
http://wm.com/api/uhkklp/stats-property/export-member-monthly?accesstoken=6d662a2a-f743-9d1e-ecbd-cac445af0e04&start=1420070400000&end=1438387200000
```

- Response Example

```
{
    "message": "success",
    "data": {
        "jobId": "4541429d091d2ce687ef09e52cc33d3d",
        "key": "FT_and_Pull_Participant_Summary_20150707"
    }
}
```


# SKU Summary Pull vs FT

- Request Method:
GET

- Request Endpoint:
http://{server-domain}/api/uhkklp/stats-property/code-avg-quarterly

- Request Parameters:

| Name | Type | Required | Example | Description |
| ---- | ---- | -------- | ------- | ----------- |
| accesstoken | string | No | 2b0f27f3-60e8-3763-c187-bd3dd6167e54 | 用户token（放在querystring中） type为'updateCompanyInfo'和'exchange'时必须 |
| year | string | Yes | 2015 | 年 |
| quarter | string | Yes | 2 | 季度 |

- Request Example

```
http://wm.com/api/uhkklp/stats-property/code-avg-quarterly?tmoffset=-8&accesstoken=92717035-cc5e-b5ea-b698-495fcb3697db&year=2015&quarter=2
```

- Response Example

```
{
    "data": {
        "Pull": [
            7,
            8
        ],
        "Free Trade": [
            7.5,
            4
        ]
    },
    "productName": [
        "澳門3天2夜自由行2人同行",
        "活动"
    ]
}
```

# Export SKU Summary Pull vs FT

- Request Method:
GET

- Request Endpoint:
http://{server-domain}/api/uhkklp/stats-property/export-code-avg-quarterly

- Request Parameters:

| Name | Type | Required | Example | Description |
| ---- | ---- | -------- | ------- | ----------- |
| accesstoken | string | No | 2b0f27f3-60e8-3763-c187-bd3dd6167e54 | 用户token（放在querystring中） type为'updateCompanyInfo'和'exchange'时必须 |
| year | string | Yes | 2015 | 年 |
| quarter | string | Yes | 2 | 季度 |

- Request Example

```
http://wm.com/api/uhkklp/stats-property/export-code-avg-quarterly?tmoffset=-8&accesstoken=92717035-cc5e-b5ea-b698-495fcb3697db&year=2015&quarter=2
```

- Response Example

```
{
    "message": "success",
    "data": {
        "jobId": "4541429d091d2ce687ef09e52cc33d3d",
        "key": "SKU_Summary_Pull_vs_FT_20150707"
    }
}
```

