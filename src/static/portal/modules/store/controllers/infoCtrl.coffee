define [
  'wm/app'
  'wm/config'
], (app, config) ->
  app.registerController 'wm.ctrl.store.info', [
    'restService'
    '$stateParams'
    '$location'
    'channelService'
    '$filter'
    (restService, $stateParams, $location, channelService, $filter) ->
      vm = this
      vm.storeId = $stateParams.id

      vm.breadcrumb = [
        'store_info'
      ]

      _getStoreInfo = ->
        restService.get config.resources.storeInfo + '/' + vm.storeId, (data) ->
          if data
            vm.data = angular.copy data
            vm.tags = []
            if vm.data.type
              vm.tags.push vm.data.type
            if vm.data.subtype
              vm.tags.push vm.data.subtype

            vm.data.description = vm.data.description or '-'
            vm.data.businessHours = vm.data.businessHours or '-'
            vm.positionIcon = "http://api.map.baidu.com/staticimage" +
              "?center=#{vm.data.position.longitude},#{vm.data.position.latitude}&width=360&height=230&zoom=14" +
              "&markers=#{vm.data.position.longitude},#{vm.data.position.latitude}" +
              "&markerStyles=-1,http://api.map.baidu.com/images/marker_red.png,-1"

            if vm.data.wechat
              vm.isShowWechatQrcode = false
              vm.wechatQrcode = [
                title: 'wechat_qrcode'
                name: vm.data.name
                link: vm.data.wechat.qrcode
              ]

            if vm.data.weibo
              vm.isShowWeiboQrcode = false
              vm.weiboQrcode = [
                title: 'weibo_qrcode'
                name: vm.data.name
                link: vm.data.weibo.qrcode
              ]

            if vm.data.alipay
              vm.isShowAlipayQrcode = false
              vm.alipayQrcode = [
                title: 'alipay_qrcode'
                name: vm.data.name
                link: vm.data.alipay.qrcode
              ]

            _getChannels()

      _getChannels = ->
        channelService.getChannels().then((channels) ->
          if channels.length
            vm.channels = channels

            if vm.data.wechat
              wechatChannel = $filter('filter')(vm.channels, {id: vm.data.wechat.channelId})
              if wechatChannel.length > 0
                vm.isWechatQrcodeEnable = true
                vm.wechatQrcode[0].channel = wechatChannel[0].name

            if vm.data.weibo
              weiboChannel = $filter('filter')(vm.channels, {id: vm.data.weibo.channelId})
              if weiboChannel.length > 0
                vm.isWeiboQrcodeEnable = true
                vm.weiboQrcode[0].channel = weiboChannel[0].name

            if vm.data.alipay
              alipayChannel = $filter('filter')(vm.channels, {id: vm.data.alipay.channelId})
              if alipayChannel.length > 0
                vm.isAlipayQrcodeEnable = true
                vm.alipayQrcode[0].channel = alipayChannel[0].name

        )

      _getStoreInfo()

      vm.showPosition = ->
        $('#position').css("left", $('#positionDetail')[0].offsetLeft + $('#positionDetail').width() + 10)
        vm.isShowPosition = true

      vm.hidePosition = ->
        vm.isShowPosition = false

      vm.showQrcode = (type, $event) ->
        if type is 'weibo'
          vm.weiboQrcode.style =
            left: $event.target.offsetLeft - 220
            top: $event.target.offsetTop + 20
          vm.isShowWeiboQrcode = true
        else if type is 'wechat'
          vm.wechatQrcode.style =
            left: $event.target.offsetLeft - 220
            top: $event.target.offsetTop + 20
          vm.isShowWechatQrcode = true
        else if type is 'alipay'
          vm.alipayQrcode.style =
            left: $event.target.offsetLeft - 220
            top: $event.target.offsetTop + 20
          vm.isShowAlipayQrcode = true

      vm.linkToProductShelf = ->
        $location.url '/store/shelf/' + vm.storeId

      vm.linkToStaff = ->
        $location.url '/store/staff/' + vm.storeId

      vm
  ]
