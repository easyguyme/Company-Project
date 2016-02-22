$ ->
  $scan = $("#dd-index-scan")

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
          'biz.util.scan'
        ]
      })

      # ready for dd add click event.
      dd.ready( ->
        scanConf =
          type: 'qrCode'
          onSuccess: scanQrCodeSuccsss
          onFail: scanQrCodeError
        $scan.click ->
          dd.biz.util.scan(scanConf)
      )

      dd.error((error) ->
        href '/mobile/common/dd403'
        dingdingLog(error)
        dingdingLog('DD js SDK configuration failed')
      )

  scanQrCodeSuccsss = (data) ->
    if data
      url = data.text
      if (url?.indexOf '/member/') isnt -1
        items = url.split('/')
        memberId = items.pop()
        url = '/mobile/content/member?memberId=' + memberId
      href url

  scanQrCodeError = (error) ->
    href '/mobile/common/dd403'
    dingdingLog(error)
    dingdingLog('DD scan QRcode failed')

  href = (url) ->
    window.location.href = url

  init()
