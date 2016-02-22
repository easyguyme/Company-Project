define [
  'wm/app'
  'wm/config'
], (app, config) ->
  app.registerController 'wm.ctrl.uhkklp.edit.westpromotion', [
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

      MOBILE_REGEXP = /^09\d{8}$/
      # ZIPCODE_REGEXP = /^\d*$/
      $scope.showError = false
      # $scope.zipcodeShowError = false
      $scope.active = $location.search().active
      $scope.submitted = false
      $scope.isEnable = false
      $scope.isClickable = false
      $scope.isShowValidateBtn = true
      $scope.activities = []
      vm.regisNums = []

      $scope.formData = {
        activityName : ''
        name : ''
        mobile : ''
        restaurantName : ''
        businessForm : ''
        address : ''
        city : ''
        perPrice : ''
        perComingDay : ''
        registrationNumber : ''
        lineName : ''
        registrationTime : ''
        restaurantId : ''
        confirmRegistration : ''
      }

      $scope.$watch('formData.mobile', (newValue) ->
        if not MOBILE_REGEXP.test(newValue) and newValue isnt '' and newValue?
          $scope.showError = true
        else
          $scope.showError = false
      )

      restService.get '/api/uhkklp/west-pro-activity/get-activity-regis-list', null, (data) ->
         angular.forEach(data.list, (item) ->
            $scope.activities.unshift(item.name);
         )

      $scope.reset = ->
        $scope.submitted = false

      _loadRegisNums = ->
        vm.regisNums = []
        if not ($scope.formData.activityName is '' or not $scope.formData.activityName?)
          $http
              method: 'POST'
              url: '/api/uhkklp/west-pro-activity/get-regis-nums'
              data: $.param activityName : $scope.formData.activityName
              headers:
                'Content-Type': 'application/x-www-form-urlencoded'
          .success (data) ->
            if data['code'] is 200
              angular.forEach(data.list[0].registrationNumber, (item) ->
                  vm.regisNums.push(item);
              )

      $scope.getRegisNums = ->
        $scope.submitted = false
        vm.regisNums = []
        if $scope.formData.activityName is '' or not $scope.formData.activityName?
          return
        $http
            method: 'POST'
            url: '/api/uhkklp/west-pro-activity/get-regis-nums'
            data: $.param activityName : $scope.formData.activityName
            headers:
              'Content-Type': 'application/x-www-form-urlencoded'
        .success (data) ->
          if data['code'] is 200
            angular.forEach(data.list[0].registrationNumber, (item) ->
                vm.regisNums.push(item);
            )

      # $scope.$watch('formData.zipCode', (newValue)->
      #   if not ZIPCODE_REGEXP.test(newValue) and newValue isnt '' and newValue?
      #     $scope.zipcodeShowError = true
      #   else
      #     $scope.zipcodeShowError = false
      # )

      title = if typeof $location.search().id is 'boolean' then 'registration_add_title' else 'registration_edit_title'
      if typeof $location.search().id isnt 'boolean'
          $scope.isShowValidateBtn = false
          $http
            method: 'POST'
            url: '/api/uhkklp/registration/get-one'
            data: $.param _id : $location.search().id
            headers:
              'Content-Type': 'application/x-www-form-urlencoded'
          .success (data) ->
            if data['result'] is 'success'
              $scope.formData.activityName = data['activityName']
              $scope.formData.name = data['name']
              $scope.formData.mobile = data['mobile']
              $scope.formData.restaurantName = data['restaurantName']
              $scope.formData.businessForm = data['businessForm']
              $scope.formData.perComingDay = data['perComingDay']
              $scope.formData.perPrice = data['perPrice']
              $scope.formData.address = data['address']
              $scope.formData.city = data['city']
              $scope.formData.lineName = data['lineName']
              $scope.formData.registrationNumber = data['registrationNumber']
              $scope.formData.registrationTime = data['registrationTime']
              $scope.formData.restaurantId = data['restaurantId']
              $scope.formData.confirmRegistration = data['confirmRegistration']
              _loadRegisNums()
            else
              notificationService.error 'registration_edit_error', false
          .error (data) ->
            notificationService.error 'registration_edit_error', false

      vm.breadcrumb = [
        {
          text: 'uhkklp_registration'
          href: '/uhkklp/westpromotion?active=' + $scope.active
        }
        title
      ]

      $scope.submit = ->
        if $scope.formData.activityName is '' or not $scope.formData.activityName? or $scope.formData.registrationNumber is ''
            $scope.isClickable = false
            $scope.submitted = true
            notificationService.error 'all_required', false
            # notificationService.error 'activityName_required', false
            return
        $scope.isClickable = true
        $http
            method: 'POST'
            url: '/api/uhkklp/west-pro-activity/validate-registration-time'
            data: $.param
              activityName : $scope.formData.activityName
            headers:
              'Content-Type': 'application/x-www-form-urlencoded'
        .success (data) ->
          $scope.isClickable = false
          if data['code'] is 1
            notificationService.error 'late_error', false
            return
          else if data['code'] is 2
            notificationService.error 'early_error', false
            return
          else if data['code'] is 3
            # if $scope.formData.registrationNumber is ''
            #   $scope.isClickable = false
            #   notificationService.error 'registrationNumber_required', false
            #   return
            if $scope.registrationForm.$invalid or $scope.showError
              $scope.isClickable = false
              notificationService.error 'all_required', false
              return
            else
              $scope.formData.id = localStorageService.getItem(config.keys.currentUser).id
              if typeof $location.search().id isnt 'boolean'
                url = '/api/uhkklp/registration/update'
                $scope.formData._id = $location.search().id
              else
                url = '/api/uhkklp/registration/save'
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
                else if data['code'] is '2000'
                  notificationService.error 'registration_save_repeat', false
                else if data['code'] is '20000'
                  notificationService.error 'registration_tag_fail', false
                else  notificationService.error 'recipe_edit_save_error', false
              .error (data) ->
                $scope.isClickable = false
                notificationService.error 'recipe_edit_save_error', false
          else if data['code'] is 4
            notificationService.error 'fail_validate', false
            return
          else if data['code'] is 5
            notificationService.error 'no_activity', false
            return
        .error (data) ->
          $scope.isClickable = false
          notificationService.error 'fail_error', false
          return

      $scope.validateNumber = ->
        if $scope.formData.mobile is '' or not MOBILE_REGEXP.test($scope.formData.mobile)
          notificationService.error 'registration_no_number_tip', false
          return
        else
          $scope.isEnable = true
          $http
            method: 'POST'
            url: '/api/uhkklp/registration/validate-number'
            data: $.param
              mobile : $scope.formData.mobile
            headers:
              'Content-Type': 'application/x-www-form-urlencoded'
          .success (data) ->
            if data is '' or not data?
              notificationService.error 'validate_miss', false
              $scope.isEnable = false
              return
            (
              if item.name is 'name'
                $scope.formData.name = item.value
              if item.name is '餐廳名稱'
                $scope.formData.restaurantName = item.value
              if item.name is '經營形態'
                $scope.formData.businessForm = item.value
              if item.name is '平均消費單價'
                $scope.formData.perPrice = item.value
              if item.name is '餐廳縣市'
                $scope.formData.city = item.value
              if item.name is '餐廳地址'
                $scope.formData.address = item.value
              if item.name is '每日來客數量'
                $scope.formData.perComingDay = item.value
            ) for item in data.properties
            console.log data
            $scope.isEnable = false
          .error (data) ->
            $scope.isEnable = false
            notificationService.error 'validate_error', false
      vm
  ]

