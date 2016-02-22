((win) ->
  win.dingdingLog = (message) ->
    trackerParams =
      msg: JSON.stringify message
      js: win.location.href
      path: win.location.pathname
      ua: win.navigator.userAgent
      env: (win.trackerLog.env or '') + '.mobile'

    alog('err.send', 'err', trackerParams) if alog
)(window)
