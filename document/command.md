# Console

### Scan the modules folder to generate map in redis cache

- Command:
{your-src-folder}/yii module/scan

- Command Parameters:

| Name | Required | Example | Description |
| ---- | -------- | ------- | ----------- |

- Command Example

```
./yii module/scan
```

### Generate new module related configuation (submodule and soft-link configuration)

- Command:
{your-src-folder}/yii module/add

- Command Parameters:

| Name | Required | Example | Description |
| name | Yes | seed | The name for module, it should be lowercase |

- Command Example

```
./yii module/add seed
```

# Cron

### Update the status of goods

- Command:
{your-src-folder}/yii cron/minute/goods-sale

- Command Parameters:

| Name | Required | Example | Description |
| ---- | -------- | ------- | ----------- |

- Command Example

```
./yii cron/minute/goods-sale
```

### Update the status of store goods

- Command:
{your-src-folder}/yii cron/minute/store-goods-sale

- Command Parameters:

| Name | Required | Example | Description |
| ---- | -------- | ------- | ----------- |

- Command Example

```
./yii cron/minute/store-goods-sale
```

### Automatic clear member score

- Command:
{your-src-folder}/yii cron/daily/reset-score

- Command Parameters:

| Name | Required | Example | Description |
| ---- | -------- | ------- | ----------- |

- Command Example

```
./yii cron/daily/reset-score
```

### Check and expire membership card

- Command:
{your-src-folder}/yii cron/daily/check-card-expired-time

- Command Parameters:

| Name | Required | Example | Description |
| ---- | -------- | ------- | ----------- |

- Command Example

```
./yii cron/daily/check-card-expired-time
```

### Create data for promotioncode analysis

- Command:
{your-src-folder}/yii cron/daily/promotion-code-analysis

- Command Parameters:

| Name | Required | Example | Description |
| ---- | -------- | ------- | ----------- |

- Command Example

```
./yii cron/daily/promotion-code-analysis
```

### Stats of uhkklp

- Command:
{your-src-folder}/yii cron/daily/statistics-uhkklp

- Command Parameters:

| Name | Required | Example | Description |
| ---- | -------- | ------- | ----------- |
- Command Example

```
./yii cron/daily/statistics-uhkklp
```

### Member module daily job for birthday and location

- Command:
{your-src-folder}/yii cron/daily/daily-job

- Command Parameters:

| Name | Required | Example | Description |
| ---- | -------- | ------- | ----------- |

- Command Example

```
./yii cron/daily/daily-job
```

# Management

### Save account menus and mods

- Command:
{your-src-folder}/yii management/account/add-menus-and-mods

- Command Parameters:

| Name | Required | Example | Description |
| ---- | -------- | ------- | ----------- |

- Command Example

```
./yii management/account/add-menus-and-mods
```

### Remove account mods

- Command:
{your-src-folder}/yii management/account/remove-mods

- Command Parameters:

| Name | Required | Example | Description |
| ---- | -------- | ------- | ----------- |
| accountId | Yes | 52b763b475df406048b4569 | account id |
| modsStr | Yes | store,product | muti mods split by , |

- Command Example

```
./yii management/account/remove-mods 52b763b475df406048b4569 product
```

### Remove account menu

- Command:
{your-src-folder}/yii management/account/remove-menus {$accountId} {$menusStr}

- Command Parameters:

| Name | Required | Example | Description |
| ---- | -------- | ------- | ----------- |
| accountId | Yes | 52b763b475df406048b4569 | account id |
| menusStr | Yes | store,product | muti menusStr split by , |

- Command Example

```
./yii management/account/remove-menus 52b763b475df406048b4569 product
```

### Add account service start time

- Command:
{your-src-folder}/yii management/account/add-service-start-time

- Command Parameters:

| Name | Required | Example | Description |
| ---- | -------- | ------- | ----------- |

- Command Example

