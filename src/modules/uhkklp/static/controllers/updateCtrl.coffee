define [
  'wm/app'
  'wm/config'
], (app) ->
  app.registerController 'wm.ctrl.uhkklp.update', [
    '$scope'
    '$http'
    ($scope, $http) ->
      vm = this

      vm.breadcrumb = [
        'uhkklp_update'
      ]

      $scope.submitted = false
      $scope.postData =
        ios: ''
        android: ''

      ($http.get '/api/uhkklp/version/get').success (data) ->
        $scope.postData.ios = data.result.ios
        $scope.postData.android = data.result.android

        return

      _beforeSubmit = ->
        return

      $scope.submit = ->
        _beforeSubmit()
        $scope.submitted = true

        url = '/api/uhkklp/version/set'

        $http
          url: url
          method: 'POST'
          headers:
            'Content-Type': 'application/json'
          data: JSON.stringify $scope.postData
        .success () ->
          notificationService.success 'mt_fm_save_succ', false
          return

        return

      vm
  ]
