$( ->

  type = util.queryMap.type

  init = ->
    document.title = '兑换记录'
    initItemDetailTitle()
    getExchange()

  initItemDetailTitle = ->
    detailsLabel =
      exchange:
        'phone': '手机'
        'time': '兑换时间'
        'number': '数量'
        'score': '消耗积分'
        'receiveMode': '收货方式'
        'address': '收货地址'
      gift:
        'phone': '手机'
        'time': '抽奖时间'
        'number': '活动名称'
        'score': '消耗积分'

    for key, name of detailsLabel
      $(".item-#{key}").find('.detail-item-title').text(name)

  renderItemDetail = (data) ->
    giftTypeName =
      scratchcard: '幸运刮刮卡'
      luckywheel: '幸运大转轮'

    if type is 'exchange'
      if $.isArray(data.goods) and data.goods.length > 0
        data.count = data.goods[0].count
        data.name = data.goods[0].productName
        data.picture = data.goods[0].picture

    elemAlias =
      exchange:
        'phone': 'telephone'
        'time': 'createdAt'
        'number': 'count'
        'score': 'usedScore'
        'name': 'name'
        'picture': 'picture'
        'receiveMode': 'receiveMode'
        'address': 'address'
      gift:
        'phone': 'telephone'
        'time': 'time'
        'number': 'type'
        'score': 'usedScore'
        'name': 'productName'
        'picture': 'picture'

    for key, field of elemAlias[type]
      value = data[field]
      if key is 'receiveMode'
        if value is 'express'
          value = '快递送货'
          $('span.self').hide()
        else if value is 'self'
          value = '上门自提'
          $('span.express').hide()
      value = value + '积分' if key is 'score'
      value = giftTypeName[value] if type is 'gift' and key is 'number'
      $(".item-#{key}").find('.detail-item-content').text(value)

    if data['isDelivered']
      $('.unexpress').hide()
    else
      $('.inexpress').hide()

    picture = data[elemAlias[type].picture] or '/images/mobile/article_default.png'
    $('.goods-snap-image').attr('src', picture)
    $('.goods-snap-title').text(data[elemAlias[type].name])
    $('.goods-snap-points').text(data.usedScore / data.count) if type is 'exchange'

  getExchange = (callback) ->
    id = util.queryMap.id

    if not id
      throw new Error "Lack of #{type} id"
    else
      switch type
        when 'exchange'
          rest.get "/mall/goods-exchange-log/#{id}", (data) ->
            if data
              renderItemDetail(data)
            callback and callback()
        when 'gift'
          # TODO: according API get gift detail
          return

  init()

)
