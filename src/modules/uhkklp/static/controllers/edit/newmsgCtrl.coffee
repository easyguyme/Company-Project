define [
  'wm/app'
  'wm/config'
], (app, config) ->
  app.registerController 'wm.ctrl.uhkklp.edit.newmsg', [
    '$scope'
    'uploadService'
    '$stateParams'
    'notificationService'
    '$http'
    '$location'
    '$rootScope'
    '$timeout'
    ($scope, uploadService, $stateParams, notificationService, $http, $location, $rootScope, $timeout) ->

      scrollTo 0, 0

      vm = this

      #breadcrum
      title = if $stateParams.id then 'mt_tt_editmsg' else 'mt_tt_newmsg'
      vm.breadcrumb = [
        {
          text: 'uhkklp_message'
          href: '/uhkklp/newmsg'
        }
        title
      ]

      if not $stateParams.id
        $scope.message =
          content: ''

          pushMethod: 'all'
          pushDevices: ''

          pushTime: ''

          linkType: 'app'
          newsId: ''

      else
        url = '/api/uhkklp/message/get?$id=' + $stateParams.id
        ($http.get url).success (data) ->

          data.pushTime = data.pushTime.sec * 1000
          $scope.message = data

      $scope.$watch 'message.content', (newVal, oldVal) ->
        if not newVal
          return

        # byteCount = newVal.length + (newVal.replace /[\u0000-\u00ff]/g, "").length
        byteCount = newVal.length
        if byteCount > 30
          $scope.message.content = oldVal
        return

      _beforeSubmit = ->
        if not ($scope.message.pushMethod is 'nameList')
          $scope.message.pushDevices = ''
        if not ($scope.message.linkType is 'news')
          $scope.message.newsId = ''
        return

      # _validateSubmit = ->
      #   $scope.submitted = true
      #   (
      #     if v.$invalid
      #       elem = $ '[name=' + k + ']'
      #       elem.focus()
      #       elem.blur()
      #       if not firstInvalidElemName
      #         firstInvalidElemName = k
      #   )for k, v of $scope.messageForm when not /^\$/.test k
      #   elem = $ '[name=' + firstInvalidElemName + ']'
      #   if firstInvalidElemName
      #     scrollTo elem[0].offsetParent.offsetLeft, elem[0].offsetParent.offsetTop
      #   return

      $scope.submitting = false
      $scope.submit = ->
        if $scope.submitting
          return
        $scope.submitting = true
        $scope.submitted = true
        # _validateSubmit()
        if $scope.messageForm.$invalid
          $scope.submitting = false
          return

        _beforeSubmit()

        url = '/api/uhkklp/message/save'
        if $stateParams.id
          if (new Date()).getTime() >= $scope.message.pushTime
            notificationService.warning 'mt_fm_cant_edit', false
            $scope.submitting = false
            return
          url = '/api/uhkklp/message/update-one'
        $http
          url: url
          method: 'POST'
          headers:
            'Content-Type': 'application/json'
          data: JSON.stringify $scope.message
        .success (data) ->
          if data.code is '1'
            $rootScope.uhkklp_newmsg_tip = 'mt_fm_create_succ'
          if data.code is '2'
            $rootScope.uhkklp_newmsg_tip = 'mt_fm_edit_succ'
          $location.url '/uhkklp/newmsg'
          return

        return

      # $scope.$watch 'excelFileB64', (newVal, oldVal) ->
      #   if  not newVal
      #     return
      #   url = '/api/uhkklp/excel-conversion/to-json'
      #   param = $.param fileB64: newVal
      #   $http
      #     url: url
      #     method: 'POST'
      #     headers:
      #       'Content-Type': 'application/x-www-form-urlencoded'
      #     data: param
      #   .success (data) ->
      #     if data.fileError
      #       $scope.excelFileB64 = ''
      #       $scope.excelConversionError = 'mt_tp_excel_parse_error'
      #       return
      #     if data.numError
      #       $scope.numError = 'mt_tp_excel_num_error'
      #       $scope.wrongNums = data.wrongNums
      #       return
      #     $scope.numError = false
      #     $scope.excelConversionError = false
      #     $scope.message.pushDevices = data
      #   .error ->
      #     $scope.excelFileB64 = ''
      #     $scope.excelConversionError = 'mt_tp_excel_parse_error'
      #     return
      #   return

      $scope.chooseExcelBtnClick = ->
        $('#chooseExcelBtn').click()
        return

      $scope.fileTypes = ['application/vnd.ms-excel', 'application/octet-stream', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'text/csv']
      $scope.onFileSelect = (files) ->
        if not files.length
          return
        for file in files
          if $.inArray(file.type, $scope.fileTypes) is -1
            notificationService.error 'mt_tp_excel_format_error', false
            return
        $scope.loading = true
        notificationService.info 'mt_fm_excel_reading', false

        reader = new FileReader()
        reader.readAsDataURL files[0]
        reader.onload = (loadEvent) ->
          # $scope.$apply ->
          #   $scope.excelFileB64 = loadEvent.target.result
          #   return
          _upLoadExcel loadEvent.target.result
          return

        return

      _upLoadExcel = (b64File) ->
        if  not b64File
          return
        url = '/api/uhkklp/excel-conversion/to-json'
        param = $.param fileB64: b64File
        $http
          url: url
          method: 'POST'
          headers:
            'Content-Type': 'application/x-www-form-urlencoded'
          data: param
        .success (data) ->
          if $('.message')
            ($ '.message') .hide()
          $scope.loading = false
          if data.fileError
            notificationService.error 'mt_tp_excel_parse_error', false
            return
          if data.numError
            # $scope.numError = 'mt_tp_excel_num_error'
            # $scope.wrongNums = data.wrongNums
            notificationService.error 'mt_tp_excel_content_error', false
            return
          if data.length is 0
            notificationService.error 'mt_tp_excel_null_error', false
            return
          # $scope.numError = false
          # $scope.excelConversionError = false
          $scope.message.pushDevices = data
          $scope.loading = false
        .error ->
          if $('.message')
            ($ '.message').hide()
          $scope.loading = false
          notificationService.error 'mt_tp_excel_parse_error', false
          return
        return

      vm

  ]
