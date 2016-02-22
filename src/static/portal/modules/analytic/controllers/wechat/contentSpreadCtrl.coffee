define [
  'wm/app'
  'wm/config'
  'core/directives/wmCharts'
], (app, config) ->
  app.registerController 'wm.ctrl.analytic.wechat.contentSpread', [
    '$rootScope'
    '$scope'
    '$modal'
    '$location'
    'restService'
    ($rootScope, $scope, $modal, $location, restService) ->

      vm = $scope
      rvm = $rootScope

      dateTypes = ['Day', 'Week', 'Month']
      contentSpreadTypes = ['intPageRead', 'oriPageRead', 'share', 'addToFav']
      contentSpreadTypeTitles =
        intPageRead: 'analytic_int_page_read_number'
        oriPageRead: 'analytic_ori_page_read_number'
        share: 'analytic_share_forward_number'
        addToFav: 'analytic_wechat_favor_number'
      tabTypeTitles =
        intPageRead: 'analytic_int_page_read'
        oriPageRead: 'analytic_ori_page_read'
        share: 'analytic_share_foward'
        addToFav: 'analytic_wechat_favor'
      contentSpreadTypeValues =
        intPageRead: 'INT_PAGE_READ'
        oriPageRead: 'ORI_PAGE_READ'
        share: 'SHARE'
        addToFav: 'ADD_TO_FAV'

      vm.allChannels = rvm.channels

      getYesterday = ->
        param =
          channelId: vm.channelId
        restService.get config.resources.yesterday, param, (data) ->
          vm.overviewList = []
          indexItem = {}

          angular.forEach contentSpreadTypes, (type) ->
            indexItem.title = contentSpreadTypeTitles[type]
            indexItem.value = data[type + 'Count'] or 0
            indexItem.statistics = []
            angular.forEach dateTypes, (dateType) ->
              statisticItem =
                type: angular.lowercase dateType
                growth: if data[type + 'Count' + dateType + 'Growth'] isnt undefined then data[type + 'Count' + dateType + 'Growth'] else 'NaN'
              indexItem.statistics.push angular.copy statisticItem
              return
            vm.overviewList.push angular.copy indexItem
            return
          return
        return

      getInterval = ->
        param =
          channelId: vm.channelId
          startDate: vm.startDate
          endDate: vm.endDate
          type: vm.curTab.value
        if vm.curTab.value is contentSpreadTypeValues['share'] and vm.way isnt 'all'
          param.subType = vm.way
        restService.get config.resources.interval, param, (data) ->
          intervals = angular.copy data

          vm.lineChartOptions =
            color: ['#57C6CD', '#C490BF']
            startDate: moment(vm.startDate).format('YYYY-MM-DD')
            endDate: moment(vm.endDate).format('YYYY-MM-DD')

          if intervals?
            vm.lineChartOptions.categories = intervals.statDate
            vm.lineChartOptions.series = [
              {
                name: 'analytic_user_count'
                data: intervals.userNumber
              }
              {
                name: 'analytic_times_count'
                data: intervals.userCount
              }
            ]

      _displayList = ->
        vm.richTexts = []
        vm.param =
          channelId: vm.channelId
          startDate: vm.massiveStartDate
          endDate: vm.massiveEndDate
          'per-page': vm.pageSize
          page: vm.currentPage
          orderby: '{"' + vm.richType + '":' + '"' + vm.sortType + '"}'

        restService.get config.resources.massMessageList, vm.param, (data) ->
          vm.richTexts = data.items
          vm.currentPage = data._meta.currentPage
          vm.pageSize = data._meta.perPage
          vm.totalItems = data._meta.totalCount
          return

      isWechatType = (currentChannelId) ->
        result = false
        if vm.allChannels
          angular.forEach vm.allChannels, (channel) ->
            if channel.id is currentChannelId and channel.type is 'wechat'
              result = true
        result

      watchChannelInURL = ->
        vm.location = $location
        vm.$watch 'location.search().channel', (newVal, oldVal) ->
          if newVal isnt oldVal and isWechatType(newVal)
            init()

      init = ->
        vm.broadcastFlag = true
        vm.channelId = $location.search().channel

        vm.ways =
        [
            {
                text: 'analytic_all_path'
                value: 'all'
            }
            {
                text: 'analytic_friends_transpond'
                value: 'FRIENDS_TRANSPOND'
            }
            {
                text: 'analytic_friends_circle'
                value: 'FRIENDS_CIRCLE'
            }
            {
                text: 'analytic_tencent_weibo'
                value: 'TENCENT_WEIBO'
            }
            {
                text: 'analytic_other'
                value: 'OTHER'
            }
        ]
        vm.way = vm.ways[0].value

        vm.tabs = []
        for type in contentSpreadTypes
          tab =
            name: tabTypeTitles[type]
            value: contentSpreadTypeValues[type]
          vm.tabs.push angular.copy tab

        vm.curTab = vm.tabs[0]

        vm.startDate = moment().subtract(7, 'days').startOf('day').valueOf()
        vm.endDate = moment().subtract(1, 'days').startOf('day').valueOf()

        getYesterday()
        getInterval()

        #### rich text broadcast
        vm.pageSize = $location.search().pageSize or 5
        vm.currentPage = $location.search().currentPage or 1
        vm.massiveStartDate = moment().subtract(7, 'days').startOf('day').valueOf()
        vm.massiveEndDate = moment().subtract(1, 'days').startOf('day').valueOf()

        vm.richTypes = [
          {
              text: 'analytic_send_date'
              value: 'sentDate'
          }
          {
              text: 'sent_count'
              value: 'sentUser'
          }
          {
              text: 'int_page_read_count'
              value: 'intPageReadUser'
          }
          {
              text: 'ori_page_read_count'
              value: 'oriPageReadUser'
          }
          {
              text: 'share_count'
              value: 'shareUser'
          }
          {
              text: 'addto_favcount'
              value: 'addToFavUser'
          }
        ]
        vm.richType = vm.richTypes[0].value

        vm.sortTypes = [
          {
              text: 'analytic_asc'
              value: 'asc'
          }
          {
              text: 'analytic_desc'
              value: 'desc'
          }
        ]
        vm.sortType = vm.sortTypes[0].value
        _displayList()

      vm.broadcastActive = (flag) ->
        vm.broadcastFlag = if flag then true else false

      vm.changeSelect = (value, index) ->
        vm.way = value
        getInterval()

      vm.changeTab = ->
        getInterval()

      vm.changeDate = ->
        today = moment().startOf('day').valueOf()

        if vm.startDate? and vm.endDate? and vm.startDate < today and vm.endDate < today and vm.startDate <= vm.endDate
          getInterval()

      vm.selectDate = ->
        vm.currentPage = 1
        _displayList()

      vm.changeSize = (pageSize) ->
        vm.pageSize = pageSize
        vm.currentPage = 1
        _displayList()

      vm.changePage = (currentPage) ->
        vm.currentPage = currentPage
        _displayList()

      vm.changeRichType = (value, index) ->
        vm.currentPage = 1
        vm.richType = value
        _displayList()

      vm.changeSortType = (value, index) ->
        vm.currentPage = 1
        vm.sortType = value
        _displayList()

      vm.viewDetail = (processData, richText) ->
        detailData =
          processData: processData
          richText: richText
        modalInstance = $modal.open(
          templateUrl: 'richTextDetail.html'
          controller: 'wm.ctrl.analytic.wechat.richTextDetail'
          windowClass: 'contentspread-dialog'
          resolve:
            modalData: ->
              detailData
        ).result.then( (data) ->
          if data

            return
        )

      init()
      watchChannelInURL()

      vm
  ]

  app.registerController 'wm.ctrl.analytic.wechat.richTextDetail', [
    '$scope'
    'modalData'
    '$modalInstance'
    '$filter'
    ($scope, modalData, $modalInstance, $filter) ->
      vm = $scope
      vm.richText = modalData.richText
      vm.processData = modalData.processData
      # formate render data
      intPageReadCount = []
      oriPageReadCount = []
      shareCount = []
      addToFavCount = []

      for item in vm.richText.dailyStatistics
        intPageReadCount.push item.intPageReadCount
        oriPageReadCount.push item.oriPageReadCount
        shareCount.push item.shareCount
        addToFavCount.push item.addToFavCount

      vm.processData.readCount.init = intPageReadCount
      vm.processData.readCount.origin = oriPageReadCount
      vm.processData.readCount.share = shareCount
      vm.processData.readCount.favor = addToFavCount

      vm.hbarChartOptions = {
        color: ['#B3DCF7', '#C490BF']
        categories: ['', '', '', '']
        series: [{
          name: 'user_count'
          data: [vm.richText.addToFavUser + vm.richText.shareUser, vm.richText.oriPageReadUser, vm.richText.intPageReadUser, vm.richText.sentUser]
        }]
        config:
          grid:
            x: 5
            y: 30
            x2: 25
            y2: 40
        hideLegend: true
      }

      vm.richTabs = [
        {
          name: 'analytic_int_page_read'
          value: 'init'
        }
        {
          name: 'analytic_ori_page_read'
          value: 'origin'
        }
        {
          name: 'analytic_share_foward'
          value: 'share'
        }
        {
          name: 'analytic_wechat_favor'
          value: 'favor'
        }
      ]

      vm.innerStyle = true
      vm.curTab = vm.richTabs[0]

      vm.richTextOptions = {
        color: ['#57C6CD', '#C490BF']
        categories: vm.processData.categories
        series: [
          {
            name: 'analytic_user_count'
            data: vm.processData.userCount[vm.curTab.value]
          }
          {
            name: 'analytic_times_count'
            data: vm.processData.readCount[vm.curTab.value]
          }
        ]
        startDate: vm.richText.sentDate
        endDate: vm.processData.endDate
        config:
          grid:
            x: 50
            y: 50
            x2: 50
            y2: 50
      }

      vm.changeRichTab = ->
        vm.richTextOptions.categories = vm.processData.categories
        vm.richTextOptions.series[0].data = vm.processData.userCount[vm.curTab.value]
        vm.richTextOptions.series[1].data = vm.processData.readCount[vm.curTab.value]

      vm.hideModal = ->
        $modalInstance.close()
  ]
