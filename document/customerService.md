# Sequence Diagram

## Helpdesk Sequence Diagram

![Helpdesk Sequence Diagram](http://vdemo.qiniudn.com/helpdesk-new.png)

## Wechat User Sequence Diagram

![Wechat User Sequence Diagram](http://vdemo.qiniudn.com/wechat-user.png)

## Web User Sequence Diagram

![Web User Sequence Diagram](http://vdemo.qiniudn.com/web-user.png)

# Design Documentation

## Basic rules

* Portal only manage the mapping relationship for helpdesk and user (wechat or web) in redis cache. `user-presence` (online or offfline) event recieved from tuisongbao webhook will trigger the modification for the cache data.

* Portal will use tuisongbao openAPI (sending message) and webhook (recieve message) to mock the behavior of wechat user as tuisongbao group user.

* Chat message and conversation data will be stored in tuisongbao, portal use tuisongbao open API to retrive and manage related data.

* Helpdesk will be notified that conversation is created or `web/wechat` user is offline by sending plain message with tuisongbao open API. The conversation list will be updated if any of the two events is triggered.

* Web user will be notified that conversation is created or helpdesk is offline by sending plain message with tuisongbao open API.

* The helpdesk and clients (wechat user, web user, pending user) will be removed from cache if the helpdesk or client is silent for a long time (set by the admin), it it implemented by schedule job `chat/job/ClearOffline.php`.

## Models

### Helpdesk (mongodb collection)

```
helpDesk
{
    _id                 objectId
    name                nickname
    badge               
    email               
    password            
    salt                
    avatar              
    language            (zh，en)
    isActivated         true or false
    isEnabled           true or false
    isDeleted           true or false
    createdAt           
    updatedAt           
    accountId           account objectId
    clientCount         the amount of users serve for
}
```

### Helpdesk Setting (mongodb collection)

```
helpDeskSetting
{
    _id                 objectId
    accountId           account ObjectId
    maxWaitTime         the maxinum waiting time
    maxClient           the maxinum amount of users to serve for helpdesk
    ondutyTime          on duty time
    offdutyTime         off duty time
    createdAt           
    updatedAt           
    isDeleted           true or false
    systemReplies: [
      {
        name            i18n keys for name (wait_for_service, close_service, non_working_time, auto_brake, connect_success, desk_droping, system_error)
        type            (waitting, close, nonworking, brake, success, droping, error)
        replyText       plain reply text
        isEnabled       true or false
      }
    ]
    channels: [
        {
            id          channel id
            isSet       whether it is enabled in channel menu (true or false)
        }
    ]
    websites: [
        {
            id          objectId
            name        website name
            url         website url
            code        used to create popup dialog to connect helpdesk
        }
    ]
}
```

### pending clients (mongodb collection)

```
pendingClient
{
    _id                 objectId
    nick                nickname
    avatar              
    openId              user ID, OpenID for wechat user; random ID for web user
    source              web or wechat
    channelId           the channel information for user (wechat, weibo, alipay)          
    createdAt           
    accountInfo         empty for web user
    accountId           account objectId
    tags                client tags which is used for VIP helpdesk case
    channelInfo {
        name            channel name
        type            channel type, //WEIBO, ALIPAY, SUBSCRIPTION_ACCOUNT, SUBSCRIPTION_AUTH_ACCOUNT, SERVICE_ACCOUNT, SERVICE_AUTH_ACCOUNT
    }
}
```

### chatSession

```
chatSession
{
    _id              ObjectId
    conversationId   string
    desk {           array
        avatar   string
        badge    string
        email    string
        name     string
        id       ObjectId
    },
    client {         array
        accountId   string
        source      string //[website/wechat/weibo/alipay/app]
        avatar      string
        nick        string
        openId      string
        tags        array    // member's tags
        channelInfo {  array
            name   string
            type   string
        },
        channelId:   string
        //if source is not website, else is null
    },
    startMessageId    int
    endMessageId      int
    lastChatTime      MongoDate
    accountId         MongoId
    createdAt         MongoDate
}
```

## Cache Design

### mapping cache for helpdesks and clients (encoded cache key)

key: conversations-{accountId}

```
[
    helpDeskId => [
        clientId,
        clientId
        ...
    ]
    ...
]
```

Manage it in code

```
$conversations = ChatConversation::getConversationMap($accountId);
ChatConversation::setConversationMap($accountId, $conversations);
```

### activities cache for helpdesks and clients (encoded cache key)

key: activities-{accountId}

```
[
    'helpDeskId' => 'lastActiveAt'
    'openId' => 'lastActiveAt'
    ...
]
```

Manage it in code

```
$conversations = ChatConversation::getConversationMap($accountId);
ChatConversation::setConversationMap($accountId, $conversations);
```

### connections count cache for clients (redis hash type)

key: user-daily-connect-{accountId}

```
{
    'clientId' : countValue
    'clientId' : countValue
    ...
}
```

expired at: today middle night (00:00:00)

### self helpdesk mode cache for helpdesks (redis string type)

key: conversation-mode-{accountId}-{clientId}

```
conversation-mode-{accountId}-{clientId} : isSelfHelpdeskMode
```

expired at: 600s later

### self helpdesk mode cache for input sequence flow of clients (redis string type)

key: selfHelpdesk-{accountId}-{clientId}

```
self-helpdesk-inputs-{accountId}-{clientId} : userInputSequenceFlow
```

expired at: 600s later

### unpublished self helpdesk setting content cache for account (redis string type)

key: self-helpdesk-settting-{accountId}-{clientId}

```
self-helpdesk-settting-{accountId}-{clientId} : helpdeskSetting
```

expired at: Empty the structure if self helpdesk setting is unset

## Events

### Tuisongbao Events

[Webhook Events](http://staging.tuisongbao.com/docs/engineGuide/webHook##%23%23chat-event) recieved from tuisongbao webhook, supported types: `message_new`, `message_offline`.

#### Message Webhook Format (message_new)

```
{
  "events": [
    {
      "name": "message_new",
      "conversationId": "5655004b1837ba3cfbc09775",
      "message": {
        "messageId": 4,
        "type": "singleChat",
        "from": "42baec1abe74-54a1461eb8137480048b4567",
        "to": "54a26aa9b81374bd048b456d",
        "content": {
          "type": "text",
          "text": "test",
          "extra": {
            "action": "chat",
            "source": "",      // Enum values: website, wechat, alipay, weibo, ios, android
            "nick": "vincent",
            "avatar": "",
            "accountId": "56556aa5afc3fb6f2d8b4567",
            "isHelpdesk": true // Indicate that the role of `from` field
          }
        },
        "createdAt": "2015-11-25T00:28:46.956Z"
      }
    }
  ],
  "timestamp": 1448411326973
}
```

### Message with Action Type

#### Join Message

The message will be sent if a client is assgined to a helpdesk

```
{
    "messageId": 2,
    "type": "singleChat",
    "from": "5f7748c82666-54a1461eb8137480048b4567",
    "to": "54a26aa9b81374bd048b456d",
    "content": {
        "type": "text",
        "text": "",
        "extra": {
            "action": "join",
            "conversationId": "565ecdc11837ba3cfbc16a84",
            "helpdeskId": "54a26aa9b81374bd048b456d",
            "client": {
                "source": "website", // Enum values: website, wechat, alipay, weibo, ios, android
                "avatar": "",
                "nick": "guest-5f7748c82666",
                "openId": "5f7748c82666-54a1461eb8137480048b4567",
                "channelId": "",      // The field will be empty if source value is website
                "channelInfo": {      // The field will be empty if source value is website
                    "name": "rex",
                    "type": "SERVICE_AUTH_ACCOUNT"
                }
            },
            "accountId": "54a1461eb8137480048b4567",
            "isHelpdesk": false      // Indicate that the role of `from` field        
        }
    },
    "createdAt": "2015-12-02T10:53:53.502Z"
}
```

#### Leave Message

The message will be sent if the client or helpdesk is offline

```
{
    "messageId": 3,
    "type": "singleChat",
    "from": "f21e41860e30-55e50fe2afc3fb49698b4567",
    "to": "56556aa5afc3fb6f2d8b4567",
    "content": {
        "type": "text",
        "text": "",
        "extra": {
            "action": "leave"
            "conversationId": "565ecdc11837ba3cfbc16a84",
            "client": {
                "source": "website", // Enum values: website, wechat, alipay, weibo, ios, android
                "avatar": "",
                "nick": "guest-5f7748c82666",
                "openId": "5f7748c82666-54a1461eb8137480048b4567",
                "channelId": "",      // The field will be empty if source value is website
                "channelInfo": {      // The field will be empty if source value is website
                    "name": "rex",
                    "type": "SERVICE_AUTH_ACCOUNT"
                }
            },
            "targetChannel": ""      // The field will be empty if website user or helpdesk recieve the message
            "accountId": "56556aa5afc3fb6f2d8b4567",
            "isHelpdesk": true // Indicate that the role of `from` field
        }
    },
    "createdAt": "2015-12-03T08:14:39.537Z"
}
```

#### Transfer Message

The message will be sent if the client is transfered by a helpdesk

```
{
    "messageId": 2,
    "type": "singleChat",
    "from": "846e18fb8249-55e50fe2afc3fb49698b4567",
    "to": "56556aafafc3fb712d8b4567",
    "content": {
        "type": "text",
        "text": "",
        "extra": {
            "action": "transfer",
            "startTime": 1449130849000,
            "chatTimes": 1,
            "lastChatTime": "2015-12-03T08:20:42.058Z",
            "client": {
                "source": "wechat",                    // Enum values: website, wechat, alipay, weibo, ios, android
                "avatar": "/images/management/image_hover_default_avatar.png",
                "nick": "guest-846e18fb8249",
                "openId": "846e18fb8249-55e50fe2afc3fb49698b4567",
                "channelId": "54d9c155e4b0abe717853ee1" // The field will be empty if source value is website
                "channelInfo": {                        // The field will be empty if source value is website
                    "name": "rex",
                    "type": "SERVICE_AUTH_ACCOUNT"
                }
            },
            "helpdesk": {
                "badge": "123",
                "nick": "Woody",
                "id": "56556aa5afc3fb6f2d8b4567"
            },
            "targetHelpdesk": {
                "badge": "YT00123",
                "nick": "Rex",
                "id": "56556aa6gfc3fb6f2d8b4569"
            },
            "conversationId": "565ffb611837ba3cfbc1864a"
            "accountId": "56556aa5afc3fb6f2d8b4567",
            "isHelpdesk": true // Indicate that the role of `from` field
        }
    },
    "createdAt": "2015-12-03T08:20:49.844Z"
}
```

#### Before Transfer Message

The message will be sent before the client transfer by a helpdesk

```
{
    "messageId": 2,
    "type": "singleChat",
    "from": "846e18fb8249-55e50fe2afc3fb49698b4567",
    "to": "56556aafafc3fb712d8b4567",
    "content": {
        "type": "text",
        "text": "",
        "extra": {
            "action": "beforeTransfer",
            "accountId": "56556aa5afc3fb6f2d8b4567",
            "helpdeskId": "54a26aa9b81374bd048b456d",
            "isHelpdesk": true // Indicate that the role of `from` field
        }
    },
    "createdAt": "2015-12-03T08:20:49.844Z"
}
```

#### Chat Message

Normal chat message, only support text type message for the moment.

##### Plain Text Message

```
{
    "messageId": 3,
    "type": "singleChat",
    "from": "5f7748c82666-54a1461eb8137480048b4567",
    "to": "54a26aa9b81374bd048b456d",
    "content": {
        "type": "text",
        "text": "asdfasfd",
        "extra": {
            "action": "chat",
            "source": "website",     // Enum values: website, wechat, alipay, weibo, ios, android
            "nick": "vincent",
            "avatar": "",
            "targetChannel": ""      // The field will be empty if website user or helpdesk recieve the message
            "accountId": "56556aa5afc3fb6f2d8b4567",
            "isHelpdesk": true       // Indicate that the role of `from` field
        }
    },
    "createdAt": "2015-12-02T11:10:14.597Z"
}
```

##### Article Message

```
{
    "messageId": 3,
    "type": "singleChat",
    "from": "5f7748c82666-54a1461eb8137480048b4567",
    "to": "54a26aa9b81374bd048b456d",
    "content": {
        "type": "text",
        "text": "55d56672d6f97f84138b4577", // The article id if the type field in extra field
        "extra": {
            "action": "chat",
            "source": "",        // Enum values: website, wechat, alipay, weibo, ios, android
            "channelId": "",     // Only present if the source value is wechat, alipay or weibo
            "nick": "vincent",
            "avatar": "",
            "isHelpdesk": true    // Indicate that the role of `from` field
            "type": "article"     // The extra field for extented type
            "targetChannel": ""   // The field will be empty if website user or helpdesk recieve the message
            "accountId": "56556aa5afc3fb6f2d8b4567",
            "isHelpdesk": true    // Indicate that the role of `from` field
        }
    },
    "createdAt": "2015-12-02T11:10:14.597Z"
}
```

# API Documentation

## conversation

### Transfer Helpdesk

- Request Method:
PUT

- Request Endpoint:
http://{server-domain}/api/chat/conversation/transfer

- Request Parameters:

| Name | Type | Required | Example | Description |
| ---- | ---- | -------- | ------- | ----------- |
| helpdesk | object | Yes | {"id": "54a26aa9b81374bd048b456d","nick": "客服001","badge": "T00005"} | 发起转接的客服信息 |
| client | object | Yes | {"openId": "30bce17b9a31-54a1461eb8137480048b4567","nick": "guest-30bce17b9a31","avatar": ""} | 被转接客户信息 |
| targetHelpdesk | object | Yes | {"id": "54acdeb0b81374987d8b456b","nick": "harry","badge": "T04411"} | 接收转接的客服信息 |

- Request Example

```
{
    "helpdesk": {
        "id": "54a26aa9b81374bd048b456d",
        "nick": "客服001",
        "badge": "T00005"
    },
    "client": {
        "source": "wechat",                    // Enum values: website, wechat, alipay, weibo
        "avatar": "/images/management/image_hover_default_avatar.png",
        "nick": "麦田",
        "openId": "oDn77jjjXhs9XpwVOHkZ7an5VzLw",
        "channelId": "54d9c155e4b0abe717853ee1" // The field will be empty if source value is website
        "channelInfo": {                        // The field will be empty if source value is website
            "name": "rex",
            "type": "SERVICE_AUTH_ACCOUNT"
        }
    },
    "targetHelpdesk": {
        "id": "54acdeb0b81374987d8b456b",
        "nick": "harry",
        "badge": "T04411"
    }
}
```

- Response Example

```
{
    "status": "ok"
}
```

### Get Conversations

- Request Method:
GET

- Request Endpoint:
http://{server-domain}/api/chat/conversations

- Request Parameters:

| Name | Type | Required | Example | Description |
| ---- | ---- | -------- | ------- | ----------- |
| openId | string | Yes | "54a26aa9b81374bd048b456d | 客户ID |

- Request Example

```
http://wm.com/api/chat/conversations?openId=54a26aa9b81374bd048b456d
```

- Response Example

```
[
    {
        "conversationId": "5665879f1837ba3cfbc1f4a9",
        "type": "singleChat",
        "target": "551b40f6d6f97f7f098b4569",
        "unreadMessageCount": 1,
        "lastActiveAt": "2015-12-09T01:12:17.145Z",
        "extra": {
            "helpdesk": {
                "id": "551b40f6d6f97f7f098b4569",
                "nick": "Rex",
                "badge": "YT00353"
            },
            "client": {
                "nick": "éº¦ç”°å‚€å„¡",
                "avatar": "http://wx.qlogo.cn/mmopen/yUpwOb9Dhpc2OzkcUjrpR4DTDdcFKOnBozia3OwnmnibmibxJ20DVia94W1bRLDcx30SVibX5O8pGIYFuooIGvaKVRsL2mR5NY7L5/0",
                "openId": "oDn77jjjXhs9XpwVOHkZ7an5VzLw",
                "source": "wechat",
                "channelId": "552621b9e4b00231bde18bdb",
                "accountInfo": {
                    "type": "SERVICE_AUTH_ACCOUNT",
                    "name": "rex"
                }
            }
        },
        "lastMessage": {
            "messageId": 9,
            "type": "singleChat",
            "from": "551b40f6d6f97f7f098b4569",
            "to": "oDn77jjjXhs9XpwVOHkZ7an5VzLw",
            "content": {
                "type": "text",
                "text": "",
                "extra": {
                    "action": "leave",
                    "helpdeskId": "551b40f6d6f97f7f098b4569",
                    "isHelpdesk": true,
                    "accountId": "55189a7cd6f97f41048b4567",
                    "targetChannel": ""
                }
            },
            "createdAt": "2015-12-09T01:12:17.145Z"
        }
    }
]
```

### Get Messages for a conversation

- Request Method:
GET

- Request Endpoint:
http://{server-domain}/api/chat/conversation/messages

- Request Parameters:

| Name | Type | Required | Example | Description |
| ---- | ---- | -------- | ------- | ----------- |
| conversationId | string | Yes | "5665879f1837ba3cfbc1f4a9" | 会话唯一标识 |
| startMessageId | integer | Yes | 2 | 起始 messageId |
| endMessageId | integer | Yes | 5 | 结束 messageId |
| limit | integer | Yes | 30 | 消息个数, 默认值20 |


- Request Example

```
http://wm.com/api/chat/conversations?conversationsId=5665879f1837ba3cfbc1f4a9
```

- Response Example

```
[
    {
        "messageId": 2,
        "type": "singleChat",
        "from": "oDn77jjjXhs9XpwVOHkZ7an5VzLw",
        "to": "551b40f6d6f97f7f098b4569",
        "content": {
            "type": "text",
            "text": "",
            "extra": {
                "action": "join",
                "client": {
                    "nick": "Test",
                    "avatar": "http://wx.qlogo.cn/mmopen/yUpwOb9Dhpc2OzkcUjrpR4DTDdcFKOnBozia3OwnmnibmibxJ20DVia94W1bRLDcx30SVibX5O8pGIYFuooIGvaKVRsL2mR5NY7L5/0",
                    "openId": "oDn77jjjXhs9XpwVOHkZ7an5VzLw",
                    "source": "wechat",
                    "channelId": "552621b9e4b00231bde18bdb",
                    "accountInfo": {
                        "type": "SERVICE_AUTH_ACCOUNT",
                        "name": "rex"
                    }
                },
                "helpdeskId": "551b40f6d6f97f7f098b4569",
                "conversationId": "5665879f1837ba3cfbc1f4a9",
                "accountId": "55189a7cd6f97f41048b4567",
                "isHelpdesk": false
            }
        },
        "createdAt": "2015-12-07T13:20:32.129Z"
    }
]
```

## client

### Update client (member) properties

- Request Method:
PUT

- Request Endpoint:
http://{server-domain}/api/chat/client/update

- Request Parameters:

| Name | Type | Required | Example | Description |
| ---- | ---- | -------- | ------- | ----------- |
| openId | string | Yes | "5665879f1837ba3cfbc1f4a9" | 用户ID |
| properties | array | Yes | [{"id": "55b7491cb81374bb468b456d","name": "name","value": "test1"}] | 需要更新的属性 |

- Request Example

```
{
    "openId": "oTAN2jjnqWghQ7GGoSFwIOZnmXoU",
    "properties": [{
        "id": "55b7491cb81374bb468b456d",
        "name": "name",
        "value": "test1"
    }, {
        "id": "54ab4283b81374cb0d8b4567",
        "name": "asdfasdf",
        "value": "sdf2"
    }, {
        "id": "55b7491cb81374bb468b456e",
        "name": "tel",
        "value": "12345678901"
    }, {
        "id": "55b7491cb81374bb468b456f",
        "name": "gender",
        "value": "male"
    }, {
        "id": "55b7491cb81374bb468b4570",
        "name": "birthday",
        "value": 1437408000000
    }, {
        "id": "55b7491cb81374bb468b4571",
        "name": "email",
        "value": "805252866@qq.com"
    }]
}
```

- Response Example

```
{
    "status": "ok"
}
```

### Get client (member) info

- Request Method:
GET

- Request Endpoint:
http://{server-domain}/api/chat/client/info

- Request Parameters:

| Name | Type | Required | Example | Description |
| ---- | ---- | -------- | ------- | ----------- |
| openId | string | Yes | "5665879f1837ba3cfbc1f4a9" | 用户ID |

- Request Example



- Response Example

member
```
{
    "id": "565e9f84d6f97fef088b4588",
    "socials": [],
    "card": {
        "id": "55c97304d6f97f28118b4567",
        "name": "金卡会员",
        "poster": "http://wm.com/images/mobile/membercard.png",
        "fontColor": "#ffffff",
        "privilege": "<p>山东省</p>",
        "condition": null,
        "usageGuide": "<p>随碟附送</p>",
        "isEnabled": true,
        "isDefault": true,
        "isAutoUpgrade": false,
        "scoreResetDate": {
            "month": 8,
            "day": 28
        },
        "provideCount": 16047,
        "createdAt": "2015-08-11 11:59:00",
        "updatedAt": "2015-09-07 09:41:05"
    },
    "createdAt": "2015-12-02 15:36:36",
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
            "id": "5518bfacd6f97f41048b456e",
            "name": "name",
            "value": "林文惠",
            "isDefault": true
        },
        {
            "id": "5518bfacd6f97f41048b456f",
            "name": "tel",
            "value": "0937481134",
            "isDefault": true
        },
        {
            "id": "5518bfacd6f97f41048b4570",
            "name": "gender",
            "value": "female",
            "isDefault": true
        },
        {
            "id": "5518bfacd6f97f41048b4572",
            "name": "email",
            "value": "",
            "isDefault": true
        }
    ],
    "cardProvideTime": "2015-12-02 15:36:35",
    "cardExpired": 0,
    "avatar": "https://dn-quncrm.qbox.me/image_hover_default_avatar.png",
    "location": {
        "country": "",
        "province": "",
        "city": "",
        "detail": ""
    },
    "tags": [
        "APP USER"
    ],
    "score": 0,
    "remarks": null,
    "cardNumber": "10000034",
    "unionId": null,
    "totalScore": 0,
    "cardExpiredAt": "",
    "birth": null,
    "openId": "asd",
    "qrcodeViewed": false,
    "totalScoreAfterZeroed": 0,
    "isDisabled": false
}
```

follower
```
{
    "id": "56791446d6fc61e3557dc07b",
    "channelId": "552621b9e4b00231bde18bdb",
    "openId": "oDn77jjjXhs9XpwVOHkZ7an5VzLw",
    "properties": [
        {
            "id": "5673e64cd6f97f5d438b4567",
            "name": "name",
            "value": "麦田傀儡1",
            "isDefault": true
        },
        {
            "id": "5673e64cd6f97f5d438b4569",
            "name": "gender",
            "value": "male",
            "isDefault": true
        }
    ]
}
```

### Get client (member) properties

- Request Method:
GET

- Request Endpoint:
http://{server-domain}/api/chat/client/properties

- Request Parameters:

| Name | Type | Required | Example | Description |
| ---- | ---- | -------- | ------- | ----------- |
| openId | string | Yes | "5665879f1837ba3cfbc1f4a9" | 用户ID |

- Request Example



- Response Example

```
[
    {
        "id": "5518bfacd6f97f41048b456f",
        "order": 1,
        "name": "tel",
        "options": null,
        "type": "input",
        "defaultValue": "",
        "isRequired": true,
        "isUnique": false,
        "isVisible": true,
        "isDefault": true,
        "propertyId": null
    },
    {
        "id": "56499f58d6f97fc0138b4569",
        "order": 13,
        "name": "aaaabb",
        "options": null,
        "type": "textarea",
        "defaultValue": "",
        "isRequired": false,
        "isUnique": false,
        "isVisible": true,
        "isDefault": false,
        "propertyId": "aaaaa"
    }
]
```
