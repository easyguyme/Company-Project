$("title")[0].innerText = "开通会员卡"
wait = 60
captcha = ''
memberURl = ''
isSendCaptcha = 0
getCodeTime = 0

errorBorderColor = '#b42d14'
defaultBorderColor = '#d8d8d8'

verificationCode = ''
canChangePic = true

$phone = $('#phone')
$verification = $('#verification')
$captcha = $('#captcha')

$btnChangePic = $('#btnChangePic')
$verificationTip = $('.center-verification-form-tip')
$iconVerificationCode = $('#iconVerificationCode')

time = (btn) ->
  if wait is 0
    btn.removeAttribute "disabled"
    $('#btnSendCode').text("获取验证码")
    setBtnStyle("#f9910a", "rgba(151, 89, 29, 0.24)")
    wait = 60
  else
    btn.setAttribute "disabled", true
    setBtnStyle("#a8a8a8", "rgba(109, 109, 109, 0.24)")
    $('#btnSendCode').text(wait + "秒后重新发送")
    wait--
    setTimeout (->
      time btn
      return
    ), 1000

    # Send ajax request.
    unionId = 0
    if wait is 59
      phone = document.getElementById("phone").value
      code = $.trim $verification.val()
      openId = getQuery("openId")
      channelId = getQuery("channelId")
      unionId = getQuery("unionId")
      isSendCaptcha++
      # Track phone number
      # _hmt.push(['_trackEvent', "member", "request-sms", phone]) if _hmt

      params =
        'mobile': phone,
        'openId': openId,
        'channelId': channelId
        'unionId': unionId
        'type': 'bind'
        'code': code
        'codeId': verificationCode

      getCodeTime = new Date().getTime()
      $.ajax
        type: "POST"
        url: "/api/mobile/send-captcha"
        data: JSON.stringify params
        dataType: "json"
        success: (data) ->
          if data.message is 'Error'
            displayErrorMsg 'phone', '验证码发送失败'
            getVerification()
          else
            captcha = data
        error: (xMLHttpRequest, errorType, error) ->
          if xMLHttpRequest.status is 440
            response = $.parseJSON xMLHttpRequest.response if xMLHttpRequest.response
            for key, value of $.parseJSON response.message
              displayErrorMsg key, value
            wait = 0
          else
            response = if xMLHttpRequest.response then JSON.parse(xMLHttpRequest.response) else {}
            response.params = params

            trackerSendCaptchaLog response

          getVerification()
          return
      return

trackerSendCaptchaLog = (response) ->
  response = response or {}

  trackerParams =
    msg: JSON.stringify response
    js: window.location.href
    path: window.location.pathname
    ua: window.navigator.userAgent
    env: (window.trackerLog.env or '') + '.mobile'

  alog('err.send', 'err', trackerParams) if alog

###
# Show error message when send too much captcha.
###
displayErrorMsg = (elem, msg) ->
  $('.center-' + elem + '-form-tip').text(msg)
  $('.center-' + elem + '-form-tip').css "border-color", "#b42d14"

document.getElementById("btnSendCode").onclick = ->
  if validate() & validatePic()
    time this
    return

validate = ->
  setFromTipErrorColor(null, "#phone")
  $('.center-captcha-form-tip').text('')
  $('.center-phone-form-tip').text('')
  phone = document.getElementById("phone").value
  # re = new RegExp('^0?1[0-9]{10}$')
  # reTel = new RegExp('^09[0-9]{8}$')

  # if typeof(phone) is "undefined" or not phone
  #   $('.center-phone-form-tip').text('手机号码不能为空')
  #   setFromTipErrorColor("#phone", "#captcha")
  #   return false

  # if not re.test(phone) and not reTel.test(phone)
  #   $('.center-phone-form-tip').text('手机号码格式不正确')
  #   setFromTipErrorColor("#phone", "#captcha")
  #   return false
  rel = util.checkTelNum phone
  if rel
    $('.center-phone-form-tip').text(rel)
    setFromTipErrorColor("#phone", "#captcha")
    return false

  return true

validatePic = ->
  setComponentsColor {'verification': defaultBorderColor}
  $verificationTip.text ''

  verification = $verification.val()
  flag = true

  if typeof(verification) is 'undefined' or not verification
    $verificationTip.text '验证字符不能为空'
    setComponentsColor {'verification': errorBorderColor}
    flag = false

  flag

