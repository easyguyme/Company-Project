define [
  'core/coreModule'
], (mod) ->

  # Collect the frontend error
  alog 'define', 'err', ->
    errTracker = alog.tracker('err')
    mod.factory '$exceptionHandler', [
      ->
        (exception, cause) ->
          stackLine = exception.stack.match(/\(.+?\)\n/)[0]
          parts = stackLine.split ':'
          errTracker.send 'err',
            msg: exception.name + ':' + exception.message
            file: parts[1]
            line: parts[2]
            path: window.location.pathname
            ua: window.navigator.userAgent
            env: (window.trackerLog.env or '') + '.pc'
          console and console.error exception.stack, cause
    ]
    errTracker

  # Create an error reporting instance
  alog 'err.create', postUrl: window.trackerLog.url or ''
  return
