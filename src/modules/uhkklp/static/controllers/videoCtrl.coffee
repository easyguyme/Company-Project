define [
  'wm/app'
  'wm/config'
], (app, config) ->
  app.registerController 'wm.ctrl.uhkklp.video', [
    '$scope'
    '$http'
    'notificationService'
    '$location'
    'validateService'
    '$rootScope'
    '$filter'
    ($scope, $http, notificationService, $location, validateService, $rootScope, $filter) ->

      $scope.breadcrumb = [
        'uhkklp_video'
      ]

      if $rootScope.uhkklp_video_tip
        notificationService.success $rootScope.uhkklp_video_tip, false
        delete $rootScope.uhkklp_video_tip

      # $scope.tabs = [
      #   {
      #     name: 'recipe_list_tab_active_list',
      #     value: 0
      #   }
      #   {
      #     name: 'recipe_list_tab_inactive_list',
      #     value: 1
      #   }
      # ]
      # tabVal = $location.search().active
      # $scope.curTab = if tabVal then $scope.tabs[tabVal] else $scope.tabs[0]

      $scope.tabs = [
        {
          name: 'mt_video_list'
          value: ''
        }
      ]

      $scope.pageSize = 10
      $scope.page = 1
      $scope.totalCount = 0
      $scope.list = {
        columnDefs: [
          {
            field: 'id'
            label: 'mt_videotb_ID'
          }
          {
            field: 'title'
            label: 'mt_videotb_title'
          }
          {
            field: 'url'
            label: 'mt_videotb_url'
          }
          {
            field: 'imgPrev'
            label: 'mt_videotb_imgPrev'
            type: 'html'
          }
          {
            field: 'position'
            label: 'mt_ad_video_position'
          }
          {
            field: 'createdAt'
            label: 'mt_videotb_createdAt'
          }
          {
            field: 'creator'
            label: 'mt_videotb_creator'
          }
          {
            field: 'operations'
            label: 'operations'
            type: 'operation'
          }
        ]
        data: []
        hasLoading: true

        editHandler: (item) ->
          $location.url '/uhkklp/edit/video/' + $scope.list.data[item].id
          return
        deleteHandler: (item) ->
          url = '/api/uhkklp/video/delete'
          params =
            id: $scope.list.data[item].id
          $http
            url: url
            method: 'POST'
            headers:
              'Content-Type': 'application/json'
            data: params
          .success (data) ->
            notificationService.success 'mt_fm_delete_succ', false
            _getList()
            return
          return
      }

      _formatResult = (datas) ->
        newDatas = []
        (
          tmp = {}
          tmp.id = data._id.$id
          tmp.title = data.title
          tmp.url = data.url
          tmp.imgPrev = '<img class="mt_video_prev" src="' + data.imgUrl + '">'
          if data.position is 'horizontal'
            tmp.position = $filter('translate')('mt_rd_video_horizontal')
          else
            tmp.position = $filter('translate')('mt_rd_video_vertical')
          createdAt = new Date data.createdAt.sec * 1000
          tmp.createdAt = $filter('date')(createdAt, 'yyyy-MM-dd HH:mm')
          tmp.creator = data.creator
          tmp.operations = [
            {
              name: 'edit'
              title: "mt_edit"
            }
            {
              name: 'delete'
              title: 'mt_delete'
            }
          ]
          newDatas.push tmp
        ) for data, i in datas

        newDatas

      _getList = ->
        params =
          currentPage: $scope.page
          pageSize: $scope.pageSize
        $http
          url: '/api/uhkklp/video/get-list'
          method: 'POST'
          headers:
            'Content-Type': 'application/json'
          data: params
        .success (data) ->
          $scope.totalCount = data.dataCount
          results = _formatResult data.video
          $scope.list.data = results
          $scope.list.hasLoading = false

          return
        return
        return

      $scope.changePage = (currentPage) ->
        $scope.page = currentPage
        _getList()
        return

      $scope.changeSize = (pageSize) ->
        $scope.page = 1
        $scope.pageSize = pageSize
        _getList()
        return


      _getList()

      return
  ]

  return
