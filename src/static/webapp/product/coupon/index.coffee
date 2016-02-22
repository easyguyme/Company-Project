$ ->
  $('title')[0].innerText = '优惠券'

  $success = '<div class="success-wrapper">
      <ul class="success-options">
          <li class="success-tip">
              <div class="tip-one">领取成功</div>
              <div class="tip-two">你可以在会员中心“我的优惠”中查看</div>
          </li>
          <li class="success-option success-view">查看</li>
          <li class="success-option success-close">关闭</li>
      </ul>
  </div>'

  bindWechatShare = (channelId, couponId, description, imgLink) ->
    if wx
      wx.config({
        debug: false
        appId: options.appId
        timestamp: options.timestamp
        nonceStr: options.nonceStr
        signature: options.signature
        jsApiList: [
          'onMenuShareAppMessage',
          'onMenuShareTimeline',
          'onMenuShareQQ',
          'onMenuShareWeibo'
        ]
      })

      wx.ready( ->
        DOMAIN = location.protocol + '//' + location.host
        message =
          title: '优惠券'
          link: DOMAIN + '/api/mobile/coupon?channelId=' + channelId + '&couponId=' + couponId
          desc: description
          imgUrl: imgLink

        wx.onMenuShareAppMessage(message)
        wx.onMenuShareQQ(message)
        wx.onMenuShareWeibo(message)
        wx.onMenuShareTimeline(message)
      )

  couponId = util.queryMap.couponId
  memberId = util.queryMap.memberId
  channelId = util.queryMap.channelId
  id = util.queryMap.id
  result = util.queryMap.result
  message = util.queryMap.message

  $('.coupon-link-detail').attr('href', '/mobile/product/couponDetail?couponId=' + couponId)
  $('.coupon-link-store').attr('href', '/mobile/product/couponStore?couponId=' + couponId)

  if couponId
    param =
      couponId: couponId
      channelId: channelId
      memberId: memberId
    rest.get '/mobile/open-coupon', param, (data) ->
      if data
        $('.coupon-img').attr('src', data.picUrl)
        $('.coupon-title').html(data.title)
        $('.coupon-time').html(_formateTime(data.time))
        bindWechatShare(channelId, couponId, data.title, data.picUrl) if channelId

        if result
          if result is 'success'
            $(document.body).append $success
            $('.success-view').on('click', ->
              window.location.href = '/mobile/product/couponSingle?id=' + id + '&memberId=' + memberId
            )
            $('.success-close').on('click', ->
              $('.success-wrapper').remove()
              _validateReceive(data)
            )

          else
            $('.coupon-btn').attr("style","background-color:#bbb")
            $('.coupon-error-tip').html(decodeURI(message))
            $('.coupon-error-tip').show()

        else
          _validateReceive(data)

    , (xhr) ->
      location.href = '/mobile/common/404'
  else
    location.href = '/mobile/common/404'

  _validateReceive = (data) ->
    if data.isReceived
      _receiveCoupon()
    else
      if data.message
        $('.coupon-btn').attr("style","background-color:#bbb")
        $('.coupon-error-tip').html(data.message)
        $('.coupon-error-tip').show()

  _receiveCoupon = ->
    $('.coupon-btn').on('click', ->
      param =
        couponId: couponId
        channelId: channelId
        memberId: memberId
        type: 'received'
      rest.get '/mobile/coupon', param, (data) ->
        location.href = data.url if data.url
    )

  _formateTime = (time) ->
    str = '有效期：'
    if time.type is 'absolute'
      startTime = time.beginTime.replace(/-/g, '.').split(' ')[0]
      endTime = time.endTime.replace(/-/g, '.').split(' ')[0]
      str += startTime
      if startTime != endTime
        str += ' - ' + endTime
    else
      if time.beginTime is 0
        str += '领取后当天生效，'
      else
        str += '领取后' + time.beginTime + '天生效，'
      str += '有效天数' + time.endTime + '天'
    str
