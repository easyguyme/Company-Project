define [
  'wm/app'
  'wm/config'
  'core/directives/wmCharts'
], (app, config) ->
  app.registerController 'wm.ctrl.member.analytic', [
    'restService'
    '$q'
    '$filter'
    'exportService'
    '$scope'
    (restService, $q, $filter, exportService, $scope) ->
      vm = this

      order =
        portal: 1
        app_android: 2
        app_ios: 3
        app_webview: 4
        app_web: 5
        wechat: 6
        weibo: 7
        alipay: 8
        others: 9

      _getSignupStats = ->
        params =
          start: vm.beginMonth
          end: vm.endMonth

        restService.get config.resources.statSignupSummary, params, (data) ->
          if data and data.date
            if angular.isArray(data.date) and data.date.length > 0
              vm.signupOptions.categories = angular.copy data.date
              series = []
              for key, value of data.data
                item =
                  name: key.replace /:/, '_'
                  data: value
                series.push item
              series.sort (pre, next) ->
                preOrder = order[pre.name] or 10
                nextOrder = order[next.name] or 10
                return preOrder - nextOrder

              vm.signupOptions.series = angular.copy series

      _getShipEngagementStats = ->
        params =
          year: moment(vm.shipYear).format('YYYY')

        restService.get config.resources.statEngagement, params, (data) ->
          if data and data.date
            if angular.isArray(data.date) and data.date.length > 0
              data.date = data.date.map (item) ->
                item.toLowerCase()
              vm.shipOptions.categories = angular.copy data.date
              vm.shipOptions.series = [{
                name: 'member_active'
                data: data.data
              }]

      _getTrackingStats = ->
        params =
          year: vm.quarterDate.year
          quarter: vm.quarterDate.quarter

        restService.get config.resources.statAcctTracking, params, (data) ->
          vm.acctOptions.series = []
          vm.activeOptions.series = []

          if data and (data.totalActive or data.totalInactive)
            vm.acctOptions.series = [
              {value: data.totalActive, name: 'member_active'}
              {value: data.totalInactive, name: 'member_inactive'}
            ]

            # Fix bug: the unexcept case for data.totalActive < data.totalNew, 0 replace
            vm.activeOptions.series = [
              {value: Math.min(data.totalActive, data.totalNew), name: 'member_new'}
              {value: Math.max(data.totalActive - data.totalNew, 0), name: 'member_current'}
            ]

      vm.selectDate = (type) ->
        switch type
          when 'signup' # Change member signup summary statistics data
            _getSignupStats()
          when 'ship' # Change member ship engagement statistics data
            _getShipEngagementStats()

      vm.selectQuarter = ->
        _getTrackingStats()

      vm.exportSignup = ->
        vm['signup'] = true
        params =
          start: vm.beginMonth
          end: vm.endMonth
        exportService.export 'member-signup-summary', config.resources.exportMemberSignup, params, false

      vm.exportAcct = ->
        vm['acct'] = true
        params =
          year: vm.quarterDate.year
          quarter: vm.quarterDate.quarter
        exportService.export 'member-acct-tracking', config.resources.exportAcct, params, false

      vm.exportEngagement = ->
        vm['engagement'] = true
        params =
          year: moment(vm.shipYear).format('YYYY')
        exportService.export 'member-ship-engagement', config.resources.exportEngagement, params, false

      $scope.$on 'exportDataPrepared', (event, type) ->
        switch type
          when 'member-signup-summary'
            vm['signup'] = false
          when 'member-acct-tracking'
            vm['acct'] = false
          when 'member-ship-engagement'
            vm['engagement'] = false

      _init = ->
        vm.shipYear = moment().valueOf()

        vm.endMonth = moment().valueOf()
        vm.beginMonth = moment().subtract(5, 'months').valueOf()

        vm.quarterDate =
          year: parseInt moment().format('YYYY')
          quarter: Math.ceil(parseInt(moment().format('MM')) / 3)

        vm.breadcrumb = [
          {
            text: 'member_analytic'
            icon: 'statmember'
          }
        ]

        vm.acctOptions = {
          color: ["#88C6FF", "#19BE9B"]
          title: 'member_acct_tracking'
          type: "inner"
          series: []
        }

        vm.activeOptions = {
          color: ["#4DE3C8", "#37C3AA"]
          title: 'member_acct_tracking'
          totalTitle: 'member_active',
          series: []
        }

        vm.signupOptions = {
          color: ["#688fdd", "#76a2dc", "#4bb1f1", "#5dc8f2", "#85e5e6", "#85E6D3", "#6CEEB5", "#55F387", "#73F020", "#A5F020", "#C6F020"]
          categories: []
          stack: true
          type: 'percent'
          series: []
          config:
            legend:
              x: 'right'
              padding: [10, 75, 15, 5]
        }

        vm.shipOptions = {
          color: ["#88C6FF"]
          categories: [],
          series: []
        }

        _getTrackingStats()

      _init()
      vm
  ]
