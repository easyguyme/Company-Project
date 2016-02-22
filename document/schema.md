# Account

```
account {
    enabledMods: [ String, String],
    company      企业名称
    name         姓名
    phone        手机号
    menus        账号开启的菜单
    mods         账号开启的模块,用于顶部显示
    syncWechat： [       绑定且已同步门店的微信列表
        wechatId        微信Id
    ],
    accessKey           account的Access Key
    secretKey           account的Secret Key
    keyCreatedAt        APP Key创建时间
    tags: [             系统内公共标签，用于会员和粉丝
        String, String
    ]
    availableExtMods    可使用的扩展模块
    priceType           billingAccount的定价级别,
    trialStartAt        试用开始时间
    trialEndAt          试用结束时间
    serviceStartAt      服务开始时间
    serviceEndAt        服务结束时间
    isDeleted           是否删除
    createdAt           创建时间
    updatedAt           更新时间
}
```

```
applications {
    _id             APP Key ID, ObjectId
    name            APP 名称
    privateKey      APP Private Key
    icon            APP 图标 url
    content         APP 描述内容
    accountId       accountID
    isDeleted       是否删除
    createdAt       创建时间
    updatedAt       更新时间
}
```

```
webHook {               应用回调记录表
    _id                 ObjectId
    url                 回调地址
    isEnabled           回调是否被启用
    channels [          绑定渠道列表
        channelId       渠道ID
    ],
    accountId           Account ObjectId
    updatedAt           更新时间
    createdAt           创建时间
    isDeleted           回调是否被删除
```

```
user {
    _id             用户ID, ObjectId
    email           邮箱
    password        密码
    salt            密码盐值
    role            角色
    name            昵称
    accountId       accountID
    avatar          头像 url
    language        语言（zh，en）
    isActivated     是否验证
    isDeleted       是否删除
    createdAt       创建时间
    updatedAt       更新时间
}
```

```
token {
    _id             Token ID
    accessToken     用户Token
    expireTime      用户Token过期时间
    userId          用户ID
    accountId       billingAccount ID
    添加用于方便查询的字段
    role            用户角色
    language        用户角色
    enabledMods     账号激活模块
    isDeleted       是否删除
    createdAt       创建时间
    updatedAt       更新时间
}
```

```
captcha {
    _id             ObjectId
    ip              用户ip地址
    mobile          电话
    code            验证码
    isExpired       是否过期
    accountId       ObjectId (注册群脉企业账户时发送的验证码为null)
    createdAt       MongoDate
}
```

```
validation {
    _id             Validation ID
    code            邀请码
    userId          该用户的ID
    expire          邀请链接过期时间
    isDeleted       是否删除
    createdAt       创建时间
    updatedAt       更新时间
}
```

```
module {
    _id
    name
    introductions
    settings: {

    }
    urlState 所有这个模块下的链接都有这个前缀, 例如 channel, 那么channel下的其他页面会是 channel/keyword, channel/menu
    urlPath
    owner
    createdAt
    installedCount
    version
}
```

```
sensitiveOperation {
    name            定义敏感信息对应前台国际化的key
    users    [      敏感信息页面对应的白名单用户
        userId      用户ID
    ],
    actions  [      敏感信息页面对应的后台action列表
        actionName  action名称
    ],
    isActivated     是否激活
    accountId       accountID
    isDeleted       是否删除
    createdAt       创建时间
    updatedAt       更新时间
}

```

# Group

```
group {
    _id             集团ID, ObjectId
    name            姓名
    logo            logo url
    description     描述
    accounts: [     关联品牌账户
        ObjectId    账户ID
    ]
    isDeleted       是否删除
    createdAt       创建时间
    updatedAt       更新时间
}
```

```
groupUser {
    _id             用户ID, ObjectId
    groupId         集团ID, ObjectId
    email           邮箱
    password        密码
    salt            密码盐值
    isDeleted       是否删除
    createdAt       创建时间
    updatedAt       更新时间
}
```

# Content

```
graphics {
    _id             ObjectID
    accountId       ObjectID
    type   'single' or 'multiple'
    articles [{
        title
        description
        picUrl
        author
        sourceUrl //原文链接 用户填写在表单中
        content //富文本编辑器提交的内容
        contentUrl //根据content生成的页面url
    }]
}
```

# Helpdesk

```
helpDesk
{
    _id                 ObjectId
    name                客服昵称
    badge               客服工号
    email               客服邮箱
    password            密码
    salt                密码盐值
    avatar              客服头像
    language            语言（zh，en）
    isActivated         true or false
    isEnabled           true or false
    isDeleted           true or false
    createdAt           创建时间
    updatedAt           更新时间
    accountId           Account ObjectId
    clientCount         客户数量
}
```

