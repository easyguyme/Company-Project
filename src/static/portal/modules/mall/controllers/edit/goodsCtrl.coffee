define [
  'wm/app'
  'wm/config'
], (app, config) ->
  app.registerController 'wm.ctrl.mall.edit.goods', [
    'restService'
    '$stateParams'
    '$location'
    'validateService'
    '$filter'
    'notificationService'
    '$modal'
    '$timeout'
    (restService, $stateParams, $location, validateService, $filter, notificationService, $modal, $timeout) ->
      vm = this

      listPageUrl = '/mall/goods'
      vm.isDisabled = true

      _init = ->
        vm.showErrorTip = false
        vm.showErrorBorder = false
        vm.goods = {}
        vm.breadcrumb = [
          {
            text: 'shelf_management'
            href: listPageUrl
            icon: 'layout'
          }
          'product_edit_goods'
        ]

        _getGoods()

        vm.goodsShelvesTypes = [
          {
            name: 'product_goods_shelves_temporarily_not'
            value: 'off'
            status: 'off'
            time: ''
          }
          {
            name: 'product_goods_shelves_righ_now'
            value: 'now'
            status: 'on'
            time: null
          }
          {
            name: 'product_goods_shelves_by_schedule'
            value: 'schedule'
            status: 'off'
            time: null
          }
        ]

        vm.shelvesType = vm.goodsShelvesTypes[0].value

      _getProduct = (id) ->
        restService.get config.resources.product + '/' + id, (data) ->
          if data
            product = data
            vm.product = angular.copy product
            return

      _getGoods = ->
        vm.isDisabled = true
        restService.get config.resources.goods + '/' + $stateParams.id, (data) ->
          if data
            vm.goods = angular.copy data
            vm.shelvesType = vm.goods.status

            if vm.goods.status is 'off'
              if vm.goods.onSaleTime is '' or moment(vm.goods.onSaleTime).valueOf() < moment().valueOf
                vm.shelvesType = 'off'
              else
                vm.shelvesType = 'schedule'
                vm.goodsShelvesTypes[2].time = angular.copy moment(vm.goods.onSaleTime).valueOf()
            else
              vm.shelvesType = 'now'

            if vm.shelvesType is 'schedule'
              vm.isDisabled = false
            vm.express = vm.goods.receiveModes? and 'express' in vm.goods.receiveModes
            vm.self = vm.goods.receiveModes? and 'self' in vm.goods.receiveModes
            vm.selectAddress = vm.goods.addresses
            _getProduct(vm.goods.productId)
            _getAddress()
            return

      _getAddress = ->
        param =
          unlimited: true
        restService.get config.resources.receiveAddresss, param, (data) ->
          if data
            vm.addresses = data.items
            for address in vm.addresses
              address.check = address.id in vm.selectAddress
            vm.selectOne()

      vm.selectMethod = ->
        _hideAddressError()

      vm.selectAll = ->
        _hideAddressError()
        for address in vm.addresses
          address.check = vm.addressAll
        vm.items = if vm.addressAll then vm.addresses else []

      vm.selectOne = ->
        _hideAddressError()
        vm.items = vm.addresses.filter (address) ->
          if address.check
            return true
        vm.addressAll = vm.items.length is vm.addresses.length

      _hideAddressError = ->
        vm.showErrorTip = false
        vm.showErrorBorder = false

      vm.changeShelvesType = ->
        vm.isDisabled = true
        if vm.shelvesType is 'schedule'
            vm.isDisabled = false

        if vm.goodsShelvesTypes
          vm.goodsShelvesTypes.forEach (item) ->
            item.time = null

      vm.submit = ->

        cannotSubmit = false
        cannotSubmit = true if vm.checkPictures() isnt ''

        if vm.goods.score and vm.checkPositiveInt('pointsRequired', vm.goods.score) isnt ''
          cannotSubmit = true

        if vm.goods.total and vm.checkPositiveInt('itemsRedeemable', vm.goods.total) isnt ''
          cannotSubmit = true

        if (vm.express and not vm.self) or (vm.self and vm.items and vm.items.length > 0)
          _hideAddressError()
        else
          cannotSubmit = true
          vm.showErrorTip = true
          vm.showErrorBorder = true if vm.self

        if cannotSubmit
          return

        param =
          pictures: vm.goods.pictures
          score: parseInt(vm.goods.score)
          total: parseInt(vm.goods.total)
        param.total = parseInt(vm.goods.total) or ''

        param.description = vm.goods.description if vm.goods.description?

        switch vm.shelvesType
          when 'off'
            param.status = vm.goodsShelvesTypes[0].status
          when 'now'
            param.status = vm.goodsShelvesTypes[1].status
            param.onSaleTime = moment().valueOf()
          when 'schedule'
            param.status = vm.goodsShelvesTypes[2].status
            param.onSaleTime = vm.goodsShelvesTypes[2].time

        param.receiveModes = []
        param.addresses = []
        param.receiveModes.push 'express' if vm.express

        if vm.self
          param.receiveModes.push 'self'

          for address in vm.addresses
            param.addresses.push address.id if address.check

        restService.put config.resources.goods + '/' + $stateParams.id, param, (data) ->
          notificationService.success 'product_goods_update_success'

          $timeout (->
            $location.url listPageUrl
          ), 500
          return

      vm.checkPictures = ->
        tip = ''
        if vm.goods.pictures.length is 0
          tip = 'store_icon_field_tip'
          validateService.highlight($('#goodsPictures'), $filter('translate')('store_icon_field_tip'))
        tip

      vm.checkPositiveInt = (id, number) ->
        tip = ''
        reg = /^[1-9][0-9]*$/
        if number and not reg.test number # if the number is '' then do not check
          tip = 'product_promotion_activity_member_number_tip'
          validateService.highlight($('#' + id), $filter('translate')('product_promotion_activity_member_number_tip'))
        tip

      vm.cancel = ->
        $location.url listPageUrl

      vm.choosePictures = ->
        modalInstance = $modal.open(
          templateUrl: 'choosePictures.html'
          controller: 'wm.ctrl.mall.edit.choosePictures'
          windowClass: 'choose-pictures-dialog'
          resolve:
            modalData: ->
              data =
                pictures: vm.product.pictures
                chosenPictures: vm.goods.pictures
              data
        ).result.then( (data) ->
          if data
            vm.goods.pictures = angular.copy data
            if data.length > 0
              validateService.restore($('#goodsPictures'), '')
        )

      _init()

      vm
  ]


  app.registerController 'wm.ctrl.mall.edit.choosePictures', [
    '$scope'
    '$modalInstance'
    'restService'
    'modalData'
    ($scope, $modalInstance, restService, modalData) ->
      vm = $scope

      _init = ->

        vm.showPictures = new Array(5)

        vm.chosenPictures = []
        vm.chosenPictures = angular.copy modalData.chosenPictures if modalData.chosenPictures
        vm.pictures = angular.copy modalData.pictures if modalData.pictures

        if vm.chosenPictures.length > 0
          angular.forEach vm.pictures, (item) ->
            item.checked = $.inArray(item.url, vm.chosenPictures) isnt -1

      vm.choose = (index) ->
        if vm.chosenPictures.length is 5 and not vm.pictures[index].checked
          return

        vm.pictures[index].checked = not vm.pictures[index].checked
        if vm.pictures[index].checked
          vm.chosenPictures.push vm.pictures[index].url
        else
          vm.chosenPictures = angular.copy vm.chosenPictures.filter (item) ->
            return item isnt vm.pictures[index].url

      vm.removeCheckedPic = (index) ->
        removePicture = vm.chosenPictures[index]
        vm.chosenPictures = angular.copy vm.chosenPictures.filter (item, idx) ->
            return idx isnt index

        angular.forEach vm.pictures, (item) ->
          if item.url is removePicture
            item.checked = false

      vm.submit = ->
        $modalInstance.close(vm.chosenPictures)

      vm.hideModal = ->
        $modalInstance.close()

      _init()

      vm
  ]
