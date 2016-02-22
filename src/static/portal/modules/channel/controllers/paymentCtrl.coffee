define [
  'wm/app'
  'wm/config'
], (app, config) ->
  app.registerController 'wm.ctrl.channel.payment', [
    ->
      vm = this
      vm.tabs = [
        {
          active: true
          name: 'channel_payment_records'
          template: 'payment.html'
        }
        {
          active: false
          name: 'channel_refund_records'
          template: 'refund.html'
        }
      ]
      vm.breadcrumb = [
        text: 'channel_payment'
      ]

      vm.curTab = vm.tabs[0]
      vm
  ]

  app.registerController 'wm.ctrl.channel.payment.payment', [
    'restService'
    '$stateParams'
    '$scope'
    'exportService'
    '$filter'
    (restService, $stateParams, $scope, exportService, $filter) ->
      vm = this
      channelId = $stateParams.id

      vm.list =
        columnDefs: [
          field: 'tradeNo'
          label: 'channel_transaction_number'
          cellClass: 'text-el'
          type: 'typeDetail'
        ,
          field: 'outTradeNo'
          label: 'channel_business_order_number'
          type: 'link'
          cellClass: 'text-el'
        ,
          field: 'userName'
          label: 'channel_payment_records_member'
          cellClass: 'text-el'
        ,
          field: 'totalFee'
          label: 'channel_payment_records_amount'
          sortable: true
          desc: true
        ,
          field: 'subject'
          label: 'product_description'
          cellClass: 'text-el'
        ,
          field: 'paymentTime'
          label: 'channel_payment_records_time'
          sortable: true
          desc: true
          type: 'date'
        ]
        data: []
        emptyMessage: 'search_no_data'
        sortHandler: (colDef) ->
          vm.orderBy = colDef.field
          vm.ordering = if colDef.desc then 'desc' else 'asc'
          vm.currentPage = 1
          _getList()

      vm.changePage = (currentPage) ->
        vm.currentPage = currentPage
        _getList()

      vm.changeSize = (pageSize) ->
        vm.pageSize = pageSize
        vm.currentPage = 1
        _getList()

      vm.selectDate = ->
        _getList()

      vm.search = ->
        _getList()

      vm.export = ->
        if not vm.enableExport
          return

        params = _getParams()

        exportService.export 'channel-payment-record', config.resources.wechatPaymentExport, params, false
        vm.enableExport = false

      $scope.$on 'exportDataPrepared', (event, type) ->
        if type is 'channel-payment-record'
          vm.enableExport = true

      _getParams = ->
        params =
          pageNum: vm.currentPage
          pageSize: vm.pageSize
          channelId: channelId
        params.ordering = angular.copy vm.ordering if vm.ordering
        params.orderBy = angular.copy vm.orderBy if vm.orderBy
        params.tradeNo = vm.searchKey if vm.searchKey
        params.paymentTimeFrom = vm.startTime if vm.startTime
        params.paymentTimeTo = vm.endTime + 24 * 3600 * 1000 - 1 if vm.endTime
        params

      _getList = ->
        if channelId
          params = _getParams()

          restService.get config.resources.wechatPayments, params, (data) ->
            items = []
            if data
              vm.enableExport = true
              vm.totalItems = data.totalAmount
              vm.pageSize = data.pageSize
              angular.forEach data.results, (item) ->
                tradeNoDetail = if item.metadata?.isTest is false then '' else '(' + $filter('translate')('management_channel_test') + ')'
                outTradeNo =
                  text: item.metadata.orderNumber or item.outTradeNo
                outTradeNo.link = item.metadata.detailUrl if item.metadata?.detailUrl
                items.push
                  tradeNo:
                    text: item.tradeNo
                    detail: tradeNoDetail
                    tooltip: item.tradeNo
                    style:
                      'max-width': '70%'
                  outTradeNo: outTradeNo
                  paymentTime: item.paymentTime
                  totalFee: Number(item.totalFee) / 100
                  userName: item.extension?.buyerNickname or '--'
                  subject: item.subject

            vm.list.data = items

      _init = ->
        vm.searchKey = ''
        vm.currentPage = 1
        vm.pageSize = 10
        vm.totalItems = 0
        vm.startTime = moment().subtract(6, 'days').startOf('day').valueOf()
        vm.endTime = moment().startOf('day').valueOf()
        vm.enableExport = false

        _getList()

      _init()
      vm
  ]

  app.registerController 'wm.ctrl.channel.payment.refund', [
    'restService'
    '$stateParams'
    '$scope'
    'exportService'
    '$filter'
    (restService, $stateParams, $scope, exportService, $filter) ->
      vm = this
      channelId = $stateParams.id
      vm.list =
        columnDefs: [
          field: 'refundNo'
          label: 'channel_transaction_number'
          cellClass: 'text-el'
          type: 'typeDetail'
        ,
          field: 'userName'
          label: 'channel_refund_records_member'
          cellClass: 'text-el'
        ,
          field: 'refundFee'
          label: 'channel_refund_records_amount'
          sortable: true
          desc: true
        ,
          field: 'subject'
          label: 'product_description'
          cellClass: 'text-el'
        ,
          field: 'createTime'
          label: 'channel_refund_records_time'
          sortable: true
          desc: true
          type: 'date'
        ]
        data: []
        emptyMessage: 'search_no_data'
        sortHandler: (colDef) ->
          vm.orderBy = colDef.field
          vm.ordering = if colDef.desc then 'desc' else 'asc'
          vm.currentPage = 1
          _getList()

      vm.changePage = (currentPage) ->
        vm.currentPage = currentPage
        _getList()

      vm.changeSize = (pageSize) ->
        vm.pageSize = pageSize
        vm.currentPage = 1
        _getList()

      vm.selectDate = ->
        _getList()

      vm.search = ->
        _getList()

      vm.export = ->
        if not vm.enableExport
          return

        params = _getParams()

        exportService.export 'channel-refund-record', config.resources.wechatRefundExport, params, false
        vm.enableExport = false

      $scope.$on 'exportDataPrepared', (event, type) ->
        if type is 'channel-refund-record'
          vm.enableExport = true

      _getParams = ->
        params =
          pageNum: vm.currentPage
          pageSize: vm.pageSize
          channelId: channelId
        params.ordering = angular.copy vm.ordering if vm.ordering
        params.orderBy = angular.copy vm.orderBy if vm.orderBy
        params.refundNo = vm.searchKey if vm.searchKey
        params.createTimeFrom = vm.startTime if vm.startTime
        params.createTimeTo = vm.endTime + 24 * 3600 * 1000 - 1 if vm.endTime
        params

      _getList = ->
        if channelId
          params = _getParams()

          restService.get config.resources.wechatRefunds, params, (data) ->
            items = []
            if data
              vm.enableExport = true
              vm.totalItems = data.totalAmount
              vm.pageSize = data.pageSize
              angular.forEach data.results, (item) ->
                refundNoDetail = if item.metadata?.isTest is false then '' else '(' + $filter('translate')('management_channel_test') + ')'
                items.push
                  refundNo:
                    text: item.refundNo
                    detail: refundNoDetail
                    tooltip: item.refundNo
                    style:
                      'max-width': '70%'
                  createTime: item.createTime
                  refundFee: Number(item.refundFee) / 100
                  userName: item.extension?.buyerNickname or '--'
                  subject: item.metadata?.subject

            vm.list.data = items


      _init = ->
        vm.searchKey = ''
        vm.currentPage = 1
        vm.pageSize = 10
        vm.totalItems = 0
        vm.startTime = moment().subtract(6, 'days').startOf('day').valueOf()
        vm.endTime = moment().startOf('day').valueOf()
        vm.enableExport = false

        _getList()

      _init()
      vm
  ]