```
helpDeskSetting
{
    _id                 ObjectId
    accountId             Account ObjectId
    maxWaitTime         会话自动结束等待时间
    maxClient           每个客服最大连接数
    ondutyTime          客服工作开始时间
    offdutyTime         客服工作结束时间
    createdAt           创建时间
    updatedAt           更新时间
    isDeleted           true or false
    systemReplies: [
      {
        name            系统恢复类型名称
        type            自动回复类型
        replyText       系统回复消息内容
        isEnabled       系统回复是否被禁用
      }
    ]
    channels: [
        {
            id          微信公众账号ID
            isSet       是否在微信菜单中开启客服
        }
    ]
    websites: [
        {
            id          ObjectId
            name        客服网站名
            url         客服网站地址
            code        客服网站的嵌入code
        }
    ]
}
```

```
chatConversation
{
    _id                 ObjectId
    conversation        会话ID, 可以是聊天的渠道号, 规则是presence-wm-chat-{deskId}-{clientOpenId}
    status              open or closed
    desk: {
        badge           客服工号
        id              客服ID
        email           客服email
        avatar          客服头像
        name            客服名称
    }
    client: {
        nick            昵称
        avatar          客户头像
        openId          客户ID, 如果是微信用户, 则是微信OpenID; 网站用户是随机ID
        originId        客户原始的ID
        source          website, wechat, weibo or alipay
        sourceChannel   目前只针对微信用户, 是微信用户关注的微信号渠道信息
        accountInfo: {
            type:       账号类型 (WEIBO, ALIPAY, SERVICE_AUTH_ACCOUNT, SUBSCRIPTION_AUTH_ACCOUNT)
            name:       账号名称
        }
    }
    date                日期 “Y-m-d”格式方便查询
    lastChatTime        最后一条消息发送的时间
    isDeleted           是否删除
    createdAt           创建时间
    updatedAt           更新时间
    accountId           Account ObjectId
}
```

```
chatMessage
{
    _id                 ObjectId
    content: {
        msgType:        消息类型, text or image
        body:           如果是text, 则是消息内容; 如果是image, 则是图片地址
    }
    date                日期 “Y-m-d”格式方便查询
    sentTime            发送时间
    isReply             客服发送的消息则为true, 否则是false
    conversationId      chatConversation ObjectId
    accountId           Account ObjectId
    createdAt           创建时间
    updatedAt           更新时间
    isDeleted           是否删除
}
```

```
pendingClient
{
    _id                 ObjectId
    nick            昵称
    avatar          客户头像
    openId          客户ID, 如果是微信用户, 则是微信OpenID; 网站用户是随机ID
    source          web or wechat
    sourceChannel   目前只针对微信用户, 是微信用户关注的微信号渠道信息
    lastPingTime            最后一次tickle时间
    requestTime         发起聊天请求的时间, 优先服务等待时间久的客户
    isDeleted           是否删除
    createdAt           创建时间
    updatedAt           更新时间
    accountId           Account ObjectId
}
```

# Member

```
memberProperty {
    _id                 ObjectId
    order               属性的展示顺序(1-n)
    name                属性的名称
    propertyId          string
    type                属性的类型('single-text','multiple-text','date','single-select','multiple-select')
    根据类型不同可以扩展新的属性
    options             根据type不同可能没有
    defaultValue        属性的默认值，根据属性的类型而有所不同(select type使用数组存储选择项目，其他使用字符串)
    isRequired          true or false
    isUnique            true or false
    isVisible           true or false
    isDefault           true or false 是否为系统默认属性
    isDeleted           是否删除
    createdAt           创建时间
    updatedAt           更新时间
    accountId           Account ObjectId
}
会员创建时默认属性"姓名","手机","性别","生日","邮箱", 默认值参考 #15
```

```
member {
    _id                 ObjectId
    avatar              会员头像
    cardId              MembershipCard ObjectId
    cardNumber          会员卡
    phone               电话
    cardExpiredAt       会员卡失效时间
    location: {         会员地理位置(自己host全国地理信息)
        county          国家
        province        省份
        city            城市
        detail          详细地址
    }
    tags: [String, String]
    score               会员积分
    socialAccountId     社交账号来源ID (We Connect ID)
    openId              会员社交账号ID
    properties [
        {id: ObjectId, name: String, value: String},
        {id: ObjectId, name: String, value: String},
        ...
    ]
    remarks             会员备注
    socials             [
        {
            openId: string,
            channel: string,
            origin: string,
            originScene: string
        }
    ]
    qrcodeViewed        是否查看过qrcode帮助
    isDeleted           是否删除
    createdAt           创建时间
    updatedAt           更新时间
    accountId           Account ObjectId
    unionId             union ObjectId
    origin              可以为wechat/weibo/alipay/portal/app:android/app:ios/app:web/app:webview/others
    cardProvideTime     会员卡绑定的时间
    birth               生日
    totalScore          会员累计积分
    totalScoreAfterZeroed    最后一次清0后的累计积分
    isDisabled          是否被设为无效
}
```

