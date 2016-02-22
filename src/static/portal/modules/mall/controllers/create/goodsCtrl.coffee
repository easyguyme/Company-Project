define [
  'wm/app'
  'wm/config'
  'wm/modules/product/controllers/chooseProductCtrl'
], (app, config) ->
  app.registerController 'wm.ctrl.mall.create.goods', [
    'restService'
    'notificationService'
    '$location'
    '$modal'
    'validateService'
    '$filter'
    '$scope'
    (restService, notificationService, $location, $modal, validateService, $filter, $scope) ->
      vm = this

      listPageUrl = '/mall/goods'

      vm.breadcrumb = [
        {
          text: 'shelf_management'
          href: listPageUrl
          icon: 'layout'
        }
        'product_add_many_goods'
      ]

      vm.list = {
        columnDefs: [
          {
            field: 'goodsName'
            label: 'product_item'
            type: 'goodsIcon'
            cellClass: 'text-el'
          },{
            field: 'score'
            label: 'product_goods_points_required'
            type: 'inputText'
            text: 'product_promotion_basic_score_unit'
            inputClass: 'select-goods-input'
            maxlength: 6
          }, {
            field: 'total'
            label: 'product_goods_total_amount'
            type: 'input'
            placeholder: 'channel_wechat_mass_unlimited'
            inputClass: 'select-goods-input'
            reg: '^((^[1-9][0-9]{0,4}$)|(^100000000$))$'
            maxlength: 9
          }
        ]
        operations: [
          {
            name: 'delete'
          }
        ]
        noOptText: true
        nodata: 'product_no_select_goods'
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
              type: 'product'
        ).result.then( (data) ->
          vm.list.data = data.list if data?.list
        )

      vm.submit = ->
        if vm.list.data.length is 0
          notificationService.error 'product_not_select_goods_error', false
        else if _checkInt()
          params = {}
          items = []
          for goods in vm.list.data
            item =
              productId: goods.id
              categoryId: goods.category?.id
              score: Number (goods.score)
              total: Number(goods.total) or ''
            item.pictures = []
            angular.forEach goods.pictures, (picture, index) ->
              if index < 5
                item.pictures.push picture.url
            items.push item
          params.goods = items

          restService.post config.resources.createGoods, params, (data) ->
            if data
              $location.url listPageUrl

      vm.cancel = ->
        $location.url listPageUrl

      _checkInt = ->
        regx = '^((^[1-9][0-9]{0,4}$)|(^100000$))$'
        reg = new RegExp(regx)
        canSubmit = true
        for item, index in vm.list.data
          idx = 0
          if not reg.test item.score
            idx = 1
          if item.total and not reg.test item.total
            idx = 2
          if idx isnt 0
            $trs = $('.colored-table .tbody-wrapper').find('tr')
            $tr = $ $trs[index] if $trs.length > index
            $input = $($tr.find('td')[idx]).find('input')
            $input.focus ->
              validateService.restore($(this), '')
            validateService.highlight($input, $filter('translate')('product_promotion_activity_member_number_tip'))
            canSubmit = false
        return canSubmit

      vm
  ]
