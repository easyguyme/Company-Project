define [
  'wm/app'
  'wm/config'
], (app, config) ->
  app.registerController 'wm.ctrl.uhkklp.products', [
    '$scope'
    '$http'
    'notificationService'
    '$location'
    'validateService'
    '$rootScope'
    '$filter'
    ($scope, $http, notificationService, $location, validateService, $rootScope, $filter) ->

      if $rootScope.uhkklp_product_tip
        notificationService.success $rootScope.uhkklp_product_tip, false
        delete $rootScope.uhkklp_product_tip

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
          name: 'mt_product_list'
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
            label: 'mt_tb_ID'
          }
          {
            field: 'name'
            label: 'mt_producttb_name'
          }
          {
            field: 'url'
            label: 'mt_producttb_url'
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
          $location.url '/uhkklp/edit/products/' + $scope.list.data[item].id
          return
        deleteHandler: (item) ->
          url = '/api/uhkklp/product/delete'
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
          tmp.name = data.name
          tmp.url = data.url
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
          url: '/api/uhkklp/product/get-list'
          method: 'POST'
          headers:
            'Content-Type': 'application/json'
          data: params
        .success (data) ->
          $scope.totalCount = data.dataCount
          results = _formatResult data.product
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
