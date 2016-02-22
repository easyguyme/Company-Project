_init = ()->
  $qrcodeElem = $('#qrcodeIcon')
  options =
    width  : $qrcodeElem.width()
    height : $qrcodeElem.height()
  options.text = $('#graphicContentFrame').attr('src')
  if options.text
    $qrcodeElem.qrcode options

_init()
