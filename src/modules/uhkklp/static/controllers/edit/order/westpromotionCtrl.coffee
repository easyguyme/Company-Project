define [
  'wm/app'
  'wm/config'
], (app, config) ->
  app.registerController 'wm.ctrl.uhkklp.edit.order.westpromotion', [
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
      $scope.submitted = false
      $scope.showError = false
      $scope.isClickable = false
      $scope.active = $location.search().active
      vm.products = []
      $scope.isEnable = false
      $scope.isShowValidateBtn = true
      vm.tagAll = false
      vm.params =
          tags: []
      $scope.activities = []
      vm.regisNums = []

      title = if typeof $location.search().id is 'boolean' then 'order_add_title' else 'order_edit_title'
      vm.breadcrumb = [
        {
          text: 'uhkklp_order'
          href: '/uhkklp/westpromotion?active=' + $scope.active
        }
        title
      ]

      $scope.formData = {
        activityName : ''
        name : ''
        mobile : ''
        restaurantName : ''
        address : ''
        city : ''
        productor : ''
        product : [],
        orderTime : '',
        lineName : '',
        restaurantId : '',
      }

      $scope.$watch('formData.mobile', (newValue) ->
        if not MOBILE_REGEXP.test(newValue) and newValue isnt '' and newValue?
          $scope.showError = true
        else
          $scope.showError = false
      )

      restService.get '/api/uhkklp/west-pro-activity/get-activity-order-list', null, (data) ->
        angular.forEach(data.list, (item) ->
            $scope.activities.unshift(item.name);
        )

      $scope.user =
        city :
          city : ''
        district : ''
        detail : ''

      $scope.getProduct = ->
        vm.products = []
        vm.regisNums = []
        $scope.submitted = false
        if $scope.formData.activityName is '' or not $scope.formData.activityName?
          return
        $http
            method: 'POST'
            url: '/api/uhkklp/west-pro-activity/get-product-list'
            data: $.param activityName : $scope.formData.activityName
            headers:
              'Content-Type': 'application/x-www-form-urlencoded'
        .success (data) ->
          if data['code'] is 200
            angular.forEach(data.list[0].promotionProducts, (item) ->
                vm.products.push(item);
            )

      if typeof $location.search().id isnt 'boolean'
          $scope.isShowValidateBtn = false
          $http
            method: 'POST'
            url: '/api/uhkklp/order/get-one'
            data: $.param _id : $location.search().id
            headers:
              'Content-Type': 'application/x-www-form-urlencoded'
          .success (data) ->
            if data['result'] is 'success'
              $scope.formData.activityName = data['activityName']
              $scope.formData.name = data['name']
              $scope.formData.mobile = data['mobile']
              $scope.formData.restaurantName = data['restaurantName']
              $scope.formData.productor = data['productor']
              $scope.formData.orderTime = data['orderTime']
              $scope.formData.lineName = data['lineName']
              $scope.formData.restaurantId = data['restaurantId']
              $scope.formData.address = data['address']
              $scope.formData.city = data['city']
              vm.params['tags'] = data['product']
              $http
                  method: 'POST'
                  url: '/api/uhkklp/west-pro-activity/get-product-list'
                  data: $.param activityName : $scope.formData.activityName
                  headers:
                    'Content-Type': 'application/x-www-form-urlencoded'
              .success (data) ->
                if data['code'] is 200
                  angular.forEach(data.list[0].promotionProducts, (item) ->
                      vm.products.push(item);
                  )
                angular.forEach vm['products'], (item) ->
                  angular.forEach vm.params['tags'], (it) ->
                    console.log 'item: ' +  item.name + " it: " + it
                    if item.name is it
                      item.check = true
                if vm['products'].length is vm.params['tags'].length
                  vm['tagAll'] = true
            else
              notificationService.error 'registration_edit_error', false
          .error (data) ->
            notificationService.error 'registration_edit_error', false

      $scope.show = ->
        $scope.user.district = ''

      $scope.reset = ->
        $scope.submitted = false

      vm.selectAllTag = ->
        if vm['tagAll']
          items = []
          angular.forEach vm['products'], (item) ->
            items.push item['name']
            item.check = true
          vm.params['tags'] = items
        else
          angular.forEach vm['products'], (item) ->
            item.check = false
          vm.params['tags'] = []
        return

      vm.selectTag = (tag) ->
        index = $.inArray(tag['name'], vm.params['tags'])
        if tag.check
          if index is -1
            vm.params['tags'].push tag['name']
        else
          if index isnt -1
            vm.params['tags'].splice(index, 1)

        if vm.params['tags'].length is vm['products'].length
          vm['tagAll'] = true
        else
          vm['tagAll'] = false
        return

      $scope.pictureClick = (product) ->
        product.check = not product.check
        vm.selectTag(product)

      $scope.submit = ->
        if $scope.formData.activityName is '' or not $scope.formData.activityName?
            $scope.isClickable = false
            $scope.submitted = true
            # notificationService.error 'activityName_required', false
            notificationService.error 'all_required', false
            return
        $scope.isClickable = true
        $http
            method: 'POST'
            url: '/api/uhkklp/west-pro-activity/validate-order-time'
            data: $.param
              activityName : $scope.formData.activityName
            headers:
              'Content-Type': 'application/x-www-form-urlencoded'
        .success (data) ->
          if data['code'] is 1
            notificationService.error 'late_error', false
            $scope.isClickable = false
            return
          else if data['code'] is 2
            notificationService.error 'early_error', false
            $scope.isClickable = false
            return
          else if data['code'] is 3
            if vm.params.tags.length is 0
              notificationService.warning 'product_required', false
              $scope.isClickable = false
              return
            if $scope.orderForm.$invalid or $scope.showError
              notificationService.error 'all_required', false
              $scope.isClickable = false
              return
            $scope.formData.product = vm.params.tags
            $scope.formData.id = localStorageService.getItem(config.  keys.currentUser).id
            # console.log $scope.formData.product
            if typeof $location.search().id isnt 'boolean'
              url = '/api/uhkklp/order/update'
              $scope.formData._id = $location.search().id
            else
              url = '/api/uhkklp/order/save'
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
                notificationService.success 'order_save_repeat', false
              else if data['code'] is '20000'
                notificationService.error 'registration_tag_fail', false
              else
                notificationService.error 'recipe_edit_save_error', false
            .error (data) ->
              $scope.isClickable = false
              notificationService.error 'recipe_edit_save_error', false
          else if data['code'] is 4
            notificationService.error 'fail_validate', false
            $scope.isClickable = false
            return
          else if data['code'] is 5
            notificationService.error 'no_activity', false
            $scope.isClickable = false
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
              if item.name is '餐廳地址'
                $scope.formData.address = item.value
              if item.name is '餐廳縣市'
                $scope.formData.city = item.value
            ) for item in data.properties
            $scope.isEnable = false
          .error (data) ->
            $scope.isEnable = false
            notificationService.error 'validate_error', false

      vm
  ]