```
./yii management/account/add-service-start-time
```

### Migrate account channel

- Command:
{your-src-folder}/yii management/account/channel-migration

- Command Parameters:

| Name | Required | Example | Description |
| ---- | -------- | ------- | ----------- |

- Command Example

```
./yii management/account/channel-migration
```

### Create a account and user by email

- Command:
{your-src-folder}/yii management/account/generate-by-email

- Command Parameters:

| Name | Required | Example | Description |
| ---- | -------- | ------- | ----------- |
| email | Yes | 11@qq.com | this email be used as user email when create a user |

- Command Example

```
./yii management/account/generate-by-email  11@qq.com
```

### Create a default channel

- Command:
{your-src-folder}/yii management/account/create-default-channel {accountId}

- Command Parameters:

| Name | Required | Example | Description |
| ---- | -------- | ------- | ----------- |
| accountId | Yes | 52b763b475df406048b4569 or all | if this value is all,it will support all accounts who has actived microsite and do not have default channel,otherwise it only support this account who you write |

- Command Example

```
./yii management/account/create-default-channel  52b763b475df406048b4569
```


### Create a default page cover

- Command:
{your-src-folder}/yii management/account/create-default-page-cover {accountId} {hostinfo}

- Command Parameters:

| Name | Required | Example | Description |
| ---- | -------- | ------- | ----------- |
| accountId | Yes | 52b763b475df406048b4569 or all | if this value is all,it will support all accounts who has actived microsite module and do not have default page cover,otherwise it only support this account who you write |
| hostinfo | Yes | https://staging.quncrm.com | domain use to create short url |

- Command Example

```
./yii management/account/create-default-page-cover  52b763b475df406048b4569 https://staging.quncrm.com
```

### Add message setting for account

- Command:
{your-src-folder}/yii management/account/add-message-setting

- Command Parameters:

| Name | Required | Example | Description |
| accountId | Yes | 55e44579971374c40a8b4567 | Account ID |
| apiKey | No | c8716332728352dc48d3cfe71a251cde | The API key for yunpian or other service |
| url | No | 55e44579971374c40a8b4567 | The yunpian serivce path or other service path for sending SMS |

- Command Example

Override default API key for yunpian serivce

```
./yii management/account/add-message-setting 54a1461eb8137480048b4567 c8716332728352dc48d3cfe71a251cde
```

### Add email setting for account

- Command:
{your-src-folder}/yii management/account/add-email-setting

- Command Parameters:

| Name | Required | Example | Description |
| accountId | Yes | 55e44579971374c40a8b4567 | Account ID |
| apiUser | No | tubuqulvxing_ere_ZUzjGY | The API user name for sendcloud service |
| apiKey | No | MO0JLapIETmlKGpZ | The API key for sendcloud service |

- Command Example

Override default API key for yunpian serivce

```
./yii management/account/add-email-setting 54a1461eb8137480048b4567 tubuqulvxing_ere_ZUzjGY MO0JLapIETmlKGpZ
```

### Delete members and clear related tables by account id

- Command:
{your-src-folder}/yii management/delete-member-info {$accountId}

- Command Parameters:

| Name | Required | Example | Description |
| ---- | -------- | ------- | ----------- |
| accountId | Yes | 52b763b475df406048b4569 |   |

- Command Example

```
./yii management/delete-member-info 52b763b475df406048b4569
```

### Ensure all collection indexes

- Command:
{your-src-folder}/yii management/index

- Command Parameters:

| Name | Required | Example | Description |
| ---- | -------- | ------- | ----------- |

- Command Example

```
./yii management/index
```

### Support klp auth

- Command:
{your-src-folder}/yii management/klp/auth

- Command Parameters:

| Name | Required | Example | Description |
| ---- | -------- | ------- | ----------- |

- Command Example

```
./yii management/klp/auth
```

### Get module config from backend

- Command:
{your-src-folder}/yii management/module/get-config

