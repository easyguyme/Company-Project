define [
  'wm/app'
  'wm/config'
  'core/directives/wmCharts'
  'core/controllers/oauthQrcodeCtrl'
], (app, config) ->
  app.registerController 'wm.ctrl.marketing.coupon', [
    ->
      vm = this
      vm.tabs = [
        {
          active: true
          name: 'product_coupon'
          template: 'list.html'
        }
        {
          active: false
          name: 'product_acquisition'
          template: 'acquisition.html'
        }
        {
          active: false
          name: 'product_redemption'
          template: 'acquisition.html' # the page is similar to acquisition, so combined three pages into one html
        }
      ]
      vm.curTab = vm.tabs[0]

      vm.breadcrumb = [
        'product_coupon'
      ]

      vm
  ]

  app.registerController 'wm.ctrl.marketing.coupon.list', [
    'restService'
    'notificationService'
    '$location'
    '$filter'
    '$modal'
    (restService, notificationService, $location, $filter, $modal) ->
      vm = this

      RELATIVE = 'relative'
      ABSOLUTE = 'absolute'
      DATE_FORMAT = 'YYYY-MM-DD'

      vm.currentPage = $location.search().currentPage or 1
      vm.pageSize = $location.search().pageSize or 10
      vm.orderby = '{"createdAt":"desc"}'

      vm.couponList = {
        columnDefs: [
          {
            field: 'name'
            label: 'product_coupon_name'
            type: 'link'
            cellClass: 'text-el'
          }
          {
            field: 'type'
            label: 'product_coupon_type'
            type: 'translate'
          }
          {
            field: 'total'
            label: 'product_coupon_sku'
            type: 'modify'
            kind: 'plain'
            sortable: true
            desc: true
          }
          {
            field: 'validation'
            label: 'product_coupon_validation_date'
            type: 'modify'
          }
          {
            field: 'url'
            label: 'product_coupon_url'
            type: 'copy'
          }
          {
            field: 'operations'
            label: 'operations'
            type: 'operation'
          }
        ]
        data: []
        deleteTitle: 'product_coupon_delete_title'

        statisticsHandler: (idx) ->
          modalInstance = $modal.open(
            templateUrl: 'couponStatistics.html'
            controller: 'wm.ctrl.marketing.coupon.couponStatistics as coupon'
            windowClass: 'contentspread-dialog'
            resolve:
              modalData: ->
                id: vm.couponList.data[idx].id
          )

        qrcodeHandler: (idx, event) ->
          vm.isShowQrcodeDropdown = not vm.isShowQrcodeDropdown
          if vm.isShowQrcodeDropdown
            coupon = vm.couponList.data[idx]
            qrcodePaneTop = $(event.target).offset().top - 15 - $('.portal-message').height()
            vm.position =
              right: 120
              top: qrcodePaneTop
            qrcodeList = []
            vm.qrcodeList = _getQrcodeList(coupon).qrcodeList
            vm.couponIndex = idx
          return

        newqrcodeHandler: (idx) ->
          modalInstance = $modal.open(
            templateUrl: '/build/modules/core/partials/oauthQrcode.html'
            controller: 'wm.ctrl.core.oauthQrcode'
            windowClass: 'qrcode-dialog'
            resolve:
              modalData: ->
                edit: false
                tip: 'product_create_qrcode_tip'
                params:
                  couponId: vm.couponList.data[idx].id
                resource: config.resources.createCouponQrcode

            ).result.then( (data) ->
              if data
                _getList()
          )

        deleteHandler: (idx) ->
          id = vm.couponList.data[idx].id
          restService.del "#{config.resources.coupon}/#{id}", (data) ->
            _getList()
            notificationService.success 'product_delete_success'

        modifyHandler: (idx, colDef) ->
          modalInstance = $modal.open(
            templateUrl: 'editCoupon.html'
            controller: 'wm.ctrl.marketing.coupon.editCoupon as coupon'
            windowClass: 'user-dialog'
            resolve:
              modalData: ->
                colDef: colDef
                coupon: vm.couponList.data[idx]
            ).result.then( (data) ->
              if data
                _getList()
          )

        sortHandler: (colDef) ->
          key = colDef.field
          value = if colDef.desc then 'desc' else 'asc'
          vm.orderby = "{\"#{key}\":\"#{value}\"}"
          vm.currentPage = 1
          _getList()

      }

      vm.editQrcode = (idx) ->
        modalInstance = $modal.open(
          templateUrl: '/build/modules/core/partials/oauthQrcode.html'
          controller: 'wm.ctrl.core.oauthQrcode'
          windowClass: 'qrcode-dialog'
          resolve:
            modalData: ->
              edit: true
              tip: 'product_create_qrcode_tip'
              channels: _getQrcodeList(vm.couponList.data[idx]).channelIds
              params:
                couponId: vm.couponList.data[idx].id
              resource: config.resources.createCouponQrcode

          ).result.then( (data) ->
            if data
              vm.isShowQrcodeDropdown = false
              _getList()
        )

      vm.changeSize = (pageSize) ->
        vm.pageSize = pageSize
        vm.currentPage = 1
        _getList()

      vm.changePage = (currentPage) ->
        vm.currentPage = currentPage
        _getList()

      vm.search = ->
        vm.currentPage = 1
        _getList()
        vm.couponList.emptyMessage = 'search_no_data'

      _getQrcodeList = (coupon) ->
        qrcodeList = []
        channelIds = []

        for qrcode in coupon.qrcodes
          origin = if qrcode.origin is 'wechat' then 'wechat_service' else qrcode.origin
          qrcodeList.push {icon: "/images/customer/#{origin}.png", title: qrcode.channelName, name: "#{origin}_#{coupon.title}", link: qrcode.url}
          channelIds.push qrcode.channelId

        {channelIds: channelIds, qrcodeList: qrcodeList}

      _getList = ->
        vm.couponList.emptyMessage = ''

        params =
          title: vm.searchKey
          fields: 'id, title, type, total, time, url, qrcodes'
          orderBy: vm.orderby
          'per-page': vm.pageSize
          page: vm.currentPage

        restService.get config.resources.coupons, params, (data) ->

          vm.totalCount = data._meta.totalCount
          list = []

          for item in data.items
            item.name = {
              text: item.title
              link: '/marketing/view/coupon/' + item.id
            }
            if item.time.type is ABSOLUTE
              item.validation = {
                key: 'product_coupon_validation_key'
                values:
                  startTime: _formatDate item.time.beginTime
                  endTime: _formatDate item.time.endTime
              }
              if _isExpired(item.time.endTime)
                item.validation.key = 'product_coupon_validation_expired_key'

            else
              item.validation = {
                key: 'product_coupon_receive_key'
                values:
                  startTime: item.time.beginTime
                  endTime: item.time.endTime
              }
              if item.time.beginTime is 0
                item.validation = {
                  key: 'product_coupon_receive_current_key'
                  values:
                    endTime: item.time.endTime
                }

            operations = [
              name: 'statistics'
            ]

            if not item.qrcodes or item.qrcodes.length is 0
              operations.splice 1, 0, {name: 'newqrcode'}
            else
              operations.splice 1, 0, {name: 'qrcode', title: 'download_qrcode'}

            item.operations = operations

            list.push item

          vm.couponList.data = list

      _isExpired = (date) ->
        if moment(date).startOf('day').valueOf() < moment().startOf('day').valueOf()
          return true
        else
          return false

      _formatDate = (stringDate) ->
        return moment(stringDate).format(DATE_FORMAT)

      _getList()

      vm

  ]

  app.registerController 'wm.ctrl.marketing.coupon.acquisition', [
    'restService'
    'notificationService'
    '$location'
    '$filter'
    (restService, notificationService, $location, $filter) ->
      vm = this

      _orderBy = 'desc'

      vm.toggleFilter = ->
        vm.isHidden = not vm.isHidden
        vm.isShow = not vm.isShow
        _judgeShowCondition()

      _judgeShowCondition = ->
        vm.showCondition = not vm.isShow and not $.isEmptyObject(vm.filters)

      # clear all the filter query and empty the filters
      vm.clearFilters = ->
        _clearDatePicker()
        vm.searchKey = ''
        vm.filters = {}

      # clear the date time both in datepicker and in vm.filters
      _clearDatePicker = ->
        delete vm.filters.startTime
        delete vm.filters.endTime
        delete vm.startTime
        delete vm.endTime

      vm.search = ->
        _getRecords()

      vm.clearTime = ->
        _clearDatePicker()
        _getRecords()
        _judgeShowCondition()

      _init = ->
        # initial the current page type, "1" for acquisition, "2" for redemption, "3" for deletion
        vm.pageType = $location.search().active.toString()
        # initial the prefix of i18n
        vm.i18nPrefix = switch
          when vm.pageType is '1' then 'acquisition_acquired'
          when vm.pageType is '2' then 'redemption_redeemed'
          when vm.pageType is '3' then 'deletion_deleted'
        # initial the screening-condition button's icon
        vm.isHidden = true
        vm.isShow = false
        # filter object initialization
        vm.filters = {}
        # datepicker initialization
        vm.startTime = vm.endTime = undefined # can not set to null, or the display of "customer_follower_selected_condition" will be a bug
        # Table definitions initialization
        vm.list =
          columnDefs: [
            {
              field: 'memberName'
              label: 'product_acquisition_member_name'
            },{
              field: 'phoneNumber'
              label: 'product_acquisition_phone_number'
            },{
              field: 'couponName'
              label: 'product_acquisition_coupon_name'
              type: 'link'
            },{
              field: 'couponType'
              label: 'product_acquisition_coupon_type'
            },{
              field: 'quantity'
              label: "product_#{vm.i18nPrefix}_quantity"
            },{
              field: 'date'
              label: "product_#{vm.i18nPrefix}_date"
              sortable: true
              desc: true
              type: 'date'
            }
          ],
          data: []
          operations: []
          sortHandler: (colDef) ->
            _orderBy = if colDef.desc then 'desc' else 'asc'
            params = _getFilters()
            params.orderBy = _orderBy
            restService.get config.resources.couponLogs, params, (data) ->
              vm.list.data = _convertToData(data)
              $.extend vm.pagination, _convertToPagination(data)
          emptyMessage: 'search_no_data'

        if vm.pageType is "2"
          vm.list.columnDefs.push({
            field: 'store'
            label: "product_#{vm.i18nPrefix}_store"
          })
        # Pagination initializaion
        vm.pagination =
          pageCount: 0
          currentPage: $location.search().currentPage or 1
          pageSize: $location.search().pageSize or 10
          totalItems: 0
          changeSize: (pageSize) ->
            vm.pagination.pageSize = pageSize
            vm.pagination.currentPage = 1
            _getRecords()
          changePage: (currentPage) ->
            vm.pagination.currentPage = currentPage
            _getRecords()
        # table content initialization
        _getRecords()
        return

      # send get request to rest server
      _getRecords = ->
        params = _getFilters()
        params.orderBy = _orderBy
        restService.get config.resources.couponLogs, params, (data) ->
          vm.list.data = _convertToData(data)
          $.extend vm.pagination, _convertToPagination(data)

      # get the data in all filters and return it
      _getFilters = ->
        params = {}
        params.status = switch
          when vm.pageType is "1" then "received" # here, the received equals to acquired
          when vm.pageType is "2" then "redeemed"
          when vm.pageType is "3" then "deleted"
        $.extend params, _getPagination() # put the pagination info into params
        $.extend params, _getSearchKey() # put the content of search bar into params
        # put all the chosen filt data into vm.filters
        $.extend vm.filters, _getDatePickers()
        #put the vm.filters into params
        $.extend params, vm.filters
        return params

      # get the date time in datepicks
      _getDatePickers = ->
        return {
          startTime: vm.startTime
          endTime: vm.endTime
        }
      # get the searchkey memberName/phoneNumber/couponName in wm-search
      _getSearchKey = ->
        return {searchKey: vm.searchKey}
      # get the pagination parameters
      _getPagination = ->
        return {
          'per-page': vm.pagination.pageSize
          page: vm.pagination.currentPage
        }

      # access document/api.md to see the struct of records
      # extract data from records and put it in table's data
      _convertToData = (records) ->
        tableData = []
        if records.items.length > 0
          angular.forEach records.items, (record) ->
            data = {}
            data.memberName = record.member?.name
            data.phoneNumber = record.member?.phone
            data.couponName = {}
            data.couponName.text = record.title
            data.couponName.link = "/marketing/view/coupon/#{record.couponId}"
            data.couponType = $filter('translate')(record.type) # may need translate
            data.quantity = record.total
            data.date = record.operationTime
            data.store = record.store?.name if vm.pageType is "2"
            tableData.push data
        return tableData

      # extract data from records and put it in pagnation's data
      _convertToPagination = (records) ->
        paginationData = {}
        if records._meta?
          paginationData.pageCount = records._meta.pageCount
          paginationData.pageSize = records._meta.perPage
          paginationData.totalItems = records._meta.totalCount
          paginationData.currentPage = records._meta.currentPage
        return paginationData

      _init()
      vm
  ]

  app.registerController 'wm.ctrl.marketing.coupon.editCoupon', [
    '$modalInstance'
    'restService'
    'modalData'
    'notificationService'
    '$location'
    '$filter'
    ($modalInstance, restService, modalData, notificationService, $location, $filter) ->
      vm = this

      SKU = 'total'
      VALIDATION = 'validation'
      ABSOLUTE = 'absolute'
      RELATIVE = 'relative'
      ADD = 'add'
      SUB = 'sub'
      DATE_FORMAT = 'YYYY-MM-DD'

      coupon = modalData.coupon
      vm.colDef = modalData.colDef

      vm.triggerTimeItems = []
      vm.dayItems = []

      dayI18n = $filter('translate')('management_unit_day')

      # init select
      vm.skuItems = [
        {
          text: 'product_coupon_add_sku'
          value: 'add'
        }
        {
          text: 'product_coupon_sub_sku'
          value: 'sub'
        }
      ]
      vm.type = vm.skuItems[0].value

      vm.triggerTimeItems = [
        {
          value: 0
          text: 'current_day'
        }
        {
          value: 1
          text: 'one_day'
        }
      ]

      vm.dayItems = [
        {
          value: 1
          text: 'one_day'
        }
      ]

      for i in [2..90]
        vm.triggerTimeItems.push {value: i, text: "#{i}#{dayI18n}"}
        vm.dayItems.push {value: i, text: "#{i}#{dayI18n}"}

      vm.beginTime = vm.triggerTimeItems[0].value

      vm.endTime = vm.dayItems[29].value

      vm.checkSku = ->
        intRex =  /^[0-9]*[1-9][0-9]*$/
        error = ''
        if not intRex.test vm.sku
          error = 'customert_point_input_string'
        else if vm.type is SUB and Number(vm.sku) > vm.total
          error = 'product_coupon_sku_error'

        error

      vm.save = ->
        _sendData()

      vm.hideModal = ->
        $modalInstance.close()

      _checkDate = ->
        if vm.beginTime > vm.endTime
          return false
        return true

      _handleTime = ->
        vm.beginTime = moment(coupon.time.beginTime).startOf('day').valueOf()
        vm.endTime = moment(coupon.time.endTime).startOf('day').valueOf()

        vm.isDisableStartPicker = vm.beginTime < moment().startOf('day').valueOf()
        vm.isDisableEndPicker = vm.endTime < moment().startOf('day').valueOf()

        if not vm.isDisableStartPicker
          vm.startPickerConfig =
            minDate: moment().startOf('day')

        if not vm.isDisableEndPicker
          vm.endPickerConfig =
            minDate: moment().startOf('day')

      # toggle edit sku or date
      _fillEdit = ->
        if modalData.colDef is SKU
          vm[SKU] = coupon.total
          vm.title = 'product_coupon_edit_sku'
        else
          vm.title = 'product_coupon_edit_validation_date'
          if coupon.time.type is ABSOLUTE
            _handleTime()
            vm.date = ABSOLUTE
          else
            vm.date = RELATIVE
            vm.beginTime = coupon.time.beginTime
            vm.endTime = coupon.time.endTime

      _getParams = ->
        params = {}

        if modalData.colDef is SKU
          params.total = if vm.type is ADD then Number(vm.sku) else Number(vm.sku) * (-1)
        else if vm.date is ABSOLUTE
          params.time =
            type: ABSOLUTE
            beginTime: vm.beginTime
            endTime: vm.endTime
        else
          params.time =
            type: RELATIVE
            beginTime: vm.beginTime
            endTime: vm.endTime

        params

      _sendData = ->
        if vm.colDef is SKU and vm.checkSku()
          return
        if vm.colDef is VALIDATION and not _checkDate()
          return

        params = _getParams()
        id = coupon.id
        restService.put "#{config.resources.coupon}/#{id}", params, (data) ->
          $modalInstance.close 'ok'

      _fillEdit()

      vm

  ]

  app.registerController 'wm.ctrl.marketing.coupon.couponStatistics', [
    '$modalInstance'
    'restService'
    'modalData'
    'notificationService'
    '$location'
    ($modalInstance, restService, modalData, notificationService, $location) ->
      vm = this

      DATE_FORMAT = 'YYYY-MM-DD'

      couponId = modalData.id

      vm.endTime = moment().subtract(1, 'days').startOf('day').valueOf()
      vm.beginTime = moment().subtract(7, 'days').startOf('day').valueOf()

      vm.overview = [
        text: 'product_coupon_receive_number'
      ,
        text: 'product_coupon_redeem_number'
      ]

      vm.lineChartOptions =
        color: ["#57C6CD", "#C490BF"]

      vm.hideModal = ->
        $modalInstance.close()

      vm.selectDate = ->
        _getStats()

      _getOverview = ->
        restService.get "#{config.resources.couponStatOverview}/#{couponId}", (data) ->
          vm.overview[0].value = data.totalRecievedNum or 0
          vm.overview[1].value = data.totalRedeemedNum or 0

      _getStats = ->
        params =
          startTime: vm.beginTime
          endTime: vm.endTime

        restService.get "#{config.resources.couponStats}/#{couponId}", params, (data) ->
          _drawChart(data)

      _drawChart = (data) ->
        series = []
        serieNames =
          recievedNum: 'product_coupon_receive_number'
          redeemedNum: 'product_coupon_redeem_number'

        for key of data.count
          series.push {name: serieNames[key], data: data.count[key]}

        vm.lineChartOptions.categories = data.date
        vm.lineChartOptions.series = series
        vm.lineChartOptions.startDate = moment(vm.beginTime).format(DATE_FORMAT)
        vm.lineChartOptions.endDate = moment(vm.endTime).format(DATE_FORMAT)

      _getOverview()

      vm
  ]
