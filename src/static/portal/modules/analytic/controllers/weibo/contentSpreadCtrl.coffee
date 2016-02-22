define [
  'wm/app'
  'wm/config'
  'core/directives/wmCharts'
], (app, config) ->
  app.registerController 'wm.ctrl.analytic.weibo.contentSpread', [
    'restService'
    '$rootScope'
    '$scope'
    '$location'
    (restService, $rootScope, $scope, $location) ->

      vm = $scope
      rvm = $rootScope

      vm.allChannels = rvm.channels

      getStatus = ->
        param =
          channelId: vm.channelId
        restService.get config.resources.statuss, param, (data) ->
          if data
            vm.status = angular.copy data

          # backend api return empty array when weconnect return emptry data
          if not vm.status or (vm.status instanceof Array and vm.status.length is 0)
            vm.status =
              avgStatuses: 0
              originalPercentage: 0
              avgReposts: 0
              avgComments: 0


      getStatusDaily = ->
        param =
          channelId: vm.channelId
          startDate: vm.startDate
          endDate: vm.endDate

        vm.statusOptions =
          color: ['#57C6CD']
          config:
            legend:
              show: false

        vm.repostOptions =
          color: ['#57C6CD']
          config:
            legend:
              show: false

        vm.commentOptions =
          color: ['#57C6CD']
          config:
            legend:
              show: false

        contentSpreadTypes = ['status', 'repost', 'comment']
        angular.forEach contentSpreadTypes, (type) ->
          vm[type + 'Options'].startDate = moment(vm.startDate).format('YYYY-MM-DD')
          vm[type + 'Options'].endDate = moment(vm.endDate).format('YYYY-MM-DD')
          newParam = angular.copy param
          newParam.type = if type is 'status' then angular.uppercase type + 'ES' else angular.uppercase type + 'S'
          restService.get config.resources.statusDaily, newParam, (data) ->
            if data?
              vm[type + 'Options'].categories = data.statDate
              vm[type + 'Options'].series = [
                name: 'analytic_times_count'
                data: data.userCount
              ]
            return
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
        vm.startDate = moment().subtract(7, 'days').startOf('day').valueOf()
        vm.endDate = moment().subtract(1, 'days').startOf('day').valueOf()
        getStatus()
        getStatusDaily()

      vm.selectDate = ->
        today = moment().startOf('day').valueOf()

        if vm.startDate? and vm.endDate? and vm.startDate < today and vm.endDate < today and vm.startDate <= vm.endDate
          getStatusDaily()

      init()
      watchChannelInURL()

      vm
  ]
