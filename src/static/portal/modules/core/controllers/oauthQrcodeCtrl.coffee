define [
  'core/coreModule'
  'wm/config'
], (mod, config) ->
  mod.controller 'wm.ctrl.core.oauthQrcode', [
    'modalData'
    'restService'
    'notificationService'
    '$modalInstance'
    '$rootScope'
    '$scope'
    (modalData, restService, notificationService, $modalInstance, $rootScope, $scope) ->
      vm = $scope
      rvm = $rootScope

      vm.edit = modalData.edit
      vm.tip = modalData.tip
      vm.channelIds = modalData.channels if vm.edit
      params = modalData.params
      apiPath = modalData.resource

      channels = angular.copy rvm.channels
      ## if checked, each element add an attribute named checked: true
      vm.allChannels = []
      wechat = []
      weibo = []
      alipay = []

      _init = ->
        if vm.edit
          vm.title = 'channel_wechat_qrcode_edit'
        else
          vm.title = 'newqrcode'

        for channel in channels
          if vm.edit and _isSelectedChannel(channel)
            channel.checked = true
          switch channel.type
            when 'wechat'
              if channel.title.indexOf('service') isnt -1
                wechat.push channel
            when 'weibo'
              weibo.push channel
            when 'alipay'
              alipay.push channel

        if wechat.length > 0
          vm.allChannels.push {type: 'wechat', channels: wechat}

        if weibo.length > 0
          vm.allChannels.push {type: 'weibo', channels: weibo}

        if alipay.length > 0
          vm.allChannels.push {type: 'alipay', channels: alipay}

      _isSelectedChannel = (channel) ->
        for id in vm.channelIds
          if channel.id is id
            return true
        return false

      _getCheckedChannels = ->
        channelIds = []
        for channelObj in vm.allChannels
          for item in channelObj.channels
            if item.checked
              channelIds.push(item.id)
        channelIds

      _createQrcode = ->
        params['channels'] = _getCheckedChannels()

        if not vm.edit
          restService.post apiPath, params, (data) ->
            $modalInstance.close('ok') if data
        else
          restService.post apiPath, params, (data) ->
            $modalInstance.close('ok') if data

      _init()

      vm.save = ->
        _createQrcode()

      vm.hideModal = ->
        $modalInstance.close()

      vm
  ]