```
membershipCard {
    _id                 ObjectId
    name                会员卡名称
    poster              会员卡封面
    fontColor           会员卡字体颜色
    privilege           会员卡特权描述
    condition: {        发卡条件，只支持积分范围内自动发卡
        minScore        最小积分
        maxScore        最大积分
    }
    usageGuide          会员卡使用说明
    isEnabled           true or false
    isDefault           true or false 是否是默认卡
    isAutoUpgrade       true or false 是否可自动升级
    isDeleted           是否删除
    createdAt           创建时间
    updatedAt           更新时间
    accountId           Account ObjectId
    scoreResetDate      积分重置时间
}
```

```
memberLogs {
    _id                 ObjectId
    accountId           account ObjectId
    memberId            会员Id ObjectId
    operation           viewed/redeem
    operationAt         操作时间
    createdAt           统计时间
}
```

```
memberStatistics {
    _id                 ObjectId
    locationStatistics  本地会员资源
    updatedAt           更新时间
    createdAt           创建时间
    isDeleted           是否删除
    accountId           account ObjectId
}

```

```
scoreHistory {
    _id                 ObjectId
    assigner            积分发放者(admin,rule)
    increment           正数表示增加，负数表示减少
    brief               shake_score, reward_score, admin_issue_score, admin_deduct_score, rule_assignee, auto_zeroed, exchange_goods, exchange_promotion_code
    description         摇一摇积分， 积分奖励的描述， 用户填写的积分发放描述，积分规则的名字，商品兑换详情或积分兑换详情
    createdAt           创建时间
    memberId            Member ObjectId
    channel             {
        id              渠道账号id
        origin          portal，wechat, app:ios等等
        name            渠道账号的名字
    }
    user                {
        id              MongoId管理员id
        name            管理员昵称
    }
    accountId           account objectId
}
```

```
scoreRule {
    _id                 ObjectId
    name                积分规则名称
    rewardType          奖励类型 score，coupon
    score               会员积分
    couponId            优惠券ID
    code                激励代码
    limit {             激励限制
        type            限制类型(unlimited, day)
        value           次数
    }
    description         积分描述
    times               发放次数
    memberCount         发放会员数
    triggerTime         触发发送的时间类型('day','week','month'), 只有生日积分有该属性
    properties [        只有完善资料送积分有该属性
        propertyId      完善资料送积分时，为需要完善的propertyId
    ]
    startTime           开始时间(默认规则留空)
    endTime             结束时间(默认规则留空)
    isEnabled           true or false
    isDefault           true or false
    isDeleted           是否删除
    createdAt           创建时间
    updatedAt           更新时间
    accountId           Account ObjectId
}
```

# Microsite

```
article {
    _id                 ObjectId
    name                标题
    url                 url
    createdBy           创建人
    picUrl              图片地址
    content             正文
    fields [            自定义字段
        {name: fieldName, type: fieldType, content: ...},
        ...
    ]
    channel             频道id
    isDeleted           是否删除
    createdAt           创建时间
    updatedAt           更新时间
    accountId           Account ObjectId
}
```

```
articleChannel {
    _id                 ObjectId
    name                标题
    fields [            自定义字段
        {id: ObjectId, type: fieldType, name: fieldName, content: ...},
        ...
    ]
    isDefault           true or false
    accountId           account ObjectId
    updatedAt           更新时间
    createdAt           创建时间
    idDeleted           是否删除(true or false)
}

```

```
page {
    _id                 ObjectId
    title               标题
    description         描述
    accountId           account ObjectId
    creator {            create ObjectId
        [id: ObjectId, name: 创建人]
    }
    url                 长链接url
    isPublished         true or false
    count               数量
    deletable           能否删除(true or false)
    updatedAt           更新时间
    createdAt           创建时间
    idDeleted           是否删除(true or false)
    shortUrl            短链接url
    color               颜色
    type                类型
    isFinished          true or false
}

```

```
pageComponent {
    _id                           ObjectId
    jsonConfig {
        tabs [
            [
                name:             名称
                active:           true or false,
                cpts: [
                    _id           ObjectId
                    url           url
                    color         颜色
                    name          名称
                    pageId        page ObjectId
                    parentId      parent ObjectId
                    tabIndex      索引
                    jsonConfig {
                        name      名称
                        image     图片路径
                        linkUrl   链接Url
                        firstTime 次数
                    }
                    ...
                ]
                ...
            ]
        ]
        ...
    }
    color                         颜色
    name                          名称
    pageId                        page ObjectId
    order                         排序次序
    parentId                      parent ObjectId
    accountId                     account ObjectId
    updatedAt                     更新时间
    createdAt                     创建时间
    isDeleted                     是否删除
    tabIndex
    tabs
}
```

```
channel {
    name                频道名称
    fields: [
        {name: fieldName, type: fieldType}
    ]
    isDeleted           是否删除
    createdAt           创建时间
    updatedAt           更新时间
    accountId           Account ObjectId
}
```

# Admin

```
admin {
    _id                 ObjectId
    name                管理员姓名
    email               管理员邮箱
    password            密码
    salt                密码盐值
    avatar              管理员头像
    language            语言（zh，en）
    isDeleted           true or false
    createdAt           创建时间
    updatedAt           更新时间
    accounts [          属于该管理员的account
        accountId       Account ObjectId
    ]
}
```

