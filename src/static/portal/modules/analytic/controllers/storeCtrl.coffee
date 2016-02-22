define [
  'wm/app'
  'wm/config'
  'core/directives/wmCharts'
], (app, config) ->
  app.registerController 'wm.ctrl.analytic.store', [
    'restService'
    (restService) ->
      vm = this

      # Init wechat data.
      vm.statDate = ''
      vm.scanNum = 0
      vm.newNum = 0
      vm.followerNum = 0
      vm.unfollowerNum = 0
      vm.storeId = ''
      vm.isStore = true

      vm.wechat =
        scanNum: 0
        followerNum: 0
      vm.weibo =
        scanNum: 0
        followerNum: 0

      vm.breadcrumb = [
        'analytic_store'
      ]

      # Get statistic data.
      getStatistic = (storeId) ->
        params =
          storeId: storeId
          startDate: vm.startDate
          endDate: vm.endDate
        restService.get config.resources.storeStatistic, params, (data) ->
          vm.wechat.scanNum = data.wechat.scanNumber
          vm.wechat.followerNum = data.wechat.followNumber
          vm.weibo.scanNum = data.weibo.scanNumber
          vm.weibo.followerNum = data.weibo.followNumber
        return

      # Iterates data.
      changeStatistic = (lineChartOptions, data) ->
        lineChartOptions.categories = data.statDate
        lineChartOptions.series[0].data = data.scanNumber
        lineChartOptions.series[1].data = data.followNumber
        lineChartOptions.startDate = moment(vm.startDate).format('YYYY-MM-DD')
        lineChartOptions.endDate = moment(vm.endDate).format('YYYY-MM-DD')
        return

      # Get wechat data and weibo data.
      getWechatAndWeiboStatistic = (storeId) ->
        if not vm.startDate or not vm.endDate
          return

        param =
          storeId: storeId
          startDate: vm.startDate
          endDate: vm.endDate
        restService.get config.resources.storeChart, param, (data) ->
          if data
            vm.wechatLineChartOptions = {
               color: ['#99CCFF','#57C6CD']
               categories: []
               series: [{
                   name: 'analytic_scan_number'
                   data: []
               }
               {
                    name: 'analytic_followers'
                    data: []
               }]
               startDate: vm.startDate
               endDate: vm.endDate
            }

            # Init weibo data.
            vm.weiboLineChartOptions = {
              color: ['#99CCFF', '#57C6CD']
              categories: []
              series: [{
                  name: 'analytic_scan_number'
                  data: []
              }
              {
                  name: 'analytic_followers'
                  data: []
              }]
              startDate: vm.startDate
              endDate: vm.endDate
            }
            changeStatistic(vm.wechatLineChartOptions, data.wechat)
            changeStatistic(vm.weiboLineChartOptions, data.weibo)
          return

      _init = ->
        vm.startDate = moment().subtract(7, 'days').startOf('day').valueOf()
        vm.endDate = moment().subtract(1, 'days').startOf('day').valueOf()
      _init()

      vm.getStoreInfo = (storeId) ->
        vm.storeId = storeId
        if not storeId
          return

        vm.isStore = false
        getStatistic(storeId)
        getWechatAndWeiboStatistic(storeId)
        return

      vm.electDate = ->
        if not vm.storeId
          return

        getWechatAndWeiboStatistic(vm.storeId)
        return
      vm
  ]
