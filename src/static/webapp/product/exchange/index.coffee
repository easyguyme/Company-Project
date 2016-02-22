$(->

  myScore = 0
  total = 0
  usedCount = 0
  goodsScore = 0
  defaultCount = 1
  DISABLED_CLASS = 'mb-btn-disable'
  document.title = '确认兑换'
  # Cache global DOM
  $incBtn = $('#count-inc')
  $count = $('#count-value')
  $descBtn = $('#count-desc')
  $costPoints = $('#points-value')

  $submitBtn = $('#submit')

  renderGoodsDetail = (data) ->
    $('#goods-pic').attr 'src', data.pictures[0] if data.pictures.length
    $('#goods-name').text data.productName
    $('.mb-breadcrumb-title').text data.productName
    $('#goods-points').text data.score
    $submitBtn.addClass DISABLED_CLASS if data.total is 0

  renderSubmitBtn = ->
    method = if validateAll() then 'remove' else 'add'
    $submitBtn["#{method}Class"] DISABLED_CLASS

  renderFormTip = (msg) ->
    msgMap = JSON.parse msg
    for q, msg of msgMap
      $("##{q}").parent().find('.mb-error-tip').text(msg)

  # Get the detail information for the exchange product
  getGoodsDetail = (callback) ->
    goodsId = util.queryMap.goodsId
    if goodsId
      rest.get "/mall/goods/#{goodsId}", (data) ->
        # Render DOM elements
        renderGoodsDetail(data)
        # Store global values
        total = data.total
        usedCount = data.usedCount
        goodsScore = data.score
        $costPoints.text(goodsScore)
        callback and callback()
    else
      throw new Error 'Lack of goods id'

  getMemberScore = ->
    memberId = util.queryMap.memberId
    if memberId
      rest.get "/member/member/#{memberId}", (data) ->
        myScore = data.score

  validateAll = ->
    parseInt($count.val()) > 0

  validateCountScore = ->
    flag = true
    countScore = parseInt($count.val()) * goodsScore
    if countScore > myScore or countScore is 0
      $submitBtn.addClass DISABLED_CLASS
      flag = false
    else
      $submitBtn.removeClass DISABLED_CLASS
    flag

  # Bind handlers
  $incBtn.click ->
    value = parseInt($count.val())
    if $.trim(total) is '' or value < total
      $count.val(++value)
      $costPoints.text(goodsScore * value)
      renderSubmitBtn()
    validateCountScore()
  $descBtn.click ->
    value = parseInt($count.val())
    if value > 1
      $count.val(--value)
      $costPoints.text(goodsScore * value)
      $submitBtn.addClass DISABLED_CLASS if not value
    validateCountScore()
  $count.focusin ->
    defaultCount = parseInt($count.val())

  $count.focusout ->
    validateCountScore()
    value = parseInt($count.val().replace(/\D/g,''))
    value = defaultCount if isNaN value
    if total isnt ''
      left = total
      value = left if value > left
    $count.val(value)
    $costPoints.text(goodsScore * value)

  $submitBtn.click ->
    if validateCountScore()
      window.location.href = '/mobile/product/address' + window.location.search + '&quantity=' + $count.val()

  init = ->
    getGoodsDetail()
    getMemberScore()

  # Init
  init()
)

