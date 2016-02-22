define [
  'wm/app'
  'wm/config'
  './importNumberCtrl'
], (app, config) ->
  app.registerController 'wm.ctrl.uhkklp.edit.sms', [
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
    '$timeout'
    (restService, $stateParams, $scope, $filter, $location, notificationService, localStorageService, validateService, $modal, $http, exportService, $interval, $timeout) ->
      vm = this

      $scope.importResult = []
      $scope.isImport = false
      $scope.testContent = ''
      $scope.testNumber = null
      $scope.totalCount = 0
      $scope.formData = {
        modelContent : "【UFS】%param1%你好，你已成功重設密碼。使用家樂牌「儲分有賞」APP，毋須擔心忘記密碼，請即下載%param2%"
        sendTime : ''
      }

      $scope.active = $location.search().active

      #breadcrum
      title = if typeof $location.search().id is 'boolean' then 'mt_tt_sms' else 'mt_tt_editsms'

      if typeof $location.search().id isnt 'boolean'
          $http
            method: 'POST'
            url: '/api/uhkklp/sms/get-one'
            data: $.param _id : $location.search().id
            headers:
              'Content-Type': 'application/x-www-form-urlencoded'
          .success (data) ->
            if data['result'] is 'success'
              $scope.formData.modelContent = data['modelContent']
              $scope.formData.sendTime = data['sendTime']
              $scope.importResult = data['list']
              if data['list']? and data['list'].length > 0
                $scope.totalCount = data['list'].length
                $scope.isImport = true
                $scope.testNumber = parseInt(data['list'][0].mobile)
                $scope.testContent = data['list'][0].content
            else
              notificationService.error 'registration_edit_error', false
          .error (data) ->
            notificationService.error 'registration_edit_error', false

      vm.breadcrumb = [
        {
          text: 'uhkklp_sms'
          href: '/uhkklp/sms?active=' + $scope.active
        }
        title
      ]

      $scope.import = ->
        modalInstance = $modal.open(
          templateUrl: '/build/modules/uhkklp/partials/edit/importNumber.html'
          controller: 'wm.ctrl.uhkklp.edit.importNumber'
          windowClass: 'setting-dialog'
          resolve:
            content : ()->
                          contents : $scope.formData.modelContent
        ).result.then( (data) ->
          if data.result is 'success'
            $scope.totalCount = data.record
            $scope.importResult = data.resultList
            if data.resultList? and data.resultList.length > 0
              $scope.isImport = true
              $scope.testNumber = parseInt(data.resultList[0].mobile)
              $scope.testContent = data.resultList[0].content
            else
              notificationService.error 'sms_empty', false
        )

      calculateVariableCount = ->
        if $scope.formData.modelContent?
          count = $scope.formData.modelContent.match(/%param\d*%/g)
        else
          count = []
        if count?
          return count.length
        return 0

      wordMonitor = ->
        $scope.$watch('formData.modelContent', (newVal) ->
          if $scope.formData.modelContent?
            $scope.wordCount = $scope.formData.modelContent.length
          else
            $scope.wordCount = 0
          $scope.variableCount = calculateVariableCount()
          $scope.smsCount = Math.ceil($scope.wordCount / 70)
        )

      $scope.submit = ->
        console.log $scope.formData.sendTime
        $scope.isClickable = true
        # console.log $scope.formData.sendTime
        if not $scope.formData.modelContent? or not $scope.formData.sendTime? or not $scope.isImport
          notificationService.error 'sms_save_error', false
          $scope.isClickable = false
          return
        if typeof $location.search().id isnt 'boolean'
          url = '/api/uhkklp/sms/update-sms'
          $scope.formData._id = $location.search().id
        else
          url = '/api/uhkklp/sms/save-sms'
        $scope.formData.list = $scope.importResult
        $http
          method: 'POST'
          url: url
          data: $.param($scope.formData)
          headers:
            'Content-Type': 'application/x-www-form-urlencoded'
        .success (data) ->
          $scope.isClickable = false
          if data.code is '200'
            notificationService.success 'registration_save_succ', false
          else
            notificationService.error 'recipe_edit_save_error', false
          $timeout(() ->
                    $location.url '/uhkklp/sms?active=' + $scope.active,
          2000)
        .error (data) ->
          $scope.isClickable = false
          notificationService.error 'recipe_edit_save_error', false
        return

      $scope.send = ->
        if not $scope.testNumber or not $scope.testContent
          notificationService.warning 'sms_empty', false
          return
        params = $.param
          testNumber : $scope.testNumber
          testContent : $scope.testContent
        $("#smsSend").attr "disabled", true
        restService.post '/api/uhkklp/sms/test-sms', params, (data) ->
          $("#smsSend").attr "disabled", false
          if data.result is 'success'
            notificationService.success 'sms_test_send_suc', false
          else
            notificationService.error 'sms_test_send_fail', false
        return

      _init = ->
        wordMonitor()
        $scope.variableCount = calculateVariableCount()
        return

      _init()
      vm
  ]
