define [
    'core/coreModule'
    'wm/config'
], (mod, config) ->
  mod.factory 'judgeDeviceService', [
    ->
      judge = {}

      # Judge browser's type: http://zhidao.baidu.com/link?url=F0zV0vVmwfxzrJ7uWmNFOAEYRte4v9Tvh0vGNYHxNfWaVxsf3xnXA-jzYmmrALRzPxtsJU-NlP_q74WYM8wE6gysQdpnAF0Eo4ij6PXLGPW
      judge.judgeBrowser = ->
        name = window.navigator.userAgent
        if name.indexOf("MSIE") >= 0
          return "IE"
        else if name.indexOf("rv:11.0") >= 0
          return "IE-11"
        else
          return "other"

      judge.isMobile = ->
        userAgentInfo = navigator.userAgent
        agents = ["Android", "iPhone", "SymbianOS", "Windows Phone", "iPad", "iPod"]
        flag = false

        for agent in agents
          if userAgentInfo.indexOf(agent) > 0
            flag = true
            break

        return flag

      judge
  ]
