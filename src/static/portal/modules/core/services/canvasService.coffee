define [
  'core/coreModule'
  'wm/config'
  'qrcode'
], (mod, config, qrcode) ->
  mod.factory 'canvasService', [
    'downloadService'
    (downloadService) ->
      canvas = {}
      defaultSetting =
        imageType: 'png'
        width: 100
        height: 100

      canvas.toImage = (canvasElem, imageType) ->
        imageData = canvasElem.toDataURL imageType or defaultSetting.imageType
        return imageData

      # Download a image from a canvas
      canvas.download = (canvasElem, filename, imageType, scourceLink) ->
        downloadService.download canvasElem, filename, (imageType or defaultSetting.imageType), scourceLink
        return

      # Covent a text to a qrcode canvas, and download as a image
      canvas.downloadQrcode = (content, filename, width, height, imageType, scourceLink) ->
        qrcodeOptions =
          text: content
          width: width or defaultSetting.width
          height: height or defaultSetting.height
        elem = document.createElement 'div'
        $(elem).qrcode qrcodeOptions
        this.download $(elem).find('canvas')[0], filename, (imageType or defaultSetting.imageType), scourceLink
        return

      canvas
  ]
