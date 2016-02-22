define [
  'core/coreModule'
  'chat/config'
], (mod, config) ->
  mod.factory 'fileDownloadService', [
    'notificationService'
    '$timeout'
    (notificationService, $timeout) ->

      download = {}

      _createIFrame = (url, triggerDelay) ->
        setTimeout ->
          iframe = $('<iframe style="display: none;" class="multi-download"></iframe>')
          iframe.attr 'src', url
          $(document.body).after iframe
          return
        , triggerDelay
        return

      _createLink = (url) ->
        aLink = document.createElement 'a'
        event = document.createEvent 'HTMLEvents'
        event.initEvent 'click'
        aLink.download = ''
        aLink.href = url
        aLink.dispatchEvent event
        return

      download.multiDownload = (downloadUrl, source) ->
        flag = 0
        triggerDelay = 200
        for url in downloadUrl
          if source is 'server'
            _createIFrame url, flag*triggerDelay
          else
            _createLink url
          flag++

      download
    ]