# Store

```
store {
    _id                 ObjectId
    name                门店名
    branchName          分店名
    type                门店服务类型
    subtype             门店子服务类型
    telephone           门店电话
    location: {         门店地理位置(自己host全国地理信息)
        province        省份
        city            城市
        district        区/县级市
        detail          详细地址
    }
    position            定位
    image               门店图片
    businessHours       营业时间
    description         描述
    wechat: {
        channelId       微信渠道ID
        qrcodeId        微信二维码ID
        qrcode          微信二维码
    }
    weibo: {
        channelId       微博渠道ID
        qrcodeId        微博二维码ID
        qrcode          微博二维码
    }
    alipay: {
        channelId       alipay渠道ID
        qrcodeId        alipay二维码ID
        qrcode          alipay二维码
    }
    isDeleted           true or false
    createdAt           创建时间
    updatedAt           更新时间
}
```

```
storeLocation {
    _id                 ObjectId
    name                省/市/区/门店的名称
    parentName          省/市/区/门店的父级名称
    level               级别 省：1，市：2，区：3，门店：4
    isDeleted           true or false
    createdAt           创建时间
    updatedAt           更新时间
}
```

# Product
```
product {               商品表
    _id                 ObjectId
    sku                 商品编号
    name                商品名称
    type                商品所属组
    pictures    [{
        name:           图片名称
        url：           图片地址qiniu
        size:           图片大小
    }]
    category    {      商品类别
        id                  ObjectId
        name                类型的名称
        properties  [{
            id                  uuid
            name                属性的名称
            value               属性值
            type                属性的类型
        }]
    }
    specifications [           规格
        {
            id                 uuid
            name               规格名称
            properties {
                id             uuid
                name           规格属性
            }
        }
    ]
    qrcode {
        id              qrcode ID
        qiniuKey        qiniu key
    }
    isBindCode          true or false是否绑定promotionCode
    batchCode           生成促销码的批次
    isDeleted           true or false
    createdAt           创建时间
    updatedAt           更新时间
    accountId           Account ObjectId
}
```

```
productInfo {             //商品描述表
    _id                   ObjectId //directly use product's ObjectId
    intro                 商品的描述
    updatedAt             商品更新时间
    createdAt             商品创建时间
    isDeleted             商品是否被删除(true or false)
}
```

```
productCategory {       商品类型表
    _id                 ObjectId
    name                类型的名称
    type                属性类别[product(商品),reservation(服务)]
    properties  [{
        id                  uuid
        order               属性的展示顺序(1-n)
        propertyId          属性Id 第三方api使用
        name                属性的名称
        type                属性的类型('input','date','radio','checkbox')
        options             根据type不同可能没有
        defaultValue        属性的默认值，根据属性的类型而有所不同(select type使用数组存储选择项目，其他使用字符串)
        isRequired          true or false
    }]
    isDeleted           是否删除
    createdAt           创建时间
    updatedAt           更新时间
    accountId           Account ObjectId
}
```

```
promotionCode {         促销码表
    _id                 ObjectId
    productId           商品id ObjectId
    code                促销码
    isUsed              是否已使用
    usedBy: {
        memberId        member Id
        memberNumber    会员卡号
        channelId       channel ID
    }
    usedAt              使用时间
    random              生成促销码的随机数，在导出的时候排序，使原本递增的促销码无序
    isDeleted           是否删除
    createdAt           创建时间
    updatedAt           更新时间
    accountId           Account ObjectId
}
```

```
PromotionCodeAnalysis {  促销码分析
   _id                  objectId
   createdAt            统计时间
   productId            产品ID
   productName          产品名称
   total                总数
   campaignId           活动ID
   type                 统计类型 1.参加者,2.兑换总次数,3.赠品兑换次数,4.参与者总数
   accountId            Account ObjectId
}
```

```
StatsPromotionCodeAnalysis {  促销码分析统计
   _id                  objectId
   createdAt            统计时间
   total                总数
   campaignId           活动ID
   campaignName         活动名称
   type                 统计类型 1.参加者,2.兑换总次数,3.赠品兑换次数,4.参与者总数
   accountId            Account ObjectId
}
```

```
campaign {              促销活动表
    _id                 ObjectId
    name                活动的名称
    startTime           活动开始时间
    endTime             活动结束时间
    participantCount    参与总人数
    usedCount           已参与的人数
    limitTimes          限制每个人参与的次数 大于0
    //above are basic informations

    promotion: {
        type            活动类型, 当前默认  'promotion_code'
        data   [
            productIds  商品id ObjectId 使用这些商品才能参与活动
        ]
        products        参加兑换的商品 'unlimited', 'first' and [productId]
        tags            会员tag
        channels        渠道
        gift    {
            type:'score'    赠品类型 'score' or 'lottery'
            config:     {   赠品的配置规则
                method      'times' or 'score'
                number      倍数或积分
            },
            type:'lottery'
            config:      {
                method       发放赠品方式(按比例或数目) 'scale' or 'number'
                prize   [{
                    name     奖品名称
                    number   奖品数目或中奖人数比例
                }]
            }
        }
    }
    userTags            用户标签
    isAddTags           是否给参加促销码活动的会员添加用户标签
    isActivated         是否启用
    isDeleted           是否删除
    createdAt           创建时间
    updatedAt           更新时间
    accountId           Account ObjectId
}
```

