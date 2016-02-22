define [
  'wm/app'
  'wm/config'
], (app, config) ->
  app.registerController 'wm.ctrl.uhkklp.edit.goods.westpromotion', [
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
    (restService, $stateParams, $scope, $filter, $location, notificationService, localStorageService, validateService, $modal, $http, exportService, $interval) ->
      vm = this

      $scope.active = $location.search().active
      $scope.formData = {
        name : ''
        image : ''
        description : ''
        href : ''
      }
      $scope.isSubmitted = false
      $scope.isClickable = false
      LINK_REGEXP = /^((http|https|ftp):\/\/)?(w{3}\.)?[\.\w-]+(?=\.[a-z])\.[a-z]+(\/[\S]*)*$/i
      $scope.showError = false

      title = if typeof $location.search().id is 'boolean' then 'goods_add_title' else 'goods_edit_title'
      vm.breadcrumb = [
        {
          text: 'uhkklp_goods'
          href: '/uhkklp/westpromotion?active=' + $scope.active
        }
        title
      ]

      if typeof $location.search().id isnt 'boolean'
          $http
            method: 'POST'
            url: '/api/uhkklp/goods/get-one'
            data: $.param _id : $location.search().id
            headers:
              'Content-Type': 'application/x-www-form-urlencoded'
          .success (data) ->
            if data['result'] is 'success'
              $scope.formData.name = data['name']
              $scope.formData.image = data['image']
              $scope.formData.description = data['description']
              $scope.formData.href = data['href']
            else
              notificationService.error 'registration_edit_error', false
          .error (data) ->
            notificationService.error 'registration_edit_error', false

      $scope.$watch('formData.href', (newValue)->
        if not LINK_REGEXP.test(newValue) and newValue isnt '' and newValue?
          $scope.showError = true
        else
          $scope.showError = false
      )

      $scope.submitInfo = ->
        $scope.isClickable = true
        if $scope.formData.name is '' or $scope.formData.description is '' or $scope.formData.image is '' or not $scope.formData.image? or not $scope.formData.href?
          $scope.isSubmitted = true
          $scope.isClickable = false
          return
        if typeof $location.search().id isnt 'boolean'
            url = '/api/uhkklp/goods/update'
            $scope.formData._id = $location.search().id
          else
            url = '/api/uhkklp/goods/save'
        $scope.formData.id = localStorageService.getItem(config.keys.currentUser).id
        $http
          method: 'POST'
          url: url
          data: $.param($scope.formData)
          headers:
            'Content-Type': 'application/x-www-form-urlencoded'
        .success (data) ->
          $scope.isClickable = false
          if data['code'] is '1209'
            $location.url '/site/login'
          else if data['code'] is '200'
            notificationService.success 'registration_save_succ', false
            $location.url '/uhkklp/westpromotion?active=' + $scope.active
          else
            notificationService.error 'recipe_edit_save_error', false
        .error (data) ->
          notificationService.error 'recipe_edit_save_error', false
          $scope.isClickable = false

      vm
  ]

