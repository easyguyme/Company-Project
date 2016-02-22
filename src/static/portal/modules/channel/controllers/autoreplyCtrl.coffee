define [
  'wm/app'
  'wm/config'
  'core/directives/wmCharts'
], (app, config) ->
  app.registerController 'wm.ctrl.channel.autoreply', [
    'restService'
    '$stateParams'
    '$location'
    'notificationService'
    '$scope'
    '$modal'
    (restService, $stateParams, $location, notificationService, $scope, $modal) ->
      vm = this

      vm.wechatTableKeywords = []
      vm.wechatKeywords = []

      vm.wechatTableDefaultRules = []
      vm.wechatDefaultRules = []

      vm.remainCharacter = 0

      vm.breadcrumb = [
        'channel_wechat_autoreply',
      ]

      vm.items =
      [
          {
              text: 'channel_wechat_complete_match'
              value: false
          }
          {
              text: 'channel_wechat_contain'
              value: true
          }
      ]
      vm.fuzzy = vm.items[0].value
      vm.status = true
      vm.tabs =
        [
          {
            active: true
            name: 'channel_wechat_keyword_rule'
          }
          {
            active: false
            name: 'channel_wechat_default_rule'
          }
        ]
      vm.curTab = vm.tabs[0]

      vm.currentPage = $location.search().currentPage or 1
      vm.totalItems = 0
      vm.pageSize = $location.search().pageSize or 10

      formateString = (str) ->
        newStr = str
        if str.length > 15
          newStr = str.substr(0, 15) + '...'
        else if str.length = 0
          newStr = 'channel_wechat_undefined_title'
        newStr

      translateToTableParam = (data) ->
        vm.wechatTableKeywords.length = 0
        vm.showTable = data.items.length > 0
        vm.showAddNew = not vm.showTable
        for item, i in data.items
          vm.wechatTableKeywords[i] = {}
          vm.wechatTableKeywords[i].id = item.id
          vm.wechatTableKeywords[i].name = item.name
          vm.wechatTableKeywords[i].keycode = item.keycodes[0]
          if item.msgType is 'TEXT'
            vm.wechatTableKeywords[i].replyMessage =
              type: item.msgType
              content: formateString(item.content)
          else if item.msgType is 'NEWS'
            vm.wechatTableKeywords[i].replyMessage =
              type: item.msgType
              content: formateString(item.content.articles[0].title)
          vm.wechatTableKeywords[i].hitCount = item.hitCount
          vm.wechatTableKeywords[i].status = item.status
        vm.wechatKeywords = data

      translateToTableDefaultRule = (data) ->
        vm.wechatTableDefaultRules.length = 0
        if data.items.length > 0
          for item, i in data.items
            vm.wechatTableDefaultRules[i] = {}
            if item.type is 'SUBSCRIBE'
              vm.wechatTableDefaultRules[i].name = 'channel_wechat_subscribe'
            else if item.type is 'RESUBSCRIBE'
              vm.wechatTableDefaultRules[i].name = 'channel_wechat_resubscribe'
            else if item.type is 'DEFAULT'
              vm.wechatTableDefaultRules[i].name = 'channel_wechat_default'
            if item.msgType is 'TEXT'
              vm.wechatTableDefaultRules[i].replyMessage =
                type: item.msgType
                content: formateString(item.content)
            else if item.msgType is 'NEWS'
              vm.wechatTableDefaultRules[i].replyMessage =
                type: item.msgType
                content: formateString(item.content.articles[0].title)
            vm.wechatTableDefaultRules[i].hitCount = item.hitCount
            vm.wechatTableDefaultRules[i].status = item.status
        vm.wechatDefaultRules = data

      _init = ->
        active = $location.search().active
        if typeof(active) is 'undefined' or active is '0'
          getList()
        else if active is '1'
          getDefaultRuleList()

      vm.changeTab = ->
        if vm.tabs[0].active is true
          getList()
        else
          getDefaultRuleList()

      getList = ->
        params =
          'channelId': $stateParams.id
          'per-page': vm.pageSize
          'page': vm.currentPage
        restService.get config.resources.keywords, params, (data) ->
          if data
            translateToTableParam angular.copy data
            vm.totalItems = data._meta.totalCount

          return

      getDefaultRuleList = ->
        restService.get config.resources.defaultrules, 'channelId': $stateParams.id, (data) ->
          translateToTableDefaultRule angular.copy data
          return

      vm.changePage = (currentPage) ->
        vm.currentPage = currentPage
        getList()
      vm.changePageSize = (pageSize) ->
        vm.currentPage = 1
        vm.pageSize = pageSize
        getList()

      _init()

      vm.addNewKeyword = ->
        $location.url '/channel/edit/autoreply/' + $stateParams.id

      vm.list =
      {
        columnDefs: [
          {
            field: 'name'
            label: 'channel_wechat_rule_name'
            cellClass: 'text-el'
          }, {
            field: 'keycode'
            label: 'channel_wechat_keycode'
            cellClass: 'text-el'
          }, {
            field: 'replyMessage'
            label: 'channel_wechat_reply_message'
            type: 'label'
            cellClass: 'reply-col'
          }, {
            field: 'hitCount'
            label: 'channel_wechat_hit_count'
          }, {
            field: 'status'
            label: 'channel_wechat_status'
            type: 'status'
          }
        ],
        data: vm.wechatTableKeywords
        operations: [
          {
            name: 'statistics'
          }, {
            name: 'edit'
          }, {
            name: 'delete',
            deleteTitle: 'Are you sure to delete this?'
          }
        ],
        editHandler: (idx) ->
          $location.url '/channel/edit/autoreply/' + $stateParams.id + '?id=' + vm.wechatKeywords.items[idx].id

        deleteHandler: (idx) ->
          restService.del config.resources.keyword + '/' + vm.wechatKeywords.items[idx].id, 'channelId': $stateParams.id, (data) ->
            getList()
            notificationService.success 'channel_wechat_keyword_delete_success'
            return

        statisticsHandler: (idx) ->
          if vm.list.data?[idx]
            modalInstance = $modal.open(
              templateUrl: 'statistics.html'
              controller: 'wm.ctrl.channel.autoreply.statistics'
              size: 'lg'
              resolve:
                modalData: ->
                  vm.list.data[idx]
            )
          return

        switchHandler: (idx) ->
          data =
            {
              'channelId': $stateParams.id
              'keywordId': vm.wechatKeywords.items[idx].id
            }
          if vm.wechatTableKeywords[idx].status is 'ENABLE'
            restService.post config.resources.keyword + '/disable', data, (data) ->
                vm.wechatKeywords.items[idx].status = 'DISABLE'
                notificationService.success 'channel_wechat_keyword_status_update_success'
          else
            restService.post config.resources.keyword + '/enable', data, (data) ->
                vm.wechatKeywords.items[idx].status = 'ENABLE'
                notificationService.success 'channel_wechat_keyword_status_update_success'

        selectable: false
        deleteTitle: 'channel_wechat_keyword_delete_confirm'
      }

      vm.showDefaultRuleList = true
      vm.backToDefaultRuleList = ->
        vm.showDefaultRuleList = true

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

      _checkFiled = ->
        _removeErrorTip()
        if not vm.defaultRuleReplyMessage
          _addErrorTip()
          return false
        return true

      _checkLength = ->
        if vm.remainCharacter? and vm.remainCharacter < 0
          notificationService.warning 'channel_broadcast_message_too_long', false
          return false
        return true

      vm.updateWechatDefaultRule = ->
        if _checkFiled() and _checkLength()
          if vm.defaultRuleStatus is true
            vm.wechatDefaultRule.status = 'ENABLE'
          else
            vm.wechatDefaultRule.status = 'DISABLE'
          if typeof(vm.defaultRuleReplyMessage) is 'string'
            vm.wechatDefaultRule.msgType = 'TEXT'
            vm.wechatDefaultRule.content = vm.defaultRuleReplyMessage
          else
            vm.wechatDefaultRule.msgType = 'NEWS'
            vm.wechatDefaultRule.content = vm.defaultRuleReplyMessage
          restService.post config.resources.defaultrule + '/init', vm.wechatDefaultRule, (data) ->
            notificationService.success 'channel_wechat_default_update_success'
            getDefaultRuleList()
            return
          vm.showDefaultRuleList = true
          return

      vm.defaultrule =
      {
        columnDefs: [
          {
            field: 'name'
            label: 'channel_wechat_rule_name'
            type: 'translate'
          }, {
            field: 'replyMessage'
            label: 'channel_wechat_reply_message'
            type: 'label'
            cellClass: 'reply-col'
          }, {
            field: 'hitCount'
            label: 'channel_wechat_hit_count'
          }, {
            field: 'status'
            label: 'channel_wechat_status'
            type: 'status'
          }
        ],
        data: vm.wechatTableDefaultRules
        operations: [
          {
            name: 'edit',
            link: ''
          }
        ],
        editHandler: (idx) ->
          vm.wechatDefaultRule = vm.wechatDefaultRules.items[idx]
          vm.wechatDefaultRule.channelId = $stateParams.id
          if vm.wechatDefaultRule.type is 'SUBSCRIBE'
            vm.defaultRuleName = 'channel_wechat_subscribe'
          else if vm.wechatDefaultRule.type is 'RESUBSCRIBE'
            vm.defaultRuleName = 'channel_wechat_resubscribe'
          else if vm.wechatDefaultRule.type is 'DEFAULT'
            vm.defaultRuleName = 'channel_wechat_default'
          if vm.wechatDefaultRule.status is 'ENABLE'
            vm.defaultRuleStatus = true
          else
            vm.defaultRuleStatus = false
          vm.defaultRuleReplyMessage = vm.wechatDefaultRule.content
          vm.showDefaultRuleList = false
          _removeErrorTip()

        switchHandler: (idx) ->
          data =
            {
              'channelId': $stateParams.id
              'type': vm.wechatDefaultRules.items[idx].type
            }
          if vm.wechatTableDefaultRules[idx].status is 'ENABLE'
            restService.post config.resources.defaultrule + '/disable', data, (data) ->
                vm.wechatDefaultRules.items[idx].status = 'DISABLE'
                notificationService.success 'channel_wechat_default_status_update_success'
          else
            restService.post config.resources.defaultrule + '/enable', data, (data) ->
                vm.wechatDefaultRules.items[idx].status = 'ENABLE'
                notificationService.success 'channel_wechat_default_status_update_success'

        selectable: false
      }
      vm
  ]
  .registerController 'wm.ctrl.channel.autoreply.statistics', [
    'restService'
    '$scope'
    '$stateParams'
    '$filter'
    '$modalInstance'
    'modalData'
    (restService, $scope, $stateParams, $filter, $modalInstance, modalData) ->
      vm = $scope
      channelId = null

      vm.selectDate = ->
        if vm.beginDate and vm.endDate
          _initData vm.beginDate, vm.endDate
        return

      _initData = (from, to) ->
        params =
          from: from
          to: to
          channelId: channelId
          keywordId: modalData.id

        restService.get config.resources.keywordstatistics, params, (data) ->
          tip = $filter('translate')('channel_wechat_mass_trigger_time')
          vm.chartData =
            categories: data.statDate,
            series: [{
               name: modalData.name
               data: data.count
            }]
            startDate: moment(parseInt(from)).format 'YYYY-MM-DD'
            endDate: moment(parseInt(to)).format 'YYYY-MM-DD'
            config:
              tooltip:
                formatter: (obj) ->
                  return obj[1] + '<br/>' + tip + ': ' + obj[2]
          return
        return

      _init = ->
        channelId = $stateParams.id unless channelId?
        vm.beginDate = moment().startOf('day').subtract(1, 'weeks').format 'x'
        vm.endDate = moment().startOf('day').subtract(1, 'days').format 'x'
        _initData vm.beginDate, vm.endDate
        return

      _init()

      vm.hideModal = ->
        $modalInstance.dismiss('cancel')
        return
      return
  ]
