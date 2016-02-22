define [
  'wm/app'
  'wm/config'
], (app, config) ->
  app.registerController 'wm.ctrl.store.shelf', [
    'restService'
    '$stateParams'
    '$modal'
    '$scope'
    '$location'
    'notificationService'
    'validateService'
    '$filter'
    'utilService'
    (restService, $stateParams, $modal, $scope, $location, notificationService, validateService, $filter, utilService) ->
      vm = this

      vm.cacheCheckRows = []
      vm.shelveType = 'now'
      vm.showOnShelvesModal = false
      vm.breadcrumb = [
        'goods_shelf'
      ]

      vm.update = {
        storeGoodsIds: {}
        status: ''
        onSaleTime: ''
      }

      vm.currentPage = $location.search().currentPage or 1
      vm.pageSize = $location.search().pageSize or 10
      vm.isShow = false
      vm.hasSelectedCondition = false
      vm.categories = []

      vm.selectStatus = [
        {
          text: 'customer_follower_all'
          value: 0
        }
        {
          text: 'product_onshelves'
          value: 'on'
        }
        {
          text: 'product_offshelves'
          value: 'off'
        }
      ]
      vm.status = vm.selectStatus[0].value

      vm.list = {
        columnDefs: [
          {
            field: 'number'
            label: 'store_goods_number'
            type: 'link'
            cellClass: 'text-el'
          }, {
            field: 'goodsName'
            label: 'store_promotion_goods_name'
            type: 'goodsIcon'
            seperate: true
          }, {
            field: 'price'
            label: 'price'
          }, {
            field: 'categoryName'
            label: 'product_goods_category'
            cellClass: 'text-el'
          }, {
            field: 'onSaleTime'
            label: 'store_shelf_date'
            type: 'date'
            sortable: true
            desc: true
          }, {
            field: 'shelveStatus'
            label: 'store_onshelves'
            type: 'status'
          }, {
              field: 'operations'
              label: 'operations'
              type: 'operation'
              cellClass: 'goods-operations-cell goods-cell-vertical'
              headClass: 'goods-operations-cell'
          }
        ],
        data: []
        selectable: true
        deleteTitle: 'product_item_delete'

        editHandler: (idx) ->
          $location.url '/store/edit/shelf/' + $stateParams.id + '?id=' + vm.list.data[idx].id

        deleteHandler: (idx) ->
          restService.del config.resources.deleteStoreGoods + '/' + vm.list.data[idx].id, (data) ->
            getGoods()
            notificationService.success 'product_delete_success'

        switchHandler: (idx) ->
          item = vm.list.data[idx]
          item.status = if item.status is 'on' then 'off' else 'on'
          vm.update =
            status: item.status
            storeGoodsIds: [item.id]
            onSaleTime: ''
          updateStatus()

        selectHandler: (checked, idx) ->
          if idx?
            goods = vm.list.data[idx]
            if checked
              vm.cacheCheckRows.push goods.id if $.inArray(goods.id, vm.cacheCheckRows) is -1
            else
              position = $.inArray(goods.id, vm.cacheCheckRows)
              vm.cacheCheckRows.splice position, 1 if position > -1
          else
            rememberCheck()
        sortHandler: (colDef) ->
          key = colDef.field
          value = if colDef.desc then 'desc' else 'asc'
          vm.orderBy = "{\"#{key}\":\"#{value}\"}"
          vm.currentPage = 1
          getGoods()
      }

      init = ->
        getGoods()
        getAllCategories()

      rememberCheck = ->
        for item in vm.list.data
          cacheCheck(item)

      isCacheCheckAll = ->
        if vm.cacheCheckRows.length is vm.totalCount
          vm.list.checkAll = true

      cacheCheck = (item) ->
        isInCache = $.inArray(item.id, vm.cacheCheckRows)
        if item.checked and isInCache is -1
          vm.cacheCheckRows.push item.id

        if not item.checked and isInCache isnt -1
          vm.cacheCheckRows.splice isInCache, 1
        return

      getParams = ->
        vm.checkedCategories = []
        if vm.categories
          items = []
          for item in vm.categories
            if item.check
              items.push item.id
              vm.checkedCategories.push item.name
          categoryIdItems = items.join ','

        params =
          'storeId': $stateParams.id
          'per-page': vm.pageSize
          'page': vm.currentPage
          'status': vm.status

        vm.selectedStatu = utilService.getArrayElem vm.selectStatus, vm.status, 'value' if vm.selectStatus

        params.searchKey = vm.searchKey if vm.searchKey
        params.saleTimeFrom = vm.startDate if vm.startDate
        params.saleTimeTo = vm.endDate if vm.endDate

        params.priceFrom = parseFloat(vm.startPrice) if vm.startPrice
        params.priceTo = parseFloat(vm.endPrice) if vm.endPrice
        params.orderBy = vm.orderBy if vm.orderBy
        params.categoryIds = angular.copy categoryIdItems

        hasCondition = vm.checkedCategories.length or params.saleTimeFrom or params.saleTimeTo or params.priceFrom or params.priceTo or vm.status
        vm.hasSelectedCondition = hasCondition

        vm.params = angular.copy params

      restoreParams = ->
        if vm.params
          vm.status = vm.params.status
          vm.selectedStatu = utilService.getArrayElem vm.selectStatus, vm.status, 'value' if vm.selectStatus
          vm.startDate = vm.params.saleTimeFrom
          vm.endDate = vm.params.saleTimeTo
          vm.startPrice = vm.params.priceFrom
          vm.endPrice = vm.params.priceTo
          vm.checkedCategories = []

          angular.forEach vm.categories, (item) ->
            item.check = false
            if vm.params.categoryIds and vm.params.categoryIds.indexOf(item.id) isnt -1
              item.check = true
              vm.checkedCategories.push item.name
          vm.categoryAll = vm.checkedCategories.length is vm.categories.length

      clearCheck = ->
        vm.cacheCheckRows = []
        vm.list.checkAll = false

      updateStatus = ->
        restService.put config.resources.updateStoreSale, vm.update, (data) ->
          if data
            clearCheck()
            getGoods()

      offShelveshandler = (params) ->
        vm.update.status = 'off'
        delete vm.update.onSaleTime
        vm.update.storeGoodsIds = []
        if params
          if params is 'All'
            vm.update.storeGoodsIds = angular.copy vm.cacheCheckRows
          else
            angular.forEach params, (id) ->
              vm.update.storeGoodsIds.push id if id
        updateStatus()

      deleteShelf = (param) ->
        if param
          if param is 'All'
            param = angular.copy vm.cacheCheckRows
          ids = ''
          for id,index in param
            para = {}
            if index < param.length - 1
              ids += id
              ids += ','
            else
              ids += id
        restService.del config.resources.deleteStoreGoods + '/' + ids, (data) ->
          notificationService.success 'product_delete_success'
          clearCheck()
          getGoods()

      rememberCheck = ->
        for row in vm.list.data
          cacheCheck(row)

      getAllCategories = ->
        restService.get config.resources.categories, (data) ->
          if data
            for item in data.items
              if item.name is 'service'
                item.name = $filter('translate')('category_service')
              item =
                id: item.id
                name: item.name
                check: false
              vm.categories.push item

      getGoods = ->

        getParams()

        restService.get config.resources.storeGoodsList, vm.params, (data) ->
          if data
            categories  = []
            angular.forEach data.items, (item) ->
              defaultUrl = item.pictures[0] if item.pictures? and item.pictures.length > 0
              item.number =
                  text: item.sku
                  link: '/store/view/shelf/' + $stateParams.id + '?id=' + item.id
              item.goodsName =
                  name: item.productName
                  url: defaultUrl or ''
              item.price = $filter('currency')(item.price, '￥')
              if item.categoryName is 'service'
                item.categoryName = $filter('translate')('category_service')
              item.categoryName = item.categoryName
              item.onSaleTime = if item.onSaleTime then item.onSaleTime else '-'
              if item.status is 'on'
                item.shelveStatus = 'ENABLE'
              else
                item.shelveStatus = 'DISABLE'
              item.operations = [
                {
                  name: 'edit'
                }, {
                  name: 'delete'
                }
              ]
              if item.status is 'on'
                item.operations[0].disable = true
                item.operations[0].title = 'product_cannot_edit_tip'
                item.operations[1].disable = true
                item.operations[1].title = 'product_cannot_delete_tip'

              #table check
              item.checked = false
              if $.inArray(item.id, vm.cacheCheckRows) isnt -1
                item.checked = true

            vm.list.data = data.items

            vm.list.checkAll = vm.list.data.filter((item) ->
              return item.checked
            ).length is vm.list.data.length and vm.list.data.length

            vm.list.data = data.items
            vm.totalCount = data._meta.totalCount
            vm.pageCount = data._meta.pageCount
            vm.pageSize = data._meta.perPage

      restorePrice = ->
        validateService.restore($('#startPrice'))
        validateService.restore($('#endPrice'))
        validateService.restore($('#priceWrap'))
        return

      vm.clear = ->
        vm.categoryAll = false
        if vm.categories
          angular.forEach vm.categories, (category) ->
            category.check = false
        vm.status = vm.selectStatus[0].value
        vm.selectedStatu = null
        vm.startDate = null
        vm.endDate = null
        vm.startPrice = ''
        vm.endPrice = ''
        vm.orderBy = null
        vm.searchKey = ''

        restorePrice()


      vm.changeSelect = ->
        vm.startDate = null
        vm.endDate = null

      vm.removeCondition = (type) ->
        switch type
          when 'categories'
            vm.checkedCategories = []
            vm.selectAllCatogories false
            vm.params.categoryIds = []
          when 'status'
            vm.status = 0
            vm.params.status = 0
            vm.selectedStatu = null
          when 'date'
            vm.startDate = null
            vm.endDate = null
            vm.params.saleTimeFrom = null
            vm.params.saleTimeTo = null
          when 'price'
            vm.startPrice = ''
            vm.endPrice = ''
            vm.params.priceFrom = ''
            vm.params.priceTo = ''

        vm.currentPage = 1
        getGoods()

      # show and hide the condition.
      vm.showConditions = ->
        vm.isShow = not vm.isShow
        restoreParams() if not vm.isShow

      # add new goods.
      vm.newGoods = ->
        $location.url "/store/create/shelf/#{$stateParams.id}"

      # select all the condition.
      vm.selectAllCatogories = (ischeckAll) ->
        if not ischeckAll
          vm.categoryAll = ischeckAll
        if vm.categories
          angular.forEach vm.categories, (category) ->
            category.check = ischeckAll

      vm.selectCategory = (isCheck) ->
        if not isCheck
          vm.categoryAll = isCheck
        else
          vm.categoryAll = vm.categories.filter( (item) ->
              return item.check
          ).length is vm.categories.length

      vm.search = ->
        if not vm.checkPrice(vm.startPrice, vm.endPrice)
          restorePrice()
          clearCheck()
          vm.currentPage = 1
          getGoods()
          vm.list.emptyMessage = 'search_no_data'

      vm.showShelveModal = ->
        vm.showOnShelvesModal = not vm.showOnShelvesModal
        if vm.showShelveModal
          vm.shelveType = 'now'
          vm.onSaleTime = null

      vm.onShelves = ->
        vm.update.status = 'on'
        vm.update.storeGoodsIds = []
        if vm.shelveType is 'schedule'
          vm.update.status = 'off'
          if vm.onSaleTime and vm.onSaleTime <= moment().valueOf()
            validateService.showError $('#schedulePicker'), $filter('translate')('product_goods_onshelve_time_safe')
            return
          else
            vm.update.onSaleTime = vm.onSaleTime
        else
          vm.update.onSaleTime = moment().valueOf()

        id = []
        if vm.cacheCheckRows
          for item in vm.cacheCheckRows
            id.push item
        vm.update.storeGoodsIds = angular.copy id
        updateStatus()
        vm.showOnShelvesModal = false

      vm.changeSize = (pageSize) ->
        vm.pageSize = pageSize
        vm.currentPage = 1
        getGoods()

      vm.changePage = (currentPage) ->
        vm.currentPage = currentPage
        getGoods()

      vm.offShelves = ($event) ->
        param = angular.copy vm.cacheCheckRows

        notificationService.confirm $event, {
          title: 'store_delte_shelf_goods'
          submitCallback: offShelveshandler
          params: [param]
        }

      # delete the items which is selected.
      vm.deleteGoods = ($event) ->
        if vm.list.checkAll
          param = 'All'
        else
          param = angular.copy vm.cacheCheckRows

        notificationService.confirm $event, {
          title: 'product_goods_delete_tip'
          submitCallback: deleteShelf
          params: [param]
        }

      vm.changeShelveType = ->
        if vm.shelveType is 'schedule'
          vm.onSaleTime = moment().valueOf()
        else
          vm.onSaleTime = null

      vm.checkPrice = (start, end) ->
        if start or end
          tip = ''
          reg = /^(([1-9]\d*)|0)(\.\d{1,2})?$/
          if start and not reg.test start
            tip = 'product_promotion_basic_times_tip'
            validateService.highlight($('#startPrice'), '')
          else if end and not reg.test end
            tip = 'product_promotion_basic_times_tip'
            validateService.highlight($('#endPrice'), '')
          else if start and end and Number(start) > Number(end)
            tip = 'store_price_error_tip'
            validateService.highlight($('#startPrice'), '')
            validateService.highlight($('#endPrice'), '')
          validateService.highlight($('#priceWrap'), $filter('translate')(tip))
          tip

      $('#startPrice').focus ->
        restorePrice()

      $('#endPrice').focus ->
        restorePrice()

      init()

      vm
  ]
