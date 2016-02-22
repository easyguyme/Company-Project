$( ->

  document.title = '我的优惠'

  $hasItems = $('.has-items')
  $couponWrapper = $('.coupon-wrapper')
  $tabsItem = $('.mb-tab-item')
  $itemsWrapper = $hasItems.find('.items-wrapper')
  $loading = $('.m-loading')
  $noItems = $('.no-items')

  currentPage = 1
  pageCount = 0
  totalCount = 0
  elemLength = 0

  init = ->
    active = util.queryMap.active or '0'
    if active is '0'
      getNoUsedList()
    else if active is '1'
      $($('.mb-tab-item')[1]).trigger('click')
    else
      $($('.mb-tab-item')[2]).trigger('click')

  _initItem = ->
    couponItems = new Swiper('.items-wrapper .swiper-container', {
      slidesPerView: 'auto'
    })

  getNoUsedList = (isClear, callback) ->
    memberId = util.queryMap.memberId
    isClear = isClear or false

    if not memberId
      throw new Error 'Lack of member id'
    else
      $loading.show()

      param =
        page: currentPage
        'per-page': 10
        'memberId': memberId
        'status': 'unused'
      rest.get "/product/membership-discounts", param, (data) ->
        if data
          if data.items.length is 0
            noRecordsCallback('noused')
          else
            hasRecordsCallback(data.items, 'noused', isClear)
          pageCount = data._meta.pageCount
          totalCount = data._meta.totalCount
          $loading.hide()

        callback and callback()
      , (xhr) ->
        $loading.hide()

  getUsedList = (isClear, callback) ->
    memberId = util.queryMap.memberId
    isClear = isClear or false

    if not memberId
      throw new Error 'Lack of member id'
    else
      $loading.show()

      param =
        page: currentPage
        'per-page': 10
        'memberId': memberId
        'status': 'used'
      rest.get "/product/membership-discounts", param, (data) ->
        if data
          if data.items.length is 0
            noRecordsCallback('used')
          else
            hasRecordsCallback(data.items, 'used', isClear)
          pageCount = data._meta.pageCount
          totalCount = data._meta.totalCount
          $loading.hide()

        callback and callback()
      , (xhr) ->
        $loading.hide()

  getExpiredList = (isClear, callback) ->
    memberId = util.queryMap.memberId
    isClear = isClear or false

    if not memberId
      throw new Error 'Lack of member id'
    else
      $loading.show()

      param =
        page: currentPage
        'per-page': 10
        'memberId': memberId
        'status': 'expired'
      rest.get "/product/membership-discounts", param, (data) ->
        if data
          if data.items.length is 0
            noRecordsCallback('expired')
          else
            hasRecordsCallback(data.items, 'expired', isClear)
          pageCount = data._meta.pageCount
          totalCount = data._meta.totalCount
          $loading.hide()

        callback and callback()
      , (xhr) ->
        $loading.hide()

  noRecordsCallback = (type) ->
    content =
      noused:
        contentText: '您还没有未使用的记录哦'
      used:
        contentText: '您还没有已使用的记录哦'
      expired:
        contentText: '您还没有已过期的记录哦'

    $noItems.find('.no-items-content').text(content[type].contentText)

    $couponWrapper.addClass('no-records')
    $itemsWrapper.empty()

  hasRecordsCallback = (records, type, isClear) ->
    memberId = util.queryMap.memberId
    isClear = isClear or false
    $couponWrapper.removeClass('no-records')

    $itemsWrapper.empty() if isClear

    $.each records, (index, item) ->

      id = item.id
      image = item.coupon.picUrl
      validity = ''
      validityTime = ''
      name = ''
      contentName = ''

      switch type
        when 'noused'
          validity = '有效期：'
          startDate = item.coupon.startTime.replace(/-/g, '.').split(' ')[0]
          endDate = item.coupon.endTime.replace(/-/g, '.').split(' ')[0]
          validityTime = startDate + '-' + endDate
          name = item.coupon.title
        when 'used'
          validity = '已使用'
          name = item.coupon.title
        when 'expired'
          validity = '已过期'
          name = item.coupon.title

      orderWrapper = ''

      $orderItem = '<li>' +
                      '<div class="swiper-container">' +
                        '<div class="swiper-wrapper clearfix">' +
                          '<a class="order-item swiper-slide" style="height: 12rem;" href="/mobile/product/couponSingle?id=' + id + '&memberId=' + memberId + '">' +
                            '<div class="order-content-wrapper">' +
                                '<div class="order-image">' +
                                  '<img class="order-image-center" src="' + image + '">' +
                                '</div>' +
                                '<div class="order-content ' + type + 's-wrapper">' +
                                    '<div class="order-content-name"><span class="content-name">' + name + '</span></div>' +
                                    '<div class="validity-time">' +
                                      '<label class="validity-title">' + validity + '</label>' +
                                      '<span class="validity-date">' + validityTime + '</span>' +
                                    '</div>' +
                                '</div>' +
                            '</div>' +
                          '</a>' +
                          '<div class="delete-button swiper-slide" style="width: 6rem;" data-id="' + id + '" data-type="' + type + '">' +
                            '<div class="order-content-wrapper delete-icon"></div>' +
                          '</div>' +
                        '</div>' +
                      '</div>' +
                      '</li>'
      $itemsWrapper.append($orderItem)

    elemLength = $('.order-item').length

    _initItem()

  reloadRecords = (isClear) ->
    isClear = isClear or false

    checkedTab = $('.mb-checked-item').data('type')
    switch checkedTab
      when 'noused'
        getNoUsedList(isClear)
      when 'used'
        getUsedList(isClear)
      when 'expired'
        getExpiredList(isClear)

  $(window).scroll util.debounce( ->
    if $(document).height() - $(this).scrollTop() - $(this).height() <= 2
      currentPage++
      if currentPage <= pageCount
        reloadRecords()
  )

  $('.items-wrapper').on('click', '.swiper-slide', (e) ->
    $target = $(e.target)
    if $target.hasClass('swiper-container') or $target.hasClass('swiper-wrapper')
      return
    else if $target.hasClass('delete-button') or $target.parents('.delete-button').length
      elemLength = $('.order-item').length
      id = if $target.hasClass('delete-icon') then $target.parent('.delete-button').data('id') else $target.data('id')
      $target.parents('.swiper-container').remove()
      rest.del "/product/membership-discount/" + id, (data) ->
        $target.remove()
        if totalCount - 1 is 0
          $('.mb-checked-item').trigger('click')
        else if elemLength - 1 < 5 and totalCount - 1 >= 5
          currentPage = 1
          reloadRecords(true)
        else
          totalCount -= 1
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

  init()

  return
)
