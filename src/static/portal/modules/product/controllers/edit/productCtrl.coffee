define [
  'wm/app'
  'wm/config'
  'core/directives/wmSortable'
], (app, config) ->
  app.registerController 'wm.ctrl.product.edit.product', [
    'restService'
    'notificationService'
    '$location'
    '$stateParams'
    '$scope'
    '$filter'
    '$sce'
    '$timeout'
    'validateService'
    (restService, notificationService, $location, $stateParams, $scope, $filter, $sce, $timeout, validateService) ->
      vm = this
      vm.categoryId = ''
      vm.isShow = false
      vm.id = $stateParams.id
      vm.categories = []
      vm.category = {}  #transfer category data container
      propertiesOrigin = []
      listPath = '/product/product'

      vm.product = {
        pictures: []
        category: {}
      }

      vm.config = {
        toolbars: [
          ['pasteplain', 'fontsize', 'blockquote', 'removeformat', 'link', 'unlink'],
          ['bold', 'italic', 'underline', 'fontborder','backcolor', '|',
           'justifyleft', 'justifycenter', 'justifyright', 'justifyjustify', '|',
           'rowspacingtop', 'rowspacingbottom', 'lineheight', '|',
           'insertorderedlist', 'insertunorderedlist', '|',
           'imagenone', 'imageleft', 'imageright', 'imagecenter']
        ],
        initialStyle: 'ol, ul{width:initial!important}',
        initialFrameHeight: '200',
        autoHeightEnabled: false
      }

      vm.sortPicOptions =
        sort: true
        disabled: false
        animation: 200
        handle: '.product-pic-sort'
        draggable: '.product-pic-sort'
        onUpdate: (items, item) ->
          vm.product.pictures = items
          return

      vm.specifications = []
      vm.showDesc = 'other'
      vm.itemIndex = -1
      vm.descriptions = []
      STARTHTML = '<section style="width:100%;margin-bottom:10px;min-height:30px;">'
      ENDHTML = '</section>'

      vm.categoryType = if $location.search().type is 'reservation' then 'reservation' else 'product'
      listPath += '?active=1' if vm.categoryType is 'reservation'
      vm.showTagError = false

      if vm.categoryType is 'product'
        vm.goodsTitle = if vm.id then 'product_edit_goods' else 'product_goods_add_item'
      else
        vm.goodsTitle = if vm.id then 'product_edit_service' else 'product_new_service'
      vm.breadcrumb = [
        icon: 'product'
        text: 'product_management'
        href: listPath
      ,
        vm.goodsTitle
      ]

      _initTable = ->
        vm.rows = 1
        vm.rowNum = []
        vm.colNum = []
        vm.specificationsShow = []

      vm.addPicture = (picInfos) ->
        vm.showError = false
        currentLen = vm.product.pictures.length
        addLen = picInfos.length
        if currentLen < 10
          for picInfo in picInfos
            if currentLen < 10
              vm.product.pictures.push picInfo
              currentLen++
            else
              break

      vm.deletePicture = (index, event) ->
        vm.product.pictures.splice index, 1
        event.stopPropagation()
        return

      vm.generateSku = ->
        restService.noLoading().get config.resources.generateSku, (data) ->
          vm.product.sku = data.number
          $('#number').focus()

      vm.showPicLib = (index) ->
        vm.index = index
        vm.isShow = true

      vm.selectCategory = (item, idx) ->
        vm.cateName = vm.categories[idx].copyName
        _formatProperty(item)

      vm.submit = ->
        canSubmit = true
        for specification in vm.specifications
          if specification.properties.length is 0
            specification.showError = true
            canSubmit = false

        # Check whether to upload the picture
        if vm.product.pictures.length is 0
          vm.showError = true
          canSubmit = false

        if vm.validateCode()
          canSubmit = false

        for descItem, index in vm.descriptions
          if descItem.isEmpty
            vm.editItem(index)
            return

        if not canSubmit
          return

        _transferData()
        if not vm.id
          vm.product.specifications = vm.specificationsShow
        else
          vm.product.specifications = []
          for specificationsShow, index in vm.specificationsShow
            items = vm.oldSpecifications.filter (oldSpecification) ->
              if oldSpecification.name is specificationsShow.name
                return true
            if items.length > 0
              vm.product.specifications.push {
                name: items[0].name
                id: items[0].id
                properties: []
              }
              for specificationName in specificationsShow.properties
                itemNames = items[0].properties.filter (propertie) ->
                  if propertie.name is specificationName
                    return true
                if itemNames.length > 0
                  vm.product.specifications[index].properties.push itemNames[0]
                else
                  vm.product.specifications[index].properties.push {
                    name: specificationName
                  }

            else
              vm.product.specifications.push specificationsShow
        vm.product.intro = ''
        for desc in vm.descriptions
          vm.product.intro += desc.htmlStr if not desc.isEmpty

        vm.product.type = vm.categoryType
        if not vm.id
          restService.post config.resources.products, vm.product, (data) ->
            notificationService.success 'product_create_successfully', false
            window.location.href = listPath
        else
          restService.put config.resources.product + '/' + vm.id, vm.product, (data) ->
            notificationService.success 'product_update_successfully', false
            window.location.href = listPath

      vm.cancel = ->
        window.location.href = listPath

      _getCategories = ->
        params =
          type: vm.categoryType
        restService.get config.resources.categories, params, (data) ->
          propertiesOrigin = data.items

          for item in propertiesOrigin
            item.copyName = item.name
            vm.categories.push {name: item.name, id: item.id, copyName: item.copyName}

          if vm.categories.length > 0 and vm.id
            vm.categoryId = vm.product.category.id
            vm.cateName = vm.product.category.name
            _formatProperty(vm.categoryId)

      ## fill properties when update the product
      ## var properties means get product data from backend
      ## var originProperties means display property data
      _fillProperty = ->
        properties = angular.copy vm.product.category.properties
        originProperties = vm.category.properties
        for property in properties
          for oriProperty in originProperties
            if property.id is oriProperty.id
              switch property.type
                when 'date'
                  oriProperty.value = moment(property.value).valueOf()
                when 'checkbox'
                  for option in oriProperty.options
                    for val in property.value
                      if val is option.name
                        option.check = true
                        break
                else
                  oriProperty.value = property.value

      _formatProperty = (catId) ->
        properties = angular.copy propertiesOrigin
        if properties.length > 0
          for item in properties
            if item.id is catId
              for property in item.properties
                property.copyName = property.name

                property.template = '/build/modules/core/partials/properties/input.html'
                property.hasTooltip = true

              vm.category =
                id: item.id
                name: item.name
                copyName: item.copyName
                properties: angular.copy item.properties

              if vm.product.category.id is vm.categoryId
                _fillProperty()

              break

      _transferData = ->
        properties = []

        if vm.category.properties
          for property in angular.copy vm.category.properties
            obj =
              id: property.id
              name: property.copyName
              type: property.type

            if (property.value and not $.isArray(property.value)) or property.options or (property.value and $.isArray(property.value) and property.value.length > 0)
              switch property.type
                when 'date'
                  obj.value = moment.unix(property.value / 1000).format('YYYY-MM-DD')
                when 'checkbox'
                  obj.value = []
                  for option in property.options
                    obj.value.push option.name if option.check
                else
                  obj.value = property.value
              if ($.isArray(obj.value) and obj.value.length > 0) or (property.value and not $.isArray(property.value))
                properties.push obj

          if vm.category.id
            vm.product.category =
              id: vm.category.id
              name: vm.category.copyName
              properties: angular.copy properties

      vm.addSpecification = ->
        if vm.specifications.length < 4
          vm.specifications.push {
            name: ''
            firstName: ''
            properties: []
            propertiesEdit: []
            isShow: false
            showError: false
          }
        else
          notificationService.warning 'product_specification_full_tip'

      vm.addSpecificationName = (index) ->
        if vm.specifications[index].firstName and vm.specifications[index].firstName.trim().length > 0
          nameSpecification = vm.specifications.filter (item) ->
            if item.name is vm.specifications[index].firstName
              return true

          if nameSpecification.length > 0
            notificationService.warning 'product_specification_unique'
          else
            vm.specifications[index].name = angular.copy vm.specifications[index].firstName
            delete vm.specifications[index].firstName
        else
          validateService.showError $($('.product-specification-item')[index]).find('.product-specification-input'), $filter('translate')('required_field_tip')
          return

      vm.deleteSpecification = (index, event) ->
        if vm.specifications[index].name.length is 0
          vm.specifications.splice index, 1
        else
          notificationService.confirm event,{
            submitCallback: ->
              _safeApply $scope, ->
                vm.specifications.splice index, 1
                _calcTable()
          }

      vm.propertiesEditShow = (index) ->
        if not vm.specifications[index].isShow
          vm.specifications[index].propertiesEdit = angular.copy vm.specifications[index].properties
          vm.specifications[index].isShow = true
          vm.specifications[index].showError = false

      vm.propertiesEditHide = (index, event) ->
        vm.specifications[index].isShow = false
        event.stopPropagation()
        event.preventDefault()

      vm.propertiesEditOk = (index, event) ->
        if vm.specifications[index].propertiesEdit and vm.specifications[index].propertiesEdit.length > 0
          vm.specifications[index].properties = angular.copy vm.specifications[index].propertiesEdit
          _calcTable()
          vm.propertiesEditHide(index, event)
        else
          vm.showTagError = true
          $timeout ->
            vm.showTagError = false
          ,2000

      vm.propertieDelete = (pIndex, index, event) ->
        notificationService.confirm event,{
          submitCallback: ->
            _safeApply $scope, ->
              vm.specifications[pIndex].properties.splice index, 1
              vm.specifications[pIndex].propertiesEdit = angular.copy vm.specifications[pIndex].properties
              _calcTable()
        }

      _calcTable = ->
        _initTable()
        for specification in vm.specifications
          vm.specificationsShow.push specification if specification.name.length > 0 and specification.properties.length > 0
        vm.colNum.length = vm.specificationsShow.length
        for specification, index in vm.specificationsShow
          specification.properties.push '' if specification.properties.length is 0
          vm.rows = vm.rows * specification.properties.length
          for col, idx in vm.colNum
            vm.colNum[idx] = 1 if not col?
            vm.colNum[idx] = vm.colNum[idx] * specification.properties.length if index > idx

        while vm.rowNum.length < vm.rows
          vm.rowNum.push vm.rowNum.length

      vm.addItem = (type) ->
        vm.showDesc = type
        vm.itemIndex = vm.descriptions.length
        vm.imageEdit = ''
        vm.textEdit = ''
        initItem = STARTHTML + '<div class="product-empty-item">' + $filter('translate')('product_init_tip_' + type) + '</div>' + ENDHTML
        vm.descriptions.push {
          html: $sce.trustAsHtml initItem
          htmlStr: initItem
          type: type
          isEmpty: true
          value: ''
        }
        $timeout ->
          height = $('.product-detail-items').scrollTop()
          height += $($('.product-detail-items li').children().last()).offset().top - $('.product-detail-items').offset().top - $('.product-detail-items').height() + 100
          $('.product-detail-items').animate {'scrollTop': height + 'px'}, 250
        , 250
        return

      vm.editItem = (index) ->
        vm.showDesc = vm.descriptions[index].type
        vm.itemIndex = index
        if vm.showDesc is 'image'
          vm.imageEdit = vm.descriptions[index].value
        else
          vm.textEdit = vm.descriptions[index].value

      vm.removeItem = (index, event) ->
        notificationService.confirm event,{
          submitCallback: ->
            vm.descriptions.splice index, 1
            vm.showDesc = 'other' if index is vm.itemIndex
            notificationService.success 'reservation_layout_delete_success'
        }
        event.stopPropagation()
        event.preventDefault()

      vm.saveItem = ->
        if (vm.showDesc is 'image' and not vm.validateImage()) or (vm.showDesc is 'text' and not vm.validateText())
          htmlStr = STARTHTML
          vm.descriptions[vm.itemIndex].isEmpty = false
          if vm.showDesc is 'image'
            vm.descriptions[vm.itemIndex].value = vm.imageEdit
            htmlStr += '<img class="aaa-image" style="width:100%;" src="' + vm.imageEdit + '" />'
          else
            vm.descriptions[vm.itemIndex].value = vm.textEdit
            htmlStr += vm.textEdit
          htmlStr += ENDHTML
          vm.descriptions[vm.itemIndex].html = $sce.trustAsHtml htmlStr
          vm.descriptions[vm.itemIndex].htmlStr = htmlStr
        else
          if vm.showDesc is 'image'
            validateService.showError $('.product-desc-image'), $filter('translate')('product_init_tip_image')
          else
            validateService.showError $('.product-desc-text'), $filter('translate')('product_init_tip_text')
            $timeout ->
              validateService.restore $('.product-desc-text'), ''
              return
            ,2000
          return

      vm.hideError = (type) ->
        if type is 'image'
          validateService.restore $('.product-desc-image'), ''
        else
          validateService.restore $('.product-desc-text'), ''
        return

      vm.beforeAddPicture = (event) ->
        if vm.product.pictures.length >= 10
          notificationService.warning 'product_image_full_tip'
          event.stopPropagation()
          event.preventDefault()

      vm.validateCode = ->
        error = ''
        reg = /^[0-9a-zA-Z]*$/g
        error = 'product_number_tip' if not reg.test(vm.product.sku) or vm.product.sku.trim().length < 4 or vm.product.sku.trim().length > 20
        error

      vm.validateText = ->
        error = ''
        error = 'product_init_tip_text' if not vm.textEdit
        error

      vm.validateImage = ->
        error = ''
        error = 'product_init_tip_image' if not vm.imageEdit
        error

      _init = ->
        if not vm.id
          _initTable()
          _getCategories()
        else
          restService.get config.resources.product + '/' + vm.id, (data) ->
            vm.product = data
            if data.specifications
              vm.oldSpecifications = angular.copy data.specifications
              for specification, index in data.specifications
                vm.specifications.push {
                  name: angular.copy specification.name
                  properties: []
                  propertiesEdit: []
                  isShow: false
                  showError: false
                }
                for propertie in specification.properties
                  vm.specifications[index].properties.push angular.copy(propertie.name)
                  vm.specifications[index].propertiesEdit.push angular.copy(propertie.name)

            if data.intro
              descArray = data.intro.split ENDHTML
              descArray.splice(descArray.length - 1, 1) if descArray[descArray.length - 1].length is 0
              for desc, index in descArray
                vm.descriptions.push {
                  html: $sce.trustAsHtml(desc + ENDHTML)
                  htmlStr: desc + ENDHTML
                  type: ''
                  isEmpty: false
                  value: ''
                }
                if desc.indexOf('aaa-image') < 0
                  vm.descriptions[index].type = 'text'
                  vm.descriptions[index].value = desc.split(STARTHTML)[1]
                else
                  vm.descriptions[index].type = 'image'
                  vm.descriptions[index].value = desc.split('src="')[1].split('"')[0]

            _calcTable()
            _getCategories()

      _safeApply = (scope, fn) ->
        phase = if scope.$root then scope.$root.$$phase else ''
        if phase is '$apply' or phase is '$digest'
          fn() if fn and ( typeof fn is 'function')
        else
          scope.$apply(fn)

      _init()

      vm
  ]

