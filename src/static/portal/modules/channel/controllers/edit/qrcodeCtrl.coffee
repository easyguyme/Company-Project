define [
  'wm/app'
  'wm/config'
], (app, config) ->
  app.registerController 'wm.ctrl.channel.edit.qrcode', [
    'restService'
    '$stateParams'
    'notificationService'
    '$location'
    '$scope'
    (restService, $stateParams, notificationService, $location, $scope) ->
      vm = this

      vm.isAutoScanFollower = false
      vm.isShowTagsDropdown = false
      vm.checkedTags = []
      vm.tagsStore = []
      vm.required = false
      vm.requiredLength = false
      vm.tagValue = ''
      vm.isShow = false
      vm.remainCharacter = 0

      vm.channelId = $stateParams.id
      vm.qrcodeId = $location.search().id
      vm.listUrl = '/channel/qrcode/' + vm.channelId
      vm.transKey = if vm.qrcodeId then 'channel_wechat_qrcode_edit' else 'channel_wechat_qrcode_add'
      vm.breadcrumb = [
        {
          text: 'promotion_qrcode'
          href: vm.listUrl
        }
        vm.transKey
      ]
      vm.detail = {}

      _init = ->
        _editQrcode()
        _getTags()
        return

      _editQrcode = ->
        ## edit qrcode
        if vm.qrcodeId
          data =
            channelId: vm.channelId
          restService.get config.resources.qrcode + '/' + vm.qrcodeId, data, (data) ->
            if data
              vm.detail = data
              if data.content
                vm.showReplyMessage()
              if data.autoTags and data.autoTags.length isnt 0
                vm.isAutoScanFollower = true
                vm.checkedTags = angular.copy data.autoTags
                return

      _getTags = ->
        restService.get config.resources.tags, (data) ->
          if data
            angular.forEach data.items, (item) ->
              vm.tagsStore.push item.name

      _focusTagsValue = ->
        $('#tagsValue').focus()
        return

      _scrollTopDropdown = ->
        $('.autodropdown-items').scrollTop 0
        return

      _clearSelectedDropItem = ->
        $('.autodropdown-item').removeClass 'selected'
        return

      $scope.$watch 'qrcode.detail.name', (newVal) ->
        if newVal and angular.isArray(vm.checkedTags) and vm.checkedTags.length is 0
          vm.required = false
          if $.inArray(newVal, vm.checkedTags) is -1
            vm.tagValue = newVal
            return

      _checkTag = (item) ->
        item = item.trim()
        if $.inArray(item, vm.checkedTags) is -1
          vm.checkedTags.push item
        delete vm.tagValue
        vm.hideAutoDropdown()
        _focusTagsValue()
        return

      $scope.$watch 'isShowTagsDropdown', (newVal) ->
        if newVal
          $timeout ->
            _scrollTopDropdown()
          , 200

      vm.showReplyMessage = ->
        vm.isShow = not vm.isShow

      vm.checkTag = (item) ->
        _checkTag item

      vm.operateTag = (event) ->
        if not vm.isShowTagsDropdown
          _clearSelectedDropItem()
          vm.isShowTagsDropdown = true
        keyCode = event.keyCode or event.which
        selectedIndex = -1
        $dropItems = $('.autodropdown-item')
        if $dropItems.length isnt 0
          for item, index in $dropItems
            $item = $ item
            if $item.hasClass 'selected'
              selectedIndex = index
        if keyCode is 13
          if selectedIndex is -1
            item = vm.tagValue
            _checkTag item.trim() if item
          else
            _checkTag $($dropItems[selectedIndex]).text()
            selectedIndex = -1
          return false
        else if keyCode is 38 # key up
          if $dropItems.length isnt 0
            _clearSelectedDropItem()
            if selectedIndex is -1
              selectedIndex = $dropItems.length - 1
            else
              selectedIndex--
            $($dropItems[selectedIndex]).addClass 'selected'
            return false
        else if keyCode is 40 # key down
          if $dropItems.length isnt 0
            _clearSelectedDropItem()
            selectedIndex++
            $($dropItems[selectedIndex]).addClass 'selected' if selectedIndex < $dropItems.length
            return false
        return true

      vm.removeTag  = (index, event) ->
        vm.checkedTags.splice index, 1
        event.stopPropagation()
        _focusTagsValue()

      vm.hoverDropItem = (index) ->
        _clearSelectedDropItem()
        $($('.autodropdown-item')[index]).addClass 'selected' if index?
        return

      vm.showAutoDropdown = ->
        $('.autodropdown-body-tip').addClass 'hidden'
        $('.autodropdown-body-length-tip').addClass 'hidden'
        vm.isShowTagsDropdown = not vm.isShowTagsDropdown
        _focusTagsValue()

      vm.hideAutoDropdown = ->
        vm.isShowTagsDropdown = false
        _clearSelectedDropItem()

      _validateRequired = ->
        if vm.isAutoScanFollower
          if vm.checkedTags and vm.checkedTags.length is 0
            if not vm.tagValue or vm.tagValue is ''
              $('.autodropdown-body-tip').removeClass 'hidden'
              vm.required = true

      _validateTagLength = ->
        if vm.isAutoScanFollower
          vm.requiredLength = false
          if vm.checkedTags and vm.checkedTags.length is 0
            if vm.tagValue and vm.tagValue isnt '' and vm.tagValue.length > 5
              $('.autodropdown-body-length-tip').removeClass 'hidden'
              vm.requiredLength = true
          else
            angular.forEach vm.checkedTags, (tag) ->
              if tag.length > 5
                $('.autodropdown-body-length-tip').removeClass 'hidden'
                vm.requiredLength = true
          return vm.requiredLength

      _addTags = ->
        # Add new tag.
        params =
          tags: if vm.isAutoScanFollower is true then vm.checkedTags else []
          isAutoScanFollower: vm.isAutoScanFollower
        restService.post config.resources.tags, params, (data) ->
          delete vm.tagValue
          return

      vm.save = ->
        validated = true

        if not vm.detail.name
          validated = false

        if vm.remainCharacter < 0
          notificationService.warning 'channel_broadcast_message_too_long', false
          validated = false

        if _validateRequired()
          validated = false

        if _validateTagLength()
          validated = false

        if validated
          vm.msgType = 'TEXT' if vm.detail.content and typeof vm.detail.content is 'string'
          vm.msgType = 'NEWS' if vm.detail.content and typeof vm.detail.content is 'object'

          vm.checkedTags.push vm.tagValue.trim() if vm.tagValue
          delete vm.tagValue
          # if angular.isArray(vm.checkedTags) and vm.checkedTags.length isnt 0

          data =
            channelId: vm.channelId
            name: vm.detail.name
            content: vm.detail.content if vm.detail.content
            msgType: vm.msgType if vm.msgType
            autoTags: vm.checkedTags

          _addTags()

          if vm.isAutoScanFollower is false
            data.autoTags = []

          url = if vm.qrcodeId then config.resources.qrcode + '/' + vm.qrcodeId else config.resources.qrcodes

          if vm.qrcodeId
            restService.put url, data, (data) ->
              notificationService.success 'channel_wechat_qrcode_update_success', false
              $location.url vm.listUrl
          else
            restService.post url, data, (data) ->
              notificationService.success 'channel_wechat_qrcode_create_success', false
              $location.url vm.listUrl

      vm.cancel = ->
        $location.url vm.listUrl

      _init()

      vm
  ]
