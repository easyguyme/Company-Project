###
((e, t, n, a, o, i, m) ->
  e.alogObjectName = o
  e[o] = e[o] or ->
    (e[o].q = e[o].q or []).push arguments
    return

  e[o].l = e[o].l or +new Date
  i = t.createElement(n)
  i.asyn = 1
  i.src = a
  m = t.getElementsByTagName(n)[0]
  m.parentNode.insertBefore(i, m)
  return
) window, document, 'script', '/vendor/bower/alogs/alog.min.js', 'alog'
###

if alog
  alog 'define', 'err', ->
    errTracker = alog.tracker('err')
    window.onerror = (message, file, line) ->
      message = JSON.stringify(message) if typeof message is 'object' and JSON
      # Skip the 3rd party script errors which we can not handle
      if message isnt 'Script error.'
        errTracker.send 'err',
          msg: message
          js: file
          ln: line
          path: window.location.pathname
          ua: window.navigator.userAgent
          env: (window.trackerLog.env or '') + '.mobile'
      return

    errTracker

  alog 'err.create', postUrl: window.trackerLog.url or ''
