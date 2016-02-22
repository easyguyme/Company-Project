$(()->
  $continueBtn = $('#continue-btn')

  renderTotal = ()->
    memberId = util.queryMap.memberId
    if memberId
      rest.get '/product/campaign-log/total', {memberId: memberId}, (data)->
        $('#total-ponint').text(data.scoreNum)
        $('#total-gift').text(data.prizeNum)
    else
      throw new Error 'Lack of memberId'

  renderHistory = ()->
    memberId = util.queryMap.memberId
    if memberId
      param =
          memberId : memberId
          filter : ['score', 'prize']
      rest.get '/product/campaign-logs', param, (data)->
        html = ''
        if data
          data.items = data.items.filter (item) ->
            if item.member?.type isnt 'lottery'
              return true
            return false
          if data.items.length > 0
            for item in data.items
              historyMsg = ''
              if item.member?
                type = item.member.type
                if type and type is 'score'
                  historyMsg = "获得积分 #{item.member.scoreAdded}分"
                else if type and type is 'lottery'
                  historyMsg = "获得奖品 #{item.member.prize}"
                html += "<li>
                          <span class='history-time'>#{item.redeemTime}</span>
                          <span class='history-msg'>#{historyMsg}</span>
                        </li>"
          else
            html += "<li>
                        <span class='no-historys'>无兑换记录</span>
                    </li>"
        $('#history-list').html(html)
    else
      throw new Error 'Lack of memberId'

  init = ->
    renderTotal()
    renderHistory()

    queryObj = $.extend {}, util.queryMap
    query = $.param queryObj
    $continueBtn.attr 'href', "/mobile/campaign/promotion?#{query}"

  init()

  return
)
