define [
  'wm/app'
  'wm/config'
  'core/directives/wmSlider'
], (app, config) ->
  app.registerController 'wm.ctrl.mall.redemption', [
    'restService'
    'notificationService'
    '$location'
    '$scope'
    'validateService'
    '$filter'
    'exportService'
    'searchFilterService'
    'utilService'
    '$modal'
    (restService, notificationService, $location, $scope, validateService, $filter, exportService, searchFilterService, utilService, $modal) ->
      vm = this
      vm.SERACH_CACHE = 'exchangeSearchCache'
      MAXNUMBER = 1600
      origins =
        WECHAT: 'wechat'
        WEIBO: 'weibo'
        ALIPAY: 'alipay'
        OFFLINE: 'offline' # offline
        PORTAL: 'portal' # offline
        APP_ANDROID: 'app:android'
        APP_IOS: 'app:ios'
        APP_WEB: 'app:web' # mobile browser
        APP_WEBVIEW: 'app:webview' # mobile browser
        OTHERS: 'others' # pc

      appTextMap =
        app_android: 'app_android'
        app_ios: 'app_ios'
        app_web: 'app_web'
        app_webview: 'app_webview'

      otherTextMap =
        portal: 'portal'
        weibo: 'weibo'
        alipay: 'alipay'
        others: 'others'

      autoNames = ['wechat', 'weibo', 'alipay']

      method =
        express: 'product_goods_courier_service'
        self: 'product_goods_local_pickup'

      _init = ->
        vm.sliderOptions =
          min: 0
          max: MAXNUMBER
          step: 1
          range: true
          usedScores: [0, MAXNUMBER]
          ticks: [0, 200, 400, 600, 800, 1000, 1200, 1400, MAXNUMBER]
          ticksLabels: ['0', '200', '400', '600', '800', '1000', '1200', '1400', $filter('translate')('above')]

        vm.currentPage = $location.search().currentPage or 1
        vm.pageSize = $location.search().pageSize or 10
        vm.isShow = false
        vm.hasSelectedCondition = false
        vm.enableExport = true

        vm.breadcrumb = [
          'mall_redemption'
        ]

        # Table definitions
        vm.list =
          columnDefs: [
            {
              field: 'recordId',
              label: 'product_record_id'
              cellClass: 'goods-table-id'
              type: 'link'
            }, {
              field: 'memberNameLink'
              label: 'product_goods_exchanger_name'
              type: 'link'
            }, {
              field: 'telephone'
              label: 'cellphone'
              headClass: 'head-tel-wrapper'
            }, {
              field: 'address'
              label: 'product_receive_address'
              cellClass: 'cell-address-wrapper'
            } , {
              field: 'goods'
              label: 'product_exchange_item'
              type: 'multiLink'
              headClass: 'head-product-wrapper'
              cellClass: 'cell-product-wrapper'
            }, {
              field: 'createdAt'
              label: 'product_goods_redeemed_time'
              headClass: 'head-time-wrapper'
              sortable: true
              desc: true
            }, {
              field: 'method'
              label: 'product_goods_shipping_method'
            }, {
              field: 'channel'
              label: 'product_goods_redeemed_channel'
              type: 'iconText'
              cellClass: 'cell-usedfrom-wrapper'
              headClass: 'head-channel-wrapper'
            }, {
              field: 'shipStatus'
              label: 'product_goods_shipping_status'
              type: 'twoStatus'
              cellClass: 'cell-status-wrapper'
              headClass: 'head-status-wrapper'
            }
          ],
          data: [],
          hasLoading: true
          sortHandler: (colDef) ->
            key = colDef.field
            value = if colDef.desc then 'desc' else 'asc'
            vm.orderBy = "{\"#{key}\":\"#{value}\"}"
            vm.currentPage = 1
            _getExchanges()

          linkHandler: (idx) ->
            modalInstance = $modal.open(
              templateUrl: 'recordDetail.html'
              controller: 'wm.ctrl.mall.redemption.detail as record'
              windowClass: 'user-dialog'
              resolve:
                modalData: ->
                  record: vm.list.data[idx]
            )

          statusHandler: (idx, $event) ->
            notificationService.confirm $event,{
              submitCallback: ->
                params =
                  id: vm.list.data[idx].id
                restService.post config.resources.goodsExchange, params, (data) ->
                  vm.list.data[idx].isDelivered = true
                  vm.list.data[idx].shipStatus.status = 'two'
                  notificationService.success 'product_goods_ship_success'
              title: 'product_goods_ship_tip'
            }

        if $location.search().search is 't' and searchFilterService.getFilter vm.SERACH_CACHE
          _getExchanges('back')
          vm.showConditions()
          vm.isShow = false
        else
          _getExchanges()

      _getParams = (type) ->
        #Get params form searchFilter when page go back.
        if type is 'back'
          backParams = searchFilterService.getFilter vm.SERACH_CACHE
          vm.pageSize = backParams['per-page']
          vm.currentPage = backParams.page
          vm.searchKey = backParams.key
          vm.startTime = backParams.startTime
          vm.endTime = backParams.endTime
          vm.usedScoreMax = backParams.usedScoreMax
          vm.usedScoreMin = backParams.usedScoreMin
          vm.orderBy = backParams.orderBy
          vm.accountIds = backParams.accounts

        params =
          'per-page': vm.pageSize
          'page': vm.currentPage

        params.key = vm.searchKey if vm.searchKey
        params.startTime = vm.startTime if vm.startTime
        params.endTime = vm.endTime if vm.endTime
        params.accounts = angular.copy vm.accountIds if vm.accountIds

        if params.startTime and params.endTime and vm.checkTime(params.startTime, params.endTime) isnt ''
          return

        params.usedScoreMax = parseInt(vm.usedScoreMax) if vm.usedScoreMax
        params.usedScoreMin = parseInt(vm.usedScoreMin) if vm.usedScoreMin
        params.orderBy = vm.orderBy if vm.orderBy

        if vm.ship
          params.ship = 'product_goods_shipped'
          params.isDelivered = '1'
        if vm.noShip
          params.noShip = 'product_goods_not_shipped'
          params.isDelivered = if params.ship then '0,1' else '0'

        if vm.express
          params.express = 'product_goods_courier_service'
          params.receiveMode = 'express'
        if vm.self
          params.self = 'product_goods_local_pickup'
          params.receiveMode = if params.express then 'express,self' else 'self'

        vm.params = angular.copy params

        # Backup condition
        searchFilterService.setFilter vm.SERACH_CACHE, vm.params

        vm.hasSelectedCondition = vm.usedScoreMax or vm.usedScoreMin or vm.startTime or vm.endTime

        params

      vm.isSelectedAccount = (id) ->
        return $.inArray(id, vm.params.accounts) isnt -1

      _getOriginIcon = (origin) ->
        text = ''
        icon = ''
        switch origin
          when origins.WECHAT
            icon = 'wechat_service'
            text = 'wechat'
          when origins.OFFLINE
            icon = 'portal'
            text = 'portal'
          when origins.WEIBO, origins.ALIPAY, origins.PORTAL
            icon = origin
            text = otherTextMap[icon]
          when origins.APP_ANDROID, origins.APP_IOS, origins.APP_WEB, origins.APP_WEBVIEW
            icon = origin.replace ':', '_'
            text = appTextMap[icon]
          else
            icon = origins.OTHERS
            text = otherTextMap[icon]

        channel =
          text: $filter('translate')(text)
          icon: "/images/customer/#{icon}.png"

        channel.icon = '' if icon is origins.OTHERS

        channel


      _getExchanges = (type) ->
        params = _getParams(type)

        params.accounts = params.accounts.join(',') if params.accounts

        restService.get config.resources.exchanges, params, (data) ->
          if data

            angular.forEach data.items, (item) ->
              item.number =
                text: item.sku
                link: '/mall/view/goods/' + item.goodsId
              item.name =
                name: item.productName
                url: item.picture or ''
              item.recordId =
                text: item.id
                link: '#'
              item.shipStatus =
                oneStatusText: 'product_goods_shipping_soon'
                twoStatusText: 'product_goods_shipped'
                icon: '/images/product/shipped_orders_list.png'
                status: if item.isDelivered then 'two' else 'one'
                link: '#'
              item.method = $filter('translate')(method[item.receiveMode])

              item.memberName = '--' if not item.memberName
              item.memberNameLink =
                tooltip: item.memberName
                text: utilService.formateString 8, item.memberName
                link: '/member/view/member/' + item.memberId

              originIcon = angular.copy _getOriginIcon(item.usedFrom.type)
              if $.inArray(originIcon.text, autoNames) > -1
                originIcon.text = item.usedFrom.name
              item.channel = originIcon

              if item.goods
                item.goodsCount = ''
                angular.forEach item.goods, (good) ->
                  good.text = good.productName
                  good.text = good.text.slice(0, 7) + '...' if good.text.length > 7
                  good.text += ' *' + good.count
                  good.link = '/mall/view/goods/' + good.id
                  good.tooltip = good.productName

            vm.list.data = angular.copy data.items
            vm.list.hasLoading = false

            vm.totalItems = data._meta.totalCount
            vm.pageSize = data._meta.perPage
            vm.pageCount = data._meta.pageCount
            vm.currentPage = data._meta.currentPage
        , ->
          vm.list.hasLoading = false

      _showError = (id, msg, isAllLight) ->
        if isAllLight
          validateService.highlight($("#usedScoreMin"), '')
          validateService.highlight($("#usedScoreMax"), '')
        validateService.highlight($("##{id}"), $filter('translate')(msg))
        return

      vm.changeSlide = ->
        $scope.$apply( ->
          vm.usedScoreMin = vm.sliderOptions.usedScores[0]
          if vm.sliderOptions.usedScores[1] is vm.sliderOptions.max
            vm.usedScoreMax = null
          else
            vm.usedScoreMax = vm.sliderOptions.usedScores[1]
        )

      vm.checkScore = (id) ->
        tip = ''
        number = vm[id]
        lightAll = false
        if number and number isnt ''
          reg = /^[0-9]*$/
          if not reg.test number
            tip = 'product_exchange_used_score_tip'
            errorElemId = id
          else if id is 'usedScoreMin' and vm.usedScoreMax and parseInt(number) > parseInt(vm.usedScoreMax)
            tip = 'product_exchange_used_scores_tip'
            lightAll = true
            errorElemId = 'usedScores'
          else if id is 'usedScoreMax' and vm.usedScoreMin and parseInt(number) < parseInt(vm.usedScoreMin)
            tip = 'product_exchange_used_scores_tip'
            lightAll = true
            errorElemId = 'usedScores'
        if tip isnt ''
          _showError errorElemId, tip, lightAll
        tip

      vm.checkTime = (startTime, endTime) ->
        tip = ''
        if startTime > endTime
          tip = 'helpdesk_setting_time_error'
          validateService.highlight($('#beginDatePicker'), $filter('translate')('helpdesk_setting_time_error'))
          validateService.highlight($('#endDatePicker'))
        tip

      vm.restoreTimeError = ->
        validateService.restore($("#beginDatePicker"))
        validateService.restore($("#endDatePicker"))

      vm.removeError = ->
        validateService.restore($("#usedScoreMin"))
        validateService.restore($("#usedScoreMax"))
        validateService.restore($("#usedScores"))
        return

      vm.showConditions = ->
        vm.isShow = not vm.isShow

        if not vm.isShow
          # restore condition
          vm.usedScoreMax = vm.params.usedScoreMax
          vm.usedScoreMin = vm.params.usedScoreMin
          vm.startTime = vm.params.startTime
          vm.endTime = vm.params.endTime

          minValue = vm.usedScoreMin or 0
          maxValue = vm.usedScoreMax or MAXNUMBER

          vm.sliderOptions.usedScores = [minValue, maxValue]
          vm.accountIds = angular.copy vm.params.accounts

          vm.ship = vm.params.ship?
          vm.noShip = vm.params.noShip?
          vm.isDelivered = vm.ship and vm.noShip
          vm.express = vm.params.express?
          vm.self = vm.params.self?
          vm.receiveMode = vm.express and vm.self

      _clear = ->
        vm.accountIds = []

      vm.clearAccounts = ->
        _clear()
        _getExchanges()

      vm.clear = ->
        vm.searchKey = ''
        vm.restoreTimeError()
        vm.sliderOptions.usedScores = [0, MAXNUMBER]
        vm.usedScoreMin = null
        vm.usedScoreMax = null
        vm.startTime = null
        vm.endTime = null
        vm.orderBy = null
        vm.ship = false
        vm.noShip = false
        vm.isDelivered = false
        vm.express = false
        vm.self = false
        vm.receiveMode = false
        _clear()

      vm.removeCondition = (type) ->
        switch type
          when 'time'
            vm.startTime = null
            vm.endTime = null
          when 'score'
            vm.sliderOptions.usedScores = [0, MAXNUMBER]
            vm.usedScoreMin = null
            vm.usedScoreMax = null
          when 'status'
            vm.ship = false
            vm.noShip = false
            vm.isDelivered = false
          when 'method'
            vm.express = false
            vm.self = false
            vm.receiveMode = false

        vm.currentPage = 1
        _getExchanges()

      vm.search = ->
        minTip = vm.checkScore('usedScoreMin')
        if minTip is 'product_exchange_used_scores_tip'
          return

        maxTip = vm.checkScore('usedScoreMax')
        if maxTip isnt '' or minTip isnt ''
          return

        vm.currentPage = 1
        _getExchanges()
        vm.list.emptyMessage = 'search_no_data'

      vm.changePage = (currentPage) ->
        vm.currentPage = currentPage
        _getExchanges()

      vm.changeSize = (pageSize) ->
        vm.pageSize = pageSize
        vm.currentPage = 1
        _getExchanges()

      vm.export = ->
        if not vm.enableExport
          return

        params = _getParams()

        params.accounts = params.accounts.join(',')

        exportService.export 'product-goods-exchange-record', config.resources.exportGoodsExchangeRecord, params, false
        vm.enableExport = false
        return

      $scope.$on 'exportDataPrepared', (event, type) ->
        if type is 'product-goods-exchange-record'
          vm.enableExport = true

      $scope.$watch 'exchange.usedScoreMin', (value) ->
        usedScoreMax = MAXNUMBER
        if vm.usedScoreMax and /^[0-9]*$/.test vm.usedScoreMax
          usedScoreMax = parseInt(vm.usedScoreMax)
        else
          usedScoreMax = vm.sliderOptions.usedScores[1]

        if not value or value is ''
          vm.sliderOptions.usedScores = [0, usedScoreMax]
          vm.removeError()
        else if not isNaN(parseInt(value)) and (not vm.usedScoreMax or parseInt(value) <= usedScoreMax)
          vm.sliderOptions.usedScores = [parseInt(value), usedScoreMax]
          vm.removeError()

      $scope.$watch 'exchange.usedScoreMax', (value) ->
        usedScoreMin =  0
        if vm.usedScoreMin and /^[0-9]*$/.test vm.usedScoreMin
          usedScoreMin = parseInt(vm.usedScoreMin)
        else
          usedScoreMin = vm.sliderOptions.usedScores[0]

        if not value or value is ''
          vm.sliderOptions.usedScores = [usedScoreMin, MAXNUMBER]
          vm.removeError()
        else if not isNaN(parseInt(value)) and (not vm.usedScoreMin or parseInt(value) >= usedScoreMin)
          vm.sliderOptions.usedScores = [usedScoreMin, parseInt(value)]
          vm.removeError()

      vm.selectAllMethod = ->
        vm.express = vm.receiveMode
        vm.self = vm.receiveMode

      vm.selectMethod = ->
        vm.receiveMode = vm.express and vm.self

      vm.selectAllStatus = ->
       vm.ship = vm.isDelivered
       vm.noShip = vm.isDelivered

      vm.selectStatus = ->
        vm.isDelivered = vm.ship and vm.noShip

      _init()

      vm
  ]

  app.registerController 'wm.ctrl.mall.redemption.detail', [
    'restService'
    'notificationService'
    'modalData'
    '$modalInstance'
    '$rootScope'
    '$scope'
    '$filter'
    (restService, notificationService, modalData, $modalInstance, $rootScope, $scope, $filter) ->
      vm = this
      rvm = $rootScope

      _init = ->
        vm.data = modalData.record
        vm.params =
          memberName: 'product_goods_exchanger_name'
          telephone: 'cellphone'
          method: 'product_goods_shipping_method'
          postcode: 'product_receive_postcode'
          address: 'product_receive_address'
          id: 'product_record_id'
          expectedScore: 'product_points_required'
          usedScore: 'product_points_consumed'
          productName: 'product_exchange_item'
          count: 'product_goods_exchanger_items_redeemed'
        vm.paramArray = ['memberName', 'telephone', 'method', 'postcode', 'address', 'id', 'expectedScore', 'usedScore', 'productName', 'count']
        vm.params.address = 'product_goods_pickup_location' if 'self' is vm.data.receiveMode
        _dataHandle()
        _setLanguage()

      _dataHandle = ->
        vm.data.productName = ''
        vm.data.count = ''
        for good in vm.data.goods
          vm.data.productName += good.productName + '，'
          vm.data.count += good.count + '，'
        vm.data.productName = vm.data.productName.substring(0, vm.data.productName.length - 1)
        vm.data.count = vm.data.count.substring(0, vm.data.count.length - 1)
        vm.data.postcode = vm.data.postcode or '--'
      _setLanguage = ->
        vm.language = $scope.user.language or 'zh_cn'
        rvm.$on '$translateChangeSuccess', (event, data) ->
          vm.language = data.language
        return

      vm.send = ->
        params =
          id: vm.data.id
        restService.post config.resources.goodsExchange, params, (data) ->
          vm.data.isDelivered = true
          vm.data.shipStatus.status = 'two'
          notificationService.success 'product_goods_ship_success'

      vm.hideModal = ->
        $modalInstance.close()

      _init()
      vm
  ]