- Command Parameters:

| Name | Required | Example | Description |
| ---- | -------- | ------- | ----------- |

- Command Example

```
./yii management/module/get-config
```

### Add the default sensitive operation to all accounts

- Command:
{your-src-folder}/yii management/sensitive-operation

- Command Parameters:

| Name | Required | Example | Description |
| ---- | -------- | ------- | ----------- |

- Command Example

```
./yii management/sensitive-operation
```

### Add sensitive operation to hide menus for operators

- Command:
{your-src-folder}/yii management/sensitive-operation/hide-menus

- Command Parameters:

| Name | Required | Example | Description |
| module | Yes | uhkklp | the module name |
| menus | Yes | message,recipe | the menu name list |
| ruleName | No | hide-module-menus | the rule name shown in sensitive operation list |
| accountIds | No | 55e44579971374c40a8b4567 | Default update all account|

- Command Example

```
./yii management/sensitive-operation/hide-menus uhkklp message,recipe hide-uhkklp 54a1461eb8137480048b4567
```

### Remove sensitive operation by name

- Command:
{your-src-folder}/yii management/sensitive-operation/remove

- Command Parameters:

| Name | Required | Example | Description |
| name | Yes | hide-module-menus | the rule name shown in sensitive operation list |
| accountIds | No | 55e44579971374c40a8b4567 | Default update all account|

- Command Example

```
./yii management/sensitive-operation/hide-menus hide-uhkklp 54a1461eb8137480048b4567
```

### Init job

- Command
{your-src-folder}/yii management/job/init

- Command Parameters:

| Name | Required | Example | Description |
| isRemoveOldJob | No | true or false | remove old job and create a new job |

- Command Example

```
./yii management/job/init true
```

### Create a job

- Command:
{your-src-folder}/yii management/job/create

- Command Parameters:

| Name | Required | Example | Description |
| className | Yes | backend\\modules\\member\\job\\StatsMemberOrder | the job's class name |
| executeAt | No | "2015-09-17 1:00:00" | job execute time, if empty execute now |
| interval | No | 60 | interval time, seconds |
| isReplaceExistJob | No | 1 | is replace exist job |
| args | No | {"description": "Delay"} | job args, json string |

- Command Example

remove exists job, and create a new job backend\\modules\\member\\job\\StatsMemberOrder run at 1:00 every day

```
./yii management/job/create backend\\modules\\member\\job\\StatsMemberOrder "2015-09-18 1:00:00" 86400 1
```

### Cancle exists delay job

- Command:
{your-src-folder}/yii management/job/remove

- Command Parameters:

| Name | Required | Example | Description |
| className | Yes | backend\\modules\\member\\job\\StatsMemberOrder | the job's class name |

- Command Example

remove exists delay job

```
./yii management/job/remove backend\\modules\\member\\job\\StatsMemberOrder
```

### Activate modules

- Command:
{your-src-folder}/yii management/module/install

- Command Parameters:

| Name | Required | Example | Description |
| modules | Yes | game,product | the modules' name |
| accountIds | No | 55e44579971374c40a8b4567 | Default update all account|

- Command Example

Activate module game for all account.

```
./yii management/module/install game
```

### Uninstall modules

- Command:
{your-src-folder}/yii management/module/uninstall

- Command Parameters:

| Name | Required | Example | Description |
| modules | Yes | game,product | the modules' name |
| accountIds | No | 55e44579971374c40a8b4567 | Default update all account|

- Command Example

Uninstall module game for all account.

```
./yii management/module/uninstall game
```

# Member

### Add channel origin/origin_scene and delete member with same phone and score

- Command:
{your-src-folder}/yii member/member/migration

- Command Parameters:

| Name | Required | Example | Description |
| ---- | -------- | ------- | ----------- |

- Command Example

```
./yii member/member/migration
```

### Create member log

