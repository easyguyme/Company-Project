define [
  'wm/app'
  'wm/config'
  'wm/modules/product/controllers/chooseProductCtrl'
], (app, config) ->
  app.registerController 'wm.ctrl.store.create.shelf', [
    'restService'
    'notificationService'
    '$location'
    '$modal'
    'validateService'
    '$filter'
    '$scope'
    '$stateParams'
    (restService, notificationService, $location, $modal, validateService, $filter, $scope, $stateParams) ->
      vm = this

      listPath = "/store/shelf/#{$stateParams.id}"

      vm.breadcrumb = [
        text: 'goods_shelf'
        href: listPath
      ,
        'product_add_many_goods'
      ]

      vm.list = {
        columnDefs: [
          {
            field: 'goodsName'
            label: 'product_item'
            type: 'goodsIcon'
            cellClass: 'text-el'
          }, {
            field: 'price'
            label: 'price'
            labelSuffix: '(￥)'
            type: 'inputText'
            inputClass: 'select-goods-input'
          }
        ]
        operations: [
          {
            name: 'delete'
          }
        ]
        noOptText: true
        nodata: 'store_no_select_goods'
        data: []
        deleteTitle: 'product_item_delete'

        deleteHandler: (idx) ->
          vm.list.data.splice idx, 1
          notificationService.success 'product_delete_success'
      }

      vm.selectProduct = ->
        modalInstance = $modal.open(
          templateUrl: '/build/modules/product/partials/chooseProduct.html'
          controller: 'wm.ctrl.product.chooseProduct'
          windowClass: 'associated-goods-dialog'
          resolve:
            modalData: ->
              checkedItems: angular.copy vm.list.data
              type: 'store'
              id: $stateParams.id
        ).result.then( (data) ->
          vm.list.data = data.list if data?.list
        )

      vm.submit = ->
        if vm.list.data.length is 0
          notificationService.error 'product_not_select_goods_error', false
        else if _checkInt()
          params =
            storeId: $stateParams.id
            goods: []
          for goods in vm.list.data
            item =
              productId: goods.id
              price: Number(goods.price)
            params.goods.push item

          restService.post config.resources.createStoreGoods, params, (data) ->
            if data
              $location.url listPath

      vm.cancel = ->
        $location.url listPath

      _checkInt = ->
        reg = /(^[1-9]\d*(\.\d{1,2})?$)|(^0\.(([1-9]\d?)|(0[1-9]))$)/
        canSubmit = true
        for item, index in vm.list.data
          if not reg.test item.price
            $trs = $('.colored-table .tbody-wrapper').find('tr')
            $tr = $ $trs[index] if $trs.length > index
            $input = $($tr.find('td')[1]).find('input')
            $input.focus ->
              validateService.restore($(this), '')
            validateService.highlight($input, $filter('translate')('product_promotion_basic_times_tip'))
            canSubmit = false
        return canSubmit

      vm
  ]
