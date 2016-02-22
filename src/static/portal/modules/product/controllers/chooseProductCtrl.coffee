define [
  'wm/app'
  'wm/config'
], (app, config) ->
  app.registerController 'wm.ctrl.product.chooseProduct', [
    '$scope'
    '$modalInstance'
    'restService'
    'notificationService'
    'modalData'
    'utilService'
    '$timeout'
    '$filter'
    ($scope, $modalInstance, restService, notificationService, modalData, utilService, $timeout, $filter) ->
      vm = $scope

      vm.selectedList = modalData.checkedItems or []
      vm.type = modalData.type
      id = modalData.id
      vm.isSelectAll = true

      vm.curTab = {}

      vm.tabs = [
        {
          name: 'product_items'
          value: 0
          active: true
        }
        {
          name: 'product_search_items'
          value: 1
          active: false
        }
      ]

      vm.curTab.value = 0

      vm.selectItems = [
        {
          text: 'product_all_categories'
          value: 0
        }
      ]
      vm.categoryId = 0

      vm.searchList = {
        columnDefs: [
          {
            field: 'sku'
            label: 'product_number'
            cellClass: 'text-el'
          },{
            field: 'goodsName'
            label: 'product_item'
            type: 'mark'
            markText: 'product_goods_selected'
          }, {
            field: 'categoryName'
            label: 'product_goods_category'
          }
        ]
        data: []
        selectable: true

        switchedHandler: (idx) ->
          vm.selectedList = []
          vm.selectedList.push vm.searchList.data[idx]

        selectHandler: (checked, idx) ->
          if idx?
            index = utilService.getArrayElemIndex(vm.selectedList, vm.searchList.data[idx].sku, 'sku')
            if checked and index is -1
              vm.selectedList.push(vm.searchList.data[idx])
            if not checked and index isnt -1
              vm.selectedList.splice index, 1

          else
            if checked
              for item in vm.searchList.data
                index = utilService.getArrayElemIndex(vm.selectedList, item.sku, 'sku')
                if index is -1
                  vm.selectedList.push(item) if item.enabled
            else
              for item in vm.searchList.data
                index = utilService.getArrayElemIndex(vm.selectedList, item.sku, 'sku')
                if index isnt -1
                  vm.selectedList.splice index, 1

          _setScrollPostion()
      }

      if 'reservation' is vm.type
        vm.searchList.selectable = false
        vm.searchList.switchedable = true
        vm.searchList.columnDefs.pop()
        vm.isSelectAll = false

      # keep scrollbar at the bottom of the select wrapper
      _setScrollPostion = ->
        if vm.selectedList.length > 20
          $timeout ->
            $selectWrap = $('.selected-goods-wrap')
            $selectWrap.scrollTop($selectWrap[0].scrollHeight)
          , 5

      _getList = ->
        vm.searchList.hasLoading = true
        params =
          page: 1
          isAll: true
          fields: 'id,sku,name,pictures,category,isSelected'
          orderBy: '{"createdAt": "desc"}'
          searchKey: vm.searchKey
          category: if vm.categoryId is 0 then '' else vm.categoryId

        selectedField = ''
        switch vm.type
          when 'store'
            selectedField = 'isStoreGoods'
            params.storeId = id if id
          when 'reservation'
            selectedField = 'isReservationGoods'
            params.fields = 'id,sku,name,pictures,isReservationGoods'
            params.categoryType = 'reservation'
            params.shelfId = id if id
          when 'product'
            selectedField = 'isSelected'

        restService.noLoading().get config.resources.products, params, (data) ->
          items = data.items
          vm.searchList.checkAll = false

          for item in items
            item.checked = false
            item.goodsName = {
              name: item.name
              url: if item.pictures and item.pictures.length > 0 then item.pictures[0].url else ''
            }
            if vm.type isnt 'reservation'
              if item.category.name is 'service'
                item.category.name = $filter('translate')('category_service')
              item.categoryName = item.category.name
            item.enabled = not item[selectedField]

          vm.searchList.data = items

          _getDataAfter()

      _getDataAfter = ->
        # fix bug about first time checkbox cannot be checked
        $timeout( ->
          checkLen = 0
          enableLen = 0

          for item in vm.searchList.data
            if utilService.getArrayElemIndex(vm.selectedList, item.sku, 'sku') isnt -1
              item.checked = true
              item.switched = true #type is radio
              checkLen++
            if item.enabled
              enableLen++

          if checkLen > 0 and checkLen is enableLen
            vm.searchList.checkAll = true

        , 20)

        vm.searchList.hasLoading = false
        vm.searchList.nodata = 'search_no_products' if not vm.searchList.data.length

      _getAllCategories = ->
        restService.get config.resources.categories, (data) ->
          if data
            for item in data.items
              if item.name is 'service'
                item.name = $filter('translate')('category_service')
              item.text = item.name
              item.value = item.id
              vm.selectItems.push item

      vm.search = ->
        vm.searchFlag = true
        _getList()

      vm.changeTab = ->
        vm.searchFlag = false
        vm.searchKey = ''
        vm.categoryId = 0
        vm.searchList.data = []
        vm.searchList.checkAll = false
        if vm.curTab.value is 0
          _getList()

      vm.changeSelect = (value, index) ->
        vm.categoryId = vm.selectItems[index].value
        _getList()

      vm.deleteSelectedItem = (index) ->
        elem = vm.selectedList.splice index, 1
        position = utilService.getArrayElemIndex(vm.searchList.data, elem[0].sku, 'sku')
        vm.searchList.data[position].checked = false if position isnt -1
        vm.searchList.data[position].switched = false if position isnt -1 #type is radio
        vm.searchList.checkAll = false

      vm.hideModal = ->
        delete vm.selectedList
        delete vm.searchList
        $modalInstance.close()

      vm.submit = ->
        $modalInstance.close({list: angular.copy(vm.selectedList)})
        delete vm.searchList
        delete vm.selectedList

      if vm.type isnt 'reservation'
        _getAllCategories()

      _getList()

      _setScrollPostion()

  ]
