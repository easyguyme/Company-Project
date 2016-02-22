$ ->
  myScore = 0
  locationMap = util.queryMap

  _initSwipe = ->
    tabs = new Swiper('.detail-bg .pic-container', {
      pagination: '.swiper-pagination'
      spaceBetween: 30
    })

  _getMemberScore = ->
    rest.get '/member/member/' + locationMap['memberId'], (data) ->
      myScore = data.score
      $('#myScore').text myScore
      _getItemInfo()

  _getItemInfo = ->
    rest.get '/mall/goods/' + locationMap['goodsId'], (data) ->

      productName = data.productName
      productScore = data.score

      document.title = productName + '-查看商品详情'
      $('.mb-breadcrumb-title').text productName
      $('#productName').text productName
      $('#productScore').text productScore

      ## account
      if data.total is ''
        tip = '库存充足'
      else if data.total is 0
        tip = '抢光了...'
      else if data.total > 0
        tip = '剩余' + data.total + '件商品'
      $('.item-remain-status').text tip
      ## account

      if myScore < productScore
        $('#pointNotEnough').show()
      else if data.total is '' or data.total > 0
        $('#exchange').show()
      else
        $('#disableExchange').show()

      pics = ''
      pictures = data.pictures
      $('.swiper-pagination').hide() if pictures.length < 2

      if pictures.length is 0
        pics += '<li class="swiper-slide" style="background-image:url(/images/content/default.png)"></li>'
      else
        for pic in pictures
          pics += '<li class="swiper-slide" style="background-image:url(' + pic + ')"></li>'
      $('.swiper-wrapper').append(pics)
      _initSwipe()
      if data.addresses.length > 0
        $('.detail-main').append '<div id="pickup-addresses" class="pic-wrapper"></div>'
        $addressContainer = $('#pickup-addresses')
        $addressContainer.append '<div class="item-name product-detail-title">此商品提供以下自提地址</div>'
        idStr = JSON.stringify( #special data structure for receive-address api
          {
            _id:
              in: data.addresses
          }
        )
        params =
          where: idStr
          unlimited: 1
        rest.get "/product/receive-addresss",  params, (data) ->
          addressCards = ''
          for address in data.items
            addressCards = addressCards + "
              <div class='address-detail'>
                <div class='address-name'>#{address.address}</div>
                <div class='address-location'>#{address.location.province + address.location.city + address.location.district + address.location.detail}</div>
              </div>
            "
          $addressContainer.append addressCards

  _getItemDetail =  ->
    rest.get '/product/product/' + locationMap['productId'], (data) ->
      if not data.intro
        $('.product-detail').append $('<div class="no-detail">暂无详情</div>')
      else
        $('.product-detail').html data.intro

  _init = ->
    _getMemberScore()
    _getItemDetail()

  _init()

  $('#exchange').on('click', (e) ->
    window.location.href = '/mobile/product/exchange' + window.location.search
  )
