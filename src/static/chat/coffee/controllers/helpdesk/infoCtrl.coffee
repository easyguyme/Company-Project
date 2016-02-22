define [
  'core/coreModule'
  'chat/config'
], (app, config) ->
  app.controller 'wm.ctrl.helpdesk.tabs.info', [
    '$scope'
    'restService'
    '$q'
    'validateService'
    '$timeout'
    '$rootScope'
    ($scope, restService, $q, validateService, $timeout, $rootScope) ->
      vm = $scope
      rvm = $rootScope

      _init = ->
        vm.isShowExtended = false
        _setLanguage()

        if vm.openId
          _getProperties()
        else
          params =
            page: 'info'
          vm.$emit 'requestSelectedClient', params

      _setLanguage = ->
        vm.language = rvm.user?.language or 'zh_cn'
        rvm.$on '$translateChangeSuccess', (event, data) ->
          vm.language = data.language
        return

      _getValueOfProperties = ->
        defered = $q.defer()

        if vm.channelId and vm.openId and vm.source and vm.source isnt 'website'
          params =
            openId: vm.openId
            channelId: vm.channelId
          restService.get config.resources.getMemberCardInfo, params, (data) ->
            defered.resolve(data.properties) if data?.properties
        else
          defered.resolve([])

        defered.promise

      _getSettingOfProperties = ->
        defered = $q.defer()

        if vm.openId and vm.source and vm.source isnt 'website'
          params =
            openId: vm.openId
          restService.get config.resources.clientProperties, params, (data) ->
            defered.resolve(data) if data
        else
          defered.resolve([])

        defered.promise

      _getProperties = ->
        settingOfPropertiesPromise = _getSettingOfProperties()
        valueOfPropertiesPromise = _getValueOfProperties()

        $q.all([settingOfPropertiesPromise, valueOfPropertiesPromise]).then (properties) ->
          settingOfProperties = properties[0]
          valueOfProperties = properties[1]

          _getPropertiesHandler settingOfProperties, valueOfProperties

      _getPropertiesHandler = (settingOfProperties, valueOfProperties) ->
        settingOfProperties = settingOfProperties or []
        valueOfProperties = valueOfProperties or []

        vm.properties = []

        for item in settingOfProperties
          valueOfProperty = _getPropertyAccordingId item.id, valueOfProperties
          property = _packageProperty item, valueOfProperty
          vm.properties.push property

      _getPropertyAccordingId = (id, properties) ->
        property = null
        properties = properties or []

        for item in properties
          if item.id is id
            property = angular.copy item
            break

        property

      _packageProperty = (item, valueOfProperty) ->
        property =
          defaultValue: item.defaultValue
          id: item.id
          isDefault: item.isDefault
          isRequired: item.isRequired
          name: item.name
          options: item.options
          order: item.order
          type: item.type
          status: 'view'

        property.value = valueOfProperty.value if valueOfProperty

        urlStr = property.type
        switch property.type
          when 'checkbox'
            options = []
            property.hasTooltip = true

            property.value = property.value or []
            property.options.forEach (optionVal) ->
              option =
                name: optionVal
              option.check = $.inArray(optionVal, property.value) isnt -1
              options.push angular.copy option
            property.options = angular.copy options
            property.column = 6

            property.tip = {} if property.isRequired
          when 'radio'
            property.hasTooltip = true
            property.value = property.value or property.defaultValue or property.options[0]
            property.column = 6
          when 'input'
            urlStr = property.name if property.name is 'name' or property.name is 'tel'
        property.url = "/build/modules/core/partials/properties/#{urlStr}.html"

        switch property.name
          when 'name'
            property.validateHandler = _checkNameOfProperty
          when 'tel'
            property.validateHandler = _checkTelOfProperty
        property

      _restoreEditingProperty = (property) ->
        property.value = vm.editingProperties[property.id]
        property.status = 'view'
        delete vm.editingProperties[property.id]

        if property.type is 'checkbox' and property.isRequired
          _restoreCheckboxProperty property

      _reloadCheckboxProperty = (property) ->
        _restoreCheckboxProperty(property)
        if property.options and angular.isArray(property.options)
          for option in property.options
            option.check = property.value and angular.isArray(property.value) and $.inArray(option.name, property.value) isnt -1

      _setPropertyStatusToEdit = (property) ->
        vm.editingProperties = {} if not vm.editingProperties
        vm.editingProperties[property.id] = property.value

        _reloadCheckboxProperty(property) if property and property.type is 'checkbox'

        property.status = 'edit'

        $timeout(->
          $("##{property.id}").focus()
        , 200)
        return

      _highlightCheckboxProperty = (property, message) ->
        property.tip = property.tip or {}
        property.tip.status = 'error'
        property.tip.text = message

      _restoreCheckboxProperty = (property) ->
        property.tip = {} if property and property.isRequired

      _checkNameOfProperty = (value) ->
        tip = ''
        if value and (value.length < 2 or value.length > 30)
          tip = 'customer_member_name_tip'
        tip

      _checkTelOfProperty = (value) ->
        tip = validateService.checkTelNum value

      _checkRequiredOfCheckboxProperty = (property) ->
        tip = ''
        if property.isRequired
          flag = true
          for option in property.options
            if option.check
              flag = false
              break
          tip = 'required_field_tip' if flag
        tip

      _checkRequiredOfTextProperty = (value) ->
        tip = ''
        if not value
          tip = 'required_field_tip'
        tip

      _validateProperty = (property) ->
        tip = ''
        switch property.type
          when 'checkbox'
            tip = _checkRequiredOfCheckboxProperty property
            if tip
              _highlightCheckboxProperty property, tip
          else
            switch property.name
              when 'name'
                tip = _checkNameOfProperty property.value
              when 'tel'
                tip = _checkTelOfProperty property.value
        tip

      _updatePropertyHandler = (property) ->
        property.status = 'view'

      _prepareUpdateProperty = (property) ->
        item =
          id: property.id
          name: property.name
          value: property.value

        if property.type is 'checkbox'
          property.value = []
          property.options.forEach (option) ->
            property.value.push option.name if option.check
          item.value = property.value

        item.value = item.value or null
        item

      vm.cancelEditingProperty = (property) ->
        _restoreEditingProperty property

      vm.editingProperty = (property) ->
        editingProperties = vm.properties.filter (item) ->
          return item.status is 'edit'

        for item in editingProperties
          _restoreEditingProperty item

        _setPropertyStatusToEdit property

      vm.switchShowExtended = ->
        vm.isShowExtended = not vm.isShowExtended

      vm.updateProperty = (property, event) ->
        if event.valid? and not event.valid
          return

        validity = not _validateProperty(property)

        if validity
          item = _prepareUpdateProperty property

          params =
            openId: vm.openId
            properties: [ item ]

          restService.put config.resources.updateClientProperties, params, (data) ->
            _updatePropertyHandler(property)

      vm.$on 'changeClient', (event, client) ->
        if client and client.target
          vm.source = client.source
          vm.openId = client.target
          vm.channelId = client.channelId
          _init()
        else
          delete vm.properties

      _init()

      vm
  ]
