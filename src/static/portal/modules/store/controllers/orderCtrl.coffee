define [
  'wm/app'
  'wm/config'
], (app, config) ->
  app.registerController 'wm.ctrl.store.order', [
    'restService'
    '$stateParams'
    '$location'
    '$filter'
    '$scope'
    'notificationService'
    'validateService'
    '$sce'
    'utilService'
    (restService, $stateParams, $location, $filter, $scope, notificationService, validateService, $sce, utilService) ->
      vm = this

      vm.storeId = $stateParams.id
      vm.isShowCondition = false
      vm.statusAll = false
      vm.pageSize = $location.search().pageSize or 10
      vm.currentPage = $location.search().currentPage or 1
      vm.pageCount = 0
      vm.orderNumber = ''

      vm.breadcrumb = [
        'order_management'
      ]

      # selete condition
      vm.initParams =
        status: ''
        beginCreatedAt: null
        endCreatedAt: null
        minAmount: ''
        maxAmount: ''
        staff: ''
        member: ''

      vm.params = angular.copy vm.initParams
      vm.params.page = 1

      vm.status = [
        name: 'store_order_pending'
        check: false
        type: 'pending'
      ,
        name: 'store_order_finished'
        check: false
        type: 'finished'
      ,
        name: 'store_order_canceled'
        check: false
        type: 'canceled'
      ]

      vm.list =
        columnDefs: [
          field: 'orderNumber'
          label: 'store_order_id'
          type: 'link'
          cellClass: 'text-el'
        ,
          field: 'createdAt'
          label: 'store_order_ordered_at'
          type: 'date'
          headClass: 'order-at'
        ,
          field: 'expectedPrice'
          label: 'store_order_goods_expexted_price'
          type: 'currency'
        ,
          field: 'totalPrice'
          label: 'store_order_goods_total_price'
          type: 'currency'
        ,
          field: 'staffName'
          label: 'store_order_service_staff'
          cellClass: 'text-el'
        ,
          field: 'consumerName'
          label: 'store_order_member_anonymous'
          type: 'html'
          headClass: 'member-width'
        ,
          field: 'orderStatus'
          label: 'store_order_status'
          type: 'translate'
        ,
          field: 'operations'
          label: 'operations'
          type: 'operation'
        ]
        data: []

        submitHandler: (idx) ->
          params =
            status: 'finished'
          restService.put config.resources.order + "/" + vm.list.data[idx].id, params, (data) ->
            _getOrder()
            notificationService.success 'store_order_finished_success'

        cancelHandler: (idx) ->
          params =
            status: 'canceled'
          restService.put config.resources.order + "/" + vm.list.data[idx].id, params, (data) ->
            _getOrder()
            notificationService.success 'store_order_cancel_success'

      _init = ->
        _getOrder()

      _getOrder = ->
        params = angular.copy vm.params

        hasValidateError = false
        if params.minAmount and vm.checkPrice(params.minAmount) isnt ''
          hasValidateError = true

        if params.maxAmount and vm.checkPrice(params.maxAmount) isnt ''
          hasValidateError = true

        if not hasValidateError and params.minAmount and params.maxAmount and parseFloat(params.minAmount) > parseFloat(params.maxAmount)
          hasValidateError = true
          validateService.highlight($('#amounts'), $filter('translate')('store_order_price_error_tip'))

        if not hasValidateError
          params['per-page'] = vm.pageSize
          params.page = vm.currentPage
          params.storeId = vm.storeId
          params.orderNumber = vm.orderNumber
          delete params.status
          params.status = []
          if not vm.statusAll
            angular.forEach vm.status, (item) ->
              params.status.push item.type if item.check
          params.status = params.status.join(',')

          restService.get config.resources.orders, params, (data) ->
            if data.items
              orders = []
              angular.forEach data.items, (item) ->
                item.operations = [
                  name: 'submit'
                  title: 'store_order_confirm'
                  disable: false
                ,
                  name: 'cancel'
                  title: 'store_order_revoke'
                  disable: false
                ]
                if item.orderNumber
                  item.orderNumber =
                    text: item.orderNumber
                    link: '/store/view/order/' + vm.storeId + '?id=' + item.id

                item.consumerName = '-'
                if item.consumer
                  consumerName = '<span>' + utilService.formateString(8, item.consumer.name) + '</span>'
                  tooltip = item.consumer.name
                  if not item.consumer.id
                    consumerName += '<span class="gray9">&nbsp;' + $filter('translate')('store_anonymous_remark') + '</span>'
                    tooltip += $filter('translate')('store_anonymous_remark')
                  item.consumerName =
                    text: $sce.trustAsHtml(consumerName)
                    tooltip: tooltip

                item.staffName = item.staff?.name or '-'

                item.orderStatus = ''
                if item.status
                  switch item.status
                    when 'pending'
                      item.orderStatus = 'store_order_pending'
                    when 'finished'
                      item.orderStatus = 'store_order_finished'
                      item.operations[0].disable = true
                      item.operations[1].disable = true
                    when 'canceled'
                      item.orderStatus = 'store_order_canceled'
                      item.operations[0].disable = true
                      item.operations[1].disable = true
                orders.push item
            vm.list.data = angular.copy orders
            vm.totalCount = data._meta.totalCount
            vm.pageCount = data._meta.pageCount
            vm.params['per-page'] = data._meta.perPage
            vm.params.page = data._meta.currentPage

      _init()

      _clearCondition = ->
        vm.params = angular.copy vm.initParams
        vm.statusAll = false
        angular.forEach vm.status, (item) ->
          item.check = false

      _restoreCondition = ->
        # reset condition
        _clearCondition()

        if vm.conditions and angular.isArray vm.conditions
          angular.forEach vm.conditions, (item) ->
            values = item.items
            switch item.type
              when 'status'
                vm.status.map (status) ->
                  status.check = $.inArray(status.name, values) isnt -1
                vm.statusAll = values.length is vm.status.length
              when 'createAt'
                if values[0] isnt '-'
                  vm.params.beginCreatedAt = moment(values[0]).valueOf()

                if values[2] isnt '-'
                  vm.params.endCreatedAt = moment(values[2]).valueOf()
              when 'amount'
                if values[0] isnt '-'
                  vm.params.minAmount = values[0]

                if values[2] isnt '-'
                  vm.params.maxAmount = values[2]
              when 'staff'
                vm.params.staff = values[0]
              when 'member'
                vm.params.member = values[0]

      _formatCondition = ->
        vm.conditions = []
        status =
          type: 'status'
          name: 'store_order_status'
          items: []

        angular.forEach vm.status, (item) ->
          status.items.push item.name if item.check

        if vm.statusAll
          status.items = [
            'store_order_pending'
          ,
            'store_order_finished'
          ,
            'store_order_canceled'
          ]
        if status.items.length > 0
          vm.conditions.push status

        if vm.params.beginCreatedAt or vm.params.endCreatedAt
          createAt =
            type: 'createAt'
            name: 'store_order_ordered_at'
            items: [
              $filter('date')(vm.params.beginCreatedAt, 'yyyy-MM-dd HH:mm:ss') or '-'
            ,
              'content_article_to'
            ,
              $filter('date')(vm.params.endCreatedAt, 'yyyy-MM-dd HH:mm:ss') or '-'
            ]
          vm.conditions.push createAt

        if vm.params.minAmount or vm.params.maxAmount
          amount =
            type: 'amount'
            name: 'store_order_goods_total_price'
            items: [
              vm.params.minAmount or '-'
            ,
              'content_article_to'
            ,
              vm.params.maxAmount or '-'
            ]
          vm.conditions.push amount

        if vm.params.staff
          staff =
            type: 'staff'
            name: 'store_order_service_staff'
            items: [
              vm.params.staff
            ]
          vm.conditions.push staff

        if vm.params.member
          member =
            type: 'member'
            name: 'store_order_member_anonymous'
            items: [
              vm.params.member
            ]
          vm.conditions.push member

      vm.search = ->
        _formatCondition()
        _getOrder()
        vm.list.emptyMessage = 'search_no_data'

      vm.clear = ->
        _clearCondition()
        vm.orderNumber = ''
        validateService.restore($('#minAmount'))
        validateService.restore($('#maxAmount'))
        return

      vm.checkPrice = (value) ->
        tip = ''
        reg = /^(([1-9]\d*)|0)(\.\d{1,2})?$/
        if value isnt '' and not reg.test value
          tip = 'product_promotion_basic_times_tip'
        tip

      vm.removeCondition = (type) ->
        switch type
          when 'status'
            vm.statusAll = false
            angular.forEach vm.status, (item) ->
              item.check = false
          when 'createAt'
            vm.params.beginCreatedAt = null
            vm.params.endCreatedAt = null
          when 'amount'
            vm.params.minAmount = ''
            vm.params.maxAmount = ''
          when 'staff'
            vm.params.staff = ''
          when 'member'
            vm.params.member = ''

        _getOrder()
        _formatCondition()

      vm.showConditions = ->
        vm.isShowCondition = not vm.isShowCondition
        if vm.isShowCondition
          _restoreCondition()

      vm.selectAllStatus = ->
        vm.statusAll = not vm.statusAll
        angular.forEach vm.status, (item) ->
          item.check = vm.statusAll

      vm.selectStatus = (item) ->
        vm.statusAll = false if item.check
        item.check = not item.check
        vm.statusAll = $filter('filter')(vm.status, {check: false}).length is 0

      vm.changeSize = (pageSize) ->
        vm.pageSize = pageSize
        _getOrder()

      vm.changePage = (currentPage) ->
        vm.currentPage = currentPage
        _getOrder()

      vm.removeError = ->
        validateService.restore($('#amounts'))

      $scope.$watch 'order.params.minAmount', (value) ->
        if not value or value is ''
          vm.removeError()
        else if not isNaN(parseInt(value)) and (not vm.params.maxAmount or parseInt(value) <= vm.params.maxAmount)
          vm.removeError()

      $scope.$watch 'order.params.maxAmount', (value) ->
        if not value or value is ''
          vm.removeError()
        else if not isNaN(parseInt(value)) and (not vm.params.minAmount or parseInt(value) >= vm.params.minAmount)
          vm.removeError()

      vm
  ]
