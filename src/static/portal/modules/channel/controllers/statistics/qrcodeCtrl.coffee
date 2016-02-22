define [
  'wm/app'
  'wm/config'
], (app, config) ->
  app.registerController 'wm.ctrl.channel.statistics.qrcode', [
    'restService'
    '$stateParams'
    'notificationService'
    '$location'
    '$filter'
    '$scope'
    'exportService'
    (restService, $stateParams, notificationService, $location, $filter, $scope, exportService) ->
      vm = this
      channelId = $stateParams.id
      qrcodeId = $location.search().id
      firstLoad = true
      vm.exportQrcode = true

      vm.breadcrumb = [
        {
          text: 'promotion_qrcode',
          href: '/channel/qrcode/' + channelId
        }
        'channel_wechat_qrcode_statistics'
      ]

      vm.keyIndicator = [
        {
          text: 'channel_wechat_qrcode_new_scan'
        }
        {
          text: 'channel_wechat_qrcode_new_subscribe'
        }
        {
          text: 'channel_wechat_qrcode_cumulate_scan'
        }
        {
          text: 'channel_wechat_qrcode_cumulate_subscribe'
        }
      ]

      types = ['scan', 'subscribe', 'totalScan', 'totalSubscribe']

      vm.lineChartOptions =
        color: ['#57C6CD', '#C490BF'],
        categories: []
        series: [
          {
            name: 'channel_wechat_qrcode_scan'
            data: []
          }
          {
            name: 'channel_wechat_qrcode_subscribe'
            data: []
          }
        ]

      _getKeyIndicator = ->
        params =
          channelId: channelId
          qrcodeId: qrcodeId

        restService.get config.resources.qrcodeKeyIndicator, params, (data) ->
          for type, index in types
            vm.keyIndicator[index].value = data[type] or 0

      _getKeyIndicator()

      fetchStatistics = ->
        vm.lineChartOptions.startDate = moment(vm.startDate).format('YYYY-MM-DD')
        vm.lineChartOptions.endDate = moment(vm.endDate).format('YYYY-MM-DD')

        params =
          qrcodeId: qrcodeId
          channelId: channelId
          startDate: vm.startDate
          endDate: vm.endDate

        restService.get config.resources.qrcodestatistics, params, (data) ->
          vm.lineChartOptions.categories = data.statDate
          vm.lineChartOptions.series[0].data = data.scan
          vm.lineChartOptions.series[1].data = data.subscribe

      init = ->
        vm.startDate = parseInt(moment(moment().add(-7, 'days').format('YYYY-MM-DD')).format('X')) * 1000
        vm.endDate = parseInt(moment(moment().add(-1, 'days').format('YYYY-MM-DD')).format('X')) * 1000
        fetchStatistics()

      init()

      vm.selectDate = ->
        fetchStatistics()

      vm.exportQrcodeInfo = ->
        if not vm.exportQrcode
          return
        params =
          qrcodeId: qrcodeId
          channelId: channelId
          startDate: vm.startDate
          endDate: vm.endDate

        exportService.export 'channel-wechat-qrcode-spread-trend-chart', config.resources.exportQrcodeInfo, params, false
        vm.exportQrcode = false

      $scope.$on 'exportDataPrepared', (event, type) ->
        if type is 'channel-wechat-qrcode-spread-trend-chart'
          vm.exportQrcode = true

      vm
  ]
