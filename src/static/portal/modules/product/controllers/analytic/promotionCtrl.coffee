define [
  'wm/app'
  'wm/config'
], (app, config) ->
  app.registerController 'wm.ctrl.product.analytic.promotion', [
    'restService'
    '$stateParams'
    (restService, $stateParams) ->
      vm = this

      _setTabs = ->
        vm.tabs = [
          {
            active: true
            name: 'product_promotion_analytic_campaign_log'
            template: 'campaignLog.html'
          }
          {
            active: false
            name: 'product_promotion_analytic_participate_statistics'
            template: 'participateStat.html'
          }
        ]
        vm.curTab = vm.tabs[0]
        return

      _setBreadcrumb = ->
        activityId = $stateParams.id
        # Get the promotion activity detail
        vm.breadcrumb = ['活动列表', '统计－多芬2015春季促销']
        return

      _init = ->
        _setTabs()
        _setBreadcrumb()

      _init()

      vm
  ]

  app.registerController 'wm.ctrl.product.analytic.promotion.campaignLog', [
    'restService'
    'notificationService'
    '$location'
    '$stateParams'
    (restService, notificationService, $location, $stateParams) ->
      vm = this

      _getData = ->
        activityId = $stateParams.id
        if activityId
          params:
            currentPage: vm.currentPage
            'per-page': vm.pageSize
            activityId: activityId
          vm.totalRecords = 23567
          vm.list =
            columnDefs: [
              {
                field: 'code'
                label: 'product_promotion_code'
              }, {
                field: 'memberId'
                label: 'product_promotion_member_card_no'
              },{
                field: 'usedTime'
                label: 'product_promotion_used_time'
              },{
                field: 'scoreAdd'
                label: 'product_promotion_score_change'
              },{
                field: 'usedFromName'
                label: 'product_promotion_campaign_channel'
              }
            ],
            data: [
              {
                code: '432323456',
                memberId: '6589236546589',
                usedTime: '2015-03-27 20:45:56',
                scoreAdd: '+30 (兑换后积分为358)'
                usedFromName: '联合利华'
              },
              {
                code: '432323456',
                memberId: '6589236546589',
                usedTime: '2015-03-27 20:45:56',
                scoreAdd: '+30 (兑换后积分为358)'
                usedFromName: '联合利华'
              },
              {
                code: '432323456',
                memberId: '6589236546589',
                usedTime: '2015-03-27 20:45:56',
                scoreAdd: '+30 (兑换后积分为358)'
                usedFromName: '联合利华'
              }
            ]
        else
          console.log 'Can not get promotion activity id'
        return

      _initPagination = ->
        vm.currentPage = $location.search().currentPage or 1
        vm.pageSize = $location.search().pageSize or 10
        vm.totalItems = 100

      _init = ->
        _initPagination()
        _getData()

      _init()

      vm.changeSize = (pageSize) ->
        vm.pageSize = pageSize
        vm.currentPage = 1
        _getData()

      vm.changePage = (currentPage) ->
        vm.currentPage = currentPage
        _getData()

      vm
  ]

  app.registerController 'wm.ctrl.product.analytic.promotion.participateStat', [
    'restService'
    'notificationService'
    '$location'
    (restService, notificationService, $location) ->
      vm = this

      _getNumberDataByDate = ->
        vm.numberDateStatOptions =
          categories: ['2015-01-01', '2015-01-02', '2015-01-03', '2015-01-04', '2015-01-05', '2015-01-06', '2015-01-07'],
          series: [{
             name: '总数',
             data: [110, 195, 325, 839, 901, 820, 110]
          }, {
              name: '商品编号:215487654',
              data: [811, 154, 345, 289, 904, 830, 150]
          }, {
              name: '商品编号:215487321',
              data: [101, 115, 535, 789, 930, 807, 410]
          }, {
              name: '商品编号:215487896',
              data: [141, 165, 345, 893, 90, 830, 130]
          }]
        return

      _getNumberDataByChannel = ->
        vm.numberChannelStatOptions =
          title: '社交渠道分布'
          series: [
              {value: 3770, name: '微信-联合利华华北区'},
              {value: 2852, name: 'APP-掌上购物'},
              {value: 1345, name: '微博-联合利华官徽'},
              {value: 1209, name: '微博-联合利华华东北区'},
              {value: 915, name: '官方网站'}
          ]
        return

      _getRegions = ->
        vm.regions = [
          {order: 1, name: '北京', value: 400, percent: '45.7%'},
          {order: 2, name: '天津', value: 300, percent: '34.5%'},
          {order: 3, name: '上海', value: 200, percent: '23.2%'},
          {order: 4, name: '重庆', value: 150, percent: '20.6%'},
          {order: 5, name: '河北', value: 100, percent: '19.8%'},
          {order: 6, name: '河南', value: 50, percent: '12.8%'}
          {order: 7, name: '北京', value: 40, percent: '11.7%'},
          {order: 8, name: '天津', value: 30, percent: '10.5%'},
          {order: 9, name: '上海', value: 20, percent: '9.2%'},
          {order: 10, name: '重庆', value: 10, percent: '7.6%'},
        ]
        return

      _getClosetNumber = (data) ->
        maxValue = 0
        if data.length
          for item in data
            if item.value > maxValue
              maxValue = item.value
        maxValue

      _getNumberDataByRegion = ->
        numberData = [
          {name: '北京', value: 400},
          {name: '天津', value: 309},
          {name: '上海', value: 200},
          {name: '重庆', value: 150},
          {name: '河北', value: 100},
          {name: '河南', value: 50}
        ]
        vm.maxValue = _getClosetNumber(numberData)
        vm.numberRegionStatOptions =
          max: vm.maxValue
          series: [
              {
                  name: '人次',
                  data: numberData
              }
          ]
        return

      _getExchangeDataByDate = ->
        vm.exchangeDateStatOptions =
          categories: ['2015-01-01', '2015-01-02', '2015-01-03', '2015-01-04', '2015-01-05', '2015-01-06', '2015-01-07'],
          series: [{
             name: '积分',
             data: [110, 195, 325, 839, 901, 820, 110]
          }]
        return

      _getAllData = ->
        _getNumberDataByDate()
        _getNumberDataByChannel()
        _getRegions()
        _getNumberDataByRegion()
        _getExchangeDataByDate()
        return

      _initPagination = ->
        vm.currentPage = $location.search().currentPage or 1
        vm.pageSize = $location.search().pageSize or 10
        vm.totalPages = 10

      _init = ->
        vm.chartHalfWidth = $('.tabs').width() / 2 + 'px'
        vm.chartWidth = $('.tabs').width() + 'px'

        _initPagination()
        _getAllData()
        return

      _init()

      vm.selectDate = ->
        console.log vm.startDate
        console.log vm.endDate

      vm.previousPage = ->
        if vm.currentPage > 1
          vm.currentPage--
          _getRegions()
        return

      vm.nextPage = ->
        if vm.currentPage < vm.totalPages
          vm.currentPage++
          _getRegions()
        return

      vm
  ]
