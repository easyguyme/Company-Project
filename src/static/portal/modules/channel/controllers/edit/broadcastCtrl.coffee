define [
  'wm/app'
  'wm/config'
], (app, config) ->
  app.registerController 'wm.ctrl.channel.edit.broadcast', [
    'restService'
    '$stateParams'
    '$scope'
    'notificationService'
    '$location'
    'validateService'
    '$filter'
    '$modal'
    (restService, $stateParams, $scope, notificationService, $location, validateService, $filter, $modal) ->
      vm = this
      vm.id = $location.search().msg
      vm.channelId = $stateParams.id
      vm.remainCharacter = 0

      vm.breadcrumb = [
        {
          text: 'channel_wechat_mass_title',
          href: '/channel/broadcast/' + vm.channelId
        }
      ]
      if not vm.id
        vm.breadcrumb.push 'channel_wechat_mass_title_add_broadcast'
      else
        vm.breadcrumb.push 'channel_wechat_mass_title_edit_broadcast'

      vm.tags = []
      vm.sendType = 'immediate'
      vm.mixed = true

      vm.objectItems = [{
        value: 'all',
        text: 'channel_wechat_mass_all'
      }
      {
        value: 'tag',
        text: 'channel_wechat_mass_tag'
      }]
      vm.object = vm.objectItems[0].value

      vm.genderItems = [{
        value: 'ALL',
        text: 'channel_wechat_mass_unlimited'
      }
      {
        value: 'MALE',
        text: 'channel_wechat_mass_male'
      }
      {
        value: 'FEMALE',
        text: 'channel_wechat_mass_female'
      }]
      vm.gender = vm.genderItems[0].value

      # get tags
      _getTags = ->
        restService.get config.resources.tags, (data) ->
          tagContainer = []
          for item in data.items
            tagContainer.push item.name
          vm.autoCompleteItems = tagContainer

      _getTags()

      if vm.id
        vm.location = {}
        data =
          channelId: vm.channelId
        restService.get config.resources.massmessage + '/' + vm.id, data, (data) ->
          location = {}

          queries = data.userQuery
          if queries
            if queries.tags and queries.tags.length > 0
              vm.object = vm.objectItems[1].value
              vm.tags = queries.tags
            else
              vm.gender = 'MALE' if queries.gender is 'MALE'
              vm.gender = 'FEMALE' if queries.gender is 'FEMALE'
              location.country = queries.country if queries.country
              location.province = queries.province if queries.province
              location.city = queries.city if queries.city
              vm.location = location

          vm.message = data.content if data.content?
          vm.mixed = true if data.massiveType is 'MIXED'

          if data.scheduleTime?
            vm.sendType = 'timing'
            vm.scheduleTime =  new Date(data.scheduleTime.replace(/-/g, '/')).getTime()

      vm.changeObjectItems = ->
        location = {}
        if vm.object is 'tag'
          vm.tags = []
        else
          vm.gender = vm.genderItems[0].value
          vm.location = location

      vm.changeGenderItems = ->

      _addErrorTip = ->
        $('.wechat-message-wrap .message-input').addClass 'form-control-error'
        $('.text-tip').addClass 'hide'
        $('.message-error-tip').removeClass 'hide'
        return

      _removeErrorTip = ->
        $('.wechat-message-wrap .message-input').removeClass 'form-control-error'
        $('.text-tip').removeClass 'hide'
        $('.message-error-tip').addClass 'hide'
        return

      vm.checkDate = ->
        if vm.sendType is 'timing' and vm.scheduleTime and vm.scheduleTime <= moment().valueOf()
          validateService.showError $('#schedule-picker'), $filter('translate')('channel_wechat_mass_time_safe')
          return false
        return true

      _checkFields = ->
        _removeErrorTip()
        if not vm.message
          _addErrorTip()
          return false
        if vm.remainCharacter? and vm.remainCharacter < 0
          notificationService.warning 'channel_broadcast_message_too_long', false
          return false
        if not vm.checkDate()
          return false
        return true

      vm.submit = ->
        if vm.sendType is 'immediate'
          vm.schedule = ''
        else
          vm.schedule = vm.scheduleTime

        if not _checkFields()
          return false
        else
          ## formate send data
          vm.msgType = 'TEXT' if typeof vm.message is 'string'
          vm.msgType = 'NEWS' if typeof vm.message is 'object'
          gender = vm.gender if vm.gender isnt 'ALL'

          data =
            channelId: vm.channelId
            msgType: vm.msgType
            scheduleTime: vm.schedule
            userQuery:
              tags: vm.tags
              gender: gender
              country: vm.location?.country
              province: vm.location?.province
              city: vm.location?.city
            content: vm.message
            mixed: vm.mixed

          url = if vm.id then config.resources.massmessage + '/' + vm.id else config.resources.massmessages

          if vm.id
            restService.put url, data, (data) ->
              if vm.sendType is 'immediate'
                notificationService.success 'channel_wechat_immediate_mass_create_success', false
              else
                notificationService.success 'channel_wechat_mass_update_success', false
              $location.url '/channel/broadcast/' + vm.channelId
          else
            restService.post url, data, (data) ->
              if vm.sendType is 'immediate'
                notificationService.success 'channel_wechat_immediate_mass_create_success', false
              else
                notificationService.success 'channel_wechat_mass_create_success', false
              $location.url '/channel/broadcast/' + vm.channelId

      vm.cancel = ->
        $location.url '/channel/broadcast/' + vm.channelId

      vm.preview = ->
        if not vm.message
          _addErrorTip()
        else
          ## formate send data
          vm.msgType = 'TEXT' if typeof vm.message is 'string'
          vm.msgType = 'NEWS' if typeof vm.message is 'object'

          modalInstance = $modal.open(
            templateUrl: 'preview.html'
            controller: 'wm.ctrl.channel.edit.broadcast.preview'
            resolve:
              modalData: ->
                channelId: vm.channelId
                msgType: vm.msgType
                content: vm.message
          )
        return
      return
  ]
  .registerController 'wm.ctrl.channel.edit.broadcast.preview', [
    'restService'
    '$scope'
    '$modalInstance'
    'modalData'
    (restService, $scope, $modalInstance, modalData) ->
      vm = $scope

      restService.post config.resources.massmessagepreview, modalData, (data) ->
        vm.qrcode = data.qrcode
        return

      vm.hideModal = ->
        $modalInstance.dismiss('cancel')
        return
      return
  ]
