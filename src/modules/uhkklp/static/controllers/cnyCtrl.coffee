define [
  'wm/app'
  'wm/config'
], (app, config) ->
  app.registerController 'wm.ctrl.uhkklp.cny', [
    'restService'
    '$scope'
    '$filter'
    '$modal'
    '$location'
    '$http'
    'notificationService'
    'validateService'
    'exportService'
    (restService, $scope, $filter, $modal, $location, $http, notificationService, validateService, exportService) ->
      vm = this

      _init = ->
        vm.startDate = 1451577600000 # 1/1
        vm.endDate = 1456761599000 # 2/29

        vm.breadcrumb = [
          'uhkklp_cny'
        ]
        vm.tabs = [
          {
            name: 'cny_setting'
            value: 0
          }
          {
            name: 'cny_lucky_draw_title'
            value: 1
          }
        ]
        tabVal = $location.search().active
        vm.curTab = if tabVal then vm.tabs[tabVal] else vm.tabs[0]
        _checkTab()

        #pagination
        vm.pageSize = 10
        vm.currentPage = 1
        #table
        vm.list =
          columnDefs: [
            {
              field: '_id'
              label: 'cny_lucky_draw_number'
            }
            {
              field: 'createdAt'
              label: 'cny_lucky_draw_date'
            }
            {
              field: 'operations'
              label: 'operations'
              type: 'operation'
            }
          ]
          data: []
          downloadHandler: (item) ->
            exportService.export 'cny_export_winners', config.resources.exportCnyWinners + '/' + vm.list.data[item]._id, false
            return
          recordHandler: (item) ->
            exportService.export 'cny_export_winners_sms_content', config.resources.exportCnyWinnersSmsContent + '/' + vm.list.data[item]._id, false
            return
          exportHandler: (item, $event) ->
            notificationService.confirm $event, {
              submitCallback: _sendSMS
              title: 'cny_send_sms_tip'
              params: [item]
            }
            return
          viewHandler: (item) ->
            modalInstance = $modal.open(
              templateUrl: 'showSmsDetail.html'
              controller: 'wm.ctrl.uhkklp.showCnySmsDetail'
              windowClass: 'token-dialog'
              resolve:
                modalData: -> vm.list.data[item]._id
            ).result.then( (data) ->
              if data
                console.log data
            )
            return
        _getAccountSetting()
        _getActivityInfo()
        _getList()
        _checkDrawing()
        return

      vm.drawPrize = ->
        params =
          prizes: vm.prizes
        if params.prizes.length > 0
          for key, value of params.prizes
            params.prizes[key].quantity = parseInt params.prizes[key].quantity
            if not params.prizes[key].name or params.prizes[key].name is "" or not params.prizes[key].quantity or not /^([1-9][0-9]*)$/.test params.prizes[key].quantity
              notificationService.warning 'cny_submit_draw_tip',false #請正確填寫所有抽獎設置!
              return
        else
          notificationService.warning 'cny_submit_draw_tip',false
          return
        restService.post config.resources.cnyLuckyDraw, params, (data) ->
          if data
            if data.code is 200
              vm.cnyRecordId = data.recordId
              _updateDrawProcess()
            else if data.code is 500
              notificationService.error '创建job失败',true
          return
        return

      _updateDrawProcess = ->
        $("#cnyDrawBtn").val "正在抽獎"
        $("#cnyDrawBtn").attr "disabled","disabled"
        _getDrawProcess()
        clockOne = setInterval((->
          if vm.cnyRecordId
            _getDrawProcess()
          return
        ), 20000)
        clockTwo = setInterval((->
          if vm.drawProcess is 'ok'
            _getList()
            $("#cnyDrawBtn").val "抽獎"
            $("#cnyDrawBtn").removeAttr "disabled"
            notificationService.success '抽獎完成！',true
            clearInterval clockOne
            clearInterval clockTwo
          return
        ), 20000)
        return

      _getDrawProcess = ->
        $http
          url: config.resources.getCnyDrawProcess + '/' + vm.cnyRecordId
          method: 'GET'
          headers:
            'Content-Type': 'application/x-www-form-urlencoded'
        .success (data) ->
          if data
            if data.process is 'ok'
              vm.drawProcess = 'ok'
          return
        return

      _checkDrawing = ->
        $http
          url: config.resources.checkCnyDrawing
          method: 'GET'
          headers:
            'Content-Type': 'application/x-www-form-urlencoded'
        .success (data) ->
          if data.code is 200
            vm.cnyRecordId = data.recordId
            _updateDrawProcess()
          return
        return

      vm.addPrize = ->
        prize =
          name: ''
          quantity: null
        vm.prizes.push prize
        return

      vm.deletePrize = (index, $event) ->
        notificationService.confirm $event, {
            submitCallback: _deletePrizeHandler
            title: 'cny_prize_delete_tip'
            params: [index]
          }
        return

      _deletePrizeHandler = (index) ->
        vm.prizes.splice parseInt(index),1
        $scope.$apply()
        notificationService.success 'cny_remove_award_success_tip',false #成功移除該獎項！
        return

      _sendSMS = (item) ->
        restService.get config.resources.sendCnyWinnersSms + '/' + vm.list.data[item]._id, (data) ->
          if data
            vm.list.data[item].operations = [
              {
                name: 'download'
                title: 'download'
              }
              {
                name: 'export'
                title: 'cny_sendsms'
                disable: true
              }
              {
                name: 'view'
                title: 'cny_view_sms_detail'
              }
            ]
            notificationService.success 'cny_sms_sending_tip',false #系統正在發送簡訊...
          return
        return

      vm.changePage = (currentPage) ->
        vm.currentPage = currentPage
        _getList()

      vm.changeSize = (pageSize) ->
        vm.pageSize = pageSize
        vm.currentPage = 1
        _getList()

      vm.changeTab = ->
        _checkTab()
        return

      _checkTab = ->
        if vm.curTab.value is 0
          vm.showActivity = true
          vm.luckyDraw = false
        else
          vm.luckyDraw = true
          vm.showActivity = false
        return

      _getList = ->
        params =
          pageSize: vm.pageSize
          currentPage: vm.currentPage
        restService.get config.resources.getCnyWinnersList, params, (data) ->
          vm.totalCount = data.count
          (
            data.drawRecords[i]._id = drawRecord._id
            data.drawRecords[i].createdAt = drawRecord.createdAt
            data.drawRecords[i].operations = [
              {
                name: 'download'
                title: 'cny_download_draw_record'
              }
              # A button about exporting SMS content before sending
              # {
              #   name: 'record'
              #   title: 'cny_sms_content_export'
              # }
              # CNY SMS button
              # {
              #   name: 'export'
              #   title: 'cny_sendsms'
              # }
            ]
            if data.drawRecords[i].remark.sentSMS
              data.drawRecords[i].operations[1].disable = true
              data.drawRecords[i].operations[2] =
                name: 'view'
                title: 'cny_view_sms_detail'
          ) for drawRecord, i in data.drawRecords
          vm.list.data = data.drawRecords
          return
        return

      _getAccountSetting = ->
        restService.get config.resources.cnyGetAccountSetting, (data) ->
          if data.site is 'HK'
            vm.hkSetting = true
            vm.prizes = [
              {
                name: ''
                quantity: null
              }
            ]
          else if data.site is 'TW'
            vm.twSetting = true
            vm.prizes = [
              {
                name: '100元紅包'
                quantity: 100
              }
              {
                name: '4人份餐劵'
                quantity: 2
              }
            ]
          else
            notificationService.error '还没有设置accountSetting',true
          return
        return

      vm.resetActivity = ->
        vm.startDate = null
        vm.endDate = null
        vm.needPoints = null
        vm.drawDate = null
        return

      vm.updateActivity = ->
        params = _getActivityParams()
        if not params
          notificationService.warning 'cny_commit_tip',false  #請正確填寫所有必填項!
          return
        restService.post config.resources.cnyUpdateActivity, params, (data) ->
          if data.code is 200
            notificationService.success 'cny_update_success_tip',false #已成功更新活動設置!
          else if data.code is 1000
            notificationService.error 'cny_update_failed_tip',false  #活動設置更新失敗!
          return
        return

      _getActivityInfo = ->
        restService.get config.resources.cnyGetActivity, (data) ->
          if data
            vm.startDate = data.startDate
            vm.endDate = data.endDate
            vm.drawDate = data.drawDate
            vm.needPoints = data.needPoints
          else  # Set default activityInfo
            if vm.twSetting
              vm.needPoints = 10
              vm.drawDate = [
                1452009600000 #1.6
                1453219200000 #1.20
                1454428800000 #2.3
                1455638400000 #2.17
                1457452800000 #3.9
              ]
            else if vm.hkSetting
              vm.needPoints = 5
              vm.drawDate = [
                vm.hkDate = 1456675200000 #2.29
              ]
            vm.updateActivity()
          return
        return

      _getActivityParams = ->
        params =
          needPoints: parseInt vm.needPoints
          startDate: vm.startDate
          endDate: vm.endDate
          drawDate: vm.drawDate
        if not params.needPoints or not params.startDate or not params.endDate or not /^([1-9][0-9]*)$/.test params.needPoints
          return null
        if vm.twSetting
          if not params.drawDate or params.drawDate.length isnt 5
            return null
        else if vm.hkSetting
          if not params.drawDate or params.drawDate.length isnt 1
            return null
        params

      vm.checkPositiveInt = (id, number) ->
        tip = ''
        reg = /^([1-9][0-9]*)$/  # 大於0的整數
        if not reg.test number
          tip = 'uhkklp_check_positive_int_tip'
          validateService.highlight($('#' + id), $filter('translate')('uhkklp_check_positive_int_tip'))
        tip

      _init()
      vm
  ]

  app.registerController 'wm.ctrl.uhkklp.showCnySmsDetail', [
    '$scope'
    '$http'
    '$modalInstance'
    'restService'
    'notificationService'
    'modalData'
    'exportService'
    ($scope, $http, $modalInstance, restService, notificationService, modalData, exportService) ->
      vm = $scope

      _init = ->
        vm.sendingId = modalData
        _updateWinnerSmsDetail()
        return

      vm.exportSmsDetail = ->
        exportService.export 'cny_export_winners_sms', config.resources.cnyExportWinnerSmsDetail + '/' + modalData, false
        $modalInstance.close()
        return

      _updateWinnerSmsDetail = ->
        _getWinnerSmsDetail()
        clockOne = setInterval((->
          if vm.sendingId
            _getWinnerSmsDetail()
          return
        ), 5000)
        clockTwo = setInterval((->
          if vm.process isnt 1
            clearInterval clockOne
            clearInterval clockTwo
          return
        ), 5000)
        return

      _getWinnerSmsDetail = ->
        $http
          url: config.resources.getCnyWinnersSmsInfo + '/' + vm.sendingId
          method: 'GET'
          headers:
            'Content-Type': 'application/x-www-form-urlencoded'
        .success (data) ->
          vm.smsDetail =
            smsTemplate: data.smsTemplate
            total: data.total
            successful: data.successful
            failed: data.failed
          vm.process = data.process
          if vm.process is 0
            vm.prepareSms = true
          if vm.process is 1
            vm.sendText = true
            vm.prepareSms = false
          else if vm.process is 2 or vm.process is 3
            vm.exportBtn = true
            vm.sendText = false
            vm.prepareSms = false
          return
        return

      vm.hideModal = ->
        $modalInstance.close()

      _init()
  ]