```
reMemberCampaign {      用户活动关系表
    _id                 ObjectId
    memberId            用户ID
    campaignId          活动ID
    usedTimes           该用户对应该活动的参与次数
}
```

```
campaignLog {
    _id             ObjectId
    code        促销码
    member  {
        id          MemberId
        phone       手机
        name        Member nickname
        cardNumber  会员卡号码
        type        赠品类型 'score' or 'lottery'
        scoreAdded  积分变动 +20 or +30
        score       当前积分
        prize       奖品名称
        cardNumber  会员卡编号
    },
    productId       商品id
    productName     商品名称
    campaignName    活动名称
    sku             商品编号
    operaterEmail   兑换操作人员的email，自己在手机兑换的默认为空
    redeemTime      核销时间
    usedFrom     {
        id      Channel ID
        name    Channel Name
        type    Channel Type
    }
    campaignId  活动ID
    createdAt   创建时间
    accountId   Account ObjectId
    usedTime    该用户参与的时间
}
```

```
participateDateStatistics {     参与人次按时间统计表
    _id                 ObjectId
    campaignId          促销活动Id
    date                参与时间 '2015-03-05'
    productNumber       商品编号
    count               统计次数
    accountId           Account ObjectId
}
```

```
participateChannelStatistics {     参与人次按渠道统计表
    _id                 ObjectId
    campaignId          促销活动Id
    channel             渠道名称
    count               统计次数
    accountId           Account ObjectId
}
```

```
participateAreaStatistics {     参与人次按地区统计表
    _id                 ObjectId
    campaignId          促销活动Id
    area                地区名称
    count               统计次数
    accountId           Account ObjectId
}
```

```
exchangeDateStatistics {     兑换人次按时间统计表
    _id                 ObjectId
    campaignId          促销活动Id
    date                参与时间 '2015-03-05'
    name                奖品内容
    count               统计次数
    accountId           Account ObjectId
}
```

```
coupon {
    _id                 ObjectId
    type                优惠卷类型 折扣卷(discount)代金卷(cash)礼品卷(gift)优惠卷(coupon)
    title               优惠卷名称
    total               库存
    limit               每个人领取
    tip                 操作提示
    time {
        type            时间类型 absolute(详细时间) relative(相对时间)
        beginTime       详细时间时候是mongodate，相对时间填的是定义的常量(比如当天day)
        endTime         详细时间时候是mongodate，相对时间填的是int
    }
    picUrl              图片地址
    url                 链接地址
    description         优惠详情
    usageNote           使用须知
    phone               客服电话
    storeType           使用门店类型 all:所有门店；specify:指定(所有门店不保存store信息)
    stores [
        {
            id           门店id
            name         门店名
            branchName   分店名
            address      地址
            phone        电话
        }
    ]
    qrcodes [
        {
            id              二维码ID
            channelId       渠道ID
            channelName     渠道名称
            origin          来源
            qiniuKey        qiniu key
        }
    ]
    discountAmount      折扣额度
    discountCondition   折扣条件
    reductionAmount     减免金额
    isDeleted           true or false
    updatedAt           更新时间
    createdAt           创建时间
    accountId           Account ObjectId
}
```

```
couponLog {
    _id                 ObjectId
    couponId            优惠卷ID
    membershipDiscountId 会员优惠券ID
    type                优惠卷类型
    title               优惠卷名称
    status              操作类型 received(领取),redeemed(核销),deleted(删除)
    member {
        id              会员ID
        name            会员名称
        phone           会员电话
        receiveType     获取方式 (手动领取为 receive, 默认积分规则为 perfect_information，birthday， first_card， 自定义积分规则为string类型scoreRule的_id)
    }
    store {
        id              门店ID
        name            门店名称
    }
    total               领取数量
    staffId             店员ID
    operationTime       操作时间
    createdAt           创建时间
    accountId           Account ObjectId
}
```

```
MembershipDiscount {
    _id                 ObjectId
    code                券码
    coupon {
        id              优惠卷id
        title           优惠卷名称
        picUrl          图片地址
        startTime       优惠卷开始时间
        endTime         优惠卷结束时间
        status          状态 used(使用过),unused(未使用),expired(过期)
        type            优惠卷类型
        receiveType     获取方式
        discountAmount      折扣额度
        discountCondition   折扣条件
        reductionAmount     减免金额
    }
    qrcode {
        id              二维码id
        qiniuKey        qiniu key
    }
    member {
        id              会员ID
        name            会员名称
    }
    createdAt           创建时间
    accountId           Account ObjectId
}
```

```
statsCouponLogDaily {
    _id                 ObjectId
    couponId            优惠券Id
    recievedNum         领取量
    redeemedNum         审核量
    deletedNum          删除量
    date                时间
    createdAt           创建时间
    accountId           Account ObjectId
}
```

