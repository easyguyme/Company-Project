define [
  'wm/app'
  'wm/config'
], (app, config) ->
  app.registerController 'wm.ctrl.uhkklp.edit.cookingtype.recipe', [
    'restService'
    '$scope'
    '$http'
    'localStorageService'
    'notificationService'
    '$location'
    '$rootScope'
    '$filter'
    '$modalInstance'
    'modalData'
    (restService, $scope, $http, localStorageService, notificationService, $location, $rootScope, $filter, $modalInstance, modalData) ->
      vm = this
      #breadcrum
      title = if modalData.id then 'cooking_type_edit_title' else 'recipe_list_cookingtype_add'
      $scope.title = $filter('translate')(title)
      vm.listPath = '/uhkklp/recipe?active=4'

      $scope.cookingtypeId = modalData.id
      $scope.formData = {
        "radio": "true"
      }

      $scope.isCreating = if modalData.id then false else true

      #getCookingtype
      _getCookingtype = ->
        $http
          method: 'GET'
          url: '/api/uhkklp/cooking-type/get-by-id?cookingtypeId=' + $scope.cookingtypeId
        .success (data) ->
          if data['code'] is 200
            $cookingtype = data['result']
            $scope.formData = {
              name: $cookingtype['name']
              radio: $cookingtype['radio']
            }
          else
            $location.url vm.listPath
            $rootScope.uhkklp_recipe_tip = 'cookingtype_edit_miss_tip'
        .error (data) ->
          $location.url vm.listPath
          $rootScope.uhkklp_recipe_tip = 'cookingtype_edit_miss_tip'

      if modalData.id
        _getCookingtype()

      $scope.isClickable = false
      $scope.isRequired = false
      #submitInfo
      $scope.submitInfo = (isRequired) ->
        $scope.isClickable = true
        if isRequired
          notificationService.warning 'recipe_edit_required_tip', false
          $scope.isClickable = false
          return

        $scope.formData.id = localStorageService.getItem(config.keys.currentUser).id
        $scope.formData.category = '大類'
        $scope.formData.cookingtypeId = $scope.cookingtypeId

        $http
          method: 'POST'
          url: '/api/uhkklp/cooking-type/save'
          data: $scope.formData
          headers:
            'Content-Type': 'application/x-www-form-urlencoded'
        .success (data) ->
          $scope.isClickable = false
          if data['code'] is 1209
            $modalInstance.close('login')
          else if data['code'] is 200
            $modalInstance.close('success')
          else
            notificationService.warning data['msg'], false
        .error (data) ->
           notificationService.error 'recipe_edit_save_error', false

      $scope.hideModal = ->
        $modalInstance.close('close')
      vm

  ]
