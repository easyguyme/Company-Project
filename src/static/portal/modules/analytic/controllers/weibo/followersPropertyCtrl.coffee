define [
  'wm/app'
  'wm/config'
  'core/directives/wmMapChart'
  'core/directives/wmCharts'
], (app, config) ->
  app.registerController 'wm.ctrl.analytic.weibo.followersProperty', [
    '$rootScope'
    '$scope'
    '$location'
    'restService'
    ($rootScope, $scope, $location, restService) ->
      vm = $scope
      rvm = $rootScope

      vm.allChannels = rvm.channels

      # Gender distribution.
      getGender = ->
        vm.hbarChartOptions = {
          color: ['#98CFF5', '#FFA7A0', '#FDD17A']
          categories: [vm.yesterday]
          series: [{
              name: 'analytic_male'
              data: [0]
          },
          {
              name: 'analytic_female'
              data: [0]
          },
          {
              name: 'analytic_unknown'
              data: [0]
          }],
          showTwoYAxis: true
        }
        genderMap =
          MALE: 0
          FEMALE: 1
          UNKNOWN: 2
        conditions =
          channelId: vm.channelId
          property: 'gender'
        restService.get config.resources.followersProperty, conditions, (data) ->
          if data.items?
            for item in data.items
              vm.hbarChartOptions.series[genderMap[item.value]].data[0] = item.count
            return
          return

      # Location distribution.
      getLocation = ->
        condition =
          channelId: vm.channelId
          property: 'province'
          'per-page': vm.pageSize
          page: vm.currentPage
        restService.get config.resources.followersLocation, condition, (data) ->
          vm.dataList = []
          angular.forEach data.items, (data) ->
            locationItem =
              value: data.value
              userCount: data.count
            vm.dataList.push locationItem
          vm.totalItems = data._meta.totalCount
          vm.maxUserCount = data.items[0].count if vm.currentPage is 1 and data.items.length > 0
          vm.mapOptions.max = vm.maxUserCount

      getMap = ->
        condition =
          channelId: vm.channelId
          property: 'province'
        restService.get config.resources.followersLocation, condition, (data) ->
          vm.mapOptions.series = []
          mapOptionData = []
          angular.forEach data.items, (data) ->
            mapData =
              name: data.value
              value: data.count
            mapOptionData.push mapData
          seriesObject =
            name: 'analytic_user_count_unit'
            data: mapOptionData
          vm.mapOptions.series.push seriesObject

      getNumber = ->
        # Followers number
        vm.lineChartOptions = {
          color: ['#57C6CD']
          categories: ['<20', '20-99', '100-499', '500-1999', '2000-4999', '5000-9999', '10000-49999', '>=50000']
          series: [{
              name: 'analytic_fan_distribute_number'
              data: []
          }]
          config:
            legend:
              show: false
            yAxis: [{
              type: 'value'
              axisLine:
                show: false
              axisTick:
                show: false
              axisLabel:
                formatter: (value) ->
                  value + '%'
            }]
        }
        conditions =
          channelId: vm.channelId
          property: 'userFansCountDist'
        restService.get config.resources.followersProperty, conditions, (data) ->
          lineData =
            '<20': 0
            '20-99': 1
            '100-499': 2
            '500-1999': 3
            '2000-4999': 4
            '5000-9999': 5
            '10000-49999': 6
            '>=50000': 7
          if data.items?
            vm.lineChartOptions.series[0].data.length = 8
            for item in data.items
              vm.lineChartOptions.series[0].data[lineData[item.value]] = item.count
            return
          return

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
            init()

      init = ->
        vm.channelId = $location.search().channel if $location.search().channel?
        vm.yesterday  = moment().subtract(1, 'days').format('YYYY-MM-DD')

        vm.header = ['distribution_province', 'distribution_amount']
        vm.currentPage = 1
        vm.pageSize = 8

        vm.mapOptions = {}

        getGender()
        getMap()
        getLocation()
        getNumber()

      vm.getData = (currentPage) ->
        vm.currentPage = currentPage
        getLocation()

      init()
      watchChannelInURL()

      vm
]
