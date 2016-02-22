define ['core/coreModule'], (mod) ->
  mod.controller 'wm.ctrl.core.spreadByAppendMenu', [
    '$scope'
    'channelService'
    '$modalInstance'
    'modalData'
    'storeService'
    ($scope, channelService, $modalInstance, modalData, storeService) ->
      vm = $scope

      origins =
        WECHAT: 'wechat'
        WEIBO: 'weibo'
        ALIPAY: 'alipay'

      oauthLink = modalData.oauthLink
      redirectLink = modalData.redirectLink
      menuName = modalData.menuName

      CHANNEL_MENU_LINK = '/channel/menu/{{channelId}}?spread=t'

      _init = ->
        vm.title = modalData.title or 'choose_channel'
        vm.description = modalData.description or 'spread_menu_infotip'

        vm.channelTypes = [origins.WECHAT, origins.WEIBO, origins.ALIPAY]

        channelService.getChannels().then (channels) ->
          vm.channels = angular.copy channels

          vm.pickedChannel = vm.channels[0].id if vm.channels and vm.channels.length

      _formatOauthLink = (channelId) ->
        url = oauthLink

        url = redirectLink if not url and redirectLink
        if url
          url = url.replace('{{redirectLink}}', redirectLink)
            .replace('{{channelId}}', channelId)

        url

      vm.submit = ->
        if vm.pickedChannel
          authedLink = _formatOauthLink vm.pickedChannel

          if authedLink
            params =
              content: authedLink
              contentName: menuName

            storeService.setMemoryItem 'menu', params

            path = CHANNEL_MENU_LINK.replace '{{channelId}}', vm.pickedChannel

            # Force the page reloading for tab state
            # $state.reload() if $location.url() isnt path

            $modalInstance.close(path)

            #$state.go('channel-menu-{id}', {id: vm.pickedChannel})


      vm.close = ->
        $modalInstance.dismiss()

      _init()
  ]
