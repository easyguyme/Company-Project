define [
  'wm/app'
  'wm/config'
], (app, config) ->
  app.registerController 'wm.ctrl.uhkklp.edit.importNumber', [
    'restService'
    '$scope'
    '$modalInstance'
    'notificationService'
    '$timeout'
    'debounceService'
    '$http'
    '$filter'
    'content'
    (restService, $scope, $modalInstance, notificationService, $timeout, debounceService, $http, $filter, content) ->
      vm = $scope
      delayTime = 5000
      vm.status = false
      $scope.fileTypes = ['application/vnd.ms-excel', 'application/octet-stream', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'text/csv']

      _showLoading = ->
        document.getElementById('upload-loading').style.display = 'block'

      _hideLoading = ->
        document.getElementById('upload-loading').style.display = 'none'

      _getStatus = (data) ->

      vm.hideModal = ->
        $modalInstance.close('close')

      $scope.files = null
      vm.upload = (files) ->
        $scope.files = files
        vm.file = ''
        for file in files
          vm.file = vm.file + file.name + ';'
        vm.status = true

      vm.import = ->
        vm.status = false
        if not $scope.files.length
          return
        for file in $scope.files
          if $.inArray(file.type, $scope.fileTypes) is -1
            notificationService.error 'mt_tp_excel_format_error', false
            return
        notificationService.info 'mt_fm_excel_reading', false

        reader = new FileReader()
        reader.readAsDataURL $scope.files[0]
        reader.onload = (loadEvent) ->
          _upLoadNumber loadEvent.target.result
          return

        return

      _upLoadNumber = (file) ->
        url = '/api/uhkklp/excel-reader/read-number-excel'
        param = $.param
                    fileB64: file
                    content: content.contents
                    # modelGroupId : content.modelGroupId
        $http
          url: url
          method: 'POST'
          headers:
            'Content-Type': 'application/x-www-form-urlencoded'
          data: param
        .success (data) ->
          if $('.message')
            ($ '.message').hide()
          if data.fileError
            notificationService.error 'mt_tp_excel_parse_error', false
            return
          if data.contentError
            notificationService.error 'mt_tp_excel_content_error', false
            return
          if data.titleLengthError
            notificationService.error 'mt_tp_excel_title_length_error', false
            return
          if data.length is 0
            notificationService.error 'mt_tp_excel_null_error', false
            return
          message = $filter('translate')('mt_tp_number_import_succ', {count: data.recordCount})
          notificationService.success message, true
          $modalInstance.close({
              result : 'success'
              record : data.recordCount
              resultList : data.resultList
              # modelGroupId : data.modelGroupId
            })
        .error ->
          if $('.message')
            ($ '.message').hide()
          notificationService.error 'mt_tp_excel_parse_error', false
        return
      vm
    ]
