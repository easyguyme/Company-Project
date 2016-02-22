define [
  'wm/app'
  'wm/config'
], (app, config) ->
  app.registerController 'wm.ctrl.product.setting', [
    ->
      vm = this
      vm.tabs = [
        {
          active: true
          name: 'product_setting_category'
          template: 'category.html'
        }
        {
          active: false
          name: 'product_pickup_location'
          template: 'receiveAddress.html'
        }
      ]

      vm.breadcrumb = [
        icon: 'setting'
        text: 'product_setting'
      ]

      vm
  ]

  app.registerController 'wm.ctrl.product.setting.category', [
    'restService'
    '$modal'
    '$scope'
    '$filter'
    'notificationService'
    'validateService'
    (restService, $modal, $scope, $filter, notificationService, validateService) ->
      vm = this

      vm.categories = []

      vm.tabs = [
        name: 'nav_product'
        value: 'product'
        active: true
      ,
        name: 'category_service'
        value: 'reservation'
        active: false
      ]

      vm.curType = vm.tabs[0].value

      vm.changeTab = (tab) ->
        vm.curType = tab.value
        _getCategories(tab.value)

      vm.createCategory = ->
        if not vm.isCreate
          item =  {
            id: '',
            name: '',
            isEditCategory: 'true',
            data: [
              id: '',
              order: '--',
              name: '--'
            ]
          }
          item.columnDefs = angular.copy vm.columnDefs
          item.columnDefs[0].type = 'translate'
          vm.categories.unshift item
          vm.isCreate = true

          $elem = $($('.create-category-input')[0])
          _removeErrorTip $elem

          $elem.on 'focusin', ->
            _removeErrorTip $elem
        return

      vm.editCategory = (idx) ->
        vm.categories[idx].isEditCategory = true

        $elem = $('#' + vm.categories[idx].id)
        _removeErrorTip $elem

        $elem.on 'focusin', ->
          _removeErrorTip $elem
        return

      vm.deleteCategory = (idx, $event) ->
        notificationService.confirm $event, {
          'title': 'product_category_delete',
          'submitCallback': _deleteCategory,
          'params': [idx]
        }

      vm.sort = (idx) ->
        items = vm.categories[idx].data
        orderMap = {}
        hasError = false
        for item in items
          order = parseInt item.order
          if isNaN(order) or item.order < 1
            hasError = true
            order = item.order
            break
          item.order = order
          orderMap[item.id] = item.order

        if hasError
          notificationService.warning 'invalid_order_tip', false, {order: order}
        else
          categoryId = vm.categories[idx].id
          requestData =
            order: orderMap

          restService.put config.resources.categoryProperty + '/' + categoryId, requestData, (data) ->
            if data.id
              vm.categories[idx].data = $filter('orderBy')(items, 'order')
              notificationService.success 'order_success_tip'

      vm.submit = (idx, categoryId) ->
        if _checkCategoryName(idx) is ''
          params =
            type: vm.curType

          if categoryId # Update the category
            if vm.categories[idx].name and vm.categories[idx].name isnt vm.categories[idx].backupName
              params.name = vm.categories[idx].name

              restService.put config.resources.category + '/' + categoryId, params, (data) ->
                if data.id
                  notificationService.success 'product_update_category_success'
                  vm.categories[idx].isEditCategory = false
                  vm.categories[idx].name = data.name
                  vm.categories[idx].backupName = vm.categories[idx].name
            else
              vm.categories[idx].isEditCategory = false
              vm.categories[idx].name = vm.categories[idx].backupName
          else # Create the category
            params.name = vm.categories[idx].name

            restService.post config.resources.categories, params, (data) ->
              if data.id
                notificationService.success 'product_add_category_success'
                vm.categories[idx].isEditCategory = false
                vm.categories[idx].propertiesLength = 0
                vm.categories[idx].name = data.name
                vm.categories[idx].backupName = data.name
                vm.categories[idx].id = data.id
                vm.categories[idx].isDeleteCategory = true
                vm.isCreate = false
        return

      vm.cancel = (idx, categoryId) ->
        if categoryId
          vm.categories[idx].isEditCategory = false
          vm.categories[idx].name = vm.categories[idx].backupName
        else
          vm.categories.splice 0, 1
          vm.isCreate = false

      vm.editProperty = (categoryIdx, propertyIdx) ->
        modalInstance = $modal.open(
          templateUrl: 'addCategoryAttribute.html'
          controller: 'wm.ctrl.product.categoriesProperty'
          windowClass: 'members-dialog'
          resolve:
            category: ->
              return vm.categories[categoryIdx]
            editedProperty: ->
              ret = null
              if propertyIdx?
                ret =
                  data: vm.categories[categoryIdx].data[propertyIdx]
              return ret
          ).result.then((data) ->
            if data?
              _init()
          )

      _init = ->
        vm.columnDefs = [
          field: 'order'
          label: 'customer_members_sort'
          type: 'input'
        ,
          field: 'name'
          label: 'customer_members_attribute_names'
          cellClass: 'text-el'
        ,
          field: 'operations'
          label: 'operations'
          type: 'operation'
        ]
        _getCategories(vm.curType)

      _getCategories = (type) ->
        params =
          type: type

        restService.get config.resources.categories, params, (data) ->
          vm.isCreate = false
          if data.items
            angular.forEach data.items, (item, index) ->
              item.columnDefs = angular.copy vm.columnDefs
              properties = []
              if item.properties.length > 0
                angular.forEach item.properties, (property) ->
                  property.operations = [
                    {
                      name: 'edit'
                    }, {
                      name: 'delete'
                    }
                  ]
                properties = item.properties
              else
                item.columnDefs[0].type = 'translate'
                properties[0] =
                  id: '',
                  order: '--',
                  name: '--'

              item.data = properties
              item.backupName = item.name
              item.isEditCategory = false
              item.propertiesLength = 0
              item.propertiesLength = item.properties?.length
              item.deleteTitle = 'product_property_delete_confirm'

              item.editHandler = (idx) ->
                vm.editProperty index, idx

              item.deleteHandler = (idx) ->
                categoryId = vm.categories[index].id
                requestData =
                  propertyId: vm.categories[index].data[idx].id
                restService.del config.resources.categoryProperty + '/' + categoryId, requestData, ->
                  vm.categories[index].propertiesLength--
                  # Refresh the data order after deleting an item
                  if vm.categories[index].propertiesLength > 0
                    vm.categories[index].data.splice idx, 1
                    count = 1
                    orderMap = {}
                    for item in vm.categories[index].data
                      item.order = count++
                      orderMap[item.id] = item.order
                    # Sync order with backend
                    requestDatas =
                      order: orderMap
                    restService.put config.resources.categoryProperty + '/' + categoryId, requestDatas
                  else
                    _init()
                  return
              return
          vm.categories = data.items
          return

      _removeErrorTip = ($elem) ->
        if $elem?.hasClass('form-control-error')
          $elem.removeClass('form-control-error').parent().removeClass('highlight')
          $elem.next('.form-tip').remove()
        return

      _deleteCategory = (idx) ->
        id = vm.categories[idx].id
        restService.del config.resources.category + '/' + id, (data) ->
          _init()

      _checkCategoryName = (idx) ->
        tip = ''
        if not vm.categories[idx].name
          tip = 'product_category_name_tip'
          $elem = if vm.categories[idx].id then $('#' + vm.categories[idx].id) else $($('.create-category-input')[0])
          validateService.highlight($elem, $filter('translate')('product_category_name_tip'))
        tip

      _init()

      vm
  ]

  app.registerController 'wm.ctrl.product.categoriesProperty', [
    '$scope'
    '$modalInstance'
    'restService'
    'notificationService'
    'category'
    'editedProperty'
    ($scope, $modalInstance, restService, notificationService, category, editedProperty) ->
      vm = $scope
      vm.isCreateProperty = true
      vm.categoryName = category.name

      vm.property =
        name: ''

      if editedProperty
        vm.isCreateProperty = false
        vm.property = angular.copy editedProperty.data

      vm.submit = ->
        if not vm.property.name
          return

        property = angular.copy vm.property
        property.order = category.propertiesLength + 1 if not property.order

        # Create property
        method = 'post'
        url = config.resources.categoryProperties
        # Update property
        if property.id
          method = 'put'
          url = config.resources.categoryProperty + '/' + category.id
        # Limit the category of properties on frontend
        if method is 'post' and category.propertiesLength >= 20
          notificationService.warning('product_category_form_tip')
          $modalInstance.close()
        else
          # Transform the order to integer type
          property.order = parseInt property.order
          requestData = angular.copy property
          requestData.categoryId = category.id
          if property.id
            requestData.propertyId = requestData.id
            delete requestData['order']
            delete requestData['id']
          restService[method] url, requestData, (data) ->
            if data.id
              tip = if property.id then 'product_update_category_property' else 'product_add_category_property'
              notificationService.success tip
              $modalInstance.close(data)

      vm.hideModal = ->
        $modalInstance.close()

      vm
  ]

  app.registerController 'wm.ctrl.product.setting.receiveAddress', [
    'restService'
    '$modal'
    '$scope'
    'notificationService'
    '$filter'
    (restService, $modal, $scope, notificationService, $filter) ->
      vm = this

      vm.list =
        columnDefs: [
          field: 'addressName'
          label: 'product_address_name'
          cellClass: 'text-el'
          headClass: 'name-width'
        ,
          field: 'addressDetail'
          label: 'product_address_detail'
          cellClass: 'text-el'
          headClass: 'address-width'
        ,
          field: 'phone'
          label: 'product_coupon_branch_tel'
          cellClass: 'text-el'
        ]
        operations: [
          name: 'edit'
        ,
          name: 'delete'
        ]
        data: []
        nodata: 'no_data'
        deleteTitle: 'product_receive_address_delete_tip'

        editHandler: (idx) ->
          vm.editAddress idx
        deleteHandler: (idx) ->
          id = vm.list.data[idx]?.id
          restService.del config.resources.receiveAddress + '/' + id, (data) ->
            _init()
            notificationService.success 'product_receive_address_delete_success'

      vm.editAddress = (idx) ->
        modalInstance = $modal.open(
          templateUrl: 'editReceiveAddress.html'
          controller: 'wm.ctrl.product.setting.editReceiveAddress'
          windowClass: 'receive-address-dialog'
          resolve:
            modalData: ->
              ret = null
              if idx?
                ret =
                  data: vm.list.data[idx]
              return ret
          ).result.then((data) ->
            if data?
              _init()
          )

      _getList = ->
        param =
          unlimited: true
        restService.get config.resources.receiveAddresss, param, (data) ->
          if data.items
            angular.forEach data.items, (item) ->
              if item.location
                addressDetail = $filter('translate')(item.location.province) + $filter('translate')(item.location.city) + $filter('translate')(item.location.district) + item.location.detail
              vm.list.data.push
                id: item.id
                addressName: item.address
                addressDetail: addressDetail
                itemLocation: item.location
                phone: item.phone

      _init = ->
        vm.list.data = []
        _getList()

      _init()

      vm
  ]

  app.registerController 'wm.ctrl.product.setting.editReceiveAddress', [
    '$scope'
    '$modalInstance'
    'restService'
    'notificationService'
    'modalData'
    '$filter'
    'utilService'
    ($scope, $modalInstance, restService, notificationService, modalData, $filter, utilService) ->
      vm = $scope

      vm.isCreateReceiveAddress = true
      vm.locationTip = 'management_store_repeat_address'
      vm.address = ''
      vm.location =
        province: ''
        city: ''
        county: ''
      vm.detail = ''
      vm.phone = ''

      if modalData
        vm.isCreateReceiveAddress = false
        vm.address = modalData.data.addressName
        vm.location =
          province: modalData.data.itemLocation.province
          city: modalData.data.itemLocation.city
          county: modalData.data.itemLocation.district
        vm.detail = modalData.data.itemLocation.detail
        vm.phone = modalData.data.phone

      vm.changeLocation = ->
        _clearLocationMsg()

      vm.changeDetail = ->
        _clearLocationMsg()

      _clearLocationMsg = ->
        $addressBox = $('#addressBox')
        if $addressBox.hasClass 'highlight'
          $addressBox.removeClass 'highlight'
          $addressBox.find('.form-tip').text($filter('translate')('management_store_repeat_address'))
        return

      vm.submit = ->
        if utilService.checkLocationIllegal vm.location
          return

        method = 'post'
        url = config.resources.receiveAddresss

        if not vm.isCreateReceiveAddress
          method = 'put'
          url = config.resources.receiveAddress + '/' + modalData.data.id

        param =
          address: vm.address
          location:
            province: vm.location.province
            city: vm.location.city
            district: vm.location.county
            detail: vm.detail
          phone: vm.phone

        restService[method] url, param, (data) ->
          if data
            if method is 'post'
              notificationService.success 'product_new_receive_address_success'
            else
              notificationService.success 'product_edit_receive_address_success'
            $modalInstance.close('ok')

      vm.hideModal = ->
        $modalInstance.close()

      vm
  ]
