define [
  'wm/app'
  'wm/config'
], (app, config) ->
  app.registerController 'wm.ctrl.management.setting', [
    'restService'
    '$modal'
    '$scope'
    '$filter'
    '$location'
    'notificationService'
    (restService, $modal, $scope, $filter, $location, notificationService) ->
      vm = this

      _getProperties = ->
        params =
          'orderBy':
            'order': 'asc'
          'per-page': vm.pageSize
          'page': vm.currentPage
        # AJAX call for table data
        restService.get config.resources.memberProperties, params, (data) ->
          if data
            items = data.items
            operation = [
              name: 'edit'
            ]
            for item in items
              item.operation = angular.copy(operation)
              if not item.isDefault
                item.operation.push(
                  name: 'delete'
                )
            vm.tableDef.data = items

            vm.totalItems = data._meta.totalCount
            vm.pageSize = data._meta.perPage
            vm.pageCount = data._meta.pageCount

      _deleteProperty = (idx, callback) ->
        item = vm.tableDef.data[idx]
        if not item.isDefault
          restService.del config.resources.updateProperty + '/' + item.id, ->
            callback() if callback

      _init = ->
        vm.showVisibleCheckbox = true
        vm.currentPage = $location.search().currentPage or 1
        vm.pageSize = $location.search().pageSize or 10

        vm.breadcrumb = [
          'customer_property'
        ]

        vm.tableDef =
          columnDefs: [
            {
              field: 'order'
              label: 'customer_members_sort'
              type: 'input'
            }, {
              field: 'name'
              label: 'customer_members_attribute_names'
              type: 'translate'
            }, {
              field: 'type'
              label: 'customer_members_type'
              type: 'translate'
            }, {
              field: 'isRequired'
              label: 'customer_members_be_necessary'
              type: 'translate'
            }, {
              field: 'isUnique'
              label: 'customer_members_whether_only'
              type: 'translate'
            }, {
              field: 'isVisible'
              label: 'customer_members_be_visible'
              type: 'translate'
            }, {
              field: 'operation'
              label: 'customer_members_operation'
              type: 'operation'
            }
          ],
          data: []
          deleteTitle: 'customer_property_delete_confirm'
          editHandler: (idx) ->
            vm.editProperty idx
          deleteHandler: (idx) ->
            _deleteProperty idx, ->
              vm.tableDef.data.splice idx, 1
              if vm.tableDef.data.length is 0
                _getProperties()
              else
                vm.totalItems--

        _getProperties()

      vm.changePage = (currentPage) ->
        vm.currentPage = currentPage
        _getProperties()

      vm.changeSize = (pageSize) ->
        vm.pageSize = pageSize
        vm.currentPage = 1
        _getProperties()

      vm.sort = ->
        items = vm.tableDef.data
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
          restService.put config.resources.orderMemberProperties, orderMap, ->
            _getProperties()
            notificationService.success 'order_success_tip'


      vm.editProperty = (idx) ->
        if vm.tableDef.data.length is 100 and not idx
          return
        modalInstance = $modal.open(
            templateUrl: 'addMembersAttribute.html'
            controller: 'wm.ctrl.management.membersProperty'
            windowClass: 'members-dialog'
            resolve:
              propertiesCount: ->
                return vm.tableDef.data.length
              editedProperty: ->
                ret = null
                if idx?
                  ret =
                    data: vm.tableDef.data[idx]
                    idx: idx
                return ret
          ).result.then((editedProperty) ->
            if editedProperty?
              item = angular.copy editedProperty.data
              if editedProperty.data.name is 'tel'
                vm.showVisibleCheckbox = false
              item.operation = [{
                name: 'edit'
              }]
              item.operation.push {name: 'delete'} if not item.isDefault
              if idx?
                # Update the property in the data list
                vm.tableDef.data[editedProperty.idx] = item
              else
                # Create new property in the data list
                if vm.pageSize > vm.tableDef.data.length
                  vm.tableDef.data.push item
                else
                  vm.pageCount++
                vm.totalItems++
            return
          )

      _init()

      vm
    ]
  app.registerController 'wm.ctrl.management.membersProperty', [
    '$scope'
    '$modalInstance'
    'restService'
    'notificationService'
    'propertiesCount'
    'editedProperty'
    'utilService'
    'validateService'
    '$filter'
    ($scope, $modalInstance, restService, notificationService, propertiesCount, editedProperty, utilService, validateService, $filter) ->
      vm = $scope
      vm.options = []
      vm.showVisibleCheckbox = true
      vm.editedData = editedProperty
      vm.title = 'customer_members_add_attribute'
      vm.typeOptions = [
        {
          text: 'customer_members_singleline_text'
          value: 'input'
        }
        {
          text: 'customer_members_multiplelines_text'
          value: 'textarea'
        }
        {
          text: 'customer_members_date'
          value: 'date'
        }
        {
          text: 'customer_members_single_choice'
          value: 'radio'
        }
        {
          text: 'customer_members_multiple_choice'
          value: 'checkbox'
        }
      ]

      vm.property =
        name: ''
        type: vm.typeOptions[0].value
        defaultValue: ''
        isRequired: false,
        isUnique: false,
        isVisible: false
        isDefault: false

      if editedProperty
        vm.property = angular.copy editedProperty.data
        vm.property.isRequired = true if editedProperty.data.name is 'tel'
        vm.property.isUnique = false if editedProperty.data.name is 'gender' or editedProperty.data.name is 'birthday'
        vm.showVisibleCheckbox = false if editedProperty.data.name is 'tel'
        vm.isOptionType = vm.property.type is 'checkbox' or vm.property.type is 'radio'
        if vm.isOptionType
          index = 0
          for option in vm.property.options
            vm.property.defaultValue = option if index is 0
            if index isnt 0
              vm.options.push
                value: option
            index++
        delete vm.property['operation']

        vm.title = 'customer_members_edit_attribute'

      _checkSameValue = ->
        flag = true
        options = angular.copy vm.options
        options.unshift({value: vm.property.defaultValue})

        $input = $('.add-option-wrap').find('input')
        $input.each ->
          validateService.restore($(this), '')

        for option, index in options
          position = utilService.getArrayElemIndex options, option, 'value'
          #exclude self
          if position isnt -1 and position isnt index
            $input1 = $($input[index])
            $input2 = $($input[position])
            $input.focus ->
              validateService.restore($(this), '')
            validateService.highlight($input1, $filter('translate')('member_same_property_error'))
            validateService.highlight($input2, $filter('translate')('member_same_property_error'))
            flag = false
        flag

      vm.changeType = (val, idx) ->
        vm.type = val
        vm.isOptionType = val is 'checkbox' or val is 'radio'

      vm.addOption = ->
        vm.options.push(
          value: ''
        )

      vm.deleteOption = (idx) ->
        vm.options.splice idx, 1

      vm.submit = ->
        if not vm.property.isDefault and vm.checkPropertyId() isnt ''
          return

        if not _checkSameValue()
          return

        type = vm.property.type
        property = angular.copy vm.property
        if type is 'checkbox' or type is 'radio'
          options = []
          for option in vm.options
            options.push option.value
          options.unshift property.defaultValue if property.defaultValue
          property.defaultValue = '' if type is 'checkbox'
          property.options = options
        # Create property
        method = 'post'
        url = config.resources.createMemberProperty
        # Update property
        if property.id
          method = 'put'
          url = config.resources.updateProperty + '/' + property.id
        # Limit the numbers of properties on frontend
        if method is 'post' and propertiesCount > 100
          notificationService.warning('customer_members_form_tip')
          $modalInstance.close()
        else
          cannotSubmit = false
           #Check property name
          cannotSubmit = true if not property.name
          cannotSubmit = true if (vm.property.type is 'radio' or vm.property.type is 'checkbox') and not vm.property.defaultValue
          #Check property options
          if property.options
            angular.forEach property.options, (option) ->
              if not option
                cannotSubmit = true
          if cannotSubmit
            return
          # Transform the order to integer type
          property.order = parseInt property.order
          restService[method] url, property, (data) ->
            # Empty editedProperty indicates that this is the creating popup
            editedProperty = {} if not editedProperty
            editedProperty.data = angular.copy data
            $modalInstance.close(editedProperty) if property.type and property.name

      vm.checkPropertyId = ->
        tip = ''
        re = new RegExp('^[a-zA-Z0-9]{1,20}$')
        if not re.test(vm.property.propertyId or '')
          tip = 'member_property_code_error'
        tip

      vm.hideModal = ->
        $modalInstance.close()
      vm
    ]
  return
