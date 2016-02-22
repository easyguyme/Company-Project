currentPage = 1
pageCount = 1
pageSize = 15
year = 0
canGetList = true
map =
  'perfect_information': '完善资料送积分'
  'birthday': '生日积分'
  'first_card': '首次开卡送积分'
  'auto_zeroed': '会员积分自动清零'
  'exchange_goods': '商品兑换'
  'exchange_promotion_code': '产品码兑换'
  'reward_score': '积分奖励'
  'admin_issue_score': '手动发放积分'
  'admin_deduct_score': '手动扣除积分'
  'shake_score': '摇红包送积分'

assigners = [
  'rule_assignee'
  'auto_zeroed'
  'exchange_goods'
  'exchange_promotion_code'
  'reward_score'
  'admin_issue_score'
  'admin_deduct_score'
  'shake_score'
]

_getURLParams = ->
  result = {}
  params = (window.location.search.split('?')[1] or '').split('&')
  for param in params
    paramParts = param.split('=')
    result[paramParts[0]] = decodeURIComponent(paramParts[1] or '')
  result

_generateList = (listData) ->
  if listData.length
    for item in listData
      date = moment(item.createdAt, 'YYYY-MM-DD HH:mm:ss')
      if year isnt date.year()
        if year isnt 0
          $('#show-list').append '<div class="score-body-line60"></div>'
        year = date.year()
        $('#show-list').append '<span class="score-body-tag">' + year + '</span>'
        $('#show-list').append '<div class="score-body-line40"></div>'
      else
        $('#show-list').append '<div class="score-body-line55"></div>'

      if $.inArray(item.brief, assigners) > -1 or $.inArray(item.assigner, assigners) > -1
        description = map[item.brief] or map[item.description] or item.description or '未描述'
      else
        description = item.description or '未描述'

      if item.increment > 0
        $('#show-list').append(
          '<div class="score-body-record clearfix">
              <span class="score-body-record-date">' + (date.month() + 1) + ' - ' + date.date() + '</span>
              <span class="score-body-record-main">
                <img src="/images/mobile/jiedian.png" class="score-body-record-img">
                ' + description + '
              </span>
              <span class="score-body-record-number green"> +' + item.increment + '分</span>
          </div>')
      else
        $('#show-list').append(
          '<div class="score-body-record clearfix">
              <span class="score-body-record-date">' + (date.month() + 1) + ' - ' + date.date() + '</span>
              <span class="score-body-record-main">
                <img src="/images/mobile/jiedian.png" class="score-body-record-img">
                ' + description + '
              </span>
              <span class="score-body-record-number red">' + item.increment + '分</span>
          </div>')
  return

_getList = (memberId) ->
  if currentPage <= pageCount
    canGetList = false
    $.ajax
      type: 'GET'
      url: '/api/member/scores?memberId=' + memberId + '&per-page=' + pageSize + '&page=' + currentPage + '&orderby={"createdAt": "desc"}' + '&time=' + new Date().getTime()
      dataType: 'json'
      success: (data) ->
        currentPage += 1
        pageCount = data._meta.pageCount
        _generateList(data.items)
        canGetList = true
      error: ->
        console.log 'get list error'
        canGetList = true
  return

_getScore = (memberId) ->
  $.ajax
    type: 'GET'
    url: '/api/member/member/' + memberId + '?time=' + new Date().getTime()
    dataType: 'json'
    success: (data) ->
      $('#personal-score')[0].innerText = data.score
    error: ->
      console.log 'get score error'

_init = ->
  $('title')[0].innerText = '积分明细'

  URLParams = _getURLParams()
  memberId = URLParams.memberId

  _getScore(memberId)
  _getList(memberId)

  window.onscroll = ->
    if $(document).height() - $(this).scrollTop() - $(this).height() <= 10 and $(this).scrollTop() isnt 0 and pageCount >= currentPage
      _getList() if canGetList

  return

_init()
