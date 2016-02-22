$ ->
  document.title = '积分商城'

  params = {
    page: 1
    'per-page': 10
    orderBy: '{"order": "asc"}'
    category: ''
    status: 'on'
  }

  pageCount = 0
  myscore = 0
  $listContent = $('.list-content')

  # touch position
  dragStart = ''
  dragEnd = ''

  _init = ->
    $('.m-loading').hide()
    _getMemberScore()
    _getCategory()
    _getProductList()

  _initTab = ->
    tabs = new Swiper('.list-bg .swiper-container', {
      freeMode: true
      slidesPerView: 'auto'
      spaceBetween: 12
    })

  _getMemberScore = ->
    rest.get '/member/member/' + util.queryMap['memberId'], (data) ->
      $('.list-points').text(data.score)

  _getCategory = ->
    rest.get '/product/product-categorys', (data) ->
      items = data.items
      html = ''
      for item in items
        html += '<li class="swiper-slide" data-id="' + item.id + '"><span>' + item.name + '</span></li>'
      $('.swiper-wrapper').append(html)

      slides = $('.swiper-slide')
      for slide in slides
        width = $(slide).find('span').width() + $(slide).css('padding-left').replace('px', '') * 2 + 10
        $(slide).css('width', width)

      _initTab()

  _renderListDom = (item) ->
    linkUrl = '/mobile/product/detail' + window.location.search + '&goodsId=' + item.id + '&productId=' + item.productId
    productName = item.productName
    img = item.pictures[0] or '/images/content/default.png'
    score = item.score

    ##amount
    shadowDom = ''
    if item.total is ''
      remainCount = '库存充足'
    else if item.total is 0
      remainCount = '抢光了...'
      shadowDom = "<div class='item-shadow'></div>"
    else if item.total > 0
      remainCount = '剩余' + item.total + '件商品'
    ##amount

    ### remove amount now
    shadowDom = ''
    remainCount = ''
    ###

    html = "<li class='list-item'><a href='#{linkUrl}'>
                <div class='item-pic' style='background-image:url(#{img})'></div>
                <div class='item-name text-el'>#{productName}</div>
                <div class='item-name'><span class='goods-score'>#{score}</span>
                    <span class='item-remain-status'>#{remainCount}</span>
                </div>
                #{shadowDom}
            </a>
        </li>"

  _getProductList = ->
    $('.m-loading').show()
    $('.no-more-tip').hide()
    rest.get '/mall/goods/index', params, (data) ->
      pageCount = data._meta.pageCount

      if pageCount is params.page
        $('.no-more-tip').show()

      items = data.items
      html = ''
      if pageCount > 0
        for item in items
          html += _renderListDom(item)
      $listContent.append(html)
      $('.m-loading').hide()

  _touchStart = (event) ->
    e = event.touches[0]
    dragStart = e.clientY

  _touchEnd = (event) ->
    e = event.changedTouches[0]
    dragEnd = e.clientY
    percentage = (dragStart - dragEnd) / window.screen.height
    if percentage < 0 and document.body.scrollTop is 0 and Math.abs(percentage) > 0.15
      $listContent.empty()
      params.page = 1
      _getProductList()

  _touchMove = (event) ->
    e = event.touches[0]
    percentage = (dragStart - e.clientY) / window.screen.height
    if percentage < 0 and document.body.scrollTop is 0
      event.preventDefault()

  _init()

  $('.swiper-wrapper').on('click', '.swiper-slide', (e) ->
    $('.list-bg .swiper-slide').removeClass 'tab-active'
    target = $(e.target)
    if target.hasClass 'swiper-slide'
      target.addClass 'tab-active'
      params.category = target.data('id')
    else
      target.parent().addClass 'tab-active'
      params.category = target.parent().data('id')
    $listContent.empty()
    params.page = 1
    _getProductList()
  )

  $(window).scroll util.debounce( ->
    if $(document).height() - $(this).scrollTop() - $(this).height() <= 2
      params.page++
      if params.page <= pageCount
        _getProductList()
  )

  document.body.addEventListener('touchmove', _touchMove, false)
  document.body.addEventListener('touchstart',_touchStart, false)
  document.body.addEventListener('touchend', _touchEnd, false)
