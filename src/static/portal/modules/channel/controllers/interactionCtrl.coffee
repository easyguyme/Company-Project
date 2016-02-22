define [
  'wm/app'
  'wm/config'
], (app, config) ->
  app.registerController 'wm.ctrl.channel.interaction', [
    'restService'
    '$scope'
    'notificationService'
    '$stateParams'
    '$location'
    '$sce'
    'debounceService'
    '$timeout'
    '$rootScope'
    (restService, $scope, notificationService, $stateParams, $location, $sce, debounceService, $timeout, $rootScope) ->
      vm = this

      # Send Broadcast Message
      vm.remainCharacter = 0

      _init = ->
        vm.noData = 'channel_wechat_no_message'
        vm.historyMessages = []
        vm.channelId = $stateParams.id
        vm.userId = ''
        vm.currentPage = $location.search().currentPage or 1
        vm.pageSize = $location.search().pageSize or 10
        vm.isCollapsed = false
        vm.historyPageSize = 20
        vm.historyTotalCount = 1
        vm.historyNext = ''
        vm.paginationShow = false
        vm.isload = false

        vm.breadcrumb = [
          'interactive_messages'
        ]

        vm.tabs = [
          {
            'name': 'channel_wechat_message_all'
            'value': 0
          }
          {
            'name': 'today'
            'value': 1
          }
          {
            'name': 'yesterday'
            'value': 2
          }
          {
            'name': 'before_yesterday'
            'value': 3
          }
          {
            'name': 'even_earlier'
            'value': 4
          }
        ]
        vm.history = {}
        tabVal = $location.search().active
        vm.curTab = if tabVal then vm.tabs[tabVal] else vm.tabs[0]
        $scope.ignoreKeywordHit = false

      _encodeHtml = (str) ->
        str.replace /[<>&"]/g, (c) ->
          {
            '<': '&lt;'
            '>': '&gt;'
            '&': '&amp;'
            '"': '&quot;'
          }[c]

      _getInteractMessages = ->
        data =
          'time': vm.curTab.value
          'ignoreKeywordHit': if vm.ignoreKeywordHit then 1 else 0
          'per-page': vm.pageSize
          'page': vm.currentPage
          'channelId': $stateParams.id
          'searchKey': vm.searchKey
          'msgTypes': 'TEXT,IMAGE,VOICE,VIDEO,MUSIC'
        restService.get config.resources.interacts, data, (data) ->
          if data.items
            vm.interactMessages = data.items
            angular.forEach vm.interactMessages, (msg) ->
              msg.sender.headerImgUrl = msg.sender.headerImgUrl or '/images/management/image_hover_default_avatar.png'
              if msg.message.msgType is 'TEXT'
                msg.message.content = _encodeHtml msg.message.content
                if msg.keycode?
                  reg = new RegExp msg.keycode, 'i'
                  msg.message.content = msg.message.content.replace reg, '<span class="fs14">' + msg.keycode + '</span>'
                  msg.message.content = $sce.trustAsHtml msg.message.content
              else
                msg.message.content = 'channel_unsupport_message_type'
          vm.currentPage = data._meta.currentPage
          vm.totalItems = data._meta.totalCount
          vm.pageSize = data._meta.perPage
          vm.pageCount = data._meta.pageCount
        return

      _getHistoryMessage = (accountId, userId) ->
        if vm.historyTotalCount isnt 0
          vm.paginationShow = true
          params =
            'channelId': accountId
            'userId': userId
            'per-page': vm.historyPageSize
            'next': vm.historyNext
            'msgTypes': 'TEXT,IMAGE,VOICE,VIDEO,MUSIC'
          restService.get config.resources.historys, params, (data) ->
            if data.results and data.totalAmount isnt 0
              if vm.historyNext is ''
                vm.history.quantity = data.totalAmount
                history.lastMessage = vm.history.quantity
              else
                history.lastMessage = history.lastMessage - data.pageSize

              if history.lastMessage <= data.pageSize
                vm.paginationShow = false

              vm.hisMessages = vm.historyMessages
              vm.historyMessages = []
              angular.forEach data.results, (msg) ->
                if msg.direction is 'RECEIVE'
                  msg.headerImgUrl = msg.sender.headerImgUrl or '/images/management/image_hover_default_avatar.png'
                else
                  msg.isReply = true
                  angular.forEach $rootScope.channels, (channel) ->
                    if channel.id is accountId
                      msg.headerImgUrl = channel.avatar
                    return

                if msg.message.msgType is 'TEXT'
                  msg.message.content = _encodeHtml msg.message.content
                  if msg.keycode?
                    reg = new RegExp msg.keycode, 'g'
                    content = msg.message.content
                    msg.message.content = ''
                    msg.message.content = content.replace reg, '<span class="fs14">' + msg.keycode + '</span>'
                    msg.message.content = $sce.trustAsHtml msg.message.content
                  else
                    msg.message.content = msg.message.content

                else
                  msg.message.content = 'channel_unsupport_message_type'

                vm.historyMessages = vm.hisMessages.concat(data.results)
            vm.historyPageSize = data.pageSize
            vm.historyNext = data.next
            vm.historyTotalCount = data.totalAmount
            return

      _init()

      vm.changePage = (currentPage) ->
        vm.currentPage = currentPage
        _getInteractMessages()

      vm.changeSize = (pageSize) ->
        vm.pageSize = pageSize
        vm.currentPage = 1
        _getInteractMessages()

      vm.changTab = ->
        vm.noData = 'channel_wechat_no_message'
        vm.searchKey = ''
        _getInteractMessages()

      $scope.$watch 'interaction.ignoreKeywordHit', (newVal, oldVal) ->
        _getInteractMessages()

      vm.changeIgnoreKeywordHit = ->
        _getInteractMessages()

      vm.replyMessage = (index) ->
        vm.userId = vm.interactMessages[index].userId
        vm.isCollapsed = not vm.isCollapsed
        vm.replyMsg = angular.copy vm.interactMessages[index] if vm.interactMessages[index]
        if vm.replyMsg.sender?.accountId? and vm.replyMsg.sender.id?
          condition =
            channelId: vm.replyMsg.sender.accountId
          restService.get config.resources.follower + '/' + vm.replyMsg.sender.id , condition, (data) ->
            vm.replyMsg.sender = angular.copy data
        vm.historyMessages = []
        vm.historyNext = ''
        vm.historyTotalCount = 1
        vm.paginationShow = false
        $(window).scrollTop(0)
        _getHistoryMessage vm.channelId, vm.userId

      vm.loadMore = ->
        _getHistoryMessage(vm.channelId, vm.userId)

      vm.sendMessage = ->
        if vm.message and vm.message isnt ''
          msgType = if typeof vm.message is 'string' then 'TEXT' else if typeof vm.message is 'object' then 'NEWS' else ''
          if msgType is 'TEXT' or msgType is 'NEWS'

            if vm.remainCharacter? and vm.remainCharacter < 0
              notificationService.warning 'channel_broadcast_message_too_long', false
              return false

            data =
              channelId: $stateParams.id
              msgType: msgType
              toUser: vm.replyMsg.userId
              fromUser: vm.replyMsg.message?.toUser
              content: vm.message
            restService.post config.resources.interacts, data, (data) ->
              vm.currentPage = 1
              vm.closeReplyPanel()
              notificationService.success 'channel_reply_message_success', false
        else
          notificationService.warning 'channel_empty_reply_message', false

      vm.closeReplyPanel = ->
        vm.isCollapsed = not vm.isCollapsed
        vm.message = ''
        delete vm.replyMsg

      vm.search = ->
        _getInteractMessages()
        vm.noData = 'search_no_data'

      vm
  ]