- Command:
{your-src-folder}/yii member/member/member-log

- Command Parameters:

| Name | Required | Example | Description |
| ---- | -------- | ------- | ----------- |

- Command Example

```
./yii member/member/member-log
```

### Create redeem member log

- Command:
{your-src-folder}/yii member/member/redeem-member-log

- Command Parameters:

| Name | Required | Example | Description |
| ---- | -------- | ------- | ----------- |

- Command Example

```
./yii member/member/redeem-member-log
```

### Delete member log when the member is deleted

- Command:
{your-src-folder}/yii member/member/delete-member-log

- Command Parameters:

| Name | Required | Example | Description |
| ---- | -------- | ------- | ----------- |

- Command Example

```
./yii member/member/delete-member-log
```

### unset the type in member property

- Command:
{your-src-folder}/yii member/member/unset-property-type

- Command Parameters:

| Name | Required | Example | Description |
| ---- | -------- | ------- | ----------- |

- Command Example

```
./yii member/member/unset-property-type
```

### Migrate qrcode

- Command:
{your-src-folder}/yii member/member/qrcode-migration {$domian}
- Command Parameters:

| Name | Required | Example | Description |
| ---- | -------- | ------- | ----------- |
| domian | Yes | http://qiniu.com | upload domain |
- Command Example

```
./yii member/member/qrcode-migration {$domian}
```

# Microsite

### Change page and article url

- Command:
{your-src-folder}/yii microsite/change-url

- Command Parameters:

| Name | Required | Example | Description |
| ---- | -------- | ------- | ----------- |

- Command Example

```
./yii microsite/change-url
```

# Product

### Create template for redeem goods and exchange code

- Command:
{your-src-folder}/yii product/create-template

- Command Parameters:

| Name | Required | Example | Description |
| ---- | -------- | ------- | ----------- |

- Command Example

```
./yii product/create-template
```

### Create sms template for create staff

- Command:
{your-src-folder}/yii product/create-template/create-staff

- Command Parameters:

| Name | Required | Example | Description |
| ---- | -------- | ------- | ----------- |

- Command Example

```
./yii product/create-template/create-staff
```

### Delete repeat the default template of staff

- Command:
{your-src-folder}/yii product/create-template/delete-staff

- Command Parameters:

| Name | Required | Example | Description |
| ---- | -------- | ------- | ----------- |

- Command Example

```
./yii product/create-template/delete-staff
```

### Delete repeat the default template of promotioncode and redemption

- Command:
{your-src-folder}/yii product/create-template/delete-product-template

- Command Parameters:

| Name | Required | Example | Description |
| ---- | -------- | ------- | ----------- |

- Command Example

```
./yii product/create-template/delete-product-template
```

### Update the result for analysis code in every day

- Command:
{your-src-folder}/yii product/promotion-code-analysis/update-data begiin(y-m-d) end(y-m-d) type

- Command Parameters:

| Name | Required | Example | Description |
| ---- | -------- | ------- | ----------- |
| begin | yes | 2015-10-01 | when to analysis code |
| end | yes | 2015-11-01 | analysis code until this time |
| type | yes | 1 | the type valus is 1,2,3,4. '1.表示更新参与人数;2.表示更新兑换总数;3.表示每天兑换数量,4.表示没每天参加的总人数'|


- Command Example

```
./yii product/promotion-code-analysis/update-data 2015-10-01 -2015-11-01 1
```

### Add redeem time to campaign log

- Command:
{your-src-folder}/yii product/change-redeem-time

- Command Parameters:

| Name | Required | Example | Description |
| ---- | -------- | ------- | ----------- |

- Command Example

```
./yii product/change-redeem-time
```
# Store

### Refined store location

- Command:
{your-src-folder}/yii store/store-location

- Command Parameters:

| Name | Required | Example | Description |
| ---- | -------- | ------- | ----------- |

- Command Example

```
./yii store/store-location
```
