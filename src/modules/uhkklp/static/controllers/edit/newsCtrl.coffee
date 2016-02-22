define [
  'wm/app'
  'wm/config'
], (app, config) ->
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
            shareUrl: '@'
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
                  if scope.shareUrl == 'share'
                    validateService.restore elem, ''
                    return
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
  app.registerController 'wm.ctrl.uhkklp.edit.news', [
    'restService'
    '$stateParams'
    '$scope'
    '$filter'
    '$location'
    'notificationService'
    'validateService'
    (restService, $stateParams, $scope, $filter, $location, notificationService, validateService) ->
      vm = this

      LIST_URL = '/uhkklp/news'

      vm.submitting = false
      vm.categorys = [
        {
          text: 'news_list_tab_post'
          value: 1
        }
        {
          text: 'news_list_tab_preference'
          value: 4
        }
        {
          text: 'news_list_tab_audio'
          value: 3
        }
      ]
      vm.gotos = [
        {
          text: 'news_list_web'
          value: 0
          tip: 'news_list_web_tip'
        }
        {
          text: 'news_list_news_id'
          value: 1
          tip: 'news_list_news_id_tip'
        }
        {
          text: 'news_list_recipe_id'
          value: 2
          tip: 'news_list_recipe_id_tip'
        }
        {
          text: 'news_list_scan'
          value: 3
          tip: 'news_list_scan_tip'
        }
      ]
      vm.category = vm.categorys[0].value
      vm.goto = vm.gotos[0].value
      vm.gotoTip = $filter('translate')(vm.gotos[0].tip)
      vm.thumbnailDefaultUrl = 'http://vincenthou.qiniudn.com/7b5b0748f2e09e40c251d057.jpg'
      vm.thumbnailUrl = vm.thumbnailDefaultUrl
      vm.imgDefaultUrl = ''
      vm.imgUrl = vm.imgDefaultUrl
      vm.isVideo = 'N'
      vm.submitting = false
      vm.submitted = false
      vm.isSubmitted = false
      vm.youtube = ''
      vm.isLatest = 'N'
      vm.shareBtnTxt = $filter('translate')('前往了解')

      vm.changeGoto = (value) ->
        vm.gotoTip = $filter('translate')(vm.gotos[value].tip)

      $scope.uhkklp_news_title = 'uhkklp_news_insert'
      _getNewsById = ->
        if $stateParams.id isnt undefined
          params =
            device_id: 0
            news_id: $stateParams.id
          $scope.uhkklp_news_title = 'uhkklp_news_edit'

          restService.get config.resources.newsGet, params, (data) ->
            if data
              $scope.news.startDate = data.data.begin
              vm.category = data.data.icon
              $scope.news.title = data.data.title
              vm.thumbnailUrl = data.data.thumbnail
              $scope.news.content = data.data.content
              vm.imgUrl = data.data.image
              vm.isLatest = data.data.is_Latest
              vm.isVideo = data.data.is_video
              vm.youtube = data.data.youtube_url
              $scope.news.shareUrl = data.data.share_url
              vm.goto = data.data.more_info.type
              $scope.news.gotoInfo = data.data.more_info.info
              vm.shareBtnTxt = data.data.share_btn_txt
        return

      _getNewsById()

      vm.submit = ->
        if vm.submitting
          return

        if vm.thumbnailUrl == vm.thumbnailDefaultUrl or vm.imgUrl == vm.imgDefaultUrl
          vm.isSubmitted = true
          notificationService.warning '請正確填寫所有必填項！', true
          return
        else
          vm.isSubmitted = false

        if $scope.newsForm.$invalid
          notificationService.warning '請正確填寫所有必填項！', true
          vm.submitting = false
          return

        if !vm.checkYoutube('youtubeUrl')
          notificationService.warning '請正確填寫所有必填項！', true
          return

        if vm.goto != 3 &&  $scope.news.gotoInfo == ''
          validateService.highlight($('#gotoInfoTxt'), $filter('translate')('cookingtype_empty_tip'))
          notificationService.warning '請正確填寫所有必填項！', true
          return

        if $stateParams.id isnt undefined
          news_id = $stateParams.id
        else
          news_id = 0

        more =
          type: vm.goto
          info: $scope.news.gotoInfo

        params =
          newsId: news_id
          begin: $scope.news.startDate
          icon: vm.category
          title: $scope.news.title
          thumbnail: vm.thumbnailUrl
          isTop: 'N'
          isLatest: vm.isLatest
          content: $scope.news.content
          imgUrl: vm.imgUrl
          isVideo: vm.isVideo
          youtubeUrl: vm.youtube
          shareUrl: if $scope.news.shareUrl is undefined then "" else $scope.news.shareUrl
          moreInfo: more
          shareBtnTxt: vm.shareBtnTxt

        vm.submitting = true
        restService.post config.resources.newsSave, params, (data) ->
          if data.code is 200
            notificationService.success 'news_save_success', false
            if vm.category == 4
              $location.url LIST_URL + '?active=' + 2
            else
              $location.url LIST_URL + '?active=' + vm.category
          else
            notificationService.error 'news_save_fail', false

          vm.submitting = false
          return
        return

      vm.checkYoutube = (id) ->
        result = false

        if vm.isVideo == 'N'
          result = true
        else
          if vm.youtube == ''
            result = false
          else
            result = true

        if !result
          validateService.highlight($('#' + id), $filter('translate')('cookingtype_empty_tip'))
        result
      vm.goBack =()->
        history.go(-1)
        return
      vm
  ]


