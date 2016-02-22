define [
  'wm/app'
  'wm/config'
], (app, config) ->
  app.registerController 'wm.ctrl.product.product', [
    'restService'
    'notificationService'
    '$location'
    '$scope'
    (restService, notificationService, $location, $scope) ->
      vm = this
      vm.currentPage = $location.search().currentPage or 1
      vm.pageSize = $location.search().pageSize or 10
      vm.totalCount = 0

      vm.breadcrumb = [
        icon: 'product'
        text: 'product_management'
      ]

      vm.tabs = [
        {
          active: true
          name: 'product'
        }
        {
          active: false
          name: 'category_service'
        }
      ]
      vm.curTab = vm.tabs[0]
      vm.categoryType = 'product'

      # Table definitions
      vm.realList = {
        columnDefs: [
          {
            field: 'goodsId'
            label: 'product_goods_number'
            type: 'link'
          }, {
            field: 'goodsName'
            label: 'product_goods_name'
            type: 'goodsIcon'
          }, {
            field: 'codeNumber'
            label: 'product_promotion_code_number'
          }, {
              field: 'operations'
              label: 'operations'
              type: 'operation'
              cellClass: 'goods-operations-cell'
              headClass: 'goods-operations-cell'
          }
        ],
        data: []
        deleteTitle: 'product_item_delete'
        hasLoading: true
        emptyMessage: 'no_data'
        editHandler: (idx) ->
          $location.url '/product/edit/product/' + vm.list.data[idx].id + '?type=' + vm.categoryType

        deleteHandler: (idx) ->
          _deleteGoods(idx)
      }

      # Add goods
      vm.newGoods = ->
        $location.url '/product/edit/product?type=' + vm.categoryType

      _deleteGoods = (idx) ->
        restService.del config.resources.product + '/' + vm.list.data[idx].id, (data) ->
          _getAllGoods()
          notificationService.success vm.successTip

      _getAllGoods = ->
        params =
          'per-page': vm.pageSize
          page: vm.currentPage
          orderBy: '{"createdAt": "desc"}'
          expand: 'isAssociated'
          categoryType: vm.categoryType

        restService.get config.resources.products, params,(data) ->
          categories  = []
          angular.forEach data.items, (category) ->
            category.goodsId =
              text: category.sku
              link: '/product/view/product/' + category.id
            category.goodsName =
              name: category.name
              url: if category.pictures? and category.pictures.length > 0 then category.pictures[0].url else ''
            category.codeNumber = category.codeNum or '-' if vm.curTab.name is vm.tabs[0].name

            category.operations = [
              {
                name: 'edit'
                disable: false
              }, {
                name: 'delete'
                disable: (category.isAssociated or category.isSelected or category.isReservationGoods)
              }
            ]

            categories.push category

          vm.list.data = angular.copy categories
          vm.list.hasLoading = false
          vm.totalCount = data._meta.totalCount

      _init = ->
        curTab = parseInt($location.search().active)
        vm.curTab = vm.tabs[if not isNaN(curTab) and curTab < vm.tabs.length then curTab else 0]
        vm.changeTab()
        return

      vm.changeSize = (pageSize) ->
        vm.pageSize = pageSize
        vm.currentPage = 1
        _getAllGoods()
        return

      vm.changePage = (currentPage) ->
        vm.currentPage = currentPage
        _getAllGoods()
        return

      vm.changeTab = ->
        vm.currentPage = $location.search().currentPage or 1
        vm.pageSize = $location.search().pageSize or 10
        vm.list = angular.copy vm.realList
        if vm.curTab.name is vm.tabs[0].name
          vm.successTip = 'product_delete_success'
          vm.categoryType = 'product'
        else
          vm.list.columnDefs[0].label = 'product_service_id'
          vm.list.columnDefs.splice 2, 1
          vm.list.deleteTitle = ''
          vm.successTip = 'product_delete_success'
          vm.categoryType = 'reservation'
        _getAllGoods()

      _init()

      vm
  ]
