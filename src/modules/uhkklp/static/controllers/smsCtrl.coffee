# define [
#   'wm/app'
#   'wm/config'
#   './edit/importNumberCtrl'
# ], (app, config) ->
#   app.registerController 'wm.ctrl.uhkklp.sms', [
#     'restService'
#     '$stateParams'
#     '$scope'
#     '$filter'
#     '$location'
#     'notificationService'
#     'localStorageService'
#     'validateService'
#     '$modal'
#     '$http'
#     'exportService'
#     '$interval'
#     (restService, $stateParams, $scope, $filter, $location, notificationService, localStorageService, validateService, $modal, $http, exportService, $interval) ->
#       vm = this

#       #read file record count
#       $scope.totalCount = -1
#       $scope.isShowTotal = false
#       $scope.modelContent = "【UFS】%param1%你好，你已成功重設密碼。使用家樂牌「儲分有賞」APP，毋須擔心忘記密碼，請即下載%param2%"
#       $scope.modelGroupId = -1
#       $scope.sortDesc = 'DESC'
#       $scope.isImport = false
#       $scope.tmpModelContent = ''
#       $scope.haveSentCount = 0
#       $scope.totalToSend = 0
#       $scope.smsBatch = -1
#       $scope.wordCount = $scope.modelContent.length
#       $scope.smsCount = Math.ceil($scope.wordCount / 70)

#       calculateVariableCount = ->
#         if $scope.modelContent?
#           count = $scope.modelContent.match(/%param\d*%/g)
#         else
#           count = []
#         if count?
#           return count.length
#         return 0

#       wordMonitor = ->
#         $scope.$watch('modelContent', (newVal) ->
#           if $scope.modelContent?
#             $scope.wordCount = $scope.modelContent.length
#           else
#             $scope.wordCount = 0
#           $scope.variableCount = calculateVariableCount()
#           $scope.smsCount = Math.ceil($scope.wordCount / 70)
#         )

#       getTemplate = ->
#         restService.get '/api/uhkklp/excel-reader/get-template', null, (data) ->
#           if data.model?
#             $scope.modelContent = data.model.modelContent

#       $scope.save = ->
#         params = $.param
#           modelContent : $scope.modelContent
#         restService.post '/api/uhkklp/excel-reader/save-model', params, (data) ->
#           if data.result is 'success'
#             notificationService.success 'model_save_success_tip', false
#           else
#             notificationService.success 'model_save_fail_tip', false

#       $scope.import = ->
#         modalInstance = $modal.open(
#           templateUrl: '/build/modules/uhkklp/partials/edit/importNumber.html'
#           controller: 'wm.ctrl.uhkklp.edit.importNumber'
#           windowClass: 'setting-dialog'
#           resolve:
#             content : ()->
#                           modelGroupId : $scope.modelGroupId
#                           contents : $scope.modelContent
#         ).result.then( (data) ->
#           if data.result is 'success'
#             $scope.totalCount = data.record
#             $scope.modelGroupId = data.modelGroupId
#             $scope.tmpModelContent = $scope.modelContent
#             $scope.isImport = true
#             $scope.isShowTotal = true
#         )

#       $scope.exportModel = ->
#         params =
#           modelGroupId : $scope.modelGroupId
#         if $scope.modelGroupId != -1
#           exportService.export 'sms_export_model', '/api/uhkklp/sms/export-sms-model', params, false
#           return

#       $scope.send = ->
#         if not $scope.isImport
#           notificationService.warning 'sms_import_tip', false
#           return
#         $scope.haveSentCount = 0
#         $scope.totalToSend = 0
#         params = $.param
#           groupId : $scope.modelGroupId
#           modelContent : $scope.tmpModelContent
#         $("#smsSend").attr "disabled", true
#         $("#smsSend").val($filter('translate')('earlybird_btn_sending'))
#         restService.post '/api/uhkklp/sms/send-sms', params, (data) ->
#           if data.result is 'success'
#             $scope.totalToSend = data.count
#             $scope.smsBatch = data.smsBatch
#             timer = $interval(()->
#               $http
#                 method: 'POST'
#                 url: '/api/uhkklp/sms/query-sms-schedule'
#                 data: $.param smsBatch: $scope.smsBatch
#                 headers:
#                   'Content-Type': 'application/x-www-form-urlencoded'
#               .success (data) ->
#                 $scope.haveSentCount = data.haveSent
#                 if $scope.haveSentCount is $scope.totalToSend
#                   $interval.cancel(timer)
#                   notificationService.success 'sms_success_tip', false
#                   $("#smsSend").attr "disabled", false
#                   $("#smsSend").val($filter('translate')('earlybird_btn_send'))
#                   _getList()
#               .error (data) ->
#                 notificationService.error 'sms_query_have_sent_failed_tip', false
#             , 1000)
#         return

