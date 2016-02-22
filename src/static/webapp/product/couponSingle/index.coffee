$ ->
  $('title')[0].innerText = '优惠券'

  id = util.queryMap.id
  couponId = util.queryMap.couponId
  memberId = util.queryMap.memberId

  if id and memberId
    rest.get '/product/membership-discount/' + id, (data) ->
      if data
        $('.coupon-link-detail').attr('href', '/mobile/product/couponDetail?couponId=' + data.coupon.id)
        $('.coupon-link-store').attr('href', '/mobile/product/couponStore?couponId=' + data.coupon.id)

        $('.qrcode-img').attr('src', data.qrcode.url)
        $('.qrcode-code').text(data.code.replace(/(.{4})/g,'$1-').substring(0, 14))
        $('.success-img').attr('src', data.coupon.picUrl)
        $('.success-title').text(data.coupon.title)
        $('.success-time').text(_formateTime(data.coupon.startTime, data.coupon.endTime, 'absolute'))

  else if couponId
    rest.get '/marketing/coupon/' + couponId, (data) ->
      $('.success-img').attr('src', data.picUrl)
      $('.success-title').text(data.title)
      $('.success-time').text(_formateTime(data.time.beginTime, data.time.endTime, data.time.type))
      $('.coupon-link-store').addClass('link-line')
      $('.coupon-link-store').text('查看适用门店')
      $('.coupon-wrapper-bottom').append('<a class="coupon-link">查看公众号</a>')

  else
    location.href = '/mobile/common/404'

  _formateTime = (beginTime, endTime, type) ->
    str = '有效期：'
    if type is 'absolute'
      beginTimeStr = beginTime.replace(/-/g, '.').split(' ')[0]
      endTimeStr = endTime.replace(/-/g, '.').split(' ')[0]
      str += beginTimeStr
      if beginTimeStr != endTimeStr
        str += ' - ' + endTimeStr
    else
      if beginTime is 0
        str += '领取后当天生效，'
      else
        str += '领取后' + beginTime + '天生效，'
      str += '有效天数' + endTime + '天'
    str
