define [
  'core/coreModule'
  'chat/config'
], (app, config) ->
  app.controller 'wm.ctrl.helpdesk', [
    '$scope'
    '$rootScope'
    '$location'
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
    'localStorageService'
    'restService'
    'sessionService'
    ($scope, $rootScope, $location, $anchorScroll, $timeout, $interval, $translate,
    $modal, $window, chatService, notificationService, $filter, userService, localStorageService,
    restService, sessionService) ->
      vm = $scope
      rvm = $rootScope
      vm.customers = []
      vm.sessions = []
      vm.historyMessages = {}
      vm.graphicPath = config.resources.graphicList
      tuisongbaoMessageIdBegin = 2 # due to a bug in tuisongbao, the first messageId of a conversation is 2

      vm.tabs = [
        {
          active: true
          name: "helpdesk_customer_info"
          template: "info.html"
        }
        {
          active: false
          name: "helpdesk_issue_record"
          template: "issue.html"
        }
        {
          active: false
          name: "helpdesk_message_record"
          template: "message.html"
        }
        {
          active: false
          name: "helpdesk_wiki"
          template: "wiki.html"
        }
      ]

      tabIndex =
        info: 0
        issue: 1
        message: 2
        wiki: 3

      vm.curTab = vm.tabs[0]

      vm.changeTab = ->

      _getActiveTabIndex = ->
        currentTab = vm.curTab
        for tab, index in vm.tabs
          return index if currentTab.template is tab.template

      _triggerConversationList = ->
        openId = vm.customer?.target
        vm.$broadcast 'getConversations', openId if openId

      scrollToBottom = ->
        $timeout( ->
          scrollHeight = $('.chat-messages').height()
          $('.session-history').scrollTop(scrollHeight)
        , 200)

      setLanguage = ->
        language = vm.user.language if vm.user
        vm.language = language or 'zh_cn'
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
          channelId: if extra.client?.channelId then extra.client.channelId else null
          messages: []
          extra: extra # bring the extra data
          isOnline: true
        if client.source is config.chat.wechatChannel
          client.source = extra.client.channelInfo.type
        _getMemberCardInfo(client)
        client

      _getMemberCardInfo = (customer) ->
        return if not customer.channelId or not customer.target
        params =
          openId: customer.target
          channelId: customer.channelId
        restService.get config.resources.getMemberCardInfo, params, (data) ->
          return unless data?
          customer.cardName = data.card?.name
          customer.point = data.score

      appendNewCustomer = (client) ->
        customer = getCustomer(client.target)
        if not customer # new chatSession
          vm.customers = [client].concat vm.customers
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
          _getMemberCardInfo(customer) # update the member card's info
          # change the position of customer to first
          for item, index in vm.customers
            if item.target is client.target
              vm.customers.splice index, 1
              vm.customers = [customer].concat vm.customers

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
          chatService.log 'Get my message', data
          addChatMessage vm, message
        else
          openId = data.from
          chatService.log 'Get chat message from ' + openId, data
          # Add the click callback to the notification
          clickCallback = ->
            vm.selectCustomer getCustomer(openId)
          # Check whether the message is sent by selected customer
          if vm.customer? and vm.customer?.target is openId
            chatService.log 'Append to current chatting openId ' + openId, message
            addChatMessage vm, message
            if document.hidden
              if rvm.notificationSupport
                chatService.showNotification rvm.user.notificationType, {title: message.nick, body: message.content.body, icon: message.avatar}, clickCallback
              vm.currentChatAudio.play()
          else
            # Push message to the unselected customer
            chatService.log 'Other unselected client openId ' + openId, message
            customer = getCustomer(openId)
            if customer
              vm.$apply( ->
                customer.messages.push message
              )
            vm.otherChatsAudio.play()
            if rvm.notificationSupport
              # Add the notification when new message received
              chatService.showNotification rvm.user.notificationType, {title: message.nick, body: message.content.body, icon: message.avatar}, clickCallback

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
          customer?.lastMessage = if isGraphic then $filter('translate')('channel_wechat_graphic_message') else data.content.text
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
        customer?.lastMessage = $filter('translate')('helpdesk_current_client_left_tip')
        customer.isOnline = false
        message = _getClientLeftMessage()
        if customer.target is vm.customer?.target
          vm.customer.isOnline = false
          vm.sessions[vm.sessions.length - 1].messages.push message
          scrollToBottom()
        else
          customer.messages.push message

      clientJoinedHandler = (data) ->
        client = generateClientInfo(data)
        client?.lastMessage = $filter('translate')('helpdesk_client_join')
        message = _getClientJoinMessage()
        client.messages.push message
        vm.$apply ->
          appendNewCustomer(client)
        # Add the click callback to the notification
        target = data.content.extra.client.openId
        clickCallback = ->
          vm.selectCustomer getCustomer(target)
        if rvm.notificationSupport
          chatService.showNotification rvm.user.notificationType, {
            title: client.nick
            body: $filter('translate')('helpdesk_self_service_connect')
            icon: client.avatar
          }, clickCallback

      clientTransferedInHandler = (data) ->
        client = generateClientInfo(data)
        client?.startTime = data.createdAt
        client?.lastMessage = $filter('translate')('helpdesk_client_join')
        message = _getClientJoinMessage()
        client.messages.push message
        extra = data.content.extra
        $translate('helpdesk_transfered_success_info', {
          time: $filter('date')(client.startTime, 'yyyy-MM-dd HH:mm:ss')
          name: extra.helpdesk.nick # the name of previous helpdesk
          badge: extra.helpdesk.badge # the badge of previous helpdesk
        }).then (translatedMessage) ->
          client.transferedSuccessMessage = translatedMessage
          appendNewCustomer(client)
        # Add the click callback to the notification
        target = data.content.extra.client.openId
        clickCallback = ->
          vm.selectCustomer getCustomer(target)
        if rvm.notificationSupport
          chatService.showNotification rvm.user.notificationType, {
            title: $filter('translate')('helpdesk_customer_transfer')
            body: $filter('translate')('helpdesk_notification_transfer_tip', {'helpdeskName': extra.helpdesk.nick, 'customerName': client.nick})
            icon: 'https://dn-quncrm.qbox.me/build/landing/images/transfer_avatar.f4466d2d.png'
          }, clickCallback

      offlineHandler = (data) ->
        if data.isForcedOffline
          modalInstance = $modal.open(
            templateUrl: 'offline.html'
            controller: 'wm.ctrl.user.offline'
            size: 'sm'
            windowClass: 'offline-dialog'
            backdrop: 'static'
            resolve:
              modalData: ->
          ).result.then( (data) ->
            chatService.log data
          )
          # remove token
          sessionService.removeLoginInfo(true)
          # unbind event on chatManager
          chatService.dismissChat()
        return

      $translate('helpdesk_reload_tip').then((tip) ->
          shareTip = tip
          # Inform the user of protential data missing when refreshing and closing chat window
          not $window.onbeforeunload and ($window.onbeforeunload = (e) ->
            if $location.path() is config.paths.helpdesk
              e = e or window.event
              e.returnValue = shareTip if e
              return shareTip
          )
      )

      broadcastCurrentselectCustomer = ->
        if vm.curTab and vm.curTab.name is vm.tabs[0].name
          #client = angular.copy vm.customer
          #client.target = 'oC9Aes9vuisNRmC4ZNdIXY1lb_rk' if client
          vm.$broadcast 'changeClient', vm.customer

      vm.selectCustomer = (idx) ->
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

          # cache the unsend message
          preCustomer.cachedText = _setCachedText()
        vm.customer = angular.copy selected
        # Check the source of customer
        vm.customer.source = if selected.source then selected.source else 'website'
        messages = vm.customer.messages
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
        # broadcast to let message log load conversations of current customer
        _triggerConversationList() if _getActiveTabIndex() is tabIndex.message

        broadcastCurrentselectCustomer()

        # put cached text in
        vm.message = _getCachedText(vm.customer)

      _setCachedText = ->
        if vm.message then vm.message else ''

      _getCachedText = (customer) ->
        if customer.isOnline then customer.cachedText else ''

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
          chatService.log 'Send message successfully'
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

              broadcastCurrentselectCustomer()
        else if vm.customer
          removeClient(vm.customer.target, vm.customer.nick, '')
          chatService.endChat(vm.customer).then ->
            chatService.historyPage = null
            vm.customer = null
            vm.hideSelectTip = false
            vm.sessions = []

            broadcastCurrentselectCustomer()

      vm.transferHelpDesk = ->
        modalInstance = $modal.open(
          templateUrl: 'transfer.html'
          controller: 'wm.ctrl.helpdesk.transfer'
          windowClass: 'transfer-dialog'
          backdrop: 'static'
          resolve:
            modalData: ->
              desk: vm.user
              client: vm.customer
        ).result.then((data) ->
          chatService.transferChat(getCustomer data.client.openId).then ->
            # Remove the customer from the available customer list
            removeClient(vm.customer.target, vm.customer.nick, 'helpdesk_transfer_success')
            vm.customer = null
            vm.sessions = []
            vm.hideSelectTip = false
            scrollToBottom()
        )

      vm.closeTipbox = ->
        rvm.notificationTipboxStatus = ''

      vm.closeNotificationTipbox = ->
        rvm.notificationTipboxStatus = 'closed'

      vm.closeNotification = ->
        user =
          notificationType: config.chat.notificationType.mark
        restService.put config.resources.helpdesk + '/' + vm.user.id, user, (data) ->
          rvmUser = rvm.user
          currentUser = userService.getInfo()
          rvmUser['notificationType'] = user.notificationType
          currentUser['notificationType'] = user.notificationType
          localStorageService.setItem config.keys.currentUser, currentUser
          rvm.notificationTipboxStatus = 'disabled'

      _restoreCurrentChatMessages = ->
        # get N history messages (the N refers to the limit in chatService.getHistoryMessage())
        for customer, index in vm.customers
          _getCurrentChatMessagesAfterRefresh(customer)

      _getCurrentChatMessagesAfterRefresh = (customer) ->
        target = customer.target
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

      _initAudios = ->
        vm.currentChatAudio = chatService.initAudio(config.audios.currentChat, 'currentChat')
        vm.otherChatsAudio = chatService.initAudio(config.audios.otherChats, 'otherChats')

      _init = ->
        setLanguage()
        rvm.isHelpdeskPage = true
        if rvm.isLogined
          handlers =
            join: clientJoinedHandler
            offline: offlineHandler
            chat: chatHandler
            transfer: clientTransferedInHandler
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

        else
          $location.path config.paths.login
        _initAudios()

      _init()

      vm.$on 'useKnowledge', (event, data) ->
        if vm.customer
          vm.message = data

      vm.$on 'requestSelectedClient', (event, data) ->
        if data.page is 'info'
          broadcastCurrentselectCustomer()

      vm.$on 'needOpenId', ->
        _triggerConversationList()

      vm.$watch 'customer.isOnline', (newVal, oldVal) ->
        _broadcastOnlineStatus(newVal)

      _broadcastOnlineStatus = (status) ->
        vm.$broadcast 'changeCurrentClientOnlineStatus', status

      vm.$on 'needOnlineStatus', ->
        _broadcastOnlineStatus(vm.customer.isOnline)

      vm
  ]
  .controller 'wm.ctrl.user.offline', [
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
        $location.path config.paths.login
        return

      vm
  ]
  .controller 'wm.ctrl.helpdesk.transfer', [
    '$modalInstance'
    '$scope'
    '$rootScope'
    'modalData'
    'restService'
    'notificationService'
    ($modalInstance, $scope, $rootScope, modalData, restService, notificationService) ->
      vm = $scope
      vm.enableOkButton = false
      vm.hasClient = false

      _getHelpdeskList = ->
        listParams =
          clientOpenId: modalData.client.target

        restService.get config.resources.helpdesks, listParams, (data) ->
          vm.helpdesks = data
          isSetSelectedHelpdesk = false
          if data and data.length
            for item, index in data
              if item.isOnline
                vm.hasClient = true # at least 1 helpdesk is online, then show it
                if item.isLastChat
                  vm.hasLastChatHelpdesk = true
                if item.maxClient isnt item.clientCount
                  vm.enableOkButton = true # enable button if there is at least one helpdesk is online and is not busy

                if not isSetSelectedHelpdesk and item.maxClient isnt item.clientCount
                  vm.selectedHelpdesk.id = item.id
                  vm.selectedHelpdesk.badge = item.badge
                  vm.selectedHelpdesk.nick = item.name
                  isSetSelectedHelpdesk = true
                conversationCount = item.conversationCount
                maxClient = item.maxClient
                percent = conversationCount / maxClient

                if 0 <= percent <= 0.2
                  icon = 'notbusy'
                if 0.2 < percent <= 0.8
                  icon = 'alittlebusy'
                if percent > 0.8
                  icon = 'busy'
                img = "/images/helpdesk/#{icon}.png"

                item.busy =
                  icon: img
                  text: conversationCount + '/' + maxClient
          return
        return

      _init = ->
        vm.selectedHelpdesk = {}

        _getHelpdeskList()

      _init()

      vm.submit = ->
        params =
          helpdesk:
            id: modalData.desk.id
            nick: modalData.desk.name
            badge: modalData.desk.badge
          targetHelpdesk:
            id: vm.selectedHelpdesk.id
            nick: vm.selectedHelpdesk.nick
            badge: vm.selectedHelpdesk.badge
          client:
            openId: modalData.client.target
            nick: modalData.client.nick
            avatar: modalData.client.avatar
            source: modalData.client.source
            channelId: modalData.client.extra.client.channelId
            channelInfo: modalData.client.extra.client.channelInfo # which will be used in create chatSession
        if vm.selectedHelpdesk.id
          restService.post config.chat.url.transfer, params, (data) ->
            if data.status is 'ok'
              $modalInstance.close(params)
        else
          notificationService.info 'helpdesk_select_helpdesk'

      vm.close = ->
        $modalInstance.dismiss()

      vm
  ]
  .controller 'wm.ctrl.user.notification', [
    '$modalInstance'
    'modalData'
    '$scope'
    '$rootScope'
    'restService'
    'notificationService'
    'userService'
    'localStorageService'
    ($modalInstance, modalData, $scope, $rootScope, restService, notificationService, userService, localStorageService) ->
      vm = $scope

      _syncLocalData = (user) ->
        rvmUser = $rootScope.user
        currentUser = userService.getInfo()
        angular.forEach user, (value, key) ->
          rvmUser[key] = value
          currentUser[key] = value
        localStorageService.setItem config.keys.currentUser, currentUser
        return

      _init = ->
        vm.notificationType = if modalData.notificationType then modalData.notificationType else config.chat.notificationType.desktopAndMark

      vm.submit = ->
        user =
          notificationType: vm.notificationType
        restService.put config.resources.helpdesk + '/' + modalData.id, user, (data) ->
          _syncLocalData user
          $modalInstance.close({type: vm.notificationType})

      vm.close = ->
        $modalInstance.dismiss()

      _init()
  ]
