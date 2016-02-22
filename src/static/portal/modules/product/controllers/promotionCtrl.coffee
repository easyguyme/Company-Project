define [
  'wm/app'
  'wm/config'
  'core/directives/wmCharts'
], (app, config) ->
  app.registerController 'wm.ctrl.product.promotion', [
    ->
      vm = this

      vm.breadcrumb = [
        icon: 'promotion'
        text: 'promotion_code'
      ]

      vm.tabs = [
        {
          active: true
          name: 'product_promotion_active'
          template: 'activity.html'
        }
        {
          active: false
          name: 'product_promotion_exchange_record'
          template: 'exchangeRecord.html'
        }
        {
          active: false
          name: 'product_redemption_statistics'
          template: 'redeemStatistics.html'
        }
      ]
      vm.curTab = vm.tabs[0]

      vm
  ]

  app.registerController 'wm.ctrl.product.promotion.activity', [
    'restService'
    'notificationService'
    '$location'
    '$scope'
    (restService, notificationService, $location, $scope) ->
      vm = this

      _init = ->
        vm.currentPage = $location.search().currentPage or 1
        vm.totalItems = 0
        vm.pageSize = $location.search().pageSize or 10
        vm.searchKey = ''

        vm.activityList =
          columnDefs: [
            {
              field: 'name'
              label: 'product_activity'
              type: 'link'
              cellClass: 'text-el'
            }, {
              field: 'startTime'
              label: 'product_activity_start'
              type: 'date'
            }, {
              field: 'endTime'
              label: 'product_activity_end'
              type: 'date'
            }, {
              field: 'isActive'
              label: 'customer_card_status'
              type: 'status'
            },  {
              field: 'operations'
              label: 'operations'
              type: 'operation'
            }
          ]
          deleteTitle: 'product_promotion_delete_confirm'
          data: []
          selectable: false
          switchHandler: (idx) ->
            isActivated = not vm.activityList.data[idx].isActivated
            isAddTags = vm.activityList.data[idx].isAddTags
            vm.activityList.data[idx].isActive = if isActivated then 'ENABLE' else 'DISABLE'
            id = vm.activityList.data[idx].id
            data =
              isActivated: isActivated
              isAddTags: isAddTags
            restService.put config.resources.campaign + '/' + id, data, (data) ->
              notificationService.success 'product_promotion_update_status', false
              _getItems()
              return
          editHandler: (idx) ->
            $location.url '/product/edit/promotion/' + vm.activityList.data[idx].id
            return
          deleteHandler: (idx) ->
            id = vm.activityList.data[idx]?.id
            restService.del config.resources.campaign + '/' + id, (data) ->
              _getItems()
            return
        _getItems()
        return

      _getItems = ->
        condition =
          'per-page': vm.pageSize
          'page': vm.currentPage
          'search':
            'name': vm.searchKey
        restService.get config.resources.campaigns, condition, (data) ->
          if data.items
            activities = []
            angular.forEach data.items, (item) ->
              item.isActivated = false if item.isExpired
              item.switchIsDisabled = item.isExpired
              item.isActive = if item.isActivated then 'ENABLE' else 'DISABLE'
              if item.name
                item.name =
                  text: item.name
                  link: '/product/view/promotion/' + item.id
              item.operations = [
                {
                  name: 'edit'
                }, {
                  name: 'delete'
                }
              ]
              if item.isActivated
                item.operations[0].disable = true
                item.operations[1].disable = true
              if item.isExpired
                item.operations[0].disable = true
                item.operations[1].disable = false
              activities.push item
            vm.activityList.data = angular.copy activities
            vm.currentPage = data._meta.currentPage
            vm.totalItems = data._meta.totalCount
            vm.pageSize = data._meta.perPage
            vm.pageCount = data._meta.pageCount
        return

      vm.search = ->
        vm.currentPage = 1
        _getItems()
        vm.activityList.emptyMessage = 'search_no_data'

      vm.changePage = (currentPage) ->
        vm.currentPage = currentPage
        _getItems()

      vm.changeSize = (pageSize) ->
        vm.pageSize = pageSize
        vm.currentPage = 1
        _getItems()

      _init()
      vm
  ]

  app.registerController 'wm.ctrl.product.promotion.management', [
    'restService'
    'notificationService'
    '$location'
    '$scope'
    (restService, notificationService, $location, $scope) ->
      vm = this

      vm.currentPage = $location.search().currentPage or 1
      vm.totalItems = 0
      vm.pageSize = $location.search().pageSize or 10

      _getList = ->
        params =
          page: vm.currentPage
          'per-page': vm.pageSize
          orderBy: {createdAt: 'desc'}

        restService.get '/api/product/associations', params, (data) ->
          vm.totalItems = data._meta.totalCount
          list = data.items

          for item in list
            item.name = {
              text: item.productName
              link: '/product/view/product/' + item.productId
            }
            item.operations = [
              {
                name: 'edit'
                link: '/product/edit/management/promotion/' + item.id
              }
              {
                name: 'delete'
              }
            ]
            if item.used > 0 or item.isAssociated
              item.operations[1].disable = true

            vm.list.data = list


      vm.list =
        columnDefs: [
          {
            field: 'name'
            label: 'product_item_name'
            type: 'link'
          }, {
            field: 'used'
            label: 'product_promotion_code_used'
          }, {
            field: 'rest'
            label: 'product_promotion_code_unused'
          }, {
            field: 'all'
            label: 'product_promotion_total_count'
          },  {
            field: 'operations'
            label: 'operations'
            type: 'operation'
          }
        ]
        data: []
        selectable: false

        deleteHandler: (idx) ->
          id = vm.list.data[idx].id
          restService.del config.resources.productAssociation + '/' + id, (data) ->
            _getList()

        editHandler: (idx) ->

        exportHandler: (idx) ->
        # TODO

      vm.create = ->
        $location.url '/product/edit/management/promotion'

      vm.changePage = (currentPage) ->
        vm.currentPage = currentPage
        _getList()

      vm.changeSize = (pageSize) ->
        vm.pageSize = pageSize
        vm.currentPage = 1
        _getList()

      vm
  ]

  app.registerController 'wm.ctrl.product.promotion.exchangeRecord', [
    'restService'
    '$filter'
    '$scope'
    'validateService'
    'exportService'
    'utilService'
    '$location'
    '$q'
    (restService, $filter, $scope, validateService, exportService, utilService, $location, $q) ->

      vm = this

      vm.condition =
        accounts: []

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

      _populateTableData = (data) ->
        result = []
        if data
          angular.forEach data, (item) ->
            if item.usedFrom?.type?
              originIcon = angular.copy _getOriginIcon(item.usedFrom.type)
              if $.inArray(originIcon.text, autoNames) > -1
                originIcon.text = item.usedFrom.name
              item.channel = originIcon

            if item.member.type is 'score'
              memberPrize = '+' + item.member.scoreAdded + ' ' + $filter('translate')('product_promotion_basic_score_unit')
            else
              memberPrize = item.member.prize
            productSku =
              text: item.product.sku
              link: '/product/view/product/' + item.product.id

            memberTrueName = '--'
            if item.member.name
              memberTrueName =
                text: utilService.formateString 8, item.member.name
                tooltip: item.member.name

            result.push
              id: item.id
              code: item.code
              redeemTime: item.redeemTime
              memberName: memberTrueName
              memberPhone: item.member.phone
              memberCardNumber: item.member.cardNumber
              memberPrize: memberPrize
              productSku: productSku
              productName: item.product.name
              channel: item.channel
        result

      _getParams = ->
        params =
          page: vm.currentPage
          'per-page': vm.pageSize

        params.key = vm.searchKey if vm.searchKey
        params.startTime = vm.startTime if vm.startTime
        params.endTime = vm.endTime if vm.endTime
        params.accounts = vm.accountIds if vm.accountIds

        if params.startTime and params.endTime and _checkTime(params.startTime, params.endTime)
          return

        params.orderBy = vm.orderBy if vm.orderBy

        params.campaignId = vm.campaignType if vm.campaignType
        vm.params = angular.copy params

        params

      _getList = ->
        vm.enableExport = true
        params = _getParams()

        if params.accounts
          params.accounts = params.accounts.join(',')

        restService.get config.resources.promotionExchangeRecords, params, (data) ->
          vm.list.data = _populateTableData data.items
          vm.enableExport = false if vm.list.data.length is 0
          if data._meta
            vm.pageSize = data._meta.perPage
            vm.totalItems = data._meta.totalCount
            vm.pageCount = data._meta.pageCount

          vm.list.hasLoading = false
        , ->
          vm.list.hasLoading = false

      _checkTime = (startTime, endTime) ->
        isInvalid = false
        if startTime > endTime
          isValidate = true
          validateService.highlight($('#beginDatePicker'), $filter('translate')('helpdesk_setting_time_error'))
          validateService.highlight($('#endDatePicker'))
        isValidate

      _getPromotions = ->
        defered = $q.defer()
        restService.get config.resources.allCampaigns, (data) ->
          defered.resolve data
        defered.promise

      _init = ->
        vm.currentPage = $location.search().currentPage or 1
        vm.pageSize = $location.search().pageSize or 10
        vm.totalItems = 0
        vm.pageCount = 0
        vm.isShow = false

        vm.campaignTypes = [
        ]
        vm.campaignType = ''

        vm.list =
          columnDefs: [
            {
              field: 'id',
              label: 'product_record_id'
              cellClass: 'text-el'
            }, {
              field: 'memberName',
              label: 'product_goods_exchanger_name'
            }, {
              field: 'memberPhone'
              label: 'cellphone'
              headClass: 'head-tel-wrapper'
            }, {
              field: 'code'
              label: 'product_promotion_code'
            }, {
              field: 'productSku'
              label: 'product_promotion_goods_sku'
              type: 'link'
              cellClass: 'text-el'
            }, {
              field: 'productName'
              label: 'product_promotion_goods_name'
            }, {
              field: 'memberPrize'
              label: 'product_promotion_prize'
            }, {
              field: 'redeemTime'
              label: 'product_goods_redeemed_time'
              sortable: true
            }, {
              field: 'channel'
              label: 'product_promotion_campaign_channel'
              type: 'iconText'
              cellClass: 'cell-usedfrom-wrapper'
            }
          ]
          data: []
          selectable: false
          hasLoading: true
          sortHandler: (colDef) ->
            key = colDef.field
            value = if colDef.desc then 'asc' else 'desc'
            vm.orderBy = '{"' + key + '":' + '"' + value + '"}'
            vm.currentPage = 1
            _getList()

        _getList()
        _getPromotions().then (campaigns) ->
          if angular.isArray(campaigns) and campaigns.length > 0
            angular.forEach campaigns, (campaign) ->
              vm.campaignTypes.push {
                name: campaign.name
                value: campaign.id
              }

      _init()

      vm.isSelectedAccount = (id) ->
        return $.inArray(id, vm.params.accounts) isnt -1

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
        vm.list.emptyMessage = 'search_no_data'

      vm.showConditions = ->
        vm.isShow = if vm.isShow is true then false else true

        if vm.isShow
          vm.startTime =  if vm.params.startTime then vm.params.startTime else null
          vm.endTime =  if vm.params.endTime then vm.params.endTime else null

        if vm.params.campaignId
          vm.campaignType = vm.params.campaignId
          for item in vm.campaignTypes
            if vm.campaignType is item.value
              vm.campaignTypeName = item.name

        if vm.params.accounts
          vm.accountIds = angular.copy vm.params.accounts

      vm.removeCondition = ->
        vm.startTime = null
        vm.endTime = null
        vm.currentPage = 1
        _getList()
        return

      vm.removeCampaign = ->
        vm.campaignType = ''
        vm.campaignTypeName = ''
        vm.params.campaignId = ''
        vm.currentPage = 1
        _getList()

      _clear = ->
        vm.accountIds = []

      vm.clearAccounts = ->
        _clear()
        vm.currentPage = 1
        _getList()

      vm.clear = ->
        vm.startTime = null
        vm.endTime = null
        vm.searchKey = ''
        vm.campaignType = ''
        _clear()
        return

      vm.export = ->
        if not vm.enableExport
          return

        params = _getParams()

        if params.accounts
          params.accounts = params.accounts.join(',')

        exportService.export 'product-promotion-exchange-record', config.resources.exportPromoExchangeRecord, params, false
        vm.enableExport = false

      $scope.$on 'exportDataPrepared', (event, type) ->
        if type is 'product-promotion-exchange-record'
          vm.enableExport = true

      vm
  ]

  app.registerController 'wm.ctrl.product.promotion.redeemStatistics', [
    'restService'
    '$q'
    '$filter'
    'utilService'
    '$scope'
    'exportService'
    (restService, $q, $filter, utilService, $scope, exportService) ->
      vm = this

      COLORSERIES = [
        '#cbcbcb', '#50d7be', '#f5d773', '#ffafbe', '#78d7dc', '#b464c8', '#8fd2e7', '#f5be7d',
        '#f5af3c', '#bee19b', '#e1c3e6', '#fbaf98', '#b8c4ee', '#0070c0', '#205867', '#7030a0',
        '#ffc000', '#ffff00', '#92d050', '#00b050', '#6ab3f7', '#3f3151', '#4bacc6', '#974806',
        '#1d1b10', '#0f243e', '#244061', '#632423', '#4f6128', '#8064a2', '#92cddc', '#f79646',
        '#494429', '#1f497d', '#4f81bd', '#c0504d', '#9bbb59', '#b2a2c7', '#b7dde8', '#fac08f',
        '#938953', '#548dd4', '#95b3d7', '#d99694', '#c3d69b', '#ccc1d9', '#dbeef3', '#fbd5b5',
        '#c4bd97', '#8db3e2', '#b8cce4', '#e5b9b7', '#d7e3bc', '#e5e0ec', '#ebf1dd', '#fdeada',
        '#ddd9c3', '#c6d9f0', '#dbe5f1', '#f2dcdb'
      ]

      redemptionType =
        PARTICIPANTS: 1
        TOTAL: 2
        PRIZES: 3

      DEFAULTBOXHEIGHT = 300

      _init = ->
        vm.enableExportParticipants = true
        vm.enableExportTotal = true
        vm.enableExportPrizes = true

        vm.participantsHeight = DEFAULTBOXHEIGHT
        vm.totalHeight = DEFAULTBOXHEIGHT
        vm.prizesHeight = DEFAULTBOXHEIGHT

        vm.tabsWidth = $('.tabs').width() - 32

        vm.startDate = moment().subtract(7, 'days').startOf('day').valueOf()
        vm.endDate = moment().subtract(1, 'days').startOf('day').valueOf()

        vm.promotions = [
          text: 'product_statistics_all_activities'
          value: 'all'
        ]

        _getPromotions().then (campaigns) ->
          if angular.isArray(campaigns) and campaigns.length > 0
            angular.forEach campaigns, (campaign) ->
              vm.promotions.push {
                text: campaign.name
                value: campaign.id
              }
          vm.promotion = vm.promotions[0].value if vm.promotions.length > 0
          _getStatistic()

      _getStatistic = ->
        if not vm.startDate or not vm.endDate or not vm.promotion
          return

        params =
          startDate: vm.startDate
          endDate: vm.endDate
          campaignId: if vm.promotion then vm.promotion else 'all'

        restService.get config.resources.redemptionStatistic, params, (data) ->
          startDate = moment(vm.startDate).format('YYYY-MM-DD')
          endDate = moment(vm.endDate).format('YYYY-MM-DD')
          if data
            angular.forEach data, (value, key) ->

              categories = angular.copy(value.categories) or []
              series = angular.copy(value.series) or []

              # calculate legend row count and height
              calcSeriesHeight = (series.length + 1) * 20 + 10
              statisticsHeight = if calcSeriesHeight >= DEFAULTBOXHEIGHT then calcSeriesHeight else DEFAULTBOXHEIGHT

              options =
                color: COLORSERIES
                categories: categories
                series: series
                startDate: startDate
                endDate: endDate
                config:
                  legend:
                    x: 'right'

              switch parseInt(key)
                when redemptionType.PARTICIPANTS
                  vm.participantsHeight = statisticsHeight
                  if angular.isArray(options.series) and options.series.length > 0
                    angular.forEach options.series, (item, index) ->
                      if item.name is 'product_statistics_daily_total' and index
                        totalItem = options.series.splice index, 1
                        options.series.splice 0, 0, totalItem[0]
                    options.color = angular.copy COLORSERIES.slice 0, options.series.length
                  vm.participantsOptions = angular.copy options

                when redemptionType.TOTAL
                  vm.totalHeight = statisticsHeight
                  options.color = angular.copy COLORSERIES.slice 1, options.series.length + 1
                  vm.totalOptions = angular.copy options

                when redemptionType.PRIZES
                  vm.prizesHeight = statisticsHeight
                  if angular.isArray(options.categories) and options.categories.length > 0
                    totalItem =
                      name: 'product_statistics_daily_total_points'
                      data: []
                    angular.forEach options.categories, (category, index) ->
                      num = 0
                      angular.forEach options.series, (item) ->
                        num += item.data[index] if item.data.length > index
                      totalItem.data.push num
                    options.series.splice 0, 0, totalItem
                    options.color = angular.copy COLORSERIES.slice 0, options.series.length
                  vm.prizesOptions = angular.copy options

                else
                  return

      _getPromotions = ->
        defered = $q.defer()
        restService.get config.resources.allCampaigns, (data) ->
          defered.resolve data
        defered.promise

      vm.changePromotion = (campaignId) ->
        vm.promotion = campaignId
        _getStatistic()

      vm.selectDate = ->
        _getStatistic()

      vm.exportParticipants = ->
        vm['enableExportParticipants'] = false
        params =
          type: redemptionType.PARTICIPANTS
          startDate: vm.startDate
          endDate: vm.endDate

        if vm.promotion is 'all'
          url = config.resources.exportActivitiesAnalysis
        else
          url = config.resources.exportActivityAnalysis
          params.campaignId = vm.promotion
          promotion = utilService.getArrayElem(vm.promotions, vm.promotion, 'value')
          params.campaignName = if promotion then promotion.text else vm.promotion

        exportService.export 'product-statistics-participants', url, params, false

      vm.exportTotal = ->
        vm['enableExportTotal'] = false
        params =
          type: redemptionType.TOTAL
          startDate: vm.startDate
          endDate: vm.endDate

        if vm.promotion is 'all'
          url = config.resources.exportActivitiesAnalysis
        else
          url = config.resources.exportActivityAnalysis
          params.campaignId = vm.promotion
          promotion = utilService.getArrayElem(vm.promotions, vm.promotion, 'value')
          params.campaignName = if promotion then promotion.text else vm.promotion

        exportService.export 'product-statistics-redemption-count', url, params, false

      vm.exportPrizes = ->
        vm['enableExportPrizes'] = false
        params =
          type: redemptionType.PRIZES
          startDate: vm.startDate
          endDate: vm.endDate

        if vm.promotion is 'all'
          url = config.resources.exportActivitiesAnalysis
        else
          url = config.resources.exportActivityAnalysis
          params.campaignId = vm.promotion
          promotion = utilService.getArrayElem(vm.promotions, vm.promotion, 'value')
          params.campaignName = if promotion then promotion.text else vm.promotion

        exportService.export 'product-statistics-prize-redemption-volume', url, params, false

      $scope.$on 'exportDataPrepared', (event, type) ->
        switch type
          when 'product-statistics-participants'
            vm['enableExportParticipants'] = true
          when 'product-statistics-redemption-count'
            vm['enableExportTotal'] = true
          when 'product-statistics-prize-redemption-volume'
            vm['enableExportPrizes'] = true

      _init()

      vm
  ]
