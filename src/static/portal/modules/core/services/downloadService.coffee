define [
  'core/coreModule'
  'wm/config'
], (mod, config) ->
  mod.factory 'downloadService', [
    'restService'
    'judgeDeviceService'
    (restService, judgeDeviceService) ->
      downloader = {}

      downloader.download = (canvasElem, filename, imageType, scourceLink) ->
        if typeof canvasElem is 'string'
          filename = encodeURIComponent(filename)
          imageData = config.resources.imageDownload + '?url=' + canvasElem + '&name=' + filename
        else
          imageData = canvasElem.toDataURL imageType or 'png'
        type = judgeDeviceService.judgeBrowser()
        # IEs don't support "download" attribute of label <a>, so we must judge browser's type: http://www.w3school.com.cn/tags/att_a_download.asp
        if type is "IE" or type is "IE-11"
          if typeof canvasElem is 'string'
            params =
              url: canvasElem
              type: imageType or "png"
            restService.get '/api/common/download/download-cors-image', params, (data) ->
              data = decodeURIComponent data #get the pure base64 img
              $img = $("<img src=#{data}>") # the data it self contains ""
              img = $img[0]
              $canvas = $("<canvas></canvas>")
              canvas = $canvas[0]
              ctx = canvas.getContext('2d')
              canvas.width = img.width
              canvas.height = img.height
              ctx.drawImage(img, 0, 0)
              navigator.msSaveBlob(canvas.msToBlob(), filename) if navigator?.msSaveBlob?
              return
          else
            navigator.msSaveBlob(canvasElem.msToBlob(), filename) if navigator?.msSaveBlob?
        else
           _download imageData, filename, scourceLink
        return

      _download = (imageData, filename, scourceLink) ->
        saveLink = document.createElementNS 'http://www.w3.org/1999/xhtml', 'a'
        if saveLink.download?
          saveLink.href = imageData
          saveLink.download = _filenameEndode filename
          event = document.createEvent 'MouseEvents'
          event.initMouseEvent 'click', true, true, window, 0, 0, 0, 0, 0, false, false, false, false, 0, null
          saveLink.dispatchEvent event
        else
          _saveAsImage imageData, filename, scourceLink
        return

      _filenameEndode = (filename) ->
        filename.replace(/（/g, '(').replace(/）/g, ')')

      # if the browser can't identify 'download' attribute, so use execCommand function to download image
      _saveAsImage = (imageData, filename, scourceLink) ->
        if /data:([^;]*);base64,(.*)/.test(imageData) and scourceLink
            imageData = '/api/image/canvas-download?url=' + scourceLink + '&name=' + filename

          picIframe = document.createElement('IFRAME')
          document.body.insertBefore(picIframe)
          picIframe.outerHTML = "<iframe name=download style='display: none;' src=#{imageData}></iframe>"

          timer = $interval ->
            if window.frames['download'].document.readyState is 'complete'
              picIframe.execCommand('SaveAs', filename)
              $interval.cancel timer
              picIframe.parentNode.removeChild(picIframe)
          , 100

      downloader
  ]
