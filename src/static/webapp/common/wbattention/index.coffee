$ ->
  $wbattention = $ '#wbattention'
  $avatar = $ '#avatar'
  WEIBOURL = window.location.protocol + '//weibo.com/u'

  attention = ->
    appId = $wbattention.data('appId')
    window.location.href = WEIBOURL + '/' + appId

  $avatar.click ->
    attention()

