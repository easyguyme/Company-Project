$( ->

  document.title = '兑换记录'

  $noItems = $('.no-items')
  $hasItems = $('.has-items')
  $recordWrapper = $('.record-wrapper')
  $tabsItem = $('.mb-tab-item')
  $loading = $('.m-loading')
  $itemsWrapper = $hasItems.find('.items-wrapper')

  currentPage = 1
  pageCount = 0
  totalCount = 0

  operateType = 'detail'

  init = ->
    active = util.queryMap.active or '0'
    if active is '0'
      getExchangeRecords()
    else
      $($('.mb-tab-item')[1]).trigger('click')

  orderItemClickHandler = (event) ->
    $elem = $(event.target)
    if $elem.hasClass('items-wrapper')
      return
    if not $elem.hasClass('order-item')
      $elem = $elem.parents('.order-item')
    if $elem.hasClass('check-item')
      $check = $elem.find('.order-check')
      if $check.hasClass('checked-order')
        $elem.data('check', 'false')
      else
        $elem.data('check', 'true')
      $check.toggleClass('checked-order')

      $delSure = $('#delSure')
      if $('.order-item').find('.checked-order').length > 0
        $delSure.removeClass('mb-btn-disable')
      else
        $delSure.addClass('mb-btn-disable')
    else
      type = $('.mb-checked-item').data('type')
      id = $elem.data('recordId')
      location.href = "/mobile/product/hdetail?id=#{id}&type=#{type}"

  calcOrderContentWidth = ->
    $orderItem = $('.order-item')
    $orderCheck = $('.order-check')
    $orderWrapper = $('.order-content-wrapper')

    if operateType is 'check'

      checkWidth = $orderCheck.width()
      itemPaddingWidth = if $orderItem.css('padding-left') then 2 * $orderItem.css('padding-left').replace('px', '') else 0
      itemWidth = $orderItem.width() - itemPaddingWidth
      wrapperWidth = itemWidth - checkWidth
      $orderWrapper.width(wrapperWidth)
    else
      $orderWrapper.css 'width', '100%'


  calcRecordWrapperHeight = ->
    $noItems.height($('body').height() - $('.mb-tab').height() - 10)

  getExchangeRecords = (isClear, callback) ->
    memberId = util.queryMap.memberId
    isClear = isClear or false

    if not memberId
      throw new Error 'Lack of member id'
    else
      $loading.show()

      param =
        page: currentPage
        'per-page': 10
      rest.get "/mall/goods-exchange-log/member/#{memberId}", param, (data) ->
        if data
          if data.items.length is 0
            noRecordsCallback('exchange')
          else
            hasRecordsCallback(data.items, 'exchange', isClear)
          pageCount = data._meta.pageCount
          totalCount = data._meta.totalCount
          $loading.hide()

        callback and callback()
      , (xhr) ->
        $loading.hide()

  getGiftRecords = (isClear, callback) ->
    isClear = isClear or false
    records = []
    # TODO: according API get gift records
    if records.length is 0
      noRecordsCallback('gift')
    else
      hasRecordsCallback(records, 'gift', isClear)

  noRecordsCallback = (type) ->
    content =
      exchange:
        addClassName: 'no-exchanges'
        removeClassName: 'no-gifts'
        contentText: '您还没有积分兑换订单哦'
      gift:
        addClassName: 'no-gifts'
        removeClassName: 'no-exchanges'
        contentText: '您还没有获奖记录哦'

    $noItems.addClass(content[type].addClassName).removeClass(content[type].removeClassName)
    $noItems.find('.no-items-content').text(content[type].contentText)

    $recordWrapper.addClass('no-records')
    $itemsWrapper.empty()
    calcRecordWrapperHeight()

  hasRecordsCallback = (records, type, isClear) ->
    isClear = isClear or false
    $recordWrapper.removeClass('no-records')
    $recordWrapper.find('.items-wrapper').addClass("#{type}s-wrapper")

    $itemsWrapper.empty() if isClear

    $.each records, (index, item) ->
      giftTypeName =
        scratchcard: '幸运刮刮卡'
        luckywheel: '幸运大转轮'

      id = item.id
      image = item.goods[0].picture or '/images/mobile/article_default.png'
      name = ''
      shadowType = ''
      numberText = ''

      switch type
        when 'exchange'
          numberText = '<div class="content-count text-el">数量：' + item.goods[0].count + '</div>' +
                        '<div class="content-score text-el">消耗' + item.usedScore + '积分</div>'
          name = item.goods[0].productName
        when 'gift'
          shadowType = "shadow-#{item.type}"
          numberText = giftTypeName[item.type]
          name = item.name

      orderWrapper = ''
      orderWrapper = 'style="width: 100%;"' if operateType is 'check'

      $orderItem = $ '<li class="order-item ' + operateType + '-item" data-record-id="' + id + '">' +
                        '<div class="order-check"></div>' +
                        '<div class="order-content-wrapper" ' + orderWrapper + '>' +
                            '<div class="order-image" style="background-image: url(' + image + ')">' +
                                '<div class="order-image-shadow ' + shadowType + '"></div>' +
                            '</div>' +
                            '<div class="order-content">' +
                                '<h4 class="order-content-name text-el">' + name + '</h4>' +
                                '<div class="order-content-number">' + numberText + '</div>' +
                            '</div>' +
                            '<div class="order-detail"></div>' +
                        '</div>' +
                    '</li>'
      $itemsWrapper.append($orderItem)

    calcOrderContentWidth()

  reloadRecords = (isClear) ->
    isClear = isClear or false

    checkedTab = $('.mb-checked-item').data('type')
    switch checkedTab
      when 'exchange'
        getExchangeRecords(isClear)
      when 'gift'
        getGiftRecords(isClear)

  $(window).scroll util.debounce( ->
    if $(document).height() - $(this).scrollTop() - $(this).height() <= 10
      currentPage++
      if currentPage <= pageCount
        reloadRecords()
  )

  # Change tab
  $tabsItem.each (index, elem) ->
    $tab = $ elem

    $tab.on 'click', (event) ->
      $tabsItem.removeClass('mb-checked-item')
      $tab.addClass('mb-checked-item')

      currentPage = 1
      totalCount = 0

      reloadRecords(true)

      $('#delCannel').trigger('click')

  # locate to detail page
  $('.items-wrapper').on 'click', (event) ->
    orderItemClickHandler(event)
    e = event or window.event
    if (e.stopPropagation)
      e.stopPropagation()
    else
      e.cancelBubble = true

  $('.delete-icon-wrapper').on 'click', (event) ->
    $(this).addClass('hide')
    $('.btns-wrapper').removeClass('hide')
    $('#delSure').addClass('mb-btn-disable')

    $.each $('.order-item'), (index, elem) ->
      $elem = $ elem
      $elem.removeClass('detail-item').addClass('check-item')

      $content = $elem.find('.order-content-wrapper')
      $check = $elem.find('.order-check')

    operateType = 'check'
    calcOrderContentWidth()
    $recordWrapper.css('padding-bottom', '10rem')

  $('#delSure').on 'click', (event) ->
    ids = []
    orderItems = []
    elemLength = $('.order-item').length
    $.each $('.order-item'), (index, elem) ->
      $elem = $(elem)
      if $elem.data('check')
        ids.push $elem.data('recordId')
        orderItems.push $elem
    orderItemsLength = orderItems.length
    if ids.length isnt 0
      rest.del "/mall/goods-exchange-log/" + ids.join(','), (data) ->
        $.each orderItems, (index, item) ->
          $(item).remove()
        if totalCount - orderItemsLength is 0
          $('.mb-checked-item').trigger('click')
        else if elemLength - orderItemsLength < 5 and totalCount - orderItemsLength >= 5
          currentPage = 1
          reloadRecords(true)
        else
          totalCount -= orderItemsLength

        $('#delSure').addClass('mb-btn-disable')

    $recordWrapper.css('padding-bottom', '4rem')

  $('#delCannel').on 'click', (event) ->
    $(this).parents('.btns-wrapper').addClass('hide')
    $('.delete-icon-wrapper').removeClass('hide')

    $orders = $('.order-item')
    $orders.removeClass('check-item').addClass('detail-item').data('check', 'false')
    $orders.find('.order-content-wrapper').css('width', '100%')
    $orders.find('.order-check').removeClass('checked-order')
    operateType = 'detail'

    calcOrderContentWidth()
    $recordWrapper.css('padding-bottom', '4rem')

  init()

  return
)
