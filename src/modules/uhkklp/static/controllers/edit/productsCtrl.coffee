define [
  'wm/app'
  'wm/config'
], (app, config) ->
  app.registerController 'wm.ctrl.uhkklp.edit.products', [
    '$scope'
    '$http'
    'notificationService'
    '$location'
    'validateService'
    '$stateParams'
    '$rootScope'
    '$timeout'
    ($scope, $http, notificationService, $location, validateService, $stateParams, $rootScope, $timeout) ->
      scrollTo 0, 0

      #breadcrum
      title = if $stateParams.id then 'mt_tt_editproduct' else 'mt_tt_newproduct'
      $scope.breadcrumb = [
        {
          text: 'uhkklp_product'
          href: '/uhkklp/products'
        }
        title
      ]

      if not $stateParams.id
        $timeout ->
          $scope.product =
            name: ''
            url: ''
          return
      else
        url = '/api/uhkklp/product/get?id=' + $stateParams.id
        ($http.get url).success (data) ->
          $scope.product = data
          return

      $scope.submitting = false
      $scope.submit = ->
        if $scope.submitting
          return
        $scope.submitting = true
        $scope.submitted = true

        if $scope.productForm.$invalid
          $scope.submitting = false
          return

        $http
          url: '/api/uhkklp/product/save'
          method: 'POST'
          headers:
            'Content-Type': 'application/json'
          data: JSON.stringify $scope.product
        .success (data) ->
          if data.code is '1'
            $rootScope.uhkklp_product_tip = 'mt_fm_create_succ'
          if data.code is '2'
            $rootScope.uhkklp_product_tip = 'mt_fm_edit_succ'
          $location.url '/uhkklp/products'
          return

        return

      return
  ]

  app.registerDirective "mtpFormat", [
    'validateService'
    '$filter'
    (validateService, $filter) ->
      return {
        restrict: 'A'
        require: 'ngModel'
        scope:
          mtpFormat: '@'
        link: (scope, elem, attr, ngModel) ->
          reg = new RegExp scope.mtpFormat, 'i'
          elem.on 'keyup blur focus', ->
            if ($.inArray 'ng-pristine', elem.context.classList) > 0
              return
            val = elem.context.value
            if reg.test val
              validateService.restore elem, ''
            else
              if not val
                tip = $filter('translate')('mt_ck_required')
              else
                tip = $filter('translate')('mt_ck_format')
              validateService.showError elem, tip
            return

          validator = (value) ->
            validity = (ngModel.$isEmpty value) or reg.test value
            ngModel.$setValidity 'urlFormat', validity
            if validity
              return value
            return false
          ngModel.$formatters.push validator
          ngModel.$parsers.push validator

          return
      }
  ]

  return
