define [
  'wm/app'
  'echartsBasic'
  ], (app, echarts) ->

  emptySerieData =
    name: ''
    data: []

  setSize = (dom, options, attrs) ->
    width = options?.width or attrs.width or '320px'
    height = options?.height or attrs.height or '240px'
    dom.style.width = width
    dom.style.height = height
    return

  setColor = (config, options) ->
    colorList = []
    colorList = options.color if angular.isArray options.color
    if angular.isString options.color
      seriesCount = options.series?.length
      i = 0
      while i < seriesCount
        colorList.push hexToRgbaColor(options.color, 1 - i * 0.1)
        i++
    config.color = colorList if colorList.length > 0
    return

  setNoDataOption = (config, $filter) ->
    defaultOption =
      text: $filter('translate')('distribution_nodata')
      textStyle:
        fontSize: 16
      effect: 'bubble'
      effectOption:
        effect:
          n: 0
    config.noDataLoadingOption = defaultOption

  fillEmpty = (series, position) ->
    angular.forEach series, (serie) ->
      if not angular.isArray(serie.data)
        serie.data = []
      serie.data.splice(position, 0, '0')
    return

  getCategories = (options) ->
    allCategories = []
    if options.startDate and options.endDate
      categories = options.categories
      startDate = moment(options.startDate).add(-1, 'days')
      endDate = moment(options.endDate)
      diffDays = (endDate.unix() - startDate.unix()) / (60 * 60 * 24)
      i = 0
      while i < diffDays
        dateTick = startDate.add(1, 'days').format('YYYY-MM-DD')
        if $.inArray(dateTick, categories) < 0
          fillEmpty options.series, i
        i++
        allCategories.push dateTick
    else
      allCategories = options.categories
    allCategories

  hexToRgbaColor = (hexColor, alpha) ->
    reg = /^#([0-9a-fA-f]{3}|[0-9a-fA-f]{6})$/
    hexColor = hexColor.toLowerCase()
    if hexColor and reg.test(hexColor)
      if hexColor.length is 4
        hexColorNew = '#'
        i = 1
        while i < 4
          hexColorNew += hexColor.slice(i, i + 1).concat(hexColor.slice(i, i + 1))
          i++
        hexColor = hexColorNew
      hexColorChange = []
      i = 1
      while i < 7
        hexColorChange.push(parseInt('0x' + hexColor.slice(i, i + 2)))
        i += 2
      return 'rgba(' + hexColorChange[0] + ',' + hexColorChange[1] + ',' + hexColorChange[2] + ',' + alpha + ')'
    else
      return hexColor

  calculateChartContainerHeight = (chart, series, element, attrs) ->
    DEFAULTBOXHEIGHT = parseInt($(element).attr('height').replace('px', ''))
    calcSeriesHeight = (series.length + 1) * 20 + 10
    statisticsHeight = if calcSeriesHeight >= DEFAULTBOXHEIGHT then calcSeriesHeight else DEFAULTBOXHEIGHT

    size =
      height: "#{statisticsHeight}px"

    redrawChartContainer chart, element, attrs, size

  setLegendAndGrid = ($filter, config, series, element) ->
    # calculate legend row count and height
    legendTotalWidth = 10
    tabsWidth = $(element).width()

    for item, index in series
      translatedSerieName = $filter('translate')(item.name)
      legendTotalWidth += translatedSerieName.length * 15 + 40

    legendTotalHeight = Math.ceil(legendTotalWidth / (tabsWidth - 150)) * 15 + 15
    legendPadding = [10, 75, 5, 75]

    if legendTotalHeight < 50
      legendPadding[0] = 20
      legendPadding[3] = 20
      legendTotalHeight = legendTotalHeight + 25

    config.legend = config.legend or {}
    config.legend.padding = legendPadding
    config.grid = config.grid or {}
    config.grid.y = legendTotalHeight

  redrawChartContainer = (chart, element, attrs, size) ->
    if chart
      if arguments.length > 4 and attrs.id isnt arguments[4]
        return

      $element = $(element)

      for key, value of size
        if value
          $chartParent = $($element.find('div')[0])
          if $chartParent
            $chartParent.css(key, value)
          else
            $element.find('div').css(key, value)
            $element.find('.echarts-tooltip').css(key, 'auto')

      chart.resize()
      chart.refresh()

  redrawHandler = ($rootScope, chart, getChartOption, element, attrs) ->
    $rootScope.$on '$translateChangeSuccess', ->
      chart.clear()
      chart.setOption getChartOption(), true

    $(window).resize ->
      chart.resize()

    if attrs.resizeable and attrs.resizeable isnt 'false'
      $rootScope.$on 'chartResize', (event, size) ->
        args = [chart, element, attrs]
        Array.prototype.push.apply(args, Array.prototype.slice.call(arguments, 1))
        redrawChartContainer.apply(this, args)
    return

  init = (scope, element, attrs) ->
    dom = element.find('div')[0]
    setSize(dom, scope.options, attrs)
    chart = echarts.init dom

  app.registerDirective 'wmLineChart', [
    '$rootScope'
    '$filter'
    ($rootScope, $filter) ->
      return (
        restrict: 'EA',
        template: '<div></div>'
        scope: {
          options: '=options'
        },
        link: (scope, element, attrs) ->
          chart = init(scope, element, attrs)

          getChartOption = ->
            chartOptions = angular.copy(scope.options) or {}

            # Chart default config
            chartConfig =
              color: ['#57C6CD', '#C490BF', '#7ECEF4']
              tooltip:
                trigger: 'axis'
                axisPointer:
                  type: 'line'
                  lineStyle:
                    width: 1
              legend:
                x: 'right'
                padding: [20, 75, 5, 5]
              xAxis: [{
                type: 'category'
                boundaryGap: false
                axisLine:
                  show: false
                axisTick:
                  show: false
                splitLine:
                  show: false
              }]
              yAxis: [{
                type: 'value'
                axisLine:
                  show: false
                axisTick:
                  show: false
              }]
              symbolList: ['circle']

            # Merge with chart default config
            angular.extend chartConfig, chartOptions.config if chartOptions?.config

            # Set chart series colors
            setColor(chartConfig, chartOptions) if chartOptions.color

            # reset legend height and grid
            if attrs.resizeable and attrs.resizeable isnt 'false'
              setLegendAndGrid($filter, chartConfig, chartOptions.series, element)
              calculateChartContainerHeight(chart, chartOptions.series, element, attrs)

            # Set chart no data text
            setNoDataOption(chartConfig, $filter)

            # Set chart legend data, translate serie name
            legend = []
            angular.forEach chartOptions.series, (serie) ->
              serie.type = 'line'
              serie.barGap = 0
              serie.showAllSymbol = true
              translatedSerieName = $filter('translate')(serie.name)
              serie.name = translatedSerieName
              legend.push translatedSerieName
            chartConfig.legend.data = legend
            chartConfig.legend.show = false if scope.options.hideLegend
            chartConfig.series = if (angular.isArray(chartOptions.series) and chartOptions.series.length) then chartOptions.series else emptySerieData

            # Get the x axis data, if set startDate and endDate, it will fill the empty data automaticlly
            chartConfig.xAxis[0].data = getCategories(chartOptions)
            chartConfig

          redrawHandler $rootScope, chart, getChartOption, element, attrs

          scope.$watch ->
            return scope.options
          ,(value) ->
            if value
              chart.clear()
              chart.hideLoading()
              chart.setOption getChartOption(), true
          , true
      )
  ]

  app.registerDirective 'wmHAreaLineChart', [
    '$rootScope'
    '$filter'
    ($rootScope, $filter) ->
      return (
        restrict: 'EA',
        template: '<div></div>',
        scope: {
          options: '=options'
        },
        link: (scope, element, attrs) ->
          chart = init(scope, element, attrs)

          getChartOption = (chartOptions) ->

            chartOptions = chartOptions or {}

            # Chart default config
            chartConfig =
              color: ['#B3DCF7']
              tooltip:
                trigger: 'axis'
                axisPointer:
                  type: 'none'
                formatter: (params) ->
                  return params[0].seriesName + ':' + params[0].value
              grid:
                x: 0
                y: 0
                x2: 0
                y2: 30
                borderWidth: 0
              xAxis: [{
                show: false
                type: 'value'
                axisLine:
                  show: false
                axisTick:
                  show: false
                splitLine:
                  show: false
              }]
              yAxis: [{
                show: true
                type: 'category'
                boundaryGap: false
                axisLine:
                  show: false
                axisTick:
                  show: false
                splitLine:
                  show: true
                position: 'right'
              }]

            # Merge with chart default config
            angular.extend chartConfig, chartOptions.config if chartOptions.config

            # Set chart series colors
            setColor(chartConfig, chartOptions) if chartOptions.color

            # Generate chart series
            angular.forEach chartOptions.series, (serie) ->
              if serie.name
                serie.name = $filter('translate')(serie.name)
              serie.type = 'line'
              serie.itemStyle =
                normal:
                  areaStyle:
                    type: 'default'
            chartConfig.series = chartOptions.series

            chartConfig.yAxis[0].data = chartOptions.categories
            chartConfig

          redrawHandler $rootScope, chart, getChartOption, element, attrs

          scope.$watch ->
            return scope.options
          ,(value) ->
            if value
              chart.clear()
              chart.setOption getChartOption(value), true
          , true
      )
  ]

  app.registerDirective 'wmBarChart', [
    '$rootScope'
    '$filter'
    ($rootScope, $filter) ->
      return (
        restrict: 'EA',
        template: '<div></div>'
        scope: {
          options: '=options'
        },
        link: (scope, element, attrs) ->
          chart = init(scope, element, attrs)

          percentArray = {}

          getChartOption = ->
            chartOptions = angular.copy(scope.options) or {}

            # Chart default config
            chartConfig =
              color: ['#B0D530', '#7E56B5', '#2FA7C0', '#963CB0']
              tooltip:
                trigger: 'axis'
                axisPointer:
                  type: 'none'
                formatter: (params, ticket, callback) ->
                  tip = params[0].name + '<br>'
                  i = 0
                  while i < params.length
                    tip += params[i].seriesName + ':' + params[i].data
                    tip += ' (' + percentArray[params[i].seriesName][params[i].name] + ')' if chartOptions.type and chartOptions.type is 'percent'
                    tip += '<br>'
                    i++
                  return tip
              legend:
                x: 'right'
                padding: [20, 75, 5, 5]
              xAxis: [{
                type: 'category'
                boundaryGap: true
                axisLine:
                  show: false
                splitLine:
                  show: false
              }]
              yAxis: [{
                type: 'value'
                axisLine:
                  show: false
                axisTick:
                  show: false
              }]

            # translate categories
            if chartOptions.categories and angular.isArray(chartOptions.categories) and chartOptions.categories.length > 0
              chartOptions.categories = chartOptions.categories.map (item) ->
                $filter('translate')(item)

            # Merge with chart default config
            angular.extend chartConfig, chartOptions.config if chartOptions.config

            # Set chart series colors
            setColor(chartConfig, chartOptions) if chartOptions.color

            # reset legend height and grid
            if attrs.resizeable and attrs.resizeable isnt 'false'
              setLegendAndGrid($filter, chartConfig, chartOptions.series, element)
              calculateChartContainerHeight(chart, chartOptions.series, element, attrs)

            # Set chart no data text
            setNoDataOption(chartConfig, $filter)

            # Set chart legend data, translate serie name
            legend = []
            angular.forEach chartOptions.series, (serie) ->
              serie.type = 'bar'
              serie.barGap = 0
              serie.barMaxWidth = 55
              if chartOptions.type and chartOptions.type is 'percent'
                serie.itemStyle =
                  normal:
                    label:
                      show: true
                      position: 'top'
                      formatter: (params) ->
                        xAxisName = params.name
                        serieName = params.seriesName
                        serieValue = params.value
                        serieIndex = $.inArray(xAxisName, chartOptions.categories)
                        labelName = ''
                        if angular.isArray(chartOptions.series) and chartOptions.series.length
                          sum = 0
                          angular.forEach chartOptions.series, (serie) ->
                            sum += parseInt(serie.data[serieIndex], 10)
                          labelName = if sum > 0 then Math.ceil((serieValue / sum).toFixed(2) * 100) + '%' else ''
                          percentArray[serieName] = {} if typeof percentArray[serieName] is 'undefined'
                          percentArray[serieName][xAxisName] = if labelName is '' then '0%' else labelName
                          labelName = '' if serieValue is 0
                        return ''
                  emphasis:
                    label:
                      show: true
                    color: '#37c3aa'
              else
                serie.itemStyle =
                  normal:
                    label:
                      show: true
                      position: 'top'
                  emphasis:
                    label:
                      show: true
                    color: '#37c3aa'
              translatedSerieName = $filter('translate')(serie.name)
              serie.name = translatedSerieName
              legend.push translatedSerieName
              if chartOptions.stack
                serie.stack = 'total'
            chartConfig.legend.data = legend
            chartConfig.series = if (angular.isArray(chartOptions.series) and chartOptions.series.length) then chartOptions.series else emptySerieData

            # Get the x axis data, if set startDate and endDate, it will fill the empty data automaticlly
            chartConfig.xAxis[0].data = getCategories(chartOptions)
            chartConfig

          redrawHandler $rootScope, chart, getChartOption, element, attrs

          scope.$watch ->
            return scope.options
          ,(value) ->
            if value
              chart.clear()
              chart.hideLoading()
              chart.setOption getChartOption(), true
          , true
      )
  ]

  app.registerDirective 'wmHBarChart', [
    '$rootScope'
    '$filter'
    ($rootScope, $filter) ->
      return (
        restrict: 'EA',
        template: '<div></div>',
        scope: {
          options: '=options'
        },
        link: (scope, element, attrs) ->
          chart = init(scope, element, attrs)

          getChartOption = ->
            chartOptions = angular.copy(scope.options) or {}

            # Chart default config
            chartConfig =
              tooltip:
                trigger: 'axis'
                axisPointer:
                  type: 'none'
                formatter: (params) ->
                  tip = ''
                  for param in params
                    tip += param.seriesName + ':' + param.value + '</br>'
                  return tip
              legend:
                x: 'right'
                padding: [20, 75, 5, 5]
              xAxis: [{
                type: 'value'
                axisLine:
                  show: false
                splitLine:
                  show: false
              }]
              yAxis: [{
                type: 'category'
                boundaryGap: true
                axisLine:
                  show: true
                axisTick:
                  show: false
              }]
              grid:
                borderWidth: 0

            # Merge with chart default config
            angular.extend chartConfig, chartOptions.config if chartOptions.config

            # Set chart series colors
            setColor(chartConfig, chartOptions) if chartOptions.color

            # Generate the legend data and translate serie name
            legend = []
            angular.forEach chartOptions.series, (serie) ->
              serie.type = 'bar'
              serie.stack = 'total'
              translatedSerieName = $filter('translate')(serie.name)
              serie.name = translatedSerieName
              legend.push translatedSerieName
            chartConfig.legend.data = legend
            chartConfig.legend.show = false if scope.options.hideLegend
            chartConfig.series = chartOptions.series
            chartConfig.yAxis[0].data = getCategories(chartOptions)

            if chartOptions.showTwoYAxis
              y2AxisData = []
              y2AxisData = angular.copy chartConfig.yAxis[0].data
              angular.forEach y2AxisData, (data, index) ->
                y2AxisData[index] = ''
              chartConfig.yAxis[1] =
                type: 'category'
                boundaryGap: true
                axisLine:
                  show: true
                axisTick:
                  show: false
                data: y2AxisData
            chartConfig

          redrawHandler $rootScope, chart, getChartOption, element, attrs

          scope.$watch ->
            return scope.options
          ,(value) ->
            if value
              chart.clear()
              chart.setOption getChartOption(), true
          , true
      )
  ]

  app.registerDirective 'wmAccumulatedBarChart', [
    '$filter'
    ($filter) ->
      return (
        restrict: 'EA',
        template: '<div></div>',
        scope: {
          options: '=options'
        },
        link: (scope, element, attrs) ->
          chart = init(scope, element, attrs)

          getChartOption = ->
            chartOptions = angular.copy(scope.options) or {}

            # Chart default config
            chartConfig =
              color: ['#F86961', '#2DA4A8', '#A9C71E', '#3D5D9E']
              tooltip:
                trigger: 'axis'
                axisPointer:
                  type: 'none'
                formatter: (params) ->
                  if angular.isArray params
                    totalNum = 0
                    angular.forEach params, (barItem) ->
                      if not isNaN parseInt(barItem.data)
                        totalNum += barItem.data
                    prefixTitle = if chartOptions.tooltipTitle then $filter('translate')(chartOptions.tooltipTitle) + ':' else ''
                    return prefixTitle + totalNum
              legend:
                x: 'left'
                orient: 'vertical'
              xAxis: [{
                type: 'category'
                boundaryGap: true
                axisLine:
                  show: false
                splitLine:
                  show: false
              }]
              yAxis: [{
                type: 'value'
                axisLine:
                  show: false
                axisTick:
                  show: false
              }],
              grid:
                x: 200
                y: 20

            # Merge with chart default config
            angular.extend chartConfig, chartOptions.config if chartOptions.config

            # Set chart series colors
            setColor(chartConfig, chartOptions) if chartOptions.color

            # Generate special accumulated bar data
            seriesCount = chartOptions.series.length
            legend = []
            chartConfig.series = []
            angular.forEach chartOptions.series, (serie, serieIndex) ->
              if serie.length
                angular.forEach serie, (item, itemIndex) ->
                  legend.push $filter('translate')(item.name)
                  dataArr = []
                  num = seriesCount + 1
                  while num -= 1
                    dataArr.push('-')
                  dataArr[serieIndex] = item.value
                  conf =
                    type: 'bar'
                    data: dataArr
                    name: $filter('translate')(item.name)
                    stack: 'total'
                    barMaxWidth: 55
                    itemStyle:
                      normal:
                        color: hexToRgbaColor(chartConfig.color[serieIndex % chartConfig.color.length], 1 - itemIndex * 0.2)
                        label:
                          show: true,
                          position: 'inside',
                          formatter: (xAxisName, serieName, serieValue) ->
                            serieIndex = $.inArray(serieName, chartOptions.categories)
                            series = chartOptions.series[serieIndex]
                            sum = 0
                            angular.forEach series, (serie) ->
                              sum += parseInt(serie.value, 10)
                            return Math.ceil((serieValue / sum).toFixed(2) * 100) + '%';
                      emphasis:
                        color: hexToRgbaColor(chartConfig.color[serieIndex % chartConfig.color.length], 1 - itemIndex * 0.2 - 0.1)
                  chartConfig.series.push conf
            chartConfig.legend.data = legend
            chartConfig.xAxis[0].data = chartOptions.categories
            chartConfig

          redrawHandler $rootScope, chart, getChartOption, element, attrs

          scope.$watch ->
            return scope.options
          ,(value) ->
            if value
              chart.clear()
              chart.setOption getChartOption(), true
          , true
      )
  ]

  app.registerDirective 'wmPieChart', [
    '$rootScope'
    '$filter'
    ($rootScope, $filter) ->
      return (
        restrict: 'EA',
        template: '<div></div>'
        scope: {
          options: '=options'
        },
        link: (scope, element, attrs) ->
          chart = init(scope, element, attrs)

          getChartOption = ->
            chartOptions = angular.copy(scope.options) or {}

            # Chart default config
            chartConfig =
              tooltip:
                trigger: 'item',
                formatter: (params) ->
                  params.percent = params.percent or 0
                  params.seriesName + '<br/>' + params.name + ' : ' + params.value + ' (' + parseFloat(params.percent) + '%)'
                # Fix the bug about tooltip shake caused by web broswer edge
                position: ([x, y]) ->
                  return [x - 100, y]
              legend:
                show: false

            # Merge with chart default config
            angular.extend chartConfig, chartOptions.config if chartOptions.config

            # Set chart series colors
            setColor(chartConfig, chartOptions) if chartOptions.color

            # Set chart no data text
            setNoDataOption(chartConfig, $filter)

            # Set chart legend data
            legend = []
            angular.forEach chartOptions.series, (serie) ->
              serie.name = $filter('translate')(serie.name)
              legend.push serie.name
            chartConfig.legend.data = legend

            # Generate chart series
            if chartOptions.type and chartOptions.type is 'inner'
              chartConfig.series = [{
                type: 'pie'
                name: $filter('translate')(chartOptions.title)
                radius: [0, '60%']
                center: ['50%', '50%']
                data: chartOptions.series
                itemStyle:
                  normal:
                    label:
                      show: true
                      position: 'inner'
                      textStyle:
                        fontSize: 12
                        fontWeight: 'bold'
                      formatter: (params) ->
                        params.percent = params.percent or 0
                        params.name + '\n' + params.value + ' ' + parseFloat(params.percent) + '%'
                    labelLine:
                      show: false
                  emphasis:
                    label:
                      show: true
                      position: 'inner'
                      textStyle:
                        fontSize: 12
                        fontWeight: 'bold'
                      formatter: (params) ->
                        params.percent = params.percent or 0
                        params.name + '\n' + params.value + ' ' + parseFloat(params.percent) + '%'
                    labelLine:
                      show: false
              }]
            else
              chartConfig.series = [{
                type: 'pie'
                name: $filter('translate')(chartOptions.title)
                radius: [0, '60%']
                center: ['50%', '50%']
                data: chartOptions.series
                itemStyle:
                  normal:
                    label:
                      show: true
                      position: 'outer'
                      textStyle:
                        fontSize: 12
                      formatter: (params) ->
                        params.percent = params.percent or 0
                        params.name + '\n' + params.value + ' ' + parseFloat(params.percent) + '%'
                    labelLine:
                      show: true
                      lineStyle:
                        type: 'dashed'
              }]
            chartConfig

          redrawHandler $rootScope, chart, getChartOption, element, attrs

          scope.$watch ->
            return scope.options
          ,(value) ->
            if value
              chart.clear()
              chart.hideLoading()
              chart.setOption getChartOption(), true
          , true
      )
  ]

  app.registerDirective 'wmDonutChart', [
    '$rootScope'
    '$filter'
    ($rootScope, $filter) ->
      return (
        restrict: 'EA',
        template: '<div></div>'
        scope: {
          options: '=options'
        },
        link: (scope, element, attrs) ->
          chart = init(scope, element, attrs)

          getChartOption = ->
            chartOptions = angular.copy(scope.options) or {}

            # Chart default config
            chartConfig =
              tooltip:
                trigger: 'item',
                formatter: (params) ->
                  params.percent = params.percent or 0
                  params.seriesName + '<br/>' + params.name + ' : ' + params.value + ' (' + parseFloat(params.percent) + '%)'
                # Fix the bug about tooltip shake caused by web broswer edge
                position: ([x, y]) ->
                  return [x - 100, y]
              legend:
                show: false

            # Merge with chart default config
            angular.extend chartConfig, chartOptions.config if chartOptions.config

            # Set chart series colors
            setColor(chartConfig, chartOptions) if chartOptions.color

            # Set chart no data text
            setNoDataOption(chartConfig, $filter)

            # Set chart legend data
            legend = []
            angular.forEach chartOptions.series, (serie) ->
              serie.name = $filter('translate')(serie.name)
              legend.push serie.name
            chartConfig.legend.data = legend

            # Generate chart series
            totalValue = 0
            totalTitle = if chartOptions.totalTitle then $filter('translate')(chartOptions.totalTitle) else ''
            if angular.isArray(chartOptions.series) and chartOptions.series.length
              angular.forEach chartOptions.series, (serie) ->
                totalValue += serie.value
            chartConfig.series = if (angular.isArray(chartOptions.series) and chartOptions.series.length) then [{
              type: 'pie'
              name: ''
              radius: ['30%', '60%']
              center: ['50%', '50%']
              itemStyle:
                normal:
                  label:
                    position: 'center'
                    textStyle:
                      fontSize: 12
                      fontWeight: 'bold'
                      color: '#1D1D1D'
                    formatter: (params) ->
                      params.name + '\n' + $filter('number')(params.value)
                  labelLine:
                    show: false
              data: [{value: totalValue, name: totalTitle}]
            }, {
              type: 'pie'
              name: $filter('translate')(chartOptions.title)
              radius: ['30%', '60%']
              center: ['50%', '50%']
              itemStyle:
                normal:
                  label:
                    position: 'outer'
                    formatter: (params) ->
                      params.percent = params.percent or 0
                      params.name + '\n' + $filter('number')(params.value) + '\n' + parseFloat(params.percent) + '%'
                  labelLine:
                    lineStyle:
                      type: 'dashed'
              data: chartOptions.series
            }] else [emptySerieData]

            chartConfig

          redrawHandler $rootScope, chart, getChartOption, element, attrs

          scope.$watch ->
            return scope.options
          ,(value) ->
            if value
              chart.clear()
              chart.hideLoading()
              chart.setOption getChartOption(), true
          , true
      )
  ]
  return
