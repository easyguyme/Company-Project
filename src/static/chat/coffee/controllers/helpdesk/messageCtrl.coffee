define [
  'core/coreModule'
  'chat/config'
], (app, config) ->
  app.controller 'wm.ctrl.helpdesk.tabs.message', [
    '$scope'
    'restService'
    'chatService'
    ($scope, restService, chatService) ->
      vm = $scope
      vm.showDropdown = false
      vm.hasHistoryMessage = false
      vm.historyConversations = []

      vm.selectConversation = (index) ->
        vm.currentConversation = vm.historyConversations[index]
        _getMessages vm.currentConversation if vm.currentConversation
        vm.showDropdown = false

      _getHistoryConversations = (openId) ->
        params =
          openId: openId
        restService.get config.resources.getChatSessions, params, (result) ->
          # skip the situation that result.items has only one item (means no historyConversations)
          if result.items?.length > 1
            _assembleHistoryConversations(result.items)
            vm.currentConversation = vm.historyConversations[0]
            _getMessages(vm.currentConversation)
            vm.hasHistoryMessage = true
          else
            vm.hasHistoryMessage = false

      _assembleHistoryConversations = (chatSessions) ->
        for chatSession, index in chatSessions
          if index > 0 # skip the first chatSession, the current chatSession
            item = chatSession
            conv = # the conversation in array vm.historyConversations
              startTime: item.createdAt
              conversationId: item.conversationId
              startMessageId: item.startMessageId # the smaller id
              endMessageId: item.endMessageId # the larger id
              helpdesk:
                nick: item.desk.name
              client:
                type: if item.client.channelInfo then item.client.channelInfo.type.toLowerCase() else item.client.source
                name: if item.client.channelInfo then item.client.channelInfo.name else config.chat.sourceName
                nick: item.client.nick
            vm.historyConversations.push conv

      _getMessages = (conversation) ->
        params =
          conversationId: conversation.conversationId
          # I know it's weird, but in tuisongbao's open api, the startMessageId means the larger Id,
          # and the endMessageId means the smaller id, if you want to get message from 20 to 40, the
          # startMessageId should be 40, the endMessageId should be 20
          startMessageId: conversation.endMessageId
          endMessageId: conversation.startMessageId
        restService.get config.resources.getMessages, params, (messages) ->
          vm.currentMessages = _assembleChatMessage(messages, conversation)

      _assembleChatMessage = (messages, conversation) ->
        chatMessages = []
        for message in messages
          if message.content.extra.action is 'chat'
            extra = message.content.extra
            item = conversation
            mes =
              isHelpdesk: extra.isHelpdesk
              content: chatService.getContent message.content.text, extra.type is 'article'
              startTime: message.createdAt
              nick: if extra.isHelpdesk then item.helpdesk.nick else item.client.nick
            chatService.handleGraphicMessage(mes.content).then (content) ->
              mes.content = content
            chatMessages.push mes
        chatMessages

      vm.$on 'getConversations', (event, openId) ->
        vm.historyConversations = [] # in order not to show the previous customer's history conversations
        vm.showDropdown = false # to dismiss the dropdown box when select another customer
        _getHistoryConversations(openId)

      vm.$emit 'needOpenId'

      vm
  ]
