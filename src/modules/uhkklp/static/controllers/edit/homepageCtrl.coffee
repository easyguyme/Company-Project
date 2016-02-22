define [
  'wm/app'
  'wm/config'
], (app, config) ->
  app.registerController 'wm.ctrl.uhkklp.edit.homepage', [
    'restService'
    '$stateParams'
    '$scope'
    '$http'
    'localStorageService'
    'notificationService'
    '$location'
    '$rootScope'
    (restService, $stateParams, $scope, $http, localStorageService, notificationService, $location, $rootScope) ->
      vm = this

      #breadcrum
      title = if $stateParams.id then '編輯更新消息提示時間' else '新增更新消息提示時間'
      vm.breadcrumb = [
        {
          text: '最新訊息'
          href: '/uhkklp/homepage?active=1'
        }
        title
      ]

      vm.listPath = '/uhkklp/homepage?active=1'

      scrollTo 0, 0
      $scope.isSubmitted = false
      $scope.updatedTimeId = $stateParams.id
      $scope.formData = {
        id:""
      }

      #getSample
      _getUpdatedNewsTime = ->
        $http
          method: 'GET'
          url: '/api/uhkklp/updated-news-time/get-by-id?updatedTimeId=' + $scope.updatedTimeId
          headers:
            'Content-Type': 'application/x-www-form-urlencoded'
        .success (data) ->
          console.log data['result']['updateTime']
          if data['code'] is 200
            $scope.formData.updateTime = data['result']['updateTime']
            $scope.formData.id = data['result']['_id']
          else
            $location.url = vm.listPath
            $rootScope.uhkklp_recipe_tip = 'update_news_time_edit_miss_tip'
        .error (data) ->
          $location.url = vm.listPath
          $rootScope.uhkklp_recipe_tip = 'update_news_time_edit_miss_tip'

      if $stateParams.id
        _getUpdatedNewsTime()

      #valid is empty
      _validateSubmit = ->
        (
          elem = $ '[name=' + k + ']'
          elem.focus()
          elem.blur()
          if v.$invalid
            if not firstInvalidElemName
              firstInvalidElemName = k
        )for k, v of $scope.sampleForm when not /^\$/.test k
        elem = $ '[name=' + firstInvalidElemName + ']'
        if firstInvalidElemName
          scrollTo elem[0].offsetLeft, (elem[0].offsetTop - 50)
        return

      #submitInfo
      $scope.submitInfo = (isRequired) ->
        $scope.isSubmitted = true
        if isRequired
          notificationService.warning 'recipe_edit_required_tip', false
          _validateSubmit()
          $scope.isClickable = false
          return
        $http
          method: 'POST'
          url: '/api/uhkklp/updated-news-time/save'
          data: $scope.formData
          headers:
            'Content-Type': 'application/json'
        .success (data) ->
          $scope.isClickable = false
          if data['code'] is 200
            $location.url vm.listPath
            $rootScope.uhkklp_recipe_tip = 'update_news_time_list_save_success_tip'
          else
            notificationService.warning 'update_news_time_list_save_failed_tip', false
        .error (data) ->
           notificationService.error 'update_news_time_list_save_failed_tip', false
      vm

  ]
