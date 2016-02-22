define [
  'wm/app'
  'wm/config'
], (app, config) ->
  app.registerController 'wm.ctrl.uhkklp.download.activity', [
    'restService'
    '$stateParams'
    '$scope'
    '$location'
    'exportService'
    (restService, $stateParams, $scope, $location, exportService) ->
      vm = this

      _init = ->
        #pagination
        vm.pageSize = 10
        vm.currentPage = 1

        #breadcrum
        title = 'activity_export_record'
        vm.breadcrumb = [
          {
            text: 'uhkklp_activity'
            href: '/uhkklp/activity'
          }
          title
        ]

        #table
        vm.list =
          columnDefs: [
            {
              field: 'createdAt'
              label: 'activity_report_list_create_at'
            }
            {
              field: 'deviceId'
              label: 'Device ID'
            }
            {
              field: 'prizeContent'
              label: 'activity_report_list_prize'
            }
            {
              field: 'mobile'
              label: 'activity_report_list_mobile'
            }
          ]
          data: []
        _getActivityName()
        _getList()
        return

      _getList = ->
        params =
          pageSize: vm.pageSize
          currentPage: vm.currentPage
          activityId: $stateParams.id

        restService.get config.resources.activityUserList, params, (data) ->
          if data
            vm.list.data = data.list
            vm.totalCount = data.count
          return
        return

      _getActivityName = ->
        restService.get config.resources.getActivityName + '/' + $stateParams.id, (data) ->
          if data
            vm.activityName = data.activityName
          return
        return

      vm.changePage = (currentPage) ->
        vm.currentPage = currentPage
        _getList()

      vm.changeSize = (pageSize) ->
        vm.pageSize = pageSize
        vm.currentPage = 1
        _getList()

      vm.exportPrizeStatistic = ->
        $("#prizeStatisticBtn").attr "disabled",true
        params =
          activityId: $stateParams.id
        exportService.export 'activity_prize_statistic', config.resources.exportPrizeStatistic, params, false
        return

      vm.exportUserPlayCount = ->
        $("#userPlayCountBtn").attr "disabled",true
        params =
          activityId: $stateParams.id
        exportService.export 'export_user_play_count', config.resources.exportUserPlayCount, params, false
        return

      vm.exportBarUseRecord = ->
        $("#barUseRecordBtn").attr "disabled",true
        params =
          activityId: $stateParams.id
        exportService.export 'export_bar_use_record', config.resources.exportBarUseRecord, params, false
        return

      _init()
      vm
  ]
