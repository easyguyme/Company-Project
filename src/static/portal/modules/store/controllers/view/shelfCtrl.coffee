define [
  'wm/app'
  'wm/config'
], (app, config) ->
  app.registerController 'wm.ctrl.store.view.shelf', [
    'restService'
    '$stateParams'
    '$scope'
    '$sce'
    '$location'
    '$rootScope'
    (restService, $stateParams, $scope, $sce, $location, $rootScope) ->
      vm = this
      vm.shelfId = $location.search().id

      _init = ->
        vm.goods = {}

        vm.breadcrumb = []

        _getGoods( ->
          vm.breadcrumb = [
            text: 'goods_shelf'
            href: '/store/shelf/' + vm.goods.storeId
          ,
            'store_goods_detail'
          ]
        )

      _getProduct = (id) ->
        restService.get config.resources.storeProduct + '/' + id, (data) ->
          if data
            product = data
            product.intro = $sce.trustAsHtml product.intro
            if not $.isArray product.category
              for property in product.category.properties
                property.value = property.value.join('， ') if angular.isArray(property.value)

            product.type = product.category
            vm.product = angular.copy product

      _getGoods = (callback) ->
        restService.get config.resources.storeGoods + '/' + vm.shelfId, (data) ->
          if data
            vm.goods = angular.copy data
            vm.goods.labelColor = if vm.goods.onSaleTime isnt '' then 'green' else 'gray'
            if vm.goods.status is 'on'
              vm.goods.shelves = 'store_on_shelves'
            else if vm.goods.status is 'off' and vm.goods.onSaleTime isnt ''
              vm.goods.shelves = 'store_scheduled_shelves'
            else
              vm.goods.shelves = 'store_off_shelves'
            callback() if callback
            _getProduct(vm.goods.productId)
            return

      _init()

      vm
  ]