#       _init = ->
#         wordMonitor()
#         getTemplate()
#         $scope.variableCount = calculateVariableCount()

#         #pagination
#         $scope.pageSize = 10
#         $scope.currentPage = 1

#         #table
#         vm.list =
#           columnDefs: [
#             {
#               field: '_id'
#               label: 'sms_id'
#               sortable: true
#             }
#             {
#               field: 'modelContent'
#               label: 'sms_modelContent'
#             }
#             {
#               field: 'createdAt'
#               label: 'sms_date'
#             }
#             {
#               field: 'totalRecord'
#               label: 'sms_total_record'
#             }
#             {
#               field: 'successRecord'
#               label: 'sms_success_record'
#             }
#             {
#               field: 'failureRecord'
#               label: 'sms_failure_record'
#             }
#             {
#               field: 'operations'
#               label: 'operations'
#               type: 'operation'
#             }
#           ]
#           data: []
#           exportHandler: (item) ->
#             params =
#               _id : vm.list.data[item]._id
#             exportService.export 'sms_export_send_record', '/api/uhkklp/sms/export-sms-result', params, false
#             return
#           sortHandler: (colDef) ->
#               if colDef.desc
#                 $scope.sortDesc = 'ASC'
#               else
#                 $scope.sortDesc = 'DESC'
#               _getList()
#           deleteHandler: (item) ->
#             $http
#               method: 'POST'
#               url: '/api/uhkklp/sms/delete-sms-result'
#               data: $.param _id: vm.list.data[item]._id
#               headers:
#                 'Content-Type': 'application/x-www-form-urlencoded'
#             .success (data) ->
#               if data['result'] is "success"
#                 notificationService.success 'sms_list_delete_success_tip', false
#                 _getList()
#               else
#                 notificationService.error 'sms_list_delete_failed_tip', false
#             .error (data) ->
#               notificationService.error 'sms_list_delete_failed_tip', false

#         _getList()
#         return

#       $scope.changePage = (currentPage) ->
#         $scope.currentPage = currentPage
#         _getList()

#       $scope.changeSize = (pageSize) ->
#         $scope.pageSize = pageSize
#         $scope.currentPage = 1
#         _getList()

#       _getList = ->
#         params =
#           pageSize: $scope.pageSize
#           currentPage: $scope.currentPage
#           sortDesc: $scope.sortDesc
#         restService.get '/api/uhkklp/sms/get-result-list', params, (data) ->
#           # console.log '....' + data.totalPageCount
#           $scope.totalPageCount = data.totalPageCount
#           (
#             data.list[i]._id = item._id
#             data.list[i].modelContent = item.modelContent
#             data.list[i].createdAt = item.createdAt
#             data.list[i].totalRecord = item.totalRecord
#             data.list[i].successRecord = item.successRecord
#             data.list[i].failureRecord = item.failureRecord
#             data.list[i].operations = [
#               {
#                 name: 'export'
#                 title: 'sms_export_send_record'
#               }
#               {
#                 name: 'delete'
#                 title: 'sms_delete_send_record'
#               }
#             ]
#           ) for item, i in data.list
#           vm.list.data = data.list
#           return

#       _init()

#       vm

#   ]

