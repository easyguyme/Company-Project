define ['core/coreModule'], (mod) ->
  mod.controller 'wm.ctrl.core.spreadByCopyURL', [
    '$scope'
    'channelService'
    'utilService'
    '$modalInstance'
    'modalData'
    ($scope, channelService, utilService, $modalInstance, modalData) ->
      vm = $scope

      oauthLink = modalData.oauthLink
      redirectLink = modalData.redirectLink

      types = ['wechat', 'weibo', 'alipay']

      _init = ->
        vm.description = modalData.description or 'spread_url_infotip'

        channelService.getChannels().then (channels) ->
          vm.channels = _packageChannels(channels)

      _packageChannels = (channels) ->
        if channels
          channels = utilService.formatChannels(angular.copy(channels), false)

          channels.sort (first, second) ->
            $.inArray(first.type, types) - $.inArray(second.type, types)

          for channel in channels
            if oauthLink
              channel.oauthLink = oauthLink
            else if redirectLink
              channel.oauthLink = redirectLink

            if channel.oauthLink
              channel.oauthLink = channel.oauthLink.replace('{{redirectLink}}', redirectLink)
                .replace('{{channelId}}', channel.id)

        channels

      vm.close = ->
        $modalInstance.dismiss()

      _init()
  ]
