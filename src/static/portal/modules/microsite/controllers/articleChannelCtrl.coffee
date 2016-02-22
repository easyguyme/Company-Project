define [
  'wm/app'
  'wm/config'
], (app, config) ->
  app.registerController 'wm.ctrl.microsite.editArticles', [
    'modalData'
    'restService'
    '$modalInstance'
    '$scope'
    'notificationService'
    'validateService'
    '$translate'
    (modalData, restService, $modalInstance, $scope, notificationService, validateService, $translate) ->
      vm = $scope
      channels = modalData.channels
      index = modalData.index
      isEdit = modalData.isEdit
      if index is -1
        addChannel = true
        vm.channel =
          name: ''
          fields: []
      else
        addChannel = false
        vm.channel = channels[index]

      vm.types =
      [
          {
              text: 'customer_members_singleline_text'
              value: 'input'
          }
          {
              text: 'customer_members_multiplelines_text'
              value: 'textarea'
          }
          {
              text: 'content_articles_image'
              value: 'image'
          }
          {
              text: 'customer_members_date'
              value: 'date'
          }
          {
              text: 'content_articles_time'
              value: 'time'
          }
      ]

      vm.fields = vm.channel.fields
      vm.isEdit = isEdit

      translations = [
        'content_articles_field_name_tip'
      ]

      $translate(translations).then (map) ->
        vm.nameError = map['content_articles_field_name_tip']
        return

      vm.validateFieldName = (name, $elem) ->
        result = true
        if vm.fields.length > 0
          count = 0
          for field in vm.fields
            if field.name is name
              count++
          if count > 1
            result = false
            validateService.showError $elem, vm.nameError
          else
            $elem.focus()
            $elem.blur()
        return result

      vm.validateChannelName = ->
        error = ''
        for channel, i in channels
          if vm.channel.name is channel.name and i isnt index
            error = 'content_articles_channel_name_error'
        return error

      vm.addField = ->
        if vm.fields.length < 10
          vm.fields.length += 1
          vm.fields[vm.fields.length - 1] =
            {
              type: vm.types[0].value
            }
        else
          notificationService.warning 'content_articles_field_tip'

      _deleteField = (index) ->
        $scope.$apply(->
          vm.fields.splice index, 1
        )

      vm.deleteField = (index, $event) ->
        notificationService.confirm $event,{
          submitCallback: _deleteField
          params: [index]
          title: 'content_articles_field_delete_confirm'
        }

      vm.hideEditTag = ->
        $('.confirm').hide()
        $modalInstance.close()

      vm.addTag = ->
        $('.confirm').hide()
        result = true
        for field, i in vm.fields
          if not vm.validateFieldName field.name, $($('.field-name')[i])
            result = false
        result = false if vm.validateChannelName()
        if result
          if addChannel
            restService.post config.resources.articleChannels, vm.channel, (data) ->
              notificationService.success 'content_articles_channel_create'
              $modalInstance.close data
          else
            restService.put config.resources.articleChannel + '/' + vm.channel.id, vm.channel, (data) ->
              notificationService.success 'content_articles_channel_update'
              $modalInstance.close data
      vm
  ]
