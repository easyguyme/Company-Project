define [
  'wm/app'
  'wm/config'
  'core/directives/wmCharts'
], (app, config) ->
  # in order to highlight webpage, in fact is article view controller
  app.registerController 'wm.ctrl.microsite.page.view.webpage', [
    '$scope'
    '$sce'
    '$stateParams'
    'canvasService'
    'restService'
    ($scope, $sce, $stateParams, canvasService, restService) ->
      vm = this
      vm.data = []
      vm.iframe = ''

      _init = ->
        vm.breadcrumb = [
          icon: 'webpage'
          text: 'content_pages_management'
          href: '/microsite/webpage'
        ,
          'content_page_detail'
        ]
        vm.dateRange = angular.copy dataService.dateRange
        vm.selectedDateRange = vm.dateRange[0].value
        vm.changeDateRange 0
        return

      vm.changeDateRange = (index) ->
        selectedDateRange = vm.dateRange[index]
        vm.beginDate = vm.endDate = null
        vm.beginDate = moment(selectedDateRange.from, 'YYYYMMDD').valueOf()
        vm.endDate = moment(selectedDateRange.to, 'YYYYMMDD').valueOf()
        initData selectedDateRange.from, selectedDateRange.to
        return

      vm.selectDate = ->
        if vm.beginDate and vm.endDate
          beginDate = moment(vm.beginDate).format 'YYYYMMDD'
          endDate = moment(vm.endDate).format 'YYYYMMDD'
          initData beginDate, endDate
        return

      initData = (from, to) ->
        restService.get config.resources.page + '/' + $stateParams.id, {from: from, to: to}, (data) ->
            vm.data = data
            if data.statistics
              dataService.format data.statistics.Daily, from, to
            vm.chartData = dataService.chartData
            url = if data.statistics?.Long? then data.statistics.Long else data.url
            vm.iframe = "#{url}?s=1"
            return
        return

      vm.downloadQrcode = ->
        filename = 'qrcode'
        filename = vm.data.title if vm.data?.title
        filePrefix = 'png'
        canvasService.download $('#qrcode-container canvas')[0], "#{filename}.#{filePrefix}", 'png', vm.data.shortUrl
        return

      vm.trustSrc = (url) ->
        $sce.trustAsResourceUrl url

      dataService = ( ->
        yesterday = moment().subtract(1, 'days').format 'YYYYMMDD'
        dateRange = [
          text: 'content_article_last_seven_days'
          value: 0
          from: moment().subtract(1, 'weeks').format 'YYYYMMDD'
          to: yesterday
        ,
          text: 'content_article_last_one_month'
          value: 1
          from: moment().subtract(1, 'months').format 'YYYYMMDD'
          to: yesterday
        ]
        chartData = {}

        # Format clicks data
        formatDateData = (data, startDateStr, endDateStr) ->
          dailyData = []
          dailyDates = []
          angular.forEach data, (pageClick) ->
            dailyData.push pageClick.TotalClicks
            dailyDates.push moment(pageClick.Day, 'YYYYMMDD').format('YYYY-MM-DD')
            return
          clickData = []
          clickDates = []
          startDate = moment startDateStr, 'YYYYMMDD'
          endDate = moment endDateStr, 'YYYYMMDD'

          formatData =
            categories: dailyDates
            series: [
              name: 'page_visit'
              data: dailyData
            ]
            startDate: startDate.format 'YYYY-MM-DD'
            endDate: endDate.format 'YYYY-MM-DD'

          angular.extend chartData, formatData
          return

        dateRange: dateRange
        chartData: chartData
        format: formatDateData
      )()

      _init()

      vm
  ]
