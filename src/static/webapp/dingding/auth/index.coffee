$ ->
  init = ->
    dd = window.dd

    if dd
      dd.config({
        agentId: ddoptions.agentId
        corpId: ddoptions.corpId
        timeStamp: ddoptions.timeStamp
        nonceStr: ddoptions.nonceStr
        signature: ddoptions.signature
        jsApiList: [
          'runtime.permission.requestAuthCode'
        ]
      })

      # ready for dd add click event.
      dd.ready( ->
        authConf =
          corpId: util.queryMap.corpid
          onSuccess: authSuccsss
          onFail: authError
        dd.runtime.permission.requestAuthCode(authConf)
      )

      dd.error((error) ->
        href '/mobile/common/dd403'
        dingdingLog(error)
        dingdingLog('DD js SDK configuration failed')
      )

  authSuccsss = (data) ->
    if data
      url = '/mobile/common/dd403'
      suiteKey = util.queryMap.suiteKey
      corpId = util.queryMap.corpid
      appId = util.queryMap.appId
      param =
        suiteKey: suiteKey
        corpId: corpId
        appId: appId
        code: data.code
      rest.post '/ding/user', param, (data) ->
        if data?.dingUserId
          url = '/mobile/dingding/index' + window.location.search + '&dingUserId=' + data.dingUserId
          href url
      , (error) ->
        href url

  authError = (error) ->
    href '/mobile/common/dd403'
    dingdingLog(error)
    dingdingLog('Get DD code failed')

  href = (url) ->
    window.location.href = url

  init()
