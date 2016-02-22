define [
  'wm/app'
  'wm/config'
], (app, config) ->
  app.registerController 'wm.ctrl.uhkklp.edit.sample.recipe', [
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
      title = if $stateParams.id then 'sample_edit_title' else 'sample_create_title'
      vm.breadcrumb = [
        {
          text: 'recipe_list_sample'
          href: '/uhkklp/recipe?active=3'
        }
        title
      ]

      vm.listPath = '/uhkklp/recipe?active=3'

      scrollTo 0, 0
      $scope.isSubmitted = false
      $scope.sampleId = $stateParams.id
      $scope.formData = {}
      $scope.isCreating = if $stateParams.id then false else true

      #getSample
      _getSample = ->
        $http
          method: 'GET'
          url: '/api/uhkklp/sample/get-by-id?sampleId=' + $scope.sampleId
          headers:
            'Content-Type': 'application/x-www-form-urlencoded'
        .success (data) ->
          if data['code'] is 1300
            notificationService.warning data['msg'], true
            return
          $sampleData = data['result']
          $scope.formData = {
            name: $sampleData['name']
            quantity: $sampleData['quantity']
            imgUrl: $sampleData['imgUrl']
          }
        .error (data) ->
          $location.url = vm.listPath
          $rootScope.uhkklp_recipe_tip = 'sample_edit_miss_tip'

      if $stateParams.id
        _getSample()

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

      $scope.isClickable = false
      $scope.isRequired = false
      #submitInfo
      $scope.submitInfo = (isRequired) ->
        if not $scope.formData['imgUrl']? or $scope.formData['imgUrl'] is ''
          $scope.sampleForm.imgUrl.$invalid = true
        else
          $scope.sampleForm.imgUrl.$invalid = false

        $scope.isSubmitted = true
        $scope.isClickable = true
        if isRequired or $scope.sampleForm.imgUrl.$invalid
          notificationService.warning 'recipe_edit_required_tip', false
          _validateSubmit()
          $scope.isClickable = false
          return

        $scope.formData.operatorId = localStorageService.getItem(config.keys.currentUser).id
        $scope.formData.sampleId = $scope.sampleId
        $http
          method: 'POST'
          url: '/api/uhkklp/sample/save'
          data: $.param($scope.formData)
          headers:
            'Content-Type': 'application/x-www-form-urlencoded'
        .success (data) ->
          $scope.isClickable = false
          if data['code'] is 1309
            $location.url '/site/login'
            notificationService.warning 'recipe_go_to_login_tip', false
          else if data['code'] is 200
            $location.url vm.listPath
            $rootScope.uhkklp_recipe_tip = 'recipe_edit_save_success'
          else
            notificationService.warning data['msg'], false
        .error (data) ->
           notificationService.error 'recipe_edit_save_error', false
      vm

  ]