# Points Mall
```
goods {                 消费商品表
    _id                 ObjectId
    productId           商品id
    productName         商品名称
    sku                 商品编号
    categoryId          ObjectId
    pictures   [        选择的要展示的商品图片
        url             商品图片的URL，最多五张
    ]
    score               兑换积分
    total               商品剩余总数
    usedCount           已兑换商品数量
    status              商品上下架状态 'on' or 'off'
    onSaleTime          商品上架时间
    offShelfTime        商品下架时间
    url                 商品兑换地址，短链(可取到点击次数)
    order               顺序
    receiveModes [      接收货物模式(快递：express, 自提：self)
        expressage，
        self
    ]
    addresses [
        id              自提地址ID
    ]
    description         描述
    isDeleted           是否删除
    createdAt           创建时间
    updatedAt           更新时间
    accountId           Account ObjectId
}
```

```
goodsExchangeLog {           兑换商品记录表
    _id                     ObjectId
    goods [{
        id                  消费商品id goods _id
        productId           商品id
        sku                 商品编号
        picture             消费商品图片
        productName         商品名称
        count               兑换该商品的数量
    }]
    memberId            MemberId
    memberName          会员的用户名
    telephone           手机号码
    expectedScore       兑换应消耗积分
    usedScore           兑换实际消耗积分
    count               兑换所有商品的数量
    usedFrom    {
        id           Channel ID
        name         Channel Name
        type         Channel Type
    }
    address             送货地址
    postcode            邮政编码
    isRemoved           记录是否已在前台（手机客户端）删除
    isDelivered         商品发货状态
    receiveMode         接收商品方式(express:快递，self:自提)
    accountId           Account ObjectId
    createdAt           创建时间
}
```

# Receive Address
```
receiveAddress {
    _id                 ObjectId
    address             string　　　　自提地址名称
    location  {
        province   string　　　省
        city       string　　　市
        district   string　　　县/区
        detail     string　　　详细地址（不包括省市地区）
    }
    phone               string　　　　电话
    accountId           ObjectId
    createdAt           MongoDate
    updatedAt           MongoDate
    isDeleted           bool
}
```

# Member Statistics
```
statsMemberMonthly {
    _id                 ObjectId
    accountId           account ObjectId
    month               日期'2015-01'
    origin              可以为wechat/weibo/alipay/portal/app:android/app:ios/app:web/app:webview/others
    origin_name         origin为wechat/weibo时，为微信/微博账号的名称
    total               增长数
    createdAt           统计时间
}
```

```
statsMemberDaily {
    _id                 ObjectId
    accountId           account ObjectId
    date                日期'2015-01-13'
    origin              可以为wechat/weibo/alipay/portal/app:android/app:ios/app:web/app:webview/others
    origin_name         origin为wechat/weibo时，为微信微博账号的名称
    total               增长数
    createdAt           统计时间
}
```

```
statsMemberGrowthMonthly {
    _id                 ObjectId
    accountId           account ObjectId
    totalNew            新注册的活跃会员数
    totalActive         活跃会员数
    totalInactive       不活跃的会员数
    month               日期'2015-01'
    createdAt           统计时间
}
```

```
statsMemberGrowthQuarterly {
    _id                 ObjectId
    accountId           account ObjectId
    totalNew            新注册的活跃会员数
    totalActive         活跃会员数
    totalInactive       不活跃的会员数
    year                年
    quarter             1-4季
    createdAt           统计时间
}
```

```
statsMemberPropMonthly {   基于属性的会员总数月份统计
    _id                 ObjectId
    propId              统计基于的会员属性ID
    propValue           统计基于的会员属性值
    total               会员个数
    date                日期'2015-01'
    accountId           Account ObjectId
}
```

```
statsMemberPropQuarterly {   基于属性的会员总数季度统计
    _id                 ObjectId
    propId              统计基于的会员属性ID
    propValue           统计基于的会员属性值
    total               会员个数
    year                统计年份
    quarter             统计季度
    accountId           Account ObjectId
}
```

# Campaign Statistics
```
statsMemberPropAvgTradeQuarterly { 基于属性的人均商品兑换量季度统计
    _id                 ObjectId
    propId              统计基于的会员属性ID
    propValue           统计基于的会员属性值
    avg                 会员人均商品兑换量
    year                统计年份
    quarter             统计季度
    accountId           Account ObjectId
}
```

```
statsMemberPropAvgTradeQuaterly { 基于属性的人均商品码兑换量季度统计
    _id                 ObjectId
    propId              统计基于的会员属性ID
    propValue           统计基于的会员属性值
    productId           兑换商品的ID
    productName         兑换商品的名称
    avg                 会员人均商品兑换量
    year                统计年份
    quater              统计季度
    accountId           Account ObjectId
}
```

```
statsMemberPropTradeCodeQuarterly { 基于属性的商品码兑换量季度统计
    _id                 ObjectId
    propId              统计基于的会员属性ID
    propValue           统计基于的会员属性值
    total               商品兑换参与人数
    year                统计年份
    quarter             统计季度
    accountId           Account ObjectId
}
```

