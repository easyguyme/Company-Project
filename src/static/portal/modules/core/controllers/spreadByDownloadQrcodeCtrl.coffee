define ['core/coreModule'], (mod) ->
  mod.controller 'wm.ctrl.core.spreadByDownloadQrcode', [
    '$scope'
    'channelService'
    '$modalInstance'
    'modalData'
    'canvasService'
    ($scope, channelService, $modalInstance, modalData, canvasService) ->
      vm = $scope

      origins =
        WECHAT: 'wechat'
        WEIBO: 'weibo'
        ALIPAY: 'alipay'

      channelNames =
        wechat: '微信'
        weibo: '微博'
        alipay: '支付宝'

      oauthLink = modalData.oauthLink
      redirectLink = modalData.redirectLink

      prefix = modalData.prefix

      QRCODE_ICON = '/images/customer/qrcode.png'

      TRANSFER_UNIT = 25

      _init = ->
        vm.description = modalData.description or 'spread_qrcode_infotip'

        vm.channelTypes = [origins.WECHAT, origins.WEIBO, origins.ALIPAY]

        channelService.getChannels().then (channels) ->
          vm.channels = _parseChannels channels

        _packageSizes()

      _parseChannels = (channels) ->
        accounts = []

        if (angular.isArray(channels) and channels.length)
          for channel in channels
            account = $.extend true, {}, channel

            account.checked = false
            if oauthLink
              account.oauthLink = oauthLink
            else if redirectLink
              account.oauthLink = redirectLink

            if account.oauthLink
              account.oauthLink = account.oauthLink.replace('{{redirectLink}}', redirectLink)
                .replace('{{channelId}}', account.id)

            accounts.push account

        accounts

      _packageSizes = ->
        vm.sizes = [
          avatar: QRCODE_ICON
          name: '8cm'
          width: 8 * TRANSFER_UNIT
          description:
            key: 'suggest_scanning_distance'
            params:
              length: 0.5
        ,
          avatar: QRCODE_ICON
          name: '12cm'
          width: 12 * TRANSFER_UNIT
          description:
            key: 'suggest_scanning_distance'
            params:
              length: 0.8
        ,
          avatar: QRCODE_ICON
          name: '15cm'
          width: 15 * TRANSFER_UNIT
          description:
            key: 'suggest_scanning_distance'
            params:
              length: 1
        ,
          avatar: QRCODE_ICON
          name: '30cm'
          width: 30 * TRANSFER_UNIT
          description:
            key: 'suggest_scanning_distance'
            params:
              length: 1.5
        ,
          avatar: QRCODE_ICON
          name: '50cm'
          width: 50 * TRANSFER_UNIT
          description:
            key: 'suggest_scanning_distance'
            params:
              length: 2.5
        ]

      # name like 'channel, size'
      vm.selectAll = (name, value) ->
        # select all model
        allModel = "#{name}All"
        # checkbox repeate
        items = "#{name}s"
        if value?
          vm[allModel] = value
          vm[items] = vm[items].map (item) ->
            item.checked = value
            item

      vm.selectItem = (name, value) ->
        # select all model
        allModel = "#{name}All"
        # checkbox repeate
        items = "#{name}s"
        if value?
          vm[allModel] = vm[items].filter((item) ->
            return item.checked
          ).length is vm[items].length

      vm.download = ->
        pickedChannels = vm.channels.filter (item) ->
          return item.checked

        pickedSizes = vm.sizes.filter (item) ->
          return item.checked

        for channel in pickedChannels
          for size in pickedSizes
            content = channel.oauthLink
            name = "#{prefix}_#{channelNames[channel.type]}_#{channel.name}_#{size.name}.png"
            width = size.width
            height = size.width
            imageType = 'png'

            canvasService.downloadQrcode content, name, width, height, imageType, content

        $modalInstance.close()

      vm.close = ->
        $modalInstance.dismiss()

      _init()
  ]