$("#activateBtn").click ->
  $this = $(this)
  $('.center-captcha-form-tip').text('')
  $('.center-phone-form-tip').text('')
  setFromTipErrorColor(null, "#captcha")
  captcha = document.getElementById("captcha").value
  phone = document.getElementById("phone").value
  isPhone = true
  isValidate = true
  if validate() is true
    isValidate = true
  else
    isPhone = false
    isValidate = false

  if typeof(captcha) is "undefined" or not captcha
    $('.center-captcha-form-tip').text('验证码不能为空')
    isValidate = false
    if isPhone is false
      setFromTipErrorColor("#captcha", null)
    else
      setFromTipErrorColor("#captcha", "#phone")
    return

  if isSendCaptcha is 0
    $('.center-captcha-form-tip').text('验证码错误，请重新输入')
    isValidate = false
    setFromTipErrorColor("#captcha", null)
    return

  if isValidate is true
    # Open memberShip card.
    url = window.location.search
    openId = getQuery("openId")
    channelId = getQuery("channelId")
    unionId = getQuery("unionId")
    redirect = getQuery("redirect")
    appId = getQuery("appId")
    redirectType = getQuery("redirectType")
    waitTime = new Date().getTime() - getCodeTime
    # _hmt.push(['_trackEvent', "member", "register-clicked", phone, waitTime]) if _hmt

    $this.attr('disabled', "disabled")
    $.ajax
      type: "POST"
      url: "/api/mobile/bind"
      data: JSON.stringify {
        "mobile": phone,
        "openId": openId,
        "unionId": unionId,
        "channelId": channelId,
        "captcha": captcha,
        "appId": appId,
        "redirectType": redirectType,
        "redirect": redirect
      }
      dataType: "json"
      success: (result) ->
        window.location.href = result.data
      error: (xMLHttpRequest, errorType, error) ->
        if xMLHttpRequest.status is 440
          response = $.parseJSON xMLHttpRequest.response if xMLHttpRequest.response
          for key, value of $.parseJSON response.message
            displayErrorMsg key, value
        $this.removeAttr 'disabled'
        return

setFromTipErrorColor = (phoneId, captchaId) ->
  $(phoneId).css("border-color": "#b42d14")
  $(captchaId).css("border-color": "#d8d8d8")

setBtnStyle = (bgColor, boxShadowColor) ->
  $('#btnSendCode').css({
    'background-color': bgColor,
    'box-shadow': 'inset 1px 1px 2px 5px ' + boxShadowColor
  })

setComponentsColor = (components) ->
  for id, color of components
    $item = $("##{id}")
    $item.css('border-color': color) if $item and $item.length > 0

getVerification = (successCallBack, failedCallBack) ->
  $.ajax
    type: 'GET'
    url: '/api/captchas' + '?time=' + new Date().getTime()
    dataType: 'json'
    success: (result) ->
      $iconVerificationCode.attr 'src', result.data
      verificationCode = result.codeId
      successCallBack() if successCallBack
    error: (xMLHttpRequest, errorType, error) ->
      failedCallBack() if failedCallBack

$btnChangePic.click ->
  if canChangePic
    canChangePic = false
    $btnChangePic.attr 'disabled', 'disabled'
    getVerification ->
      canChangePic = true
      $btnChangePic.removeAttr 'disabled'
    , ->
      canChangePic = true
      $btnChangePic.removeAttr 'disabled'

$phone.focus ->
  setComponentsColor {'phone': defaultBorderColor}
  $('.center-phone-form-tip').text('')

$verification.focus ->
  setComponentsColor {'verification': defaultBorderColor}
  $('.center-verification-form-tip').text('')

$captcha.focus ->
  setComponentsColor {'captcha': defaultBorderColor}
  $('.center-captcha-form-tip').text('')

getQuery = (name) ->
  reg = new RegExp("(^|&)" + name + "=([^&]*)(&|$)", "i")
  resultInfo = window.location.search.substr(1).match(reg)
  if resultInfo?
    return unescape(resultInfo[2])
  else
    return null

getVerification()