```
statsMemberPropTradeQuarterly { 基于属性的会员参与量季度统计
    _id                 ObjectId
    propId              统计基于的会员属性ID
    propValue           统计基于的会员属性值
    total               商品兑换参与人数
    year                统计年份
    quarter             统计季度
    accountId           Account ObjectId
}
```

```
statsCampaignProductCodeQuarterly { 参与活动的商品兑换的码的数量
    _id                 ObjectId
    productId           兑换商品的ID
    productName         兑换商品的名称
    total               商品兑换参与人数
    year                统计年份
    quarter             统计季度
    accountId           Account ObjectId
}
```

```
messageTemplate {           信息模板文件
    name                    模板名称
    useWebhook              是否使用webhook消息通知
    weChat    [
        templateId          微信模板ID
    ],
    email  [
        title               邮件标题
        content             邮件内容
    ],
    mobile [
        message             短信内容
    ]
    accountId               accountID
    isDeleted               是否删除
    createdAt               创建时间
    updatedAt               更新时间
}

```

```
staff {                    店员表
    _id                    ObjectId
    storeId                门店ID
    phone                  手机
    badge                  员工ID
    name                   姓名
    gender                 性别female,male
    birthday               生日
    email                  邮箱
    password               密码
    salt                   盐值
    channel[
        channelId          渠道id
        channelType        渠道类型
        channelName        渠道名称
    ]
    qrcodeUrl              二维码地址
    qrcodeId               二维码ID
    qrcodeName             二维码名称
    status                 上线状态(online 或 offline)，默认为offline
    isActivated            true or false
    isDeleted              true or false
    createdAt              创建时间
    updatedAt              更新时间
    accountId              Account ObjectId
}
```

```
storeGoods {            商品表
    _id                 ObjectId
    storeId             门店id
    productId           商品id
    productName         商品名称
    sku                 商品编号
    categoryId          ObjectId
    pictures   [        选择的要展示的商品图片
        url             商品图片的URL，最多五张
    ]
    status              商品上下架状态 'on' or 'off'
    price               商品价格
    onSaleTime          商品上架时间
    offShelfTime        商品下架时间
    isDeleted           是否删除
    createdAt           创建时间
    updatedAt           更新时间
    accountId           Account ObjectId
}

```

```
order {                 订单表
    _id                 ObjectId
    storeId             门店ID
    orderNumber         订单编号
    expectedPrice       订单总价
    totalPrice          订单实付总价
    staff {             店员信息
        id              店员id(Staff ID)
        name            店员
    }
    consumer {            会员信息
        id              string类型，非匿名用户为会员ID(Member ID)
        name            会员名称
        phone           会员电话
        avatar          会员头像
    }
    storeGoods [           购买商品
        {
            id          商品ID(storeGoods ID)
            name        商品名称
            pictures [
                pictureUrl  商品图片地址
            ]
            sku         商品编号
            price       商品单价
            count       商品数量
            totalPrice  商品总价
        }
    ]
    status              finished(完成支付), pending(等待支付), canceled(撤销)
    operateTime         支付时间或撤销时间
    payWay              支付方式
    remark              优惠信息
    isDeleted           是否删除
    createdAt           创建时间
    updatedAt           更新时间
    accountId           Account ObjectId
}
```

```
statsMemberOrder {member订单统计表
    _id                 ObjectId
    consumerId          string memberId(匿名用户不统计)
    consumptionAmount   累计消费金额
    transactionCount    交易次数
    maxConsumption      单次最大消费金额
    recentTransactionCount最近6个月交易次数
    createdAt           创建时间
    accountId           Account ObjectId
}
```

```
statsOrder {总订单统计表
    _id                 ObjectId
    consumerCount       人数
    consumptionAmount   累计消费金额
    transactionCount    交易次数
    maxConsumptionTotal 单次最大消费金额累计值
    recentTransactionTotal 最近6个月交易次数累计值
    createdAt           创建时间
    accountId           Account ObjectId
}
```

```
qrcode {                二维码表
    _id                 ObjectId
    type                类型 member, coupon, membershipDiscount, game等
    associatedId        与类型关联的数据ObjectId
    content             qrcode内容url(例如：http://wm.com/webapp/member/55c2bde2d6f97f93338b4567)
    qiniuKey            qiniu key (公共空间下)
    isDeleted           是否删除
    createdAt           创建时间
    updatedAt           更新时间
    accountId           Account ObjectId
}
```

```
channel {               粉丝渠道
    _id                 ObjectId
    channelId           渠道id
    appId               渠道appId
    origin              渠道wechat/weibo/alipay等等
    name                wechat/weibo/alipay账号的名字
    type                账号类型 SERVICE_ACCOUNT， SERVICE_AUTH_ACCOUNT， SUBSCRIPTION_ACCOUNT， SUBSCRIPTION_AUTH_ACCOUNT 等
    qrcodeId            二维码id（用来关注渠道）
    status              enable/disable
    isTest              是否是测试账号
    createdAt           创建时间
    accountId           Account ObjectId
}
```

