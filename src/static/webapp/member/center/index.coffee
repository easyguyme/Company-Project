# rander data.
$("title")[0].innerText = "会员中心"
memberId = 0
centerUrl = ''
map =
  "customer_score_perfect_information": "完善资料送积分"
  "customer_score_birthday": "生日积分"
  "customer_score_first_card": "首次开卡送积分"

$(document).ready ->
  #Get defaule memberShip card information by appId.
  centerUrl = window.location.search
  channelId = getQuery("channelId")
  cardExpired = getQuery('cardExpired')

  if (centerUrl.indexOf("openId") > 0) and (centerUrl.indexOf("channelId") > 0)
    showCenter("#open-member","#opened-member")
    $.ajax
      type: "GET"
      url: "/api/mobile/card?channelId=" + channelId
      dataType: "json"
      success: (data) ->
        if data
          memberId = data.id
          $('#privilege').append(data.privilege)
          $('#usageguide').append(data.usageGuide)
      error: ->
        console.log "error"
    $.ajax
      type: "GET"
      url: "/api/mobile/score-rule?channelId=#{channelId}&where={'isEnabled':true}"
      dataType: "json"
      success: (data) ->
        if data.items.length is 0
          $(".member-scorerule-wrapper").hide()
        else
          $(".member-scorerule-wrapper").show()
          for item, i in data.items
            if i isnt 0
              $("#score-rule").append "<div class='scorerule-line'></div>"
            name = map['customer_score_' + item.name]
            $("#score-rule").append "<div class='member-group' id='score-rule-body" + i + "'></div>"
            $("#score-rule-body" + i).append "<span class='member-title-child'>" + name + "</span>"
            $("#score-rule-body" + i).append "<ul class='member-content'><li>" + item.description + "</li></ul>"
      error: ->
        console.log "error"
  else if (centerUrl.indexOf("memberId") > 0) and (centerUrl.indexOf("cardExpired") < 0 or cardExpired is "0")
    showCenter("#opened-member","#open-member")
    showCenter("", ".form-expired-icon")
    showCenter("", ".start-member-expired-btn")
    showCenter("", ".member-tip")
    getMemberInfo()

  else
    showCenter("#opened-member","#open-member")
    showCenter(".form-expired-icon", ".center-list")
    getMemberInfo()

removeQrcodeViewed = ->
  memberId = getQuery('memberId')

  params =
    memberId: memberId
  $.ajax
    type: "POST"
    url: "/api/member/member/check-qrcode-help"
    data: JSON.stringify params
    dataType: 'json'
    success: (data) ->
      return

showQrcodeHelpModal = ->
  $qrcodeGuide = $('.mb-popup-container')
  $toggleQrcode = $('#btn-toggle-qrcode')
  $qrcodeGuide.show()
  $toggleQrcode.hide()

  setTimeout ->
    $qrcodeGuide.hide() if $qrcodeGuide.length > 0
    $toggleQrcode.show()
    removeQrcodeViewed()
  , 5000

toggleQrcode = ->
  $this = $ event.target
  type = $this.data('type')
  switch type
    when 'qrcode'
      $('.qrcode-info-wrapper').hide()
      $('.card-info-wrapper').show()
      $this.data 'type', 'card'
      $('#btn-toggle-qrcode').attr 'src', '/images/mobile/img_qrcode.png'
    when 'card'
      $('.qrcode-info-wrapper').show()
      $('.card-info-wrapper').hide()
      $this.data 'type', 'qrcode'
      $('#btn-toggle-qrcode').attr 'src', '/images/mobile/img_card.png'

      # calculate qrcode picture height
      $pictureQrcode = $('.picture-qrcode')
      height = $('.center-card').height()
      picHeight = $pictureQrcode.height()

      if height - picHeight < 0
        $pictureQrcode.css(
          'height': height - 20
        )

      paddingHeight = (height - $pictureQrcode.height()) / 2
      $('.qrcode-info-wrapper').css(
        'padding-top': paddingHeight
      )

getMemberInfo = ->
  memberId = getQuery('memberId')

  $.ajax
    type: "GET"
    url: "/api/member/member/#{memberId}"
    dataType: "json"
    success: (data) ->
      name = ''
      for item in data.properties
        if item.name is 'name'
          name = item.value
      $('.center-card').css(
        'color', data.card.fontColor,
        'background-image', "url(\'#{data.card.poster}\')"
      )
      $('.center-card-name').text(data.card.name)
      $('.center-card-username').text(name)
      $('.center-card-number').text(data.cardNumber)
      $('.center-list-block-number').text(data.score)
      if data.card?.poster
        $('.center-card').css
          'background-image': "url(#{data.card.poster})"
      $pictureQrcode = $('.picture-qrcode')
      $pictureQrcode.attr 'src', data.qrcodeUrl
      showQrcodeHelpModal() if not data.qrcodeViewed

    error: ->
      console.log "error"

openMemberShip = ->
  urlStr = window.location.search
  window.location.href = "/mobile/member/activate" + urlStr
  return

$(".memberpoints").click ->
  window.location.href = "/mobile/member/score" + centerUrl
  return

$(".memberprivilege").click ->
  window.location.href = "/mobile/member/rights" + centerUrl
  return

$(".personalinformation").click ->
  window.location.href = "/mobile/member/personal" + centerUrl
  return

$(".mycoupon").click ->
  window.location.href = "/mobile/member/coupon" + centerUrl
  return

$(".exchangerecord").click ->
  window.location.href = "/mobile/product/history" + centerUrl
  return

$('.mb-popup-container').click ->
  $qrcodeGuide = $ this
  $qrcodeGuide.hide() if $qrcodeGuide.length > 0
  $('#btn-toggle-qrcode').show()
  removeQrcodeViewed()

showCenter = (open, opened) ->
  $(open).css(
    'display': 'block'
    )
  $(opened).css(
    'display': 'none'
    )

window.onresize = window.onload = ->

  clientHeight = document.documentElement.clientHeight
  clientWidth = document.documentElement.clientWidth
  width = clientWidth - 30
  height = width * 160 / 270
  widthIcon = height * 100 / 160
  $('.member-center-card-icon').css(
    'width': width
    'height': height
    'background-size': "#{width}px #{height}px"
  )

  $('.center-card').css(
    'display': 'block'
    'width': width
    'height': height
    'background-size': "#{width}px #{height}px"
  )

  $('.start-member-expired-btn').css(
    'width': widthIcon
  )

  $('.qrcode-info-wrapper').css(
    'padding-top': if height - 148 > 0 then (height - 148) / 2 else 10
  )

  if height - 148 < 0
    $('.picture-qrcode').css(
      'height': height - 20
    )

  $('.qrcode-guide-wrapper').css(
    'height': height - 15
  )

getQuery = (name) ->
  reg = new RegExp("(^|&)" + name + "=([^&]*)(&|$)", "i")
  resultInfo = window.location.search.substr(1).match(reg)
  if resultInfo?
    return unescape(resultInfo[2])
  else
    return null
