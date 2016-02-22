define [
  'wm/app'
  'wm/config'
], (app, config) ->
  app.registerController 'wm.ctrl.store.view.order', [
    'restService'
    '$stateParams'
    '$location'
    'notificationService'
    '$filter'
    (restService, $stateParams, $location, notificationService, $filter) ->
      vm = this

      _init = ->
        vm.storeId = $stateParams.id
        vm.orderId = $location.search().id

        vm.breadcrumb = [
          text: 'store_order'
          href: '/store/order/' + vm.storeId
        ,
          'store_order_details'
        ]

        vm.list = {
          columnDefs: [
            {
              field: 'sku'
              label: 'store_order_goods_id'
              cellClass: 'cell-border center-content'
              headClass: 'center-content'
            }, {
              field: 'name'
              label: 'store_order_goods_name'
              cellClass: 'cell-border text-el'
              headClass: 'center-content'
              type: 'link'
            }, {
              field: 'price'
              label: 'store_order_unit_price'
              cellClass: 'cell-border center-content'
              headClass: 'center-content'
              type: 'currency'
            }, {
              field: 'count'
              label: 'store_order_quantity'
              cellClass: 'cell-border center-content'
              headClass: 'center-content'
            }, {
              field: 'totalPrice'
              label: 'store_order_subtotal'
              cellClass: 'cell-border center-content'
              headClass: 'center-content'
              type: 'currency'
            }
          ],
          data: []
          selectable: false
          singleRow: [
            {
              rowClass: 'cell-border'
              rows: [
                {
                  title: 'store_order_grand_total'
                  content: ''
                  titleClass: 'quotes-text order-grey-text'
                }
              ]
            },{
              rowClass: 'cell-border'
              rows: [
                {
                  title: 'store_order_total_amount'
                  content: ''
                  titleClass: 'quotes-text order-grey-text'
                },
                {
                  title: 'store_order_goods_total_price'
                  content: ''
                  titleClass: 'quotes-text order-grey-text'
                  contentClass: 'red-content'
                }
              ]
            }
          ]
        }

        #_getOrder()

      _getOrder = ->
        restService.get config.resources.storeOrderView + '/' + vm.orderId, (data) ->
          if data
            vm.info = angular.copy data
            vm.info.consumer.avatar = '/images/store/avatar_default.png' if vm.info.consumer and not vm.info.consumer.avatar
            vm.list.singleRow[0].rows[0].content = $filter('currency')(vm.info.expectedPrice, '￥')
            vm.list.singleRow[1].rows[0].content = $filter('currency')(vm.info.expectedPrice, '￥')
            vm.list.singleRow[1].rows[1].content = $filter('currency')(vm.info.totalPrice, '￥')
            if vm.info.remark
              vm.list.singleRow[1].rows.push {
                title: 'store_order_remarks'
                content: vm.info.remark
                titleClass: 'quotes-text order-grey-text'
                contentClass: 'order-grey-text'
              }

            vm.orderList = []
            if data.storeGoods
              for product in angular.copy data.storeGoods
                product.name =
                  link: '/store/view/shelf/' + vm.storeId + '?id=' + product.id
                  text: product.name
                vm.orderList.push product
            vm.list.data = vm.orderList

            vm.status = 'store_order_' + data.status

      vm.handleOrder = (flag) ->
        if flag in ['finished', 'canceled']
          param =
            status: flag
          restService.put config.resources.storeOrder + '/' + vm.orderId, param, (data) ->
            if data
              vm.info.status = flag
              vm.info.payWay = data.payWay
              vm.info.operateTime = data.operateTime
              vm.status = 'store_order_' + flag
              notificationService.success 'store_order_' + flag + '_success'

      _init()

      vm
  ]
