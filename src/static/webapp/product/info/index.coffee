$ ->
  document.title = '商品详情'

  queryMap = util.queryMap

  resources =
    product: '/product-info'

  _getProductInfo = ->
    params =
      fields: 'intro'

    rest.get "#{resources.product}/#{queryMap.productId}", params, (data) ->
      $('#introduction').html(data.intro)

  _getProductInfo()


