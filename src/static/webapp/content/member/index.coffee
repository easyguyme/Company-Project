$ ->
  memberProperty = undefined
  memberTags = undefined
  memberAvatar = undefined
  memberPurchase = undefined
  memberExtendProperty = undefined
  memberPropertyModal = undefined
  memberAccount = undefined
  $memberInfo = $('.member-info')
  $memberpurchase = $('.member-purchase')
  $memberName = $('.member-name')
  $cardName = $('.member-card-name')
  $cardNumber = $('.member-card-number')

  $purchaseWrap = $('.member-purchase-wrap')
  $purchaseNoRecord = $('.member-purchase-no-record')
  $purchaseDays = $('.purchase-days')
  $purchaseTime = $('.purchase-time')

  $memberExtend = $('#member-extend')

  memberApi = '/member/member'
  memberOrderStatsApi = '/member/order/stats'
  memberPropertyApi = '/common/member-propertys'

  navs = [
    name: '基本资料'
    active: true
  ,
    name: '购买行为记录'
    active: false
  ]

  purchaseStatsConf = [
    title: '累计消费金额'
    icon: 'url(\'/images/customer/accruingamounts.png\')'
    bgColor: ['#efa46c', 'rgba(240, 165, 110, 0.4)']
    fontColor: ['#dc8746', '#7b7b7b']
    width: ['0%', '0%']
    data: []
    unit: '元'
    keyword: '平均值'
  ,
    title: '最近六个月购买次数'
    icon: 'url(\'/images/customer/times.png\')'
    bgColor: ['#8abbd8', 'rgba(140, 185, 215, 0.4)']
    fontColor: ['#508caf', '#7b7b7b']
    width: ['0%', '0%']
    data: []
    unit: '次'
    keyword: '平均值'
  ,
    title: '平均每次交易额'
    icon: 'url(\'/images/customer/average_in_amount.png\')'
    bgColor: ['#aad096', 'rgba(170, 205, 150, 0.4)']
    fontColor: ['#6fa155', '#7b7b7b']
    width: ['0%', '0%']
    data: []
    unit: '元'
    keyword: '平均值'
  ,
    title: '单次最高交易额'
    icon: 'url(\'/images/customer/highest.png\')'
    bgColor: ['#c2bdda', 'rgba(195, 190, 215, 0.4)']
    fontColor: ['#c2bdda', '#7b7b7b']
    width: ['0%', '0%']
    data: []
    unit: '元'
    keyword: '平均值'
  ]

  modalCof =
    title: '扩展属性'

  init = ->
    riot.mount('nav-tab', {navs: navs, clickNav: showHide})
    memberAvatar = riot.mount('avatar')[0]
    memberTags = riot.mount('m-tags')[0]
    memberAccount = riot.mount('account')[0]
    memberPurchase = riot.mount('m-purchase')[0]
    memberPropertyModal = riot.mount('modal', {conf: modalCof})[0]
    memberProperty = riot.mount('.member-default-property', 'kv-list')[0]
    memberExtendProperty = riot.mount('.member-extend-property', 'kv-list')[0]
    getMemberInfo()

  getMemberInfo = ->
    memberId = util.queryMap.memberId

    #get member info
    rest.get memberApi + '/' + memberId, (data) ->
      formateMemberInfo data
    , (error) ->
      window.location.href = '/mobile/common/dd403'

    #get member purchase stats
    param =
      memberId: memberId
    rest.get memberOrderStatsApi, param, (data) ->
      formateMemberPurchase data

  # Format member info
  formateMemberInfo = (data) ->
    if data
      tags =
        key: '标签'
        value: [
        ]
      tags.value = data.tags
      updateComponent memberTags, {item: tags}

      properties = {}

      if data.properties?
        for property in data.properties
          properties[property.name] = property.value

      if properties.birthday
        properties.birthday = util.dateFormat(new Date(parseInt properties.birthday), 'yyyy-MM-dd')

      avatar = data.avatar or "/images/management/image_hover_default_avatar.png"
      updateComponent memberAvatar, {img: avatar}
      $cardName.text(data.card.name or '--')
      $cardNumber.text(data.cardNumber or '--')
      $memberName.text(properties.name or '--')

      account =
        key: '获取渠道'
        accounts: [
        ]
      account.accounts.push data.socialAccount
      updateComponent memberAccount, {item: account}

      memberPropertys = [
        key: '手机'
        value: properties.tel or '--'
      ,
        key: '邮箱'
        value: properties.email or '--'
      ,
        key: '生日'
        value: properties.birthday or '--'
      ]

      updateComponent memberProperty, {items: memberPropertys}

      condition =
        'where': JSON.stringify({'isVisible': true, 'isDefault': false})
        'orderBy': JSON.stringify({'order': 'asc'})
        'unlimited': true
      # Get all the properties
      memberProperties = data.properties
      memberExtendProperties = []
      rest.get memberPropertyApi, condition, (data) ->
        if data?.items.length > 0
          for memberItem in data.items
            if memberProperties.length > 0
              for item in memberProperties
                if memberItem.id is item.id
                  value = item.value
                  if memberItem.type is 'date'
                    value = util.dateFormat(new Date(parseInt value), 'yyyy-MM-dd') if value
                  else if memberItem.type is 'checkbox'
                    value = value.join('、') if value and $.isArray(value)
            if memberItem.type is 'radio'
              if $.isArray(memberItem.options) and memberItem.options.length > 0
                value = value or memberItem.options[0]
            property =
              key: memberItem.name
              value: value or '--'
            memberExtendProperties.push property

        if memberExtendProperties.length > 0
          updateComponent memberExtendProperty, {items: memberExtendProperties}
          $memberExtend.show()

  # Format member purchase
  formateMemberPurchase = (data) ->
    if data?.operateInterval
      purchase = data
      $purchaseDays.text(purchase.operateInterval)
      $purchaseTime.text(purchase.lastOperateTime)
      formatOrderStatsInfo purchase
      updateComponent memberPurchase, {items: purchaseStatsConf}
      $purchaseWrap.show()
      $purchaseNoRecord.hide()

  # Format OrderStats info
  formatOrderStatsInfo = (data) ->
    purchaseStatsConf[0].data = [data.consumptionAmount, data.consumptionAmountAvg]
    purchaseStatsConf[1].data = [data.recentConsumption, data.recentConsumptionAvg]
    purchaseStatsConf[2].data = [data.consumption, data.consumptionAvg]
    purchaseStatsConf[3].data = [data.memberMaxConsumption, data.maxConsumption]
    purchaseStatsConf[0].width = operateStatsWidth(data.consumptionAmount, data.consumptionAmountAvg)
    purchaseStatsConf[1].width = operateStatsWidth(data.recentConsumption, data.recentConsumptionAvg)
    purchaseStatsConf[2].width = operateStatsWidth(data.consumption, data.consumptionAvg)
    purchaseStatsConf[3].width = operateStatsWidth(data.memberMaxConsumption, data.maxConsumption)

  operateStatsWidth = (param1, param2) ->
    width = ['0%', '0%']
    if param1 isnt 0 or param2 isnt 0
      param1Width = ''
      param2Width = ''

      if param1 >= param2
        param1Width = '100%'
        param2Width = Number(param2 * 100 / param1) + '%'
      else
        param1Width = Number(param1 * 100 / param2) + '%'
        param2Width = '100%'
      width = [param1Width, param2Width]
    return width

  showHide = (idx) ->
    if idx is 0
      $memberInfo.show()
      $memberpurchase.hide()
    else
      $memberInfo.hide()
      $memberpurchase.show()

  updateComponent = (component, opts) ->
    if component
      opts = opts or {}
      component.opts = component.opts or {}
      component.opts = $.extend component.opts, opts
      component.update(component.opts)

  $memberExtend.on 'click', ->
    updateComponent memberPropertyModal, {isShowModal: true}

  init()