```
questionnaire {调查问卷
    _id                 ObjectId
    name                名称
    startTime           开始时间
    endTime             结束时间
    creator{            创建人Object
        id              user ObjectId
        name            创建人名字
    }
    description         描述
    questions           [问题列表
        question ObjectId
    ]
    isPublished         是否已发布
    createdAt           创建时间
    accountId           Account ObjectId
}
```

```
question {              调查问卷问题
    _id                 ObjectId
    title               题目
    type                题目类型radio checkbox input
    options            [选项
        {
            icon        图片'support' or 'opposition'
            content     选项内容
        }
    ]
    order               顺序
    createdAt           创建时间
    accountId           Account ObjectId
}
```

```
questionnaireLog {
    _id                 ObjectId
    questionnaireId     问卷ObjectId
    user                {
        channel         社交渠道id
        openId          会员社交账号id
        origin          社交渠道
        name            昵称
    }
    answers             [答案
        {
            questionId  ObjectId
            type        question type
            value
        }
    ]
    createdAt           创建时间
    accountId           Account ObjectId
}
```

```
statsQuestionnaireDaily {
    _id                 ObjectId
    questionnaireId     问卷ObjectId
    count               参与人数数量
    date                时间'2015-08-20'
    createdAt           创建时间
    accountId           Account ObjectId
}
```

```
statsQuestionnaireAnswerDaily {
    _id                 ObjectId
    questionId          问题ObjectId
    stats               [
        {
            option      选项
            count       人数
        }
    ]
    date                时间'2015-08-20'
    createdAt           创建时间
    accountId           Account ObjectId
}
```

```
game {
    _id                 ObjectId
    name                名字
    type                类型loveprogramming， shake
    rule                规则
    poster              图片
    pictures            [详情图片
        'http://...'
    ]
    url                 链接
    qrcodes [
        {
            id          ObjectId 二维码ID
            channelId   渠道ID
            channelName 渠道账号名字
            qiniuKey    qiniu key
            origin      渠道
        }
    ]
    setting             json string
    例： 游戏猿粪的数据结构为
        [
            {
                step         关卡号
                name         关卡名称
                words        array，词语库
                time         秒
            }
        ]
    摇一摇的数据结构为
        {
            condition {         游戏条件
                type            条件类型(范围range,当天today,标签tag)
                value           条件值(范围和当天是整型，标签是数组)
            }
            rule                first avarage
            rewards [
                {
                    time {
                        startTime   从零点到开始时分的毫秒数
                        endTime     从零点到结束时分的毫秒数
                        startDate   日期毫秒时间戳
                        endDate     日期毫秒时间戳
                    }
                    gifts [
                        {
                            type  奖励类型（积分score）
                            value 奖励值
                            number 奖励数量
                        }
                    ]
                }
            ]
        }
    isPublished         是否发布
    createdAt           创建时间
    accountId           Account ObjectId
}
```

```
gameLog {
    _id                 ObjectId
    gameId              ObjectId
    player              {
        memberId        会员Id
        memberName      会员名称
        channelId       渠道账号Id
        openId          社交账号Id
        name            社交账号名称
        origin          社交渠道
    }
    data
    游戏猿粪的数据结构为 {
        level           闯关数
        score           得分（词语总数数）
    }
    摇一摇的数据结构为 {
        score           得分
    }
    createdAt           创建时间
    accountId           Account ObjectId
}
```

```
statsGameLog {
    _id                 ObjectId
    gameId              游戏ObjectId
    member {            会员信息
        id
        name
    }
    channel {           渠道信息
        id
        name
        origin
    }
    participate         参与总次数
    reward              获奖总次数
    score               获奖总积分
    createdAt           创建时间
    accountId           Account ObjectId
}
```

```
gameToken {
    _id                 ObjectId
    memberId            ObjectId
    gameId              ObjectId
    token               string
    isUsed              是否用过true, false
    validFrom           开始有效时间MongoDate
    validTo             截止有效时间MongoDate
    accountId           ObjectId
    createdAt           创建时间
}
```

```
webhookEvent {
    _id                 ObjectId
    channel             string
    type                string
    module              string
    startAt             开始时间MongoDate
    endAt               结束时间MongoDate
    createdAt           创建时间
}
```

```
dingdingUser {
    _id                 ObjectId
    corpId              string
    dingId              string(员工在企业的唯一标识对应钉钉userId)
    name                string
    avatar              string
    mobile              string
    email               string
    openId              string
    enableActions       [
        mobile_pos, helpdesk
    ]
    createdAt           创建时间
    accountId           Account ObjectId
}
```

```
follower {
    _id                 ObjectId
    channelId           string
    openId              string
    phone               string
    properties [
        {id: ObjectId, name: String, value: Int/String/Array},
        {id: ObjectId, name: String, value: Int/String/Array},
        ...
    ]
    createdAt
    accountId           Account ObjectId
}
```