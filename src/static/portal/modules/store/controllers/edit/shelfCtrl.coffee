define [
  'wm/app'
  'wm/config'
], (app, config) ->
  app.registerController 'wm.ctrl.store.edit.shelf', [
    'restService'
    '$stateParams'
    '$modal'
    'validateService'
    '$location'
    'notificationService'
    '$filter'
    '$timeout'
    (restService, $stateParams, $modal, validateService, $location, notificationService, $filter, $timeout) ->
      vm = this
      vm.shelfId = $location.search().id
      vm.isDisabled = true

      vm.goodsShelvesTypes = [
          {
            name: 'product_goods_shelves_temporarily_not'
            value: 'off'
            status: 'off'
            time: ''
          },
          {
            name: 'product_goods_shelves_righ_now'
            value: 'now'
            status: 'on'
            time: null
          },
          {
            name: 'product_goods_shelves_by_schedule'
            value: 'schedule'
            status: 'off'
            time: null
          }
        ]
      vm.shelvesType = vm.goodsShelvesTypes[0].value
      vm.goods = {}

      getProduct = (id) ->
        restService.get config.resources.product + '/' + id, (data) ->
          if data
            product = data
            vm.product = angular.copy product
            return

      getGoodsById = (callback) ->
        vm.isDisabled = true
        restService.get config.resources.getGoods + '/' + vm.shelfId, (data) ->
          if data
            vm.goods = angular.copy data
            vm.shelvesType = angular.copy vm.goods.status
            vm.storeId = data.storeId

          if vm.goods.status is 'off'
            if vm.goods.onSaleTime is '' or moment(vm.goods.onSaleTime).valueOf() < moment().valueOf()
              vm.shelvesType = 'off'
            else
              vm.shelvesType = 'schedule'
              vm.goodsShelvesTypes[2].time = moment(vm.goods.onSaleTime).valueOf()
          else
            vm.shelvesType = 'now'

          if vm.shelvesType is 'schedule'
            vm.isDisabled = false
          callback() if callback
          getProduct(vm.goods.productId)

      vm.changeShelvesType = ->
        vm.isDisabled = true
        if vm.shelvesType is 'schedule'
            vm.isDisabled = false
        if vm.goodsShelvesTypes
          angular.forEach vm.goodsShelvesTypes, (item) ->
            item.time = null

      vm.cancel = ->
        $location.url vm.listPageUrl

      init = ->
        getGoodsById(->
          vm.listPageUrl = '/store/shelf/' + vm.storeId
          vm.breadcrumb = [
            text: 'goods_shelf'
            href: '/store/shelf/' + vm.storeId
          ,
            'store_edit_goods'
          ]
        )
        return

      checkPictures = ->
        tip = ''
        if vm.goods.pictures.length is 0
          tip = 'store_icon_field_tip'
          validateService.highlight($('#goodsPictures'), $filter('translate')('store_icon_field_tip'))
        tip

      vm.submit = ->
        if checkPictures() is '' and vm.checkPrice() is ''
          params =
            pictures: vm.goods.pictures
            price: vm.goods.price

          switch vm.shelvesType
            when 'off'
              params.status = vm.goodsShelvesTypes[0].status
            when 'now'
              params.status = vm.goodsShelvesTypes[1].status
              params.onSaleTime = moment().valueOf()
            when 'schedule'
              params.status = vm.goodsShelvesTypes[2].status
              params.onSaleTime = vm.goodsShelvesTypes[2].time

          restService.put config.resources.updateStoreGoods + '/' + vm.shelfId, params, (data) ->
            notificationService.success 'product_goods_update_success'
            $timeout (->
              $location.url vm.listPageUrl
            ), 500

      vm.checkPrice = ->
        tip = ''
        reg = /(^[1-9]\d*(\.\d{1,2})?$)|(^0\.(([1-9]\d?)|(0[1-9]))$)/
        if not reg.test vm.goods.price
          tip = 'product_promotion_basic_times_tip'
        tip

      vm.choosePictures = ->
        modalInstance = $modal.open(
          templateUrl: 'choosePictures.html'
          controller: 'vm.ctrl.store.edit.choosePictures'
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
      init()
      vm
  ]
  app.registerController 'vm.ctrl.store.edit.choosePictures', [
    '$scope'
    'restService'
    '$modalInstance'
    'modalData'
    ($scope, restService, $modalInstance, modalData) ->
      vm = $scope

      init = ->
        vm.showPictures = new Array(5)
        vm.chosenPictures = angular.copy modalData.chosenPictures if modalData.chosenPictures
        vm.pictures = angular.copy modalData.pictures if modalData.pictures

        if vm.chosenPictures and vm.chosenPictures.length > 0
          angular.forEach vm.pictures, (picture) ->
            picture.checked = $.inArray( picture.url, vm.chosenPictures) isnt -1

      vm.hideModal = ->
        $modalInstance.dismiss()

      vm.choose = (index) ->
        if vm.chosenPictures
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

        if vm.pictures
          angular.forEach vm.pictures, (item) ->
            if item.url is removePicture
              item.checked = false

      vm.submit = ->
        $modalInstance.close(vm.chosenPictures)

      init()

      vm
  ]