define [
  'wm/app'
  'wm/config'
], (app, config) ->
  app.registerController 'wm.ctrl.uhkklp.sms', [
    'restService'
    '$stateParams'
    '$scope'
    '$filter'
    '$location'
    'notificationService'
    'localStorageService'
    'validateService'
    '$modal'
    '$http'
    'exportService'
    '$interval'
    '$rootScope'
    (restService, $stateParams, $scope, $filter, $location, notificationService, localStorageService, validateService, $modal, $http, exportService, $interval, $rootScope) ->
      vm = this

      vm.breadcrumb = [
        'uhkklp_sms'
      ]

      _init = ->
        vm.tabs = [
          {
            name: 'uhkklp_sms'
            value: 0
          }
        ]
        tabVal = $location.search().active
        vm.curTab = if tabVal then vm.tabs[tabVal] else vm.tabs[0]
        _checkTab()

        #pagination
        $scope.pageSize = 10
        $scope.currentPage = 1

        #table
        vm.list =
          columnDefs: [
            {
              field: '_id'
              label: 'sms_id'
              sortable: true
            }
            {
              field: 'modelContent'
              label: 'sms_modelContent'
            }
            {
              field: 'createdAt'
              label: 'sms_date'
            }
            {
              field: 'sendTime'
              label: 'sms_send_time'
            }
            {
              field: 'isSend'
              label: 'sms_is_send'
            }
            {
              field: 'operations'
              label: 'operations'
              type: 'operation'
            }
          ]
          data: []
          editHandler: (item) ->
            $rootScope.uhkklp_sms_condition = {
              currentPage: $scope.currentPage
              pageSize: $scope.pageSize
              sort: $scope.sortDesc
            }
            $location.url '/uhkklp/edit/sms?id=' + vm.list.data[item]._id + '&active=' + vm.curTab.value
          exportHandler: (item) ->
            if vm.list.data[item].isSend is '否'
              notificationService.error 'sms_export_no_data', false
              return
            params =
              _id : vm.list.data[item]._id
            exportService.export 'sms_export_send_record', '/api/uhkklp/sms/export-sms-result', params, false
            return
          moreHandler : (item) ->
            params =
              _id : vm.list.data[item]._id
            exportService.export 'sms_export_model', '/api/uhkklp/sms/export-sms-template', params, false
            return
          sortHandler: (colDef) ->
              if colDef.desc
                $scope.sortDesc = 'ASC'
              else
                $scope.sortDesc = 'DESC'
              _getList()
          deleteHandler: (item) ->
            $http
              method: 'POST'
              url: '/api/uhkklp/sms/delete-sms-record'
              data: $.param _id: vm.list.data[item]._id
              headers:
                'Content-Type': 'application/x-www-form-urlencoded'
            .success (data) ->
              if data['result'] is "success"
                notificationService.success 'sms_list_delete_success_tip', false
                _getList()
              else
                notificationService.error 'sms_list_delete_failed_tip', false
            .error (data) ->
              notificationService.error 'sms_list_delete_failed_tip', false

        _getList()
        return

      vm.changeTab = ->
        _checkTab()
        return

      _checkTab = ->
        if vm.curTab.value is 0
          vm.showSMS = true
        return

      $scope.changePage = (currentPage) ->
        $scope.currentPage = currentPage
        _getList()

      $scope.changeSize = (pageSize) ->
        $scope.pageSize = pageSize
        $scope.currentPage = 1
        _getList()

      _getList = ->
        if $rootScope.uhkklp_sms_condition
          $scope.currentPage = $rootScope.uhkklp_sms_condition.currentPage
          $scope.pageSize = $rootScope.uhkklp_sms_condition.pageSize
          $scope.sortDesc = $rootScope.uhkklp_sms_condition.sort
        params =
          pageSize: $scope.pageSize
          currentPage: $scope.currentPage
          sortDesc: $scope.sortDesc
        restService.get '/api/uhkklp/sms/get-sms-result-list', params, (data) ->
          # console.log '....' + data.totalPageCount
          $scope.totalPageCount = data.totalPageCount
          (
            data.list[i]._id = item._id
            data.list[i].modelContent = item.modelContent
            data.list[i].createdAt = item.createdAt
            data.list[i].sendTime = item.sendTime
            if item.isSend is true
              data.list[i].isSend = '是'
            else
              data.list[i].isSend = '否'
            data.list[i].operations = [
              {
                name: 'edit'
                title: "sms_edit"
              }
              {
                name: 'delete'
                title: 'sms_delete_send_record'
              }
              {
                name: 'export'
                title: 'sms_export_send_record'
              }
              {
                name: 'more'
                title: 'sms_export_model'
              }
            ]
            if item.isSend is '否'
              data.list[i].operations[2].disable = true
            else
              data.list[i].operations[2].disable = false
          ) for item, i in data.list
          vm.list.data = data.list
        return

      _init()
      _getList

      vm
  ]
