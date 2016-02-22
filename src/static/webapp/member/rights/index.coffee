$("title")[0].innerText = "会员卡详情"
map =
  "customer_score_perfect_information": "完善资料送积分"
  "customer_score_birthday": "生日积分"
  "customer_score_first_card": "首次开卡送积分"

_init = ->
  memberId = util.queryMap.memberId
  timeoffset = new Date().getTimezoneOffset() / 60
  $.ajax
    type: "GET"
    url: "/api/member/member/#{memberId}?tmoffset=#{timeoffset}"
    dataType: "json"
    success: (data) ->
      $("#card-name")[0].innerText = data.card.name
      $("#user-score")[0].innerText = data.score
      $("#card-date")[0].innerText = data.scoreProvideTime or "--"
      cardId = data.card.id
      $.ajax
        type: "GET"
        url: "/api/member/card/" + cardId
        dataType: "json"
        success: (data) ->
          $("#card-privilege").append data.privilege
          $("#card-usageGuide").append data.usageGuide
          if (data.scoreResetDate)
            $scoreZeroedHtml = '<div class="rights-list-title memberpointsautozeroed">
                                  <span class="rights-list-title-text">积分清零</span>
                                </div>
                                <div class="rights-list-body">
                                  <div class="rights-list-body-text" id="score-zeroed">{year}年{month}月{day}日24:00时积分自动清零</div>
                                </div>'
            month = data.scoreResetDate.month
            day = data.scoreResetDate.day
            date = new Date()
            year = date.getFullYear()
            if (date.getMonth() + 1) > month or ((date.getMonth() + 1) is month and date.getDay() > day)
              year++
            $('.rights-body').append $scoreZeroedHtml.replace(/{year}/, year).replace(/{month}/, month).replace(/{day}/, day)
        error: ->
          console.log "error"
    error: ->
      console.log "error"
  $.ajax
    type: "GET"
    url: "/api/member/score-rules?" + "where={'isEnabled':true}"
    dataType: "json"
    success: (data) ->
      if data.items.length is 0
        $(".memberpoints").hide()
        $("#score-rule").hide()
      else
        $(".memberpoints").show()
        $("#score-rule").show()
        for item, i in data.items
          if i isnt 0
            $("#score-rule").append "<div class='rights-list-body-line'></div>"
          name = map['customer_score_' + item.name]
          $("#score-rule").append "<div class='rights-list-body-title'>" + name + "</div>"
          $("#score-rule").append "<div class='rights-list-body-text' id='score-rule-text" + i + "'></div>"
          $("#score-rule-text" + i).append item.description
    error: ->
      console.log "error"

_init()
