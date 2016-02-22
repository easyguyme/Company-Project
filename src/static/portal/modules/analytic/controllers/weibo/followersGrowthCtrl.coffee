define [
  'wm/app'
  'wm/config'
  'core/directives/wmCharts'
], (app, config) ->
  app.registerController 'wm.ctrl.analytic.weibo.followersGrowth', [
    '$rootScope'
    '$scope'
    '$location'
    'restService'
    ($rootScope, $scope, $location, restService) ->
      vm = $scope
      rvm = $rootScope

      dateTypes = ['Day', 'Week', 'Month']
      followersGrowthTypes = ['newUser', 'cancelUser', 'netUser', 'cumulateUser']
      followersGrowthTypeTitles =
        newUser: 'analytic_new_attention'
        cancelUser: 'analytic_cancel_attention'
        netUser: 'analytic_net_attention'
        cumulateUser: 'analytic_cumulate_attention'

      followersGrowthTypeTabsText =
        cumulate: 'analytic_total_fans'
        net: 'analytic_fans_growth'
      followersGrowthTypeTabsValue =
        cumulate: 'CUMULATE'
        net: 'NET'

      vm.allChannels = rvm.channels

      _getFollowersGrowthYesterday = ->
        condition =
          channelId: vm.channelId
        restService.get config.resources.followersGrowthYesterday, condition, (data) ->
          if not data or data.length is 0
            data =
              'newUser': 0,
              'cancelUser': 0,
              'netUser': 0,
              'cumulateUser': 0,
              'newUserDay': 'NaN',
              'newUserWeek': 'NaN',
              'newUserMonth': 'NaN',
              'cancelUserDay': 'NaN',
              'cancelUserWeek': 'NaN',
              'cancelUserMonth': 'NaN',
              'netUserDay': 'NaN',
              'netUserWeek': 'NaN',
              'netUserMonth': 'NaN',
              'cumulateUserDay': 'NaN',
              'cumulateUserWeek': 'NaN',
              'cumulateUserMonth': 'NaN'
          indexItem = {}
          vm.overviewList = []
          angular.forEach followersGrowthTypes, (type) ->
            indexItem.title = followersGrowthTypeTitles[type]
            indexItem.value = data[type]
            indexItem.statistics = []
            angular.forEach dateTypes, (dateType) ->
              statisticItem =
                type: angular.lowercase dateType
                growth: data[type + dateType]
              indexItem.statistics.push angular.copy statisticItem
            vm.overviewList.push angular.copy indexItem

      _getFollowersGrowthStatistic = ->
        if not vm.startDate or not vm.endDate
          return

        startDate = vm.startDate
        endDate = vm.endDate
        type = vm.curTab.value
        tabName = vm.curTab.name

        condition =
          channelId: vm.channelId
          startDate: startDate
          endDate: endDate
          type: type
        restService.get config.resources.followersGrowthStatistic, condition, (data) ->
          if data
            vm.lineChartOptions =
              color: ['#57C6CD']
              categories: data['statDate']
              series: [{name: tabName, data: data['userCount']}]
              startDate: moment(startDate).format('YYYY-MM-DD')
              endDate: moment(endDate).format('YYYY-MM-DD')
              config:
                legend:
                  show: false

      _init = ->

        vm.startDate = moment().subtract(7, 'days').startOf('day').valueOf()
        vm.endDate = moment().subtract(1, 'days').startOf('day').valueOf()

        # yesterday statistics
        vm.channelId = $location.search().channel if $location.search().channel?

        _getFollowersGrowthYesterday()

        # tabs
        vm.tabs = []
        for key, value of followersGrowthTypeTabsValue
          tab =
            name: followersGrowthTypeTabsText[key]
            value: value
          vm.tabs.push angular.copy tab

        vm.curTab = angular.extend vm.tabs[0], {active: true}

        _getFollowersGrowthStatistic()

      isWeiboType = (currentChannelId) ->
        result = false
        if vm.allChannels
          angular.forEach vm.allChannels, (channel) ->
            if channel.id is currentChannelId and channel.type is 'weibo'
              result = true
        result

      watchChannelInURL = ->
        vm.location = $location
        vm.$watch 'location.search().channel', (newVal, oldVal) ->
          if newVal isnt oldVal and isWeiboType(newVal)
            _init()

      vm.changeTab = ->
        _getFollowersGrowthStatistic()

      vm.selectDate = ->
        _getFollowersGrowthStatistic()

      _init()
      watchChannelInURL()

      vm
  ]
