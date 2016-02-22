define [
  'wm/app'
  'wm/config'
], (app, config) ->
  app.registerController 'wm.ctrl.uhkklp.edit.video', [
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
      title = if $stateParams.id then 'mt_tt_editvideo' else 'mt_tt_newvideo'
      $scope.breadcrumb = [
        {
          text: 'uhkklp_video'
          href: '/uhkklp/video'
        }
        title
      ]

      if not $stateParams.id
        $timeout ->
          $scope.video =
            title: ''
            url: ''
            imgUrl: ''
            position: 'horizontal'
          return
      else
        url = '/api/uhkklp/video/get?id=' + $stateParams.id
        ($http.get url).success (data) ->
          $scope.video = data
          return

      $scope.submitting = false
      $scope.submit = ->
        if $scope.submitting
          return
        $scope.submitting = true
        $scope.submitted = true

        if $scope.videoForm.$invalid
          $scope.submitting = false
          return

        $http
          url: '/api/uhkklp/video/save'
          method: 'POST'
          headers:
            'Content-Type': 'application/json'
          data: JSON.stringify $scope.video
        .success (data) ->
          if data.code is '1'
            $rootScope.uhkklp_video_tip = 'mt_fm_create_succ'
          if data.code is '2'
            $rootScope.uhkklp_video_tip = 'mt_fm_edit_succ'
          $location.url '/uhkklp/video'
          return

        return

      return
  ]

  app.registerDirective "mtvFormat", [
    'validateService'
    '$filter'
    (validateService, $filter) ->
      return {
        restrict: 'A'
        require: 'ngModel'
        scope:
          mtvFormat: '@'
          formatType: '@'
        link: (scope, elem, attr, ngModel) ->
          reg = new RegExp scope.mtvFormat, 'i'
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
                if scope.formatType is 'url' and /^((http|https|ftp):\/\/)?(w{3}\.)?[\.\w-]+(?=\.[a-z])\.[a-z]+(\/[\S]*)*$/i.test val
                  tip = $filter('translate')('mt_ck_url_unsupported')
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
