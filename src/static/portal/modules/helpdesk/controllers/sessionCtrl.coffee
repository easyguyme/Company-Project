define [
  'wm/app'
  'wm/config'
  'core/directives/wmCharts'
], (app, config) ->
  app.registerController 'wm.ctrl.helpdesk.session', [
    'restService'
    '$location'
    '$scope'
    '$sce'
    '$filter'
    (restService, $location, $scope, $sce, $filter) ->
      vm = this

      vm.breadcrumb = [
        'session_management'
      ]

      origins =
        WECHAT: 'wechat'
        WEIBO: 'weibo'
        WEBSITE: 'website'
        ALIPAY: 'alipay'
        APP: 'app'

      _init = ->
        vm.isCollapsed = false
        vm.currentPage = $location.search().currentPage or 1
        vm.totalItems = 0
        vm.pageSize = $location.search().pageSize or 10
        vm.initBeginDate = parseInt(moment(moment().add(-7, 'days').format('YYYY-MM-DD')).format('X')) * 1000
        vm.initEndDate = parseInt(moment(moment().add(-1, 'days').format('YYYY-MM-DD')).format('X')) * 1000

        # five select time options: unlimited, within a month, within a week and within three days
        vm.timeOptions = [
          {
            text: 'channel_wechat_mass_unlimited'
            value: 0
          }
          {
            text: 'yesterday'
            value: moment().subtract(1, 'days').format 'YYYY-MM-DD'
          }
          {
            text: 'last_seven_days'
            value: moment().subtract(7, 'days').format 'YYYY-MM-DD'
          }
          {
            text: 'last_fifteen_days'
            value: moment().subtract(15, 'days').format 'YYYY-MM-DD'
          }
          {
            text: 'last_thirty_days'
            value: moment().subtract(30, 'days').format 'YYYY-MM-DD'
          }
        ]

        vm.overview = [
          {
              text: 'helpdesk_users_count'
          }
          {
              text: 'helpdesk_sessions_count'
          }
          {
              text: 'helpdesk_sent_message_count'
          }
        ]

        vm.tabs = [
          {
            name: 'helpdesk_session_recording'
            value: 0
          }
          {
            name: 'helpdesk_message_statistics'
            value: 1
          }
        ]

        tabVal = $location.search().active
        vm.curTab = if tabVal then vm.tabs[tabVal] else vm.tabs[0]

        vm.sessionDatas = []
        vm.sessionDetailDatas = []

        vm.list = {
          columnDefs: [
            {
              field: 'nickname'
              label: 'helpdesk_nickname'
              headClass: 'session-nickname-head'
              cellClass: 'text-el'
            }, {
              field: 'sessionTime'
              label: 'helpdesk_session_time'
              headClass: 'session-time-head'
              cellClass: 'text-el'
            }, {
              field: 'helpdesk'
              label: 'helpdesk_reception_helpdesk'
              headClass: 'session-desk-head'
              cellClass: 'text-el'
            }, {
              field: 'channel'
              label: 'helpdesk_access_account'
              type: 'iconText'
              cellClass: 'session-channel-cell'
              headClass: 'session-channel-head'
              isHideText: true
            }
          ],
          data: vm.sessionDatas
          operations: [
            {
              title: 'helpdesk_session_view'
              name: 'view'
            }
          ],
          viewHandler: (idx) ->
            vm.isCollapsed = true
            vm.viewSession = angular.copy vm.sessions[idx]
            vm.viewSession.client.avatar = if vm.viewSession.client.avatar then 'url(' + vm.viewSession.client.avatar + ')' else 'url(' + config.defaultAvatar + ')'
            vm.viewSession.desk.avatar = if vm.viewSession.desk.avatar then 'url(' + vm.viewSession.desk.avatar + ')' else 'url(' + config.defaultAvatar + ')'

            vm.detailCurrentPage = 1
            vm.detailTotalItems = 0
            vm.detailPageSize = 10

            _getHelpdeskSessionDetails()
          selectable: false
        }
        if $location.search().active? and $location.search().active is '1'
          vm.beginDate = vm.initBeginDate
          vm.endDate = vm.initEndDate
          vm.time = vm.timeOptions[2].value
          _getHelpdeskSessionsStatistics()
        else
          vm.beginDate = null
          vm.endDate = null
          vm.time = vm.timeOptions[0].value
          _getHelpdeskSessions()

      _getHelpdeskSessions = ->
        endTime = if vm.endDate? then vm.endDate else Math.round new Date().getTime()
        beginTime = if vm.beginDate? then vm.beginDate else 0
        endTime =  moment(moment(endTime).format('YYYY-MM-DD'), 'YYYY-MM-DD').valueOf() + (24 * 60 * 60 - 1) * 1000
        condition = {
          'per-page': vm.pageSize
          'page': vm.currentPage
          'orderBy': {'lastChatTime': 'desc'}
          'startTime': beginTime
          'endTime': endTime
        }
        restService.get config.resources.sessions, condition, (data) ->
          vm.sessionDatas.length = 0
          if data.items
            for item in data.items
              item.desk.badge = item.desk.badge or '-'
              item.desk.email = item.desk.email or '-'
              session =
                'nickname': item.client.nick
                'helpdesk': "#{item.desk.badge} #{item.desk.email}"
                'sessionTime': item.lastChatTime

              switch item.client.source
                when origins.WECHAT
                  serviceAccounts = ['service_auth_account', 'service_account']
                  typeName = item.client.channelInfo?.type.toLowerCase() or ''
                  if $.inArray(typeName, serviceAccounts) > -1
                    icon = 'wechat_service'
                    name = 'follower_wechat_service_number'
                  else
                    icon = 'wechat_subscription'
                    name = 'follower_wechat_subscription_number'
                when origins.WEIBO, origins.WEBSITE, origins.APP, origins.ALIPAY
                  name = icon = item.client.source

              session.channel =
                icon: "/images/customer/#{icon}.png"
                text: name
              vm.sessionDatas.push session
          vm.list.data = vm.sessionDatas
          vm.totalItems = data._meta.totalCount
          vm.pageSize = data._meta.perPage
          vm.pageCount = data._meta.pageCount
          vm.sessions = data.items

        return

      _getHelpdeskSessionsStatistics = ->
        condition = {}
        condition.startTime = vm.beginDate if vm.beginDate
        if vm.endDate
          condition.endTime = moment(moment(vm.endDate ).format('YYYY-MM-DD'), 'YYYY-MM-DD').valueOf() + (24 * 60 * 60 - 1) * 1000
        restService.get config.resources.sessionStatistics, condition, (data) ->
          if data
            if data.statistics and data.statistics.series.length > 0
              vm.overview = []
              angular.forEach data.statistics.series, (item) ->
                count = 0
                switch item.name
                  when 'helpdesk_users_count' then count = data.clientCount
                  when 'helpdesk_sent_message_count' then count = data.clientMessageCount
                  when 'helpdesk_sessions_count' then count = data.conversationCount
                vm.overview.push {
                  text: item.name
                  value: count
                }
                item.name = $filter('translate')(item.name)

              data.statistics.color = ['#7ECEF4', '#C490BF', '#57C6CD']
              length = data.statistics.categories.length
              if condition.endTime and condition.startTime
                data.statistics.startDate = moment(condition.startTime).format('YYYY-MM-DD')
                data.statistics.endDate = moment(condition.endTime).format('YYYY-MM-DD')
              else if length > 0
                data.statistics.startDate = data.statistics.categories[0]
                data.statistics.endDate = data.statistics.categories[length - 1]

              vm.lineChartOptions = data.statistics

      _getHelpdeskSessionDetails = ->
        condition =
          'sessionId': vm.viewSession.id
          'per-page': vm.detailPageSize
          'page': vm.detailCurrentPage
          'orderBy': {'sentTime': 'asc'}
        restService.get config.resources.messages, condition, (data) ->
          vm.sessionDetailDatas = []
          if data.items
            for item in data.items
              if item.content and item.content.msgType is 'TEXT'
                item.content.body = $sce.trustAsHtml item.content.body
              vm.sessionDetailDatas.push item
          vm.detailTotalItems = data._meta.totalCount
          vm.detailPageSize = data._meta.perPage
          vm.detailPageCount = data._meta.pageCount
        return

      _init()

      vm.changeTime = (val, idx) ->
        vm.timeText = vm.timeOptions[idx].text
        vm.time = val
        if val isnt 0
          vm.beginDate = moment(val, 'YYYY-MM-DD').valueOf()
          vm.endDate = moment().subtract(1, 'days').valueOf()
        else
          vm.beginDate = null
          vm.endDate = null

        if vm.curTab.value is 0
          _getHelpdeskSessions()
        else
          _getHelpdeskSessionsStatistics()

      vm.selectDate = ->
        if vm.curTab.value is 0
          _getHelpdeskSessions()
          vm.list.emptyMessage = 'search_no_data'
        else
          _getHelpdeskSessionsStatistics()

      vm.changTab = ->
        vm.list.emptyMessage = 'no_data'
        if vm.curTab.value is 0
          vm.time = vm.timeOptions[0].value
          vm.beginDate = null
          vm.endDate = null
          _getHelpdeskSessions()
        else
          vm.time = vm.timeOptions[2].value
          vm.beginDate = vm.initBeginDate
          vm.endDate = vm.initEndDate
          _getHelpdeskSessionsStatistics()

      vm.changePage = (currentPage) ->
        vm.currentPage = currentPage
        _getHelpdeskSessions()

      vm.changeSize = (pageSize) ->
        vm.pageSize = pageSize
        vm.currentPage = 1
        _getHelpdeskSessions()

      vm.detailChangePage = (currentPage) ->
        vm.detailCurrentPage = currentPage
        _getHelpdeskSessionDetails()

      vm.detailChangeSize = (pageSize) ->
        vm.detailPageSize = pageSize
        vm.detailCurrentPage = 1
        _getHelpdeskSessionDetails()

      vm.closeDetailPanel = ->
        vm.isCollapsed = not vm.isCollapsed
        delete vm.viewSession
        vm.sessionDetailDatas?.length = 0
        vm.detailPageCount = 0
      vm
  ]
