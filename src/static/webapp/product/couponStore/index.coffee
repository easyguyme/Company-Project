$ ->
  $('title')[0].innerText = '适用门店'

  couponId = util.queryMap.couponId

  if couponId
    param =
      couponId: couponId
    rest.get '/mobile/coupon-store', param, (data) ->
      if data
        _formateStore data.stores

  _formateStore = (stores) ->
    $store = ''
    if stores
      for store in stores
        $store += '<li class="store-item">
              <label class="store-title">' + store.name + '</label>
              <div class="store-content">' + store.address + '</div>
          </li>'
    $('.store-items').html $store
