$ ->
  $('title')[0].innerText = '优惠券详情'

  couponId = util.queryMap.couponId

  if couponId
    param =
      couponId: couponId
    rest.get '/mobile/coupon-store', param, (data) ->
      if data.description
        $('.detail-description').html(data.description.replace(/\n/g,'<br/>'))
      else
        $('.detail-description').remove()
        $('.detail-description-title').remove()
      $('.detail-usage').html(data.usageNote.replace(/\n/g,'<br/>'))
      $('.detail-phone').html(data.phone)
