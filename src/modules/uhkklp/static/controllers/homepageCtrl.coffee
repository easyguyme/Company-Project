define [
  'wm/app'
  'wm/config'
], (app, config) ->
  app.registerController 'wm.ctrl.uhkklp.homepage', [
    'restService'
    '$scope'
    '$http'
    'notificationService'
    '$location'
    'validateService'
    '$filter'
    (restService, $scope, $http, notificationService, $location, validateService, $filter) ->
      vm = this
      $scope.breadcrumb = [
        'uhkklp_homepage'
      ]

      $scope.tabs = [
        {
          name: 'uhkklp_homepage'
          value: 0
        }
        {
          name: 'mt_newset_news'
          value: 1
        }
      ]
      tabVal = $location.search().active
      $scope.curTab = if tabVal then $scope.tabs[tabVal] else $scope.tabs[0]
      if tabVal is "1" or tabVal is 1
        $scope.showFirstTab = false
        $scope.showSecondTab = true
      else
        $scope.showFirstTab = true
        $scope.showSecondTab = false
      $scope.changeTab = ->
        tabVal = $location.search().active
        if tabVal is "1" or tabVal is 1
          $scope.showFirstTab = false
          $scope.showSecondTab = true
        else
          $scope.showFirstTab = true
          $scope.showSecondTab = false

      isSubmit = true
      $scope.homeinfo =
        type: 'video'
        videoContent: {
          videoUrl: '',
          newsId: ''
        }
        imgContent: []

      ($http.get '/api/uhkklp/homepage/get').success (data) ->
        if data
          $scope.homeinfo = data
        return

      _beforeSubmit = ->
        if ($scope.homeinfo.type isnt 'none')
          (
            _validateEmpty 'news_id' + i
            if $scope.homeinfo.imgContent[i].imgUrl is ''
              isSubmit = false
          )for v, i in $scope.homeinfo.imgContent

        if ($scope.homeinfo.type is 'image-text')
          $scope.homeinfo.videoContent.videoUrl = ''
          $scope.homeinfo.videoContent.newsId = ''

        if ($scope.homeinfo.type is 'video')
          _validateEmpty 'videoUrl'
          _validateEmpty 'videoNewsId'
          (
            $scope.homeinfo.imgContent[i].newsId = ''
          )for v, i in $scope.homeinfo.imgContent

        (
          delete $scope.homeinfo.imgContent[i].$$hashKey
          delete $scope.homeinfo.imgContent[i].imgId
        )for v, i in $scope.homeinfo.imgContent

        return

      $scope.submit = ->
        isSubmit = true
        _beforeSubmit()
        $scope.submitted = true

        if $scope.homeinfo.imgContent.length <= 0 and $scope.homeinfo.type isnt 'none'
          notificationService.error 'mt_fm_add_one_pic', false
          return

        if not isSubmit
          return

        url = '/api/uhkklp/homepage/save'

        $http
          url: url
          method: 'POST'
          headers:
            'Content-Type': 'application/json'
          data: JSON.stringify $scope.homeinfo
        .success (data) ->
          $scope.homeinfo = data
          notificationService.success 'mt_fm_save_succ', false
          return

        return

      $scope.newImgContent_click = ->
        $scope.submitted = false
        $scope.homeinfo.imgContent.push {
          imgUrl: ''
          imgId: ''
        }
        return

      Array.prototype.remove = (item) ->
        if isNaN item
          (
            if item is val
              item = index
              break
          )for val, index in this

        (
          this[index] = this[++index]
        )for val, index in this when index >= item and index < this.length - 1

        this.length--
        return

      $scope.rmImgContent_click = (index, event) ->
        notificationService.confirm event, {
            submitCallback:
              ->
                $scope.$apply( ->
                  $scope.homeinfo.imgContent.remove index
                )
          }
        return
      _validateEmpty = (id) ->
        if $('#' + id).val() is ''
          result = true
          validateService.highlight($('#' + id), $filter('translate')('cookingtype_empty_tip'))
          isSubmit = false
        else
          result = false
        result

      $scope.updatedTimeList =
        columnDefs: [
          {
            field: 'updatedTimeId'
            label: 'id'
          }
          {
            field: 'updateTime'
            label: 'update_news_time'
            sortable: true
          }
          {
            field: 'operations'
            label: 'operations'
            type: 'operation'
          }
        ]
        data: []
        hasLoading: true

        sortHandler: (colDef) ->
          if colDef.field?
            $scope.sortName = colDef.field
            if colDef.desc
              $scope.sortDesc = 'ASC'
            else
              $scope.sortDesc = 'DESC'
          _getUpdatedTimeList()

        editHandler: (item) ->
          tabVal = $location.search().active
          $location.url '/uhkklp/edit/homepage/' + $scope.updatedTimeList.data[item].updatedTimeId + "?active=1"

        deleteHandler: (item) ->
          $http
            method: 'POST'
            url: '/api/uhkklp/updated-news-time/delete'
            data: $.param updatedTimeId: $scope.updatedTimeList.data[item].updatedTimeId
            headers:
              'Content-Type': 'application/x-www-form-urlencoded'
          .success (data) ->
            if data['code'] is 200
              notificationService.success 'update_news_time_list_delete_success_tip', false
              _getUpdatedTimeList()
            else
              notificationService.error 'update_news_time_list_delete_failed_tip',false
          .error (data) ->
            notificationService.error 'update_news_time_list_delete_failed_tip',false

      _getUpdatedTimeCount = ->
        $url = '/api/uhkklp/updated-news-time/get-count'
        $http
          method: 'GET'
          url: $url
          headers:
            'Content-Type': 'application/x-www-form-urlencoded'
        .success (data) ->
          console.log data['result']
          if data['code'] is 200
            $scope.totalCount = data['result']
          else
            notificationService.error 'update_news_time_list_count_error', false
        .error (data) ->
          notificationService.error 'update_news_time_list_count_error', false

      $scope.sortName = null
      $scope.sortDesc = null
      $scope.page = 1
      $scope.pageSize = 10
      _getUpdatedTimeList = ->
        _getUpdatedTimeCount()
        $url = '/api/uhkklp/updated-news-time/get?page=' + $scope.page + '&pageSize=' + $scope.pageSize
        if $scope.sortName?
          $url = $url + '&sortName=' + $scope.sortName + '&sortDesc=' + $scope.sortDesc
        $http
          method: 'GET'
          url: $url
          headers:
            'Content-Type': 'application/x-www-form-urlencoded'
        .success (data) ->
          console.log data['result']
          if data['code'] is 200
            updatedTimeList = data['result']
            for item in updatedTimeList
              item.operations = [
                {
                  name: 'edit'
                }
                {
                  name: 'delete'
                }
              ]
            $scope.updatedTimeList.data = updatedTimeList
            $scope.updatedTimeList.hasLoading = false
          else
            notificationService.error 'update_news_time_list_error', false
            $scope.updatedTimeList.hasLoading = false
        .error (data) ->
          notificationService.error 'update_news_time_list_error', false
          $scope.updatedTimeList.hasLoading = false

      _getUpdatedTimeList()

      vm
  ]

  app.registerDirective "mtFormat", [
    'validateService'
    '$filter'
    (validateService, $filter) ->
      return {
        restrict: 'A'
        require: 'ngModel'
        scope:
          mtFormat: '@'
          formatType: '@'
        link: (scope, elem, attr, ngModel) ->
          reg = new RegExp scope.mtFormat, 'i'
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
