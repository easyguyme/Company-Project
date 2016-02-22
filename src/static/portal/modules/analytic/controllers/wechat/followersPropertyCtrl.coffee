define [
  'wm/app'
  'wm/config'
  'core/directives/wmMapChart'
  'core/directives/wmCharts'
], (app, config) ->
  app.registerController 'wm.ctrl.analytic.wechat.followersProperty', [
    '$rootScope'
    '$scope'
    'restService'
    '$location'
    ($rootScope, $scope, restService, $location) ->
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

      # Map province distribution
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
            itemData =
              text: data.value
              value: data.value
            vm.items.push itemData
          seriesObject =
            name: 'analytic_user_count_unit'
            data: mapOptionData
          vm.mapOptions.series.push seriesObject

      #  Location select.
      getLocationOption = ->
        condition =
          channelId: vm.channelId
          property: 'city'
          'per-page': vm.pageSizeCity
          page: vm.currentPageCity
        condition.parentProvince = vm.itemSelect if vm.itemSelect isnt '全国'
        restService.get config.resources.followersLocation, condition, (data) ->
          vm.dataListCity = []
          angular.forEach data.items, (data) ->
            locationItem =
              value: data.value
              userCount: data.count
            vm.dataListCity.push locationItem
          vm.totalItemsCity = data._meta.totalCount


      #  Location distribution.
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
          vm.maxUserCountCity = vm.maxUserCount

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
        vm.mapOptions = {}
        vm.channelId = $location.search().channel if $location.search().channel?
        vm.yesterday = moment().subtract(1, 'days').format('YYYY-MM-DD')

        vm.header = ['distribution_province', 'distribution_amount']
        vm.currentPage = 1
        vm.pageSize = 8

        vm.items = [
          {
            text: 'analytic_whole_nation'
            value: '全国'
          }
        ]
        vm.itemSelect = vm.items[0].value

        vm.headerCity = ['distribution_city', 'distribution_amount']
        vm.currentPageCity = 1
        vm.pageSizeCity = 8

        getGender()
        getMap()
        getLocation()
        getLocationOption()

      vm.changeSelect = (value, index) ->
        vm.currentPageCity = 1
        vm.pageSizeCity = 8
        vm.itemSelect = value
        getLocationOption()

      vm.getDataCity = (currentPage) ->
        vm.currentPageCity = currentPage
        getLocationOption()

      vm.getData = (currentPage) ->
        vm.currentPage = currentPage
        getLocation()

      init()
      watchChannelInURL()
  ]
