define [
  'wm/app'
  'wm/config'
], (app, config) ->
  app.registerController 'wm.ctrl.channel.edit.autoreply', [
    'restService'
    '$stateParams'
    '$location'
    'notificationService'
    '$scope'
    (restService, $stateParams, $location, notificationService, $scope) ->
      vm = this

      baseAutoreplyTitle = 'channel_wechat_' + if typeof $location.search().id is "undefined" then 'newrule' else 'updaterule'
      vm.breadcrumb = [
        {
          text: 'channel_wechat_autoreply',
          href: '/channel/autoreply/' + $stateParams.id
        },
        baseAutoreplyTitle
      ]

      vm.remainCharacter = 0

      vm.items =
      [
          {
              text: "channel_wechat_complete_match"
              value: false
          }
          {
              text: "channel_wechat_contain"
              value: true
          }
      ]
      vm.fuzzy = vm.items[0].value

      _init = ->
        id = $location.search().id
        vm.wechatKeyword = {}
        if typeof id is "undefined"
          vm.create = true
          vm.wechatKeyword =
            "channelId": $stateParams.id
          vm.keycode = null
          vm.fuzzy = vm.items[0].value
          vm.status = true
          vm.replyMessage = null
        else
          restService.get config.resources.keyword + "/" + id, { "channelId": $stateParams.id }, (data) ->
            vm.create = false
            vm.wechatKeyword = data
            vm.wechatKeyword.channelId = $stateParams.id
            vm.keycode = vm.wechatKeyword.keycodes[0]
            if not vm.wechatKeyword.fuzzy
              vm.fuzzy = vm.items[0].value
            else
              vm.fuzzy = vm.items[1].value
            if vm.wechatKeyword.status is "ENABLE"
              vm.status = true
            else
              vm.status = false
            vm.replyMessage = vm.wechatKeyword.content

      _init()

      vm.addWechatKeyword = ->
        validate = true

        if not vm.replyMessage
          $('.message-error-tip').removeClass 'hide'
          $('.wechat-message-wrap .message-input').addClass 'form-control-error'
          $('.text-tip').addClass 'hide'
          validate = false

        if vm.remainCharacter? and vm.remainCharacter < 0
          notificationService.warning 'channel_broadcast_message_too_long', false
          validate = false

        if validate
          vm.wechatKeyword.fuzzy = vm.fuzzy
          vm.wechatKeyword.keycodes = new Array()
          vm.wechatKeyword.keycodes[0] = vm.keycode
          if vm.status
            vm.wechatKeyword.status = "ENABLE"
          else
            vm.wechatKeyword.status = "DISABLE"
          if typeof(vm.replyMessage) is "string"
            vm.wechatKeyword.msgType = "TEXT"
            vm.wechatKeyword.content = vm.replyMessage
          else
            vm.wechatKeyword.msgType = "NEWS"
            vm.wechatKeyword.content = vm.replyMessage
          if vm.create
            restService.post config.resources.keywords, vm.wechatKeyword, (data) ->
              notificationService.success 'channel_wechat_keyword_create_success'
              $location.url "/channel/autoreply/" + $stateParams.id
              return
          else
            restService.put config.resources.keyword + "/" + vm.wechatKeyword.id, vm.wechatKeyword, (data) ->
              notificationService.success 'channel_wechat_keyword_update_success'
              $location.url "/channel/autoreply/" + $stateParams.id
              return
          return

      vm.backToList = ->
        $location.url "/channel/autoreply/" + $stateParams.id
      vm
  ]
