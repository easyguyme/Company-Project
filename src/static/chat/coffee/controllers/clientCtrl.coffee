define [
  'core/coreModule'
  'chat/config'
], (app, config) ->
  app.controller 'wm.ctrl.client', [
    '$scope'
    '$window'
    '$location'
    '$timeout'
    '$anchorScroll'
    'chatService'
    'localStorageService'
    ($scope, $window, $location, $timeout, $anchorScroll, chatService, localStorageService) ->
      vm = $scope
      # Indicate that whether the cusotmer is served by a helpdesk
      vm.isServed = false
      # Indicate that whether the cusotmer is informed of the waiting message
      vm.isInformed = false
      # Indicate the current mode whether is the self helpdesk mode
      vm.isSelfHelpdeskMode = true
      # Indicate that whether the session is braked because of timeout
      vm.autoBraked = false
      vm.messages = []

      replyMap = {}
      selfHelpdeskSettings = {}
      clientOrders = []
      deskName = ''

      chatService.getSelfHelpdeskSetting().then((data) ->
        if data
          selfHelpdeskSettings = data.settings
          showSystemMessage getCurrentOrderContent()
        else
          connectHelpdesk()
      )

      scrollToBottom = ->
        $timeout(->
          $location.hash 'bottom'
          $anchorScroll()
        , 200)

      showSystemMessage = (text, sentTime) ->
        if text
          sentTime = new Date().getTime() if not sentTime
          message =
            nick: config.chat.systemName
            content:
              body: text #Only suppor text now
              msgType: 'TEXT'
            sentTime: sentTime
            isMine: false
          chatService.handleTextMessage message.content
          vm.messages.push message
          scrollToBottom()

      showSelfMessage = (text, sentTime) ->
        if text
          sentTime = new Date().getTime() if not sentTime
          message =
            avatar: config.chat.defaultAvatar
            nick: 'me'
            content:
              body: text #Only suppor text now
              msgType: 'TEXT'
            sentTime: sentTime
            isMine: false
          chatService.handleTextMessage message.content
          vm.messages.push message
          scrollToBottom()

      safeApply = (scope, fn) ->
        phase = if scope.$root then scope.$root.$$phase else ''
        if phase is '$apply' or phase is '$digest'
          fn() if fn and ( typeof fn is 'function')
        else
          scope.$apply(fn)

      closeSession = (callback) ->
        if vm.isServed # if in service then send leave message and leave conversation
          chatService.endChat(vm.extraCache)
        else # if not in service then just leave conversation
          chatService.leaveConversation()

      autoBrake = ->
        ###
        vm.leftTimer = $timeout(->
          if vm.isServed
            vm.autoBraked = true
            closeSession((data) ->
              showSystemMessage(replyMap[config.chat.replyType.brake], data.sentTime)
            )
        , chatService.maxWaitTime * config.chat.minute)
        ###

      connectHelpdesk = ->
        handlers =
          join: serveHandler
          chat: chatHandler
          leave: leftHandler
          pending: pendingHandler
        chatService.initClient(handlers).then(->
          waitHandler(false)
        , ->
          waitHandler(true)
        )

      pendingHandler = ->
        messageKey = config.chat.replyType.wait
        showSystemMessage(replyMap[messageKey])

      waitHandler = (offduty) ->
        if not vm.isInformed and not vm.isServed
          replyMap = chatService.getReplyMap()
          if offduty # if is offduty then inform client that helpdesk is offdutys
            messageKey = config.chat.replyType.offduty
            showSystemMessage(replyMap[messageKey])
          vm.isInformed = true
        vm.isSelfHelpdeskMode = offduty

      chatHandler = (message, isMine) ->
        # Notice: Only support text content now
        text = message.content.text
        chatService.handleTextMessage text
        name = if isMine then 'me' else message.content.extra.nick
        isGraphic = message.content.extra and message.content.extra.type is 'article'
        message =
          nick: name
          content: chatService.getContent(text, isGraphic)
          sentTime: message.createdAt or new Date().getTime()
          isMine: isMine

        if isGraphic
          chatService.handleGraphicMessage(message.content).then (content) ->
            message.content = content
            safeApply vm, ->
              vm.messages.push message
            scrollToBottom()
        else
          message.content = chatService.handleTextMessage(message.content)
          safeApply vm, ->
            vm.messages.push message
          scrollToBottom()

      leftHandler = (data) ->
        if data.extra
          # Show that helpdesk left
          showSystemMessage(replyMap[data.extra.type], data.sentTime)
        else
          # Closed by the desk
          showSystemMessage(replyMap[config.chat.replyType.close], data.sentTime) if not vm.autoBraked
        vm.isServed = false

      serveHandler = (message) ->
        startTime = message.createAt
        vm.extraCache =
          isOnline: true
          extra: message.content.extra
        # Serve the client
        vm.isServed = true
        # Inform user of connecting
        replyMap = chatService.getReplyMap()
        showSystemMessage(replyMap[config.chat.replyType.connected], startTime)
        # Send client left message invertally
        autoBrake()

      # Force closing the session on refresh page
      not $window.onbeforeunload and ($window.onbeforeunload = (e) ->
        localStorageService.removeItem config.keys.offlineMessages
        closeSession()
        return
      )

      getCurrentOrderContent = ->
        currentLevel = selfHelpdeskSettings
        if currentLevel
          angular.forEach clientOrders, (order) ->
            currentLevel = currentLevel.menus[order]
          currentLevel.content

      getSelfHelpdeskReply = (keyword) ->
        if $.isNumeric keyword
          keyword = parseInt keyword
        currentLevel = selfHelpdeskSettings
        return if not currentLevel
        angular.forEach clientOrders, (order) ->
          currentLevel = currentLevel.menus[order]
        if currentLevel.menus[keyword]
          currentLevel = currentLevel.menus[keyword]
          if currentLevel and currentLevel.type
            switch currentLevel.type
              when config.chat.selfHelpdeskReplyType.reply
                clientOrders.push keyword if keyword and currentLevel.menus
                return currentLevel.content
                break
              when config.chat.selfHelpdeskReplyType.back
                clientOrders.pop()
                return getCurrentOrderContent()
                break
              when config.chat.selfHelpdeskReplyType.connect
                connectHelpdesk()
                break
        else
          return currentLevel.content

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

      vm.closeSession = ->
        $window.close()

      vm.sendMessage = (text) ->
        vm.message = ''
        return if not text

        if vm.isSelfHelpdeskMode
          showSelfMessage text
          showSystemMessage getSelfHelpdeskReply(text)
        else
          if vm.isServed
            chatService.sendMessage(text).then((message) ->
              chatHandler(message, true)
            )
            chatService.log 'Send online message', text
            $timeout.cancel vm.leftTimer
            autoBrake()
          else
            # Save offline messages to localstorage
            chatService.log 'Send offline message', text
            messages = localStorageService.getItem config.keys.offlineMessages
            messages = [] if not messages
            message =
              avatar: config.chat.defaultAvatar
              nick: 'me'
              content:
                body: text #Only suppor text now
                msgType: 'TEXT'
              sentTime: new Date().getTime()
              isMine: false

            chatService.handleTextMessage message.content

            messages.push message
            localStorageService.setItem config.keys.offlineMessages, messages
            vm.messages.push message
            scrollToBottom()

      vm
  ]
