define [
  'wm/app'
  'echartsMap'
  ], (app, echarts) ->

  app.registerDirective 'wmMap', [
    '$rootScope'
    '$filter'
    ($rootScope, $filter) ->
      return (
        restrict: 'EA',
        template: '<div></div>',
        scope:
          options: '=options'
        link: (scope, element, attrs) ->
          dom = element.find('div')[0]
          width = options?.width or attrs.width or '320px'
          height = options?.height or attrs.height or '240px'
          dom.style.width = width
          dom.style.height = height
          chart = echarts.init dom

          getChartOption = ->
            chartOptions = angular.copy scope.options

            # Chart default config
            chartConfig =
              tooltip:
                trigger: 'item'
                formatter: '{b} : {c}{a}'
              dataRange:
                orient: 'horizontal'
                x: 'center'
                y: 'bottom'
                min: 0
                max: parseInt(scope.options.max, 10) or 2000
                precision: 0
                hoverLink: false
                calculable: true
                formatter: (a) ->
                  parseInt(a)
                itemWidth: 30
              dataRangeHoverLink: false

            # Merge with chart default config
            angular.extend chartConfig, chartOptions.config if chartOptions.config

            chartConfig.series = []
            angular.forEach chartOptions.series, (serie) ->
              conf =
                name: $filter('translate')(serie.name)
                type: 'map'
                mapType: 'china'
                color: ['#edf3fd', '#54a5dc']
                itemStyle:
                  normal:
                    label:
                      show: true
                    areaStyle:
                      color: '#d6e9f6'
                  emphasis:
                    label:
                      show: true
                    areaStyle:
                      color: '#37c3aa'
                data: serie.data
              chartConfig.series.push conf
            chartConfig

          $rootScope.$on '$translateChangeSuccess', ->
            chart.setOption getChartOption(), true

          $(window).resize ->
            chart.resize()

          scope.$watch ->
            return scope.options
          ,(value) ->
            if value
              chart.clear()
              chart.setOption getChartOption(), true
          , true
      )
  ]
  return
