define [
  'wm/app'
  'wm/config'
], (app, config) ->
  app.registerController 'wm.ctrl.mall.goods', [
    'restService'
    'notificationService'
    '$scope'
    '$location'
    'validateService'
    '$filter'
    (restService, notificationService, $scope, $location, validateService, $filter) ->
      vm = this

      vm.params = {
        page: $location.search().currentPage or 1
        'per-page': $location.search().pageSize or 10
        orderBy: '{"order": "asc"}'
        category: ''
        searchKey: ''
        status: ''
      }

      vm.update = {
        operation: ''
        id: {}
        orderBy: '{"order": "asc"}'
        onSaleTime: ''
      }

      vm.breadcrumb = [
        'shelf_management'
      ]

      vm.cacheCheckRows = []

      vm.selectItems = [
        {
          text: 'customer_follower_all'
          value: 0
        }
        {
          text: 'product_onshelves'
          value: 'on'
        }
        {
          text: 'product_have_redeemed'
          value: 'redeem'
        }
        {
          text: 'product_offshelves'
          value: 'off'
        }
      ]

      vm.params.status = vm.selectItems[0].value
      vm.shelveType = 'now'
      vm.showOnShelvesModal = false

      vm.list = {
        columnDefs: [
          {
            field: 'number'
            label: 'product_goods_number'
            type: 'link'
            cellClass: 'text-el'
          }, {
            field: 'goodsName'
            label: 'product_promotion_goods_name'
            type: 'goodsIcon'
            seperate: true
          },{
            field: 'categoryName'
            label: 'product_goods_category'
            cellClass: 'text-el'
          }, {
            field: 'score'
            label: 'product_goods_score'
            sortable: true
            desc: true
          }, {
            field: 'remainingTotal'
            label: 'product_goods_remaining_total_amount'
            type: 'textColor'
            sortable: true
            desc: true
          }, {
            field: 'usedAmount'
            label: 'product_goods_used_count'
            sortable: true
            desc: true
          }, {
            field: 'onSaleTime'
            label: 'product_goods_sale_time'
            type: 'date'
            sortable: true
            desc: true
          }, {
            field: 'shelveStatus'
            label: 'product_onshelves'
            type: 'status'
          }, {
            field: 'order'
            label: 'product_order'
            type: 'input'
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
          $location.url "/mall/edit/goods/" + vm.list.data[idx].id

        deleteHandler: (idx) ->
          restService.del config.resources.deleteGoods + "/" + vm.list.data[idx].id, (data) ->
            _clearCheckItem(vm.list.data[idx].id)
            _getGoods()
            notificationService.success 'product_delete_success'

        switchHandler: (idx) ->
          item = vm.list.data[idx]
          item.status = if item.status is 'on' then 'off' else 'on'
          vm.update.operation = item.status
          vm.update.id = {}
          vm.update.id[item.id] = item.order
          vm.update.all = false
          vm.update.onSaleTime = ''
          _updateStatus()

        selectHandler: (checked, idx) ->
          if idx?
            goods = vm.list.data[idx]
            if checked
              vm.cacheCheckRows.push goods.id if $.inArray(goods.id, vm.cacheCheckRows) is -1
            else
              position = $.inArray(goods.id, vm.cacheCheckRows)
              vm.cacheCheckRows.splice position, 1 if position > -1
          else
            _rememberCheck()

        sortHandler: (colDef) ->
          key = colDef.field
          key = 'usedCount' if key is 'usedAmount'
          key = 'total' if key is 'remainingTotal'
          value = if colDef.desc then 'desc' else 'asc'
          vm.params.orderBy = '{"' + key + '":' + '"' + value + '"}'
          vm.params.page = 1
          _getGoods()
      }

      # table check
      _rememberCheck = ->
        for row in vm.list.data
          _cacheCheck(row)

      _cacheCheck = (row) ->
        position = $.inArray(row.id, vm.cacheCheckRows)
        if row.checked and position is -1
          vm.cacheCheckRows.push row.id
        if not row.checked and position isnt -1
          vm.cacheCheckRows.splice position, 1

      _clearCheckItem = (id) ->
        position = $.inArray(id, vm.cacheCheckRows)
        if position isnt -1
          vm.cacheCheckRows.splice position, 1

      _whetherCheckAll = ->
        if vm.cacheCheckRows.length is vm.totalCount
          vm.list.checkAll = true

      # table check

      _isCheckAll = ->
        len = 0
        for item in vm.categories
          if item.check
            len++
        if len is vm.categories.length
          vm.categoryAll = true

      _offShelvesHandler = (param) ->
        vm.update.operation = 'off'
        if $.isArray(param)
          idsJson = {}
          for item in param
            idsJson[item] = 1
          vm.update.id = idsJson
          vm.update.all = false
        else #off all
          vm.update.all = true
          vm.update.id = null
        _updateStatus()

      _deleteGoodsHandler = (param) ->
        ids = ''
        para = {}
        if $.isArray(param)
          ids = param.join(',')
        else
          ids = vm.list.data[0].id
          para = {
            all: true
          }

        restService.del config.resources.deleteGoods + '/' + ids, para, (data) ->
          notificationService.success 'product_delete_success'
          _clearCheck()
          _getGoods()


      _showConditions = ->
        vm.checkedCat = []
        items = []
        for item in vm.conditions.categories
          if item.check
            vm.checkedCat.push item.name
            items.push item
        for item in vm.selectItems
          if item.value is vm.conditions.status
            vm.selectStatus = item.text
        vm.status = angular.copy vm.params.status

      _updateStatus = ->
        restService.post config.resources.updateStatus, vm.update, (data) ->
          if vm.update.operation is 'order'
            vm.params.orderBy = '{"order": "asc"}'
          _clearCheck()
          _getGoods()
        , (data) ->
          _getGoods()

      _getGoods = ->
        if vm.conditions?.categories
          items = []
          for item in vm.categories
            if item.check
              items.push item.id
          vm.params.category = items.join ','

        restService.get config.resources.goodsList, vm.params, (data) ->
          items = data.items
          vm.totalCount = data._meta.totalCount
          for item in items
            item.number =
              text: item.sku
              link: '/mall/view/goods/' + item.id
            item.goodsName =
              name: item.productName
              url: item.pictures[0] or ''

            item.usedAmount = item.usedCount
            item.remainingTotal = {}
            if item.categoryName is 'service'
              item.categoryName = $filter('translate')('category_service')
            if item.total is ''
              item.remainingTotal.text = 'channel_wechat_mass_unlimited'
            else if item.total is 0
              item.remainingTotal.text = 'product_redeemed_all'
              item.remainingTotal.color = 'goods-used-all'
            else
              item.remainingTotal.text = item.total
            if item.status is 'on'
              item.shelveStatus = 'ENABLE'
            else
              item.shelveStatus = 'DISABLE'

            item.operations = [
              {
                name: 'edit'
              }
              {
                name: 'delete'
              }
            ]

            item.onSaleTime = item.onSaleTime or '-'

            if item.status is 'on'
              item.operations[0].disable = true
              item.operations[0].title = 'product_cannot_edit_tip'
              item.operations[1].disable = true
              item.operations[1].title = 'product_cannot_delete_tip'

            #table check
            item.checked = false
            if $.inArray(item.id, vm.cacheCheckRows) isnt -1
              item.checked = true

          vm.list.data = items

          vm.list.checkAll = vm.list.data.filter((item) ->
            return item.checked
          ).length is vm.list.data.length and vm.list.data.length

      _checkAll = (isCheckAll) ->
        for category in vm.categories
          category.check = isCheckAll

      _getAllCategories = ->
        categories = []
        restService.get config.resources.categories, (data) ->
          if data
            for items,index in data.items
              if items.name is 'service'
                items.name = $filter('translate')('category_service')
              categories.push {id: items.id, name: items.name, check: false}
            vm.categories = angular.copy categories
            return

      _checkInt = ->
        for item in vm.list.data
          if not Number(item.order) or Number(item.order) < 1
            data = {value: item.order}
            notificationService.warning 'product_goods_points_not_int', false, data
            return false
        return true

      _clearCheck = ->
        vm.cacheCheckRows = []
        vm.list.checkAll = false

      _clear = ->
        vm.categoryAll = false
        angular.forEach vm.categories, (label) ->
          label.check = false
          return
        vm.params.status = vm.selectItems[0].value

      vm.newGoods = ->
        $location.url '/mall/create/goods'

      vm.showConditions = ->
        vm.isShow = not vm.isShow
        _clear()
        if vm.conditions
          vm.categories = angular.copy vm.conditions.categories
          vm.params.status = angular.copy vm.conditions.status
          _isCheckAll()

      vm.deleteCategory = ->
        vm.checkedCat = []
        delete vm.params.category
        _checkAll false
        vm.conditions.categories = angular.copy vm.categories
        _getGoods()

      vm.deleteStatus = ->
        vm.status = 0
        vm.conditions.status = 0
        delete vm.params.status
        _getGoods()

      vm.changeShelveType = ->
        if vm.shelveType is 'schedule'
          vm.onSaleTime = moment().valueOf()
        else
          vm.onSaleTime = null

      vm.order = ->
        if _checkInt()
          idsJson = {}
          for item in vm.list.data
            idsJson[item.id] = Number(item.order)
          vm.update.id = idsJson
          vm.update.operation = 'order'
          _updateStatus()

      vm.showShelveModal = ->
        vm.showOnShelvesModal = not vm.showOnShelvesModal
        if vm.showShelveModal
          vm.shelveType = 'now'
          vm.onSaleTime = null

      vm.onShelves = ->
        vm.update.operation = 'on'
        if vm.shelveType is 'schedule'
          if vm.onSaleTime and vm.onSaleTime <= moment().valueOf()
            validateService.showError $('#schedulePicker'), $filter('translate')('product_goods_onshelve_time_safe')
            return
          else
            vm.update.onSaleTime = vm.onSaleTime
        else
          vm.update.onSaleTime = ''

        idsJson = {}
        for item in vm.cacheCheckRows
          idsJson[item] = 1
        vm.update.id = idsJson
        vm.update.all = false

        _updateStatus()
        vm.showOnShelvesModal = false

      vm.offShelves = ($event) ->
        param = angular.copy vm.cacheCheckRows

        notificationService.confirm $event,{
          title: 'product_goods_offshelves_tip'
          submitCallback: _offShelvesHandler
          params: [param]
        }

      vm.deleteGoods = ($event) ->
        param = angular.copy vm.cacheCheckRows

        notificationService.confirm $event,{
          title: 'product_goods_delete_tip'
          submitCallback: _deleteGoodsHandler
          params: [param]
        }

      vm.changeSize = (pageSize) ->
        vm.params['per-page'] = pageSize
        vm.params.page = 1
        _getGoods()

      vm.changePage = (currentPage) ->
        vm.params.page = currentPage
        _getGoods()

      vm.clear = ->
        _clear()
        vm.params.searchKey = ''

      vm.searchGoods = ->
        vm.currentPage = 1
        vm.conditions =
          categories: angular.copy vm.categories
          status: vm.params.status
        _showConditions()
        _clearCheck()
        _getGoods()
        vm.list.emptyMessage = 'search_no_data'

      vm.search = ->
        vm.currentPage = 1
        _clearCheck()
        _getGoods()
        vm.list.emptyMessage = 'search_no_data'

      vm.selectAllCatogories = (checkAll) ->
        _checkAll checkAll

      vm.selectCategory = (checked) ->
        if not checked
          vm.categoryAll = checked
        else
          vm.categoryAll = vm.categories.filter( (item) ->
            return item.check
          ).length is vm.categories.length

      _getAllCategories()
      _getGoods()

      vm
  ]
