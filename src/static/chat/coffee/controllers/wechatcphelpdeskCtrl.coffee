define [
  'core/coreModule'
  'chat/config'
], (app, config) ->
  app.controller 'wm.ctrl.wechatcphelpdesk', [
    '$scope'
    '$rootScope'
    '$location'
    '$q'
    '$anchorScroll'
    '$timeout'
    '$interval'
    '$translate'
    '$modal'
    '$window'
    'chatService'
    'notificationService'
    '$filter'
    'userService'
    'localSessionService'
    'restService'
    'heightService'
    'debounceService'
    ($scope, $rootScope, $location, $q, $anchorScroll, $timeout, $interval, $translate,
    $modal, $window, chatService, notificationService, $filter, userService, localSessionService,
    restService, heightService, debounceService) ->
      vm = $scope
      rvm = $rootScope
      vm.customers = []
      vm.sessions = []
      vm.historyMessages = {}
      vm.isShowCustomerList = false
      vm.unReadMessage = 0
      tuisongbaoMessageIdBegin = 2 # due to a bug in tuisongbao, the first messageId of a conversation is 2

      scrollToBottom = ->
        $timeout( ->
          scrollHeight = $('.chat-messages').height()
          $('.session-history').scrollTop(scrollHeight)
        , 200)

      vm.toBottom = ->
        $timeout( ->
          scrollHeight = $('.chat-messages').height()
          $('.session-history').scrollTop(scrollHeight)
        , 500)

      setLanguage = ->
        vm.language = rvm.user.language or 'zh_cn'
        $rootScope.$on '$translateChangeSuccess', (event, data) ->
          vm.language = data.language

      removeClient = (openId, nick, textKey) ->
        chatService.conversationId = null
        for idx, customer of vm.customers
          if customer.target is openId
            notificationService.info textKey, false, {nick: nick}
            vm.customers.splice idx, 1
            break

      safeApply = (scope, fn) ->
        phase = if scope.$root then scope.$root.$$phase else ''
        if phase is '$apply' or phase is '$digest'
          fn() if fn and ( typeof fn is 'function')
        else
          scope.$apply(fn)

      addChatMessage = (vm, message) ->
        safeApply vm, ->
          chatService.handleTextMessage message.content
          # TODO: Handle other message types
          vm.sessions[vm.sessions.length - 1].messages.push message
          scrollToBottom()

      generateClientInfo = (message) ->
        extra = message.content.extra
        client =
          target: extra.client?.openId
          chatTimes: extra.chatTimes
          startTime: if extra.startTime then extra.startTime else message.createdAt
          # previousChatTime: data.previousChatTime
          avatar: if extra.client?.avatar then extra.client.avatar else config.chat.defaultAvatar
          nick: if extra.client?.nick then extra.client.nick else chatService.clientName(extra.client.openId)
          source: if extra.client?.source then extra.client.source else 'website'
          messages: []
          extra: extra # bring the extra data
          isOnline: true
        if client.source is config.chat.wechatChannel
          client.source = extra.client.channelInfo.type
        client

      appendNewCustomer = (client) ->
        vm.otherChatsAudio.play()
        customer = getCustomer(client.target)
        if not customer # new chatSession
          vm.customers.push client
        else # customer reconnect before the helpdesk close that chatSession
          customer.startTime = client.startTime
          customer.isOnline = true
          customer.lastMessage = $filter('translate')('helpdesk_client_join')
          customer.transferedSuccessMessage = client.transferedSuccessMessage
          customer.preSessions.push {
            time: client.startTime
            transferSuccessMessage: client.transferSuccessMessage
            transferedSuccessMessage: client.transferedSuccessMessage
            messages: angular.copy client.messages
          }
          if customer.target is vm.customer.target
            vm.customer.isOnline = true
            vm.sessions.push {
              time: client.startTime
              transferSuccessMessage: client.transferSuccessMessage
              transferedSuccessMessage: client.transferedSuccessMessage
              messages: angular.copy client.messages
            }

      getCustomer = (openId) ->
        customer = null
        for idx, customer of vm.customers
          if customer.target is openId
            index = idx
            break
        vm.customers[index]

      addChatMessageBeforeHandler = (data, message) ->
        if message.isMine
          # Append the message directly for helpdesk message
          addChatMessage vm, message
        else
          openId = data.from
          # Check whether the message is sent by selected customer
          if vm.customer and vm.customer.target is openId
            vm.currentChatAudio.play()
            addChatMessage vm, message
          else
            # Push message to the unselected customer
            customer = getCustomer(openId)
            if customer
              vm.$apply( ->
                customer.messages.push message
              )
            vm.otherChatsAudio.play()
            vm.unReadMessage =  vm.unReadMessage + 1

      chatHandler = (data, isMine) ->
        extra = data.content.extra
        # Assume it is comes from web client
        avatar = config.chat.defaultAvatar
        name = chatService.clientName(data.from)
        # Client message
        if extra
          if isMine
            avatar = rvm.user.avatar
            name = rvm.user.name
          else
            avatar = extra.avatar if extra.avatar
            name = extra.nick if extra.nick
          isGraphic = extra.type is 'article'
          message =
            avatar: avatar
            nick: name
            content: chatService.getContent(data.content.text, isGraphic)
            sentTime: data.createdAt
            isMine: isMine
          openId = if isMine then data.to else data.from
          customer = getCustomer openId
          customer.lastMessage = if isGraphic then $filter('translate')('channel_wechat_graphic_message') else data.content.text
          if isGraphic
            chatService.handleGraphicMessage(message.content).then (content) ->
              message.content = content
              addChatMessageBeforeHandler(data, message)
          else
            message.content = chatService.handleTextMessage(message.content)
            addChatMessageBeforeHandler(data, message)

      clientLeftHandler = (data) ->
        extra = data.content.extra
        # update the status of customer
        customer = getCustomer(extra.client.openId)
        customer.lastMessage = $filter('translate')('helpdesk_current_client_left_tip')
        customer.isOnline = false
        message = _getClientLeftMessage()
        if customer.target is vm.customer?.target
          vm.customer.isOnline = false
          vm.sessions[vm.sessions.length - 1].messages.push message
          scrollToBottom()
          vm.currentChatAudio.play()
        else
          customer.messages.push message
          vm.otherChatsAudio.play()
          vm.unReadMessage = vm.unReadMessage + 1

      clientJoinedHandler = (data) ->
        client = generateClientInfo(data)
        client.lastMessage = $filter('translate')('helpdesk_client_join')
        message = _getClientJoinMessage()
        client.messages.push message
        vm.unReadMessage = vm.unReadMessage + 1
        vm.$apply ->
          appendNewCustomer(client)
        target = data.content.extra.client.openId

      offlineHandler = (data) ->
        if data.isForcedOffline
          modalInstance = $modal.open(
            templateUrl: 'offline.html'
            controller: 'wm.ctrl.user.wechatcpoffline'
            size: 'sm'
            windowClass: 'offline-dialog'
            backdrop: 'static'
            resolve:
              modalData: ->
          ).result.then( (data) ->
            #
          )
        return

      vm.selectCustomer = (idx) ->
        vm.isShowCustomerList = false
        selected = if typeof idx is 'object' then idx else vm.customers[idx]
        messages = []
        # If a customer has been picked by the helpdesk
        if vm.customer and vm.customer.target
          preCustomer = getCustomer(vm.customer.target)
          # In case that the helpdesk pick the same cusotmer
          return if selected.target is preCustomer?.target
          # Save original session history in case that the history should be loaded again
          if preCustomer
            preCustomer.preSessions = angular.copy vm.sessions
        vm.customer = angular.copy selected
        # Check the source of customer
        vm.customer.source = if selected.source then selected.source else 'website'
        messages = vm.customer.messages
        unReadMessageCount = messages.length
        if vm.customer.loadedHistoryMessages?
          unReadMessageCount = messages.length - vm.customer.loadedHistoryMessages
        vm.unReadMessage = vm.unReadMessage - unReadMessageCount
        if not selected.preSessions
          selected.preSessions = [{
            time: selected.startTime
            transferSuccessMessage: selected.transferSuccessMessage
            transferedSuccessMessage: selected.transferedSuccessMessage
            messages: messages
          }]
        else
          currentMessages = selected.preSessions[selected.preSessions.length - 1].messages
          currentMessages = currentMessages.concat messages
          selected.preSessions[selected.preSessions.length - 1].messages = currentMessages
        vm.sessions = angular.copy selected.preSessions
        scrollToBottom()
        chatService.target = vm.customer.target
        chatService.historyPage = null
        selected.messages = []
        # after customer is selected, set this to 0. Otherwise the display of message count will be bad
        selected.loadedHistoryMessages = 0
        vm.hideSelectTip = true
        # Get customer offline messages

      vm.checkKeyCode = ($event, text) ->
        # Hit the enter button
        breakLine = $event.ctrlKey or $event.altKey
        if $event.keyCode is 13 and not $event.shiftKey
          if breakLine
            text += '\r\n'
            vm.message = text

            # scroll to bottom of the textarea
            $target = $ $event.target
            $target.scrollTop($target[0].scrollHeight) if $target.length
          else if text
            vm.sendMessage text
        return

      vm.sendMessage = (message) ->
        vm.message = ''
        chatService.sendMessage(message, vm.customer, vm.user).then((data) ->
          chatHandler(data, true)
        )

      vm.closeSession = (event, index) ->
        event.preventDefault() if event
        if index?
          customer = vm.customers[index]
          removeClient(customer.target, customer.nick, '')
          chatService.endChat(customer).then ->
            if customer.target is vm.customer?.target
              chatService.historyPage = null
              vm.customer = null
              vm.hideSelectTip = false
              vm.sessions = []
        else if vm.customer
          removeClient(vm.customer.target, vm.customer.nick, '')
          chatService.endChat(vm.customer).then ->
            chatService.historyPage = null
            vm.customer = null
            vm.hideSelectTip = false
            vm.sessions = []

      vm.displayCustomerList = (isShow) ->
        if isShow?
          vm.isShowCustomerList = isShow
        else
          vm.isShowCustomerList = not vm.isShowCustomerList
        return

      _restoreCurrentChatMessages = ->
        # get N history messages (the N refers to the limit in chatService.getHistoryMessage())
        for customer, index in vm.customers
          _getCurrentChatMessagesAfterRefresh(customer)

      _getCurrentChatMessagesAfterRefresh = (customer) ->
        target = customer.target
        # get current chat's messages from tuisongbao
        chatService.getPreviousChatMessages(target).then (messages) ->
          firstMessage = messages[messages.length - 1]
          customer = $.extend customer, generateClientInfo(firstMessage)
          session = _renderMessages(messages, customer)
          customer.transferedSuccessMessage = if session.transferedSuccessMessage then session.transferedSuccessMessage
          chatService.isOnline(customer).then (result) ->
            customer.isOnline = result
          chatService.parseGraphics(session.messages).then (contents) ->
            session.messages = angular.copy contents
            $.extend customer, session
            # this attr is used to hide the unread message count after refreshing the webpage
            customer.loadedHistoryMessages = customer.messages.length

      _renderMessages = (messages, customer) ->
        session = {}
        session.messages = []
        session.time = messages[0].createdAt
        for idx in [messages.length - 1 .. 0]
          item = messages[idx]
          extra = item.content.extra
          if extra.action is config.chat.action.join
            customer.lastMessage = $filter('translate')('helpdesk_client_join')
            customer.extra = extra
            message = _getClientJoinMessage()
            session.messages.push message
          else if extra.action is config.chat.action.chat
            isGraphic = extra and extra.type is 'article'
            message =
              avatar: if item.to is customer.target then rvm.user.avatar else customer.avatar
              nick: if item.to is customer.target then rvm.user.name else customer.nick
              content: chatService.getContent(item.content.text, isGraphic)
              isMine: item.to is customer.target
            if isGraphic
              chatService.handleGraphicMessage(message.content).then (content) ->
                message.content = content
            else
              message.content = chatService.handleTextMessage(message.content)
            session.messages.push message
            customer.lastMessage = if isGraphic then $filter('translate')('channel_wechat_graphic_message') else item.content.text
          else if extra.action is config.chat.action.transfer
            $translate('helpdesk_transfered_success_info', {
              time: $filter('date')(session.time, 'yyyy-MM-dd HH:mm:ss')
              name: extra.helpdesk?.nick # the name of previous helpdesk
              badge: extra.helpdesk?.badge # the badge of previous helpdesk
            }).then (translatedMessage) ->
              session.transferedSuccessMessage = translatedMessage
            customer.lastMessage = $filter('translate')('helpdesk_client_join')
            customer.extra = extra
            message = _getClientJoinMessage()
            session.messages.push message
          else if extra.action is config.chat.action.transferOut
            $translate('helpdesk_transfer_success_info', {
              name: extra.targetHelpdesk?.nick
              badge: extra.targetHelpdesk?.badge
            }).then (translatedMessage) ->
              session.transferSuccessMessage = translatedMessage
          else if extra.action is config.chat.action.leave
            customer.lastMessage = $filter('translate')('helpdesk_current_client_left_tip')
            message = _getClientLeftMessage()
            session.messages.push message
        session

      _getClientLeftMessage = ->
        message =
          avatar: ""
          nick: ""
          isMine: false # the system message is displayed on the left
          translate: true
          content: chatService.getContent 'helpdesk_current_client_left_tip'
        message

      _getClientJoinMessage = ->
        message =
          avatar: ""
          nick: ""
          translate: true
          content: chatService.getContent 'helpdesk_client_join'
          isMine: false
        message

      _hasMoreHistoryMessage = (firstMessage) ->
        return firstMessage.messageId > tuisongbaoMessageIdBegin

      _getFreeLoginUserInfo = ->
        currentPathParams = $location.search()
        defered = $q.defer()
        restService.post config.resources.wechatcpGetUserInfo, currentPathParams, (data) ->
          # persistence access token and user information in sessionStorage
          localSessionService.setItem config.keys.accessToken, data.accessToken if data.accessToken
          localSessionService.setItem config.keys.currentUser, data.userInfo if data.userInfo
          # save the login status in rootscope
          vm.accessToken = data.accessToken
          rvm.isLogined = true
          defered.resolve()
        defered.promise

      _preHandle = ->
        # get the user information from sessionStorage
        # the information is persist after login
        userInfo = localSessionService.getItem config.keys.currentUser

        if userInfo
          rvm.isIssuePage = false
          # chatService.init()
          rvm.isLogined = true
          rvm.isHelpdeskPage = true
          rvm.checkCookies()
          # trigger dirty check for object reference
          $translate.use userInfo.language
          heightService.afterLogin '.content', 'height'
          rvm.user =
            id: userInfo.id
            name: userInfo.name
            avatar: userInfo.avatar
            language: userInfo.language
            badge: userInfo.badge
            accountId: userInfo.accountId
            isFirstLogin: userInfo.isFirstLogin
          setLanguage()
        return

      _helpdeskLogout = ->
        chatService.logout().then( ->
          restService.get config.resources.logout, {}, ->
            if rvm.user
              localSessionService.removeItem config.keys.currentUser
              localSessionService.removeItem config.keys.accessToken
              rvm.isLogined = false
              delete rvm.user
        )

      vm.helpdeskLogout = ->
        params =
          accesstoken: vm.accessToken
        restService.get config.resources.logout, params, (data) ->
          localSessionService.removeItem config.keys.currentUser
          localSessionService.removeItem config.keys.accessToken
          rvm.isLogined = false
          delete rvm.user
        WeixinJSBridge.call 'closeWindow'
        return

      rvm.checkCookies = ->
        timer = $interval ->
          if not localSessionService.getItem config.keys.currentUser
            if rvm.isHelpdeskPage
              _helpdeskLogout()
            $interval.cancel(timer)
        , 3000

      _initAudios = ->
        vm.otherChatsAudio = chatService.initAudio(config.audios.currentChat, 'currentChat')
        vm.currentChatAudio = chatService.initAudio(config.audios.otherChats, 'otherChats')

      _init = ->
        _initAudios()
        rvm.isHelpdeskPage = true
        rvm.isWeChatPage = true
        if not rvm.isLogined or (rvm.isLogined and not localSessionService.getItem config.keys.currentUser)
          $('.modal, .modal-backdrop').remove()
          heightService.beforeLogin '.content', 'height'
          _getFreeLoginUserInfo().then ->
            if rvm.isLogined
              _preHandle()
              handlers =
                join: clientJoinedHandler
                offline: offlineHandler
                chat: chatHandler
                leave: clientLeftHandler
              # Init helpdesk chatting
              chatService.initHelpdesk(rvm.user, handlers).then(->
                # Get chatting client list
                chatService.getConversations().then((conversations) ->
                  # Transfer data format for showing available clients
                  clients = []
                  for item in conversations
                    client = item
                    clients.push client
                  vm.customers = clients
                  _restoreCurrentChatMessages()
                  return
                )
              , ->
                # Inform the server with joining state successfullly
                notificationService.info 'helpdesk_offduty_tip', false
              )

      _init()

      vm
  ]
  .controller 'wm.ctrl.user.wechatcpoffline', [
    '$modalInstance'
    '$scope'
    '$rootScope'
    '$location'
    ($modalInstance, $scope, $rootScope, $location) ->
      vm = $scope

      _init = ->
        return

      _init()

      vm.close = ->
        $modalInstance.dismiss()
        $rootScope.isLogined = false
        return

      vm
  ]
