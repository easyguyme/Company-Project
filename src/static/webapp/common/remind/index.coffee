$ ->
  $remindMessage = $ '#remindMessage'

  originMap =
    'wechat': '微信'
    'weibo': '新浪微博'
    'alipay': '支付宝'

  init = ->
    origin = util.queryMap.origin or 'wechat'

    $remindMessage.text "请使用#{originMap[origin]}扫描此二维码"

  init()
