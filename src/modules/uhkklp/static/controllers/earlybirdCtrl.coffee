define [
  'wm/app'
  'wm/config'
], (app, config) ->
  app.registerController 'wm.ctrl.uhkklp.earlybird', [
    'restService'
    '$stateParams'
    '$scope'
    '$modal'
    '$location'
    '$filter'
    '$http'
    'validateService'
    'exportService'
    'notificationService'
    (restService, $stateParams, $scope, $modal, $location, $filter, $http, validateService, exportService, notificationService) ->
      vm = this

      _init = ->
        vm.smsOne = '%username%您好，【集點拿獎2015最後一波優惠】即日起至11/30前，兌200點再加碼抽 $200禮劵！詳情洽http://bit.ly/1P3M1mK。提醒您，截至10/29您擁有%points%點數， 12/31後未兌換之點數將歸零。'
        vm.smsTwo = '【集點拿獎 早鳥兌獎抽獎樂】還剩七天！至11/30前兌換贈品，再加碼抽好禮！提醒您: 12/31 23:59, 未兌換之點數將歸零，詳情洽http://bit.ly/1P3M1mK'
        vm.smsThree = '%username%您好，【集點拿獎 2015活動即將結束】截至12/23您尚有%points%點數，12/31 23:59後，未兌換之點數將歸零，還剩七天！請立即兌換！詳情洽http://bit.ly/1J7bjyJ'
        vm.smsFour = '%username%您好，謝謝您參加早鳥兌獎抽獎樂，您已獲得%prizeName%抽獎資格，抽獎結果將於12/7公布ufs.com 。'

        vm.startDate = 1446307200000
        vm.endDate = 1448899199000
        vm.pointsOne = 2000
        vm.pointsTwo = 1000
        vm.pointsThree = 200
        vm.quantityOne = 5
        vm.quantityTwo = 10
        vm.quantityThree = 20
        vm.prizeNameOne = '太平洋百貨2000商品兌換劵'
        vm.prizeNameTwo = '太平洋百貨1000商品兌換劵'
        vm.prizeNameThree = '7-11超市200商品兌換劵'

        vm.breadcrumb = [
          'uhkklp_early_bird'
        ]
        vm.tabs = [
          {
            name: 'SMS'
            value: 0
          }
          {
            name: 'earlybird_btn_draw'
            value: 1
          }
        ]
        tabVal = $location.search().active
        vm.curTab = if tabVal then vm.tabs[tabVal] else vm.tabs[0]
        #pagination
        vm.pageSize = 10
        vm.currentPage = 1
        #table
        vm.list =
          columnDefs: [
            {
              field: '_id'
              label: 'earlybird_text_draw_number'
            }
            {
              field: 'createdAt'
              label: 'earlybird_text_draw_date'
            }
          ]
          operations: [
            {
              text: 'earlybird_text_draw_operations'
              name: 'download'
              title: 'download'
            }
          ]
          data: []
          downloadHandler: (item) ->
            exportService.export 'earlybird_export_winners', config.resources.exportEarlyBirdDrawPrize + '/' + vm.list.data[item]._id, false
            return
        _checkTabs()
        _getSmsRecord()
        _getList()
        return

      vm.sendSms = ($event, smsTag) ->
        notificationService.confirm $event, {
            submitCallback: _sendSms
            title: 'earlybird_send_sms_tip'
            params: [smsTag]
          }
        return

      _sendSms = (smsTag) ->
        if smsTag is 'sms_four'
          _sendDrawSms()
        else
          _changeDisabled 'unknow', true
          params =
            smsTag: smsTag
          restService.get config.resources.earlybirdSendSms, params, (data) ->
            if data
              vm.sendingId = data.smsRecordId
              switch smsTag
                when 'sms_one'
                  _changeDisabled 'smsOne', true
                  vm.totalOne = data.count
                  break
                when 'sms_two'
                  _changeDisabled 'smsTwo', true
                  vm.totalTwo = data.count
                  break
                when 'sms_three'
                  _changeDisabled 'smsThree', true
                  vm.totalThree = data.count
                  break
                else
                  break
              _getSendInfo()
            return
        return

      _sendDrawSms = ->
        params = _getDrawParams()
        if not _verifyDrawParams()
          notificationService.warning 'earlybird_tip_draw_options_set',false  #失敗,請正確填寫抽獎設置！
          return
        _changeDisabled('smsFour', true)
        restService.post config.resources.earlybirdSendDrawSms, params, (data) ->
          if data
            vm.sendingId = data.smsRecordId
            vm.totalFour = data.count
            _getSendInfo()
        return

      vm.exportFailedSms = (smsName) ->
        params =
          smsName: smsName
        exportService.export 'earlybird_failed_sms_file', config.resources.exportEarlyBirdFailedSms, params, false
        return

      vm.exportSmsDetails = (smsName) ->
        params =
          smsName: smsName
        exportService.export 'earlybird_sms_details_file', config.resources.exportEarlyBirdSmsDetails, params, false
        return

      vm.exportDrawMembers = ->
        params = _getDrawParams()
        if not _verifyDrawParams()
          notificationService.warning 'earlybird_tip_draw_options_set',false  #失敗,請正確填寫抽獎設置！
          return
        exportService.export 'earlybird_draw_members_file', config.resources.exportEarlyBirdDrawMembers, params, false
        return

      vm.drawPrize = ->
        params = _getDrawParams()
        if not _verifyDrawParams()
          notificationService.warning 'earlybird_tip_draw_options_set',false  #失敗,請正確填寫抽獎設置！
          return
        restService.post config.resources.earlyBirdDrawPrize, params, (data) ->
          if data and data.code is 1000
            notificationService.warning 'earlybird_tip_draw_failed_no_members',false #抽獎失敗,不存在符合抽獎條件的會員!
          else
            _getList()
          return
        return

      _getSendInfo = ->
        clockOne = setInterval((->
          if vm.sendingId isnt 'no'
            $http
              url: config.resources.earlybirdSmsGetSendInfo + '/' + vm.sendingId
              method: 'GET'
              headers:
                'Content-Type': 'application/x-www-form-urlencoded'
            .success (data) ->
              _updateSendInfo data.smsName, data.successful, data.failed
              if data.process isnt 1
                _doSendOver(data.process, data.smsName)
              return
          return
        ), 5000)
        clockTwo = setInterval((->
          if vm.sendingId is 'no'
            clearInterval clockOne
            clearInterval clockTwo
          return
        ), 5000)

        return

      _changeDisabled = (disabledId, status) ->
        $("#smsOne").attr "disabled",status
        $("#smsTwo").attr "disabled",status
        $("#smsThree").attr "disabled",status
        $("#smsFour").attr "disabled",status
        if status
          $("#" + disabledId).val($filter('translate')('earlybird_btn_sending'))
        else
          $("#" + disabledId).val($filter('translate')('earlybird_btn_send'))
        return

      _getSmsRecord = ->
        restService.get config.resources.earlybirdGetSmsRecord, (data) ->
          if data
            if data.sendingId
              vm.sendingId = data.sendingId
              _changeDisabled('unknow', true)
              _getSendInfo()
            else
              vm.sendingId = 'no'

            if typeof data.records.sms_one is 'object'
              vm.totalOne = data.records.sms_one.total
              vm.successfulOne = data.records.sms_one.successful
              vm.failedOne = data.records.sms_one.failed
              vm.showOne = true
            else
              vm.showOne = false

            if typeof data.records.sms_two is 'object'
              vm.totalTwo = data.records.sms_two.total
              vm.successfulTwo = data.records.sms_two.successful
              vm.failedTwo = data.records.sms_two.failed
              vm.showTwo = true
            else
              vm.showTwo = false

            if typeof data.records.sms_three is 'object'
              vm.totalThree = data.records.sms_three.total
              vm.successfulThree = data.records.sms_three.successful
              vm.failedThree = data.records.sms_three.failed
              vm.showThree = true
            else
              vm.showThree = false

            if typeof data.records.sms_four is 'object'
              vm.totalFour = data.records.sms_four.total
              vm.successfulFour = data.records.sms_four.successful
              vm.failedFour = data.records.sms_four.failed
              vm.showFour = true
            else
              vm.showFour = false
          return
        return

      _updateSendInfo = (smsName, successful, failed) ->
        switch smsName
          when 'sms_one'
            vm.successfulOne = successful
            vm.failedOne = failed
            vm.showOne = true
            _changeDisabled('smsOne', true)
          when 'sms_two'
            vm.successfulTwo = successful
            vm.failedTwo = failed
            vm.showTwo = true
            _changeDisabled('smsTwo', true)
          when 'sms_three'
            vm.successfulThree = successful
            vm.failedThree = failed
            vm.showThree = true
            _changeDisabled('smsThree', true)
          when 'sms_four'
            vm.successfulFour = successful
            vm.failedFour = failed
            vm.showFour = true
            _changeDisabled('smsFour', true)
          else
            break
        return

      _doSendOver = (process, smsName) ->
        vm.sendingId = 'no'
        if process is 2
          notificationService.success 'earlybird_tip_sms_send_success',false  #簡訊發送完成!
        if process is 3
          notificationService.error 'earlybird_tip_sms_send_error',false  #簡訊發送失敗,伺服器錯誤!
        switch smsName
          when 'sms_one'
            _changeDisabled('smsOne', false)
          when 'sms_two'
            _changeDisabled('smsTwo', false)
          when 'sms_three'
            _changeDisabled('smsThree', false)
          when 'sms_four'
            _changeDisabled('smsFour', false)
          else
            break
        return

      vm.checkPositiveInt = (id, number) ->
        tip = ''
        reg = /^([1-9][0-9]*)$/  # 大於0的整數
        if not reg.test number
          tip = 'earlybird_check_positive_int_tip'
          validateService.highlight($('#' + id), $filter('translate')('earlybird_check_positive_int_tip'))
        tip

      vm.changeTab = ->
        _checkTabs()
        return

      _checkTabs = ->
        if vm.curTab.value is 0
          vm.sms = true
          vm.draw = false
        else
          vm.sms = false
          vm.draw = true
        return

      _verifyDrawParams = ->
        params = _getDrawParams()
        reg = /^([1-9][0-9]*)$/  # 大於0的整數
        result = true
        if not params.startDate or not params.endDate or not params.pointsOne or not params.pointsTwo or not params.pointsThree
          result = false
        if not params.quantityOne or not params.quantityTwo or not params.quantityThree or not params.prizeNameOne or not params.prizeNameTwo or not params.prizeNameThree
          result = false
        if not reg.test(params.pointsOne) or not reg.test(params.pointsTwo) or not reg.test(params.pointsThree)
          result = false
        if not reg.test(params.quantityOne) or not reg.test(params.quantityTwo) or not reg.test(params.quantityThree)
          result = false
        result


      _getDrawParams = ->
        params =
          startDate: vm.startDate
          endDate: vm.endDate
          pointsOne: parseInt vm.pointsOne
          pointsTwo: parseInt vm.pointsTwo
          pointsThree: parseInt vm.pointsThree
          quantityOne: parseInt vm.quantityOne
          quantityTwo: parseInt vm.quantityTwo
          quantityThree: parseInt vm.quantityThree
          prizeNameOne: vm.prizeNameOne
          prizeNameTwo: vm.prizeNameTwo
          prizeNameThree: vm.prizeNameThree

      _getList = ->
        params =
          pageSize: vm.pageSize
          currentPage: vm.currentPage
        restService.get config.resources.getEarlyBirdWinnerList, params, (data) ->
          vm.list.data = data.list
          vm.totalCount = data.count
          return
        return

      vm.changePage = (currentPage) ->
        vm.currentPage = currentPage
        _getList()

      vm.changeSize = (pageSize) ->
        vm.pageSize = pageSize
        vm.currentPage = 1
        _getList()

      vm.showTestSms = (smsTag) ->
        vm.data = null
        if smsTag?
          if smsTag is 'sms_four'
            params = _getDrawParams()
            if not _verifyDrawParams()
              notificationService.warning 'earlybird_tip_draw_options_set',false  #失敗,請正確填寫抽獎設置！
              return
            vm.data =
              smsTag: smsTag
              params: params
          else
            vm.data =
              smsTag: smsTag
        modalInstance = $modal.open(
          templateUrl: 'showTestSms.html'
          controller: 'wm.ctrl.uhkklp.showTestSms'
          windowClass: 'token-dialog'
          resolve:
            modalData: -> vm.data
        ).result.then( (data) ->
          if data
            console.log data
        )
        return

      _init()
      vm
  ]

  app.registerController 'wm.ctrl.uhkklp.showTestSms', [
    '$scope'
    '$modalInstance'
    'restService'
    'notificationService'
    'modalData'
    ($scope, $modalInstance, restService, notificationService, modalData) ->
      vm = $scope
      vm.testSms = ''
      vm.testMobile = ''
      vm.showTips = false

      if modalData
        params = modalData
        url = config.resources.getEarlyBirdTestSms
        if modalData.smsTag is 'sms_four'
          params = modalData.params
          url = config.resources.getEarlyBirdDrawTestSms
        restService.get url, params, (data) ->
          if data
            if data.code is 1000
              notificationService.warning 'earlybird_tip_testsms_failed_no_testnumber',false  #系統中沒有找到測試手機號碼!
            else if data.code is 2000
              notificationService.warning 'earlybird_tip_testsms_failed_no_qualification',false #測試號碼不滿足抽獎條件!
            else if data.code is 200
              vm.testMobile = data.sms.mobile
              vm.testSms = data.sms.smsContent
              vm.showTips = true
          return

      vm.sendTestSms = ->
        params =
          testMobile: vm.testMobile
          testSms: vm.testSms
        restService.get config.resources.earlyBirdSendTestSms, params, (data) ->
          if data
            if data.code is 200
              notificationService.success 'earlybird_tip_testsms_send_success',false #測試簡訊發送成功!
            else
              notificationService.error 'earlybird_tip_testsms_send_failed',false #測試簡訊發送失敗!
          return
        vm.hideModal()
        return

      vm.hideModal = ->
        $modalInstance.close()
  ]
