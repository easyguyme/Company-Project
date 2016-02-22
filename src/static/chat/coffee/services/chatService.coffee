define [
  'core/coreModule'
  'chat/config'
  'moment'
  'titleNoty'
], (mod, config, moment, titleNoty) ->

  JOIN_ACTION = config.chat.action.join
  TRANSFER_ACTION = config.chat.action.transfer
  LEAVE_ACTION = config.chat.action.leave
  BEFORETRANSFER_ACTION = config.chat.action.beforeTransfer
  tuisongbaoMessageIdBegin = 2 # due to a bug in tuisongbao, the first messageId of a conversation is 2

  document.addEventListener "visibilitychange", ->
    if not document.hidden
      titleNoty.set(0)
  , false

  mod.factory 'chatService', [
    '$q'
    '$location'
    'restService'
    'localStorageService'
    '$sce'
    '$timeout'
    '$filter'
    ($q, $location, restService, localStorageService, $sce, $timeout, $filter) ->
      chat = {}

      chat.isOnDuty = (setting) ->
        now = moment()
        format = 'HH:mm'
        onDutyMoment = moment(setting.ondutyTime, format)
        offDutyMoment = moment(setting.offdutyTime, format)
        return now.isAfter(onDutyMoment) and now.isBefore(offDutyMoment)

      chat.generateOpenId = (cid) ->
        S4 = ->
          (((1 + Math.random()) * 0x10000) | 0).toString(16).substring 1
        S4() + S4() + S4() + '-' + cid

      chat.initChat = (type, id) ->
        @log "Init options with type #{type} and id #{id}"
        options =
          authEndpoint: config.chat.url.checkAuth
          authData: type + ':' + id
          basePath: config.chat.jsBasePath
          chat:
            #enableCache: true
            enableMediaMessage: true

        @engine = new Engine config.chat.appId, options
        @chatManager = @engine.chatManager
        @chatManager.login()

      chat.dismissChat = ->
        @log "Dismiss chat, logout the chatManager"
        @chatManager.logout()

      chat.initGlobalChannel = (handlers) ->
        @globalChannel = @engine.channels.subscribe(config.chat.globalChannelName + @cid)
        @globalChannel.bind('engine:subscription_succeeded', ->
          chat.log('Subscribe global channel succeessfully')
          chat.globalChannel.bindOnce('force_offline', (data) ->
            chat.log 'Forced offline', data
            if data.id is chat.helpdeskId
              chat.engine.channels.unsubscribe config.chat.globalChannelName + chat.cid
              handlers['offline'](data) if handlers['offline']
          )
        ).bind('engine:subscription_error', (err) ->
          chat.log('Fail to subscribe global channel', err)
        )

      chat.clientName = (openId) ->
        'guest-' + openId.slice(0, openId.lastIndexOf('-'))

      chat.bindChatHandler = (target, chatHandler) ->
        conversation = @conversations[target]
        if conversation
          conversation.bind('message:new', (message) ->
            chatHandler(message)
          )

      chat.bindHelpdeskHandlers = (handlers, defered) ->
        chat.conversations = {}
        @chatManager.bind('login:succeeded', ->
          chat.log 'Helpdesk logined'
          chat.initGlobalChannel(handlers)
          defered.resolve()
        )

        @chatManager.bind('login:failed', (err) ->
          chat.log 'Helpdesk fail to login', err
        )

        @chatManager.bind('message:new', (message) ->
          # Check if it is a notify message or plain message
          chat.log 'Get message', message
          extra = message.content.extra
          if extra
            action = extra.action
            # Handle join case
            if action is JOIN_ACTION and extra.client
              openId = extra.client.openId
              chat.chatManager.conversations.loadOne(
                type: 'singleChat'
                target: openId
              ).then((conversation) ->
                chat.conversations[openId] = conversation
              )
              handlers[JOIN_ACTION](message)
            # Handle transfer in case
            else if action is TRANSFER_ACTION
              openId = message.content.extra.client.openId
              chat.loadConversation(openId).then((conversation) ->
                chat.conversations[openId] = conversation
                handlers[TRANSFER_ACTION](message)
              )
            # Handle client leave case
            else if action is LEAVE_ACTION
              chat.loadConversation(message.from).then (conversation) ->
                handlers[LEAVE_ACTION](message)
            else
              handlers[action](message) if action and handlers[action]


          ###
          switch message.extra.action
            when JOIN_ACTION then joinedHandler(message)
            when 'offline' then offlineHandler(data) if offlineHandler #force offline
            when 'transfer' then joinedHandler(message)
            when 'chat' then chatHandler(message)
          ###
        )

      chat.bindClientHandlers = (handlers) ->
        @chatManager.bind('login:succeeded', ->
          chat.log "Client #{chat.openId} logined"
          params =
            id: chat.openId
            cid: chat.cid
          # Just inform the server
          restService.put config.chat.url.clientOnline, params, (data) ->
            chat.log 'Inform online state succeessfully'
            # TODO: load original chat messages
            if data.status is 'failed'
              handlers['pending']()
            else
              deskId = data.originalDeskId
              if deskId
                chat.loadConversation(deskId).then((conversation) ->
                  chat.conversation = conversation
                  handlers[JOIN_ACTION]()
                )
        )

        @chatManager.bind('login:failed', (err) ->
          chat.log 'Client fail to login', err
        )

        @chatManager.bind('message:new', (message) ->
          # Check if it is a notify message or plain message
          chat.log 'Got message', message
          extra = message.content.extra
          if extra
            action = extra.action
            # Handle join case
            helpdeskId = extra.helpdeskId # or extra.helpdesk.id #TODO: remove the 'extra.helpdeskId or ' the helpdesk in join will change from a string to an object
            if action is JOIN_ACTION and helpdeskId
              chat.loadConversation(helpdeskId).then((conversation) ->
                chat.conversation = conversation
                handlers[JOIN_ACTION](message)
              )
            # Handle transfer case
            else if action is TRANSFER_ACTION
              chat.leaveConversation() # leave the conversation of previous helpdesk
              chat.loadConversation(extra.targetHelpdesk.id).then (conversation) ->
                chat.conversation = conversation
            else if action is LEAVE_ACTION
              chat.conversation.delete()
              handlers[action](message)
            else
              handlers[action](message) if action and handlers[action]
        )

      chat.loadConversation = (target) ->
        defered = $q.defer()
        @chatManager.conversations.loadOne(
          type: 'singleChat'
          target: target
        ).then((conversation) ->
          defered.resolve(conversation)
        )
        defered.promise

      chat.initHelpdesk = (helpdesk, handlers) ->
        defered = $q.defer()
        @cid = helpdesk.accountId
        @helpdeskId = helpdesk.id
        chat.initChat('h', @helpdeskId)
        chat.getSettings().then((setting) ->
          chat.replyMap = chat.getReplyMap(setting.systemReplies)
          # Check whether helpdesk is on duty
          if chat.isOnDuty(setting)
            chat.bindHelpdeskHandlers(handlers, defered)
          else
            defered.reject()
        )
        defered.promise

      chat.initClient = (handlers) ->
        defered = $q.defer()
        params = $location.search()
        @cid = params.cid
        @openId = params.id
        if not @openId
          @openId = @generateOpenId(@cid)
          # $location.search('id', @openId)
        chat.initChat('c', @openId)
        chat.getSettings().then((setting) ->
          replies = setting.systemReplies
          chat.replyMap = chat.getReplyMap(replies)
          # Check whether helpdesk is on duty
          if chat.isOnDuty(setting)
            chat.bindClientHandlers(handlers)
            defered.resolve()
          else
            defered.reject()
        )
        defered.promise

      # Init chatting
      chat.getSettings = ->
        defered = $q.defer()
        params = {cid: @cid}
        restService.get config.chat.url.systemSetting, params, (setting) ->
          systemReplies = setting.systemReplies
          # Init global configuration with setting
          chat.maxWaitTime = setting.maxWaitTime
          chat.chatChannels = [] if not chat.chatChannels
          defered.resolve(setting)
          return
        , ->
          defered.reject()
          return
        defered.promise

      # Get the current conversation list for helpdesk
      chat.getConversations = ->
        defered = $q.defer()
        data = []
        @chatManager.conversations.load().then((conversations) ->
          chat.log conversations
          # Generate conversation map: target -> conversation object
          map = {}
          for conversation in conversations
            map[conversation.target] = conversation
          chat.conversations = map
          for item in conversations
            # Only pass back need data for conversation rendering
            data.push(
              target: item.target
              startTime: item.lastActiveAt
            )
          defered.resolve(data)

          if $location.search().dd
            for conversation in conversations
              conversation.delete().then(->
                  console.log('成功删除会话')
              ).catch((err) ->
                  console.log('操作失败，请稍后再试')
              )
        ).catch((err) ->
          chat.log 'Fail to get conversations'
          defered.reject()
        )
        defered.promise

      # Get the conversation history
      chat.getHistoryMessage = (target, isTransferedClient) ->
        defered = $q.defer()
        conversation = @conversations[target]
        if conversation
          #TODO: Skip current chat session
          conversation.loadMessages(
            type: 'singleChat'
            target: target
            #startMessageId
            #endMessageId
            limit: 60
          ).then((messages) ->
            defered.resolve(messages)
          ).catch((err) ->
            defered.reject()
          )
        else
          defered.reject()
        defered.promise

      # use recursion to get previous chat message (one chat per use)
      chat.getPreviousChatMessages = (clientId, lastMessageIdOfPreviousChat) ->
        defered = $q.defer()
        limit = 100
        conversation = chat.conversations[clientId]
        previousChatMessages = []
        if conversation
          conversation.loadMessages(
            type: 'singleChat'
            startMessageId: lastMessageIdOfPreviousChat if lastMessageIdOfPreviousChat
            limit: limit
          ).then((messages) ->
            getFirst = false # set to true when get the 'join' or 'transfer' action
            for message in messages
              previousChatMessages.push message
              action = message.content.extra.action
              if action is JOIN_ACTION or action is TRANSFER_ACTION
                getFirst = true # find the previous join message, all the chat history found
                break
            if getFirst
              defered.resolve(previousChatMessages)
            else
              lastMessageIdOfPreviousChat = parseInt(messages[0].messageId) - 1 if not lastMessageIdOfPreviousChat
              chat.getPreviousChatMessages(clientId, lastMessageIdOfPreviousChat - limit).then (chatMessages) ->
                previousChatMessages = previousChatMessages.concat chatMessages
                defered.resolve(previousChatMessages)
          )
        defered.promise

      # transfer only include graphic id messages to the graphic content structure
      chat.parseGraphics = (messages) ->
        outerDeferred = $q.defer()
        self = this
        if angular.isArray(messages) and messages.length
          promises = []

          for message in messages
            ( (item) ->
              deferred = $q.defer()

              if item.content.msgType is 'NEWS' and typeof item.content.body is 'string'
                restService.get config.resources.viewGraphic + '/' + item.content.body, (data) ->
                  if data
                    # remove create at when display graphic message
                    delete data.createdAt if data.createdAt
                    item.content.body = data
                    deferred.resolve(item)
              else
                item.content.body = self.handleTextMessage item.content.body
                deferred.resolve(item)
              # Collect promises
              promises.push(deferred.promise)
            )(message)

          # Wait for all the promises are resolved
          $q.all(promises).then (contents) ->
            outerDeferred.resolve(contents)
          , (rejects) ->
            outerDeferred.reject(rejects)
          , (notifiation) ->
            outerDeferred.notify(notifiation)
        else
          messages = []
          outerDeferred.resolve(messages)

        outerDeferred.promise

      # Generate the content structure base on message structure
      chat.getContent = (message, isGraphic) ->
        # Default type is text
        content =
          msgType: 'TEXT'
          body: message
        # Change content structure base on message
        if isGraphic and message
          content =
            msgType: 'NEWS'
            body: message
        content

      chat.wrapLink = (body) ->
        replacedBody = body
        url = /(ftp|http|https):\/\/([\w-]+\.)+(\w+)(:[0-9]+)?(\/(\w)*)*(\/|([\w#!:.?+=&%@!\-\/]+)?|\/([\w#!:.?+=&%@!\-\/]+))?/
        result = body.match url
        if result
          match = result[0]
          replacedBody = '<a target="_BLANK" href="' + match + '">' + match + '</a>'
          replacedBody = body.replace url, replacedBody
        replacedBody

      # Handle the content only contains graphic id
      chat.handleGraphicMessage = (content) ->
        defered = $q.defer()
        if content.msgType is 'NEWS' and typeof content.body is 'string'
          restService.get config.resources.viewGraphic + '/' + content.body, (data) ->
            if data
              # remove create at when display graphic message
              delete data.createdAt if data.createdAt
              content.body = data
              defered.resolve(content)
        else
          defered.resolve(content)

        defered.promise

      # Handle the content contains newline
      chat.handleTextMessage = (content) ->
        if content.msgType is 'TEXT'
          if typeof content.body is 'string'
            replacedBody = @encodeHtml content.body
            replacedBody = this.wrapLink replacedBody
          else if typeof content.body.toString is 'function'
            replacedBody = content.body.toString()
          content.body = $sce.trustAsHtml(replacedBody)
        content

      # check the certain customer is online or not
      chat.isOnline = (customer) ->
        defered = $q.defer()
        target = customer.target
        chat.loadConversation(target).then (conversation) ->
          extra = conversation?.lastMessage?.content.extra
          if extra?.action is LEAVE_ACTION
            defered.resolve(false)
          else
            defered.resolve(true)
        defered.promise

      # Send chat message
      chat.sendMessage = (message, customer, helpdesk) ->
        defered = $q.defer()
        target = customer?.target
        conversation = @conversation
        conversation = @conversations[target] if target
        if conversation
          extra =
            action: 'chat'
          # append nick, avatar, isHelpdesk, accountId, source for use in Helpdesk -> Conversations
          extra.nick = if helpdesk?.name then helpdesk.name else chat.clientName(@openId)
          extra.avatar = if helpdesk?.avatar then helpdesk.avatar else config.chat.defaultAvatar
          extra.isHelpdesk = if target then true else false
          extra.accountId = @cid
          extra.source = 'website' # define the user type "alipay", "wechat", "website"
          extra.targetChannel = if customer?.extra?.client?.channelId then customer.extra.client.channelId else ""
          # Handle articles, only send article id and type in extra data
          if angular.isObject(message) and message.id
            extra.type = 'article'
            extra.id = message.id
            message = message.id
          # TODO: Support other type message
          params =
            type: 'text'
            text: message
            extra: extra
          conversation.sendMessage(params).then((message) ->
            chat.log 'Recieve response after sending chat message', message
            defered.resolve(message)
          ).catch((err) ->
            chat.log 'Fail to send chat message'
          )
        else
          chat.log "Invalid target id: #{target}"
          defered.reject()
        defered.promise

      chat.getReplyMap = (replies) ->
        @replyMap = {} if not @replyMap
        if replies
          for reply in replies
            @replyMap[reply.type] = reply.replyText if reply.isEnabled
        @replyMap

      chat.unbindEvent = (eventName) ->
        @globalChannel.unbind(eventName)

      chat.endAllChats = ->
        endAll = $q.defer()
        @chatManager.conversations.load().then (conversations) ->
          promises = []
          angular.forEach conversations, (conversation, index) ->
            # mock the customer object
            extraCache =
              target: conversation.target
              extra:
                client:
                  opendId: conversation.target
            defered = $q.defer()
            promises.push defered.promise
            chat.endChat(extraCache).then ->
              defered.resolve()
          $q.all(promises).then(->
            endAll.resolve()
          ).catch((err) ->
            defered.reject()
          )
        endAll.promise

      chat.endChat = (customer) ->
        leaveMessage = $q.defer()
        leaveConversation = $q.defer()
        if customer.isOnline
          promises = [leaveMessage.promise, leaveConversation.promise]
          chat.sendLeaveMessage(customer).then ->
            chat.leaveConversation(customer).then ->
              leaveMessage.resolve()
              leaveConversation.resolve()
        else
          promises = [leaveConversation.promise]
          chat.leaveConversation(customer).then ->
            leaveConversation.resolve()
        $q.all(promises)

      chat.transferChat = (customer) ->
        defered = $q.defer()
        chat.sendBeforeTransferMessage(customer).then ->
          chat.leaveConversation(customer).then ->
            defered.resolve()
        defered.promise

      chat.leaveConversation = (customer) ->
        defered = $q.defer()
        target = null
        target = customer.target if customer
        conversation = @conversation
        conversation = @conversations[target] if target
        conversation.delete().then( (message) ->
          chat.log 'Delete conversation', message
          defered.resolve()
        ).catch((err) ->
          chat.log 'Fail to delete conversation', err
          defered.reject()
        )
        defered.promise

      chat.sendLeaveMessage = (customer) ->
        defered = $q.defer()
        target = customer?.target
        conversation = @conversation
        conversation = @conversations[target] if target
        message =
          type: 'text'
          text: ''
          extra:
            action: LEAVE_ACTION
            client: customer.extra.client
            isHelpdesk: !! target
            accountId: @cid
            targetChannel: if customer?.extra?.client?.channelId then customer.extra.client.channelId else ""
        conversation.sendMessage(
          message
        ).then((message) ->
          chat.log 'Recieve response after sending leave message', message
          defered.resolve(message)
        ).catch((err) ->
          chat.log 'Fail to send leave message', err
          defered.reject()
        )
        defered.promise

      chat.sendBeforeTransferMessage = (customer) ->
        defered = $q.defer()
        target = customer?.target
        conversation = @conversations[target] if target
        if conversation
          message =
            type: 'text'
            text: ''
            extra:
              action: BEFORETRANSFER_ACTION
              accountId: @cid
              isHelpdesk: !! target
          conversation.sendMessage(message).then((message) ->
            chat.log 'Recieve response after sending beforeTransfer message', message
            defered.resolve(message)
          ).catch((err) ->
            defered.reject()
          )
        else
          defered.reject()
        defered.promise

      chat.showNotification = (notificationType, message, clickCallback) ->
        if notificationType is config.chat.notificationType.desktopAndMark
          if window.Notification
            if Notification.permission is 'granted'
              notification = new Notification(message.title, {body: message.body, icon: message.icon})
              notification.onshow = ->
                $timeout ->
                  notification.close()
                , 3000
              notification.onclick = ->
                if $.isFunction clickCallback
                  clickCallback()
                window.focus()
              titleNoty.add() if document.hidden
            else
              Notification.requestPermission()
          else
            alert $filter('translate')('helpdesk_browser_not_support_notification')
        else if notificationType is config.chat.notificationType.mark
          titleNoty.add() if document.hidden

      chat.getSelfHelpdeskSetting = ->
        defered = $q.defer()
        restService.get config.chat.url.selfHelpdeskSetting, {cid: $location.search().cid}, (data) ->
          defered.resolve(data)
        defered.promise

      chat.encodeHtml = (string) ->
        if string
          reg = /\<|\>|\"|\'/g
          string = string.replace reg, (matchStr) ->
            switch matchStr
              when '<'
                return '&lt;'
              when '>'
                return '&gt;'
              when '\"'
                return '&quot;'
              when '\''
                return '&#39;'
          string = string.replace /\n/g, '<br>'
        return string

      chat.initAudio = (src, uniqueId) ->
        uniqueId = Math.random() if not uniqueId?
        $('body').append '<audio id="' + uniqueId + '" preload="true" class="ng-hide">
                            <source src="' + src + '.mp3" type="audio/mpeg"/>
                            <source src="' + src + '.ogg" type="audio/ogg" />
                          </audio>'
        audio = $("##{uniqueId}")
        return {
          play: ->
            audio.get(0).play()
          delete: ->
            audio.remove()
        }

      chat.log = ->
        console.log '--------------start-------------------'
        console.log.apply console, arguments if console
        console.log '---------------end--------------------'

      chat
  ]
