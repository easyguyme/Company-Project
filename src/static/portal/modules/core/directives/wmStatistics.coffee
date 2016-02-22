define [
  'core/coreModule'
  ], (mod) ->
  mod.directive 'wmRegionDistribution', [
    ->
      return (
        replace: true
        restrict: 'EA'
        scope:
          header: '='
          distributionData: '='
          maxUserCount: '='
          currentPage: '='
          totalItems: '='
          pageSize: '='
          stripeColor: '@'
          onChangePage: '&'
        template: '<div class="region-distribution-wrapper">
                    <div class="region-header row mlr0">
                      <div class="col-md-3 col-sm-3 col-xs-3" ng-repeat="item in header track by $index">{{item | translate}}</div>
                    </div>
                    <div ng-if="totalItems > 0">
                      <div class="region-body-row row mlr0" ng-repeat="data in distributionData track by $index">
                        <div class="col-md-3 col-sm-3 col-xs-3">{{data.value | translate}}</div>
                        <div class="col-md-3 col-sm-3 col-xs-3" ng-bind="data.userCount"></div>
                        <div class="col-md-6 col-sm-6 col-xs-6">
                          <div ng-if="stripeColor == null" class="amount-stripe" ng-style="{\'width\':(data.userCount/maxUserCount*100) + \'%\'}"></div>
                          <div ng-if="stripeColor != null" class="amount-stripe"
                            ng-style="{\'width\':(0.98 * (data.userCount/maxUserCount*100) + 2) + \'%\',\'background-color\':stripeColor,\'opacity\':0.7 * data.userCount/maxUserCount + 0.3}"></div>
                        </div>
                      </div>
                      <div class="region-footer">
                          <ul>
                            <li ng-class="{disabledpage: currentPage == 1}" ng-click="previousPage()">◂</li>
                            <li>{{currentPage}}/{{totalPages}}</li>
                            <li ng-class="{disabledpage: currentPage == totalPages}" ng-click="nextPage()">▸</li>
                          </ul>
                      </div>
                    </div>
                    <div ng-if="totalItems == 0" class="region-nodata" translate="distribution_nodata"></div>
                  </div>'
        link: (scope, element, attrs) ->
          scope.size = scope.pageSize or 8

          scope.previousPage = ->
            if scope.currentPage isnt 1
              currentPage = scope.currentPage - 1
              scope.currentPage--
              scope.onChangePage()(currentPage)

          scope.nextPage = ->
            if scope.currentPage isnt scope.totalPages
              currentPage = scope.currentPage + 1
              scope.currentPage++
              scope.onChangePage()(currentPage)

          scope.$watch 'totalItems', (newVal) ->
            scope.totalPages = Math.ceil(newVal / scope.size)
      )
  ]

  mod.directive 'wmYesterdayStatistics', [
    ->
      return (
        replace: true
        restrict: 'EA'
        scope:
          statisticsTitle: '@'
          overview: '='


        template: '<div class="panel panel-default statistic-pannel">
                    <div class="panel-heading panel-title-block">
                      <span translate="{{statisticsTitle}}"></span>
                    </div>
                    <table class="panel-body row statistics-body pd0 mlr0" style="width:100%;">
                      <tbody>
                        <tr>
                          <td class="col-md-{{12/overview.length}} statistics-body-col" style="padding-bottom:0;border-bottom:0;" ng-repeat="item in overview track by $index">
                            <div class="score-total-title">{{item.title | translate}}</div>
                          </td>
                        </tr>
                        <tr>
                          <td class="col-md-{{12/overview.length}} statistics-body-col" style="padding-top:0;" ng-repeat="item in overview track by $index">
                            <div class="yesterday-number">{{item.value | number}}</div>
                            <div class="statistics-kind" ng-repeat="statistic in item.statistics track by $index">
                              {{statistic.type | translate}}
                              <span ng-if="statistic.growth != \'NaN\'" ng-class="{uparrow: statistic.growth >= 0, downarrow: statistic.growth < 0}">{{statistic.growth | positiveNum}}%</span>
                              <span ng-if="statistic.growth == \'NaN\'">--</span>
                            </div>
                          </td>
                        </tr>
                      </tbody>
                    </table>
                  </div>'
        link: (scope, element, attrs) ->
    )
  ]

  mod.directive 'wmRichTextStatistics', [
    ->
      return (
        replace: true
        restrict: 'EA'
        scope:
          richText: '='
          viewDetail: '&'

        template: '<div class="panel panel-default statistic-pannel">
                    <div class="panel-heading panel-title-block">
                      <span class="graphic-title" ng-bind="richText.title"></span>
                      <span class="graphic-title graphic-create-date" ng-bind="richText.sentDate"></span>
                      <a href="#" class="pull-right" ng-click="view()">{{"view_rich_text" | translate}}</a>
                    </div>
                    <div class="panel-body row graphic-panel-body mlr0">
                      <div class="col-md-6 col-sm-6 col-xs-12 graphic-border">
                        <div class="col-md-7 col-xs-7 col-sm-7 pd0 graphic-statistic-number">
                          <div wm-h-area-line-chart options="hAreaLineChartOptions" width="100%" height="180px"></div>
                        </div>
                        <div class="col-md-5 col-xs-5 col-sm-5">
                          <ul class="graphic-statistic-number">
                            <li><span>{{richText.sentUser | number}}</span>{{"sent_count_chart" | translate}}<div class="graphic-statistic-bg"><i class="icon-down"></i></div></li>
                            <li><span>{{richText.intPageReadUser | number}}</span>{{"int_page_read_count_chart" | translate}}<div class="graphic-statistic-bg"><i class="icon-down"></i></div></li>
                            <li><span>{{richText.oriPageReadUser | number}}</span>{{"ori_page_read_count_chart" | translate}}<div class="graphic-statistic-bg"><i class="icon-down"></i></div></li>
                            <li><span>{{richText.shareUser+richText.addToFavUser | number}}</span>{{"share_and_favor_count" | translate}}</li>
                          </ul>
                        </div>
                      </div>
                      <div class="col-md-6 col-sm-6 col-xs-12 graphic-border">
                        <div class="choose-rich-text-type">
                          <div wm-select on-change="changeType" ng-model="type" text-field="text" value-field="value" items="types"></div>
                        </div>
                        <div wm-line-chart options="lineChartOptions" width="100%" height="200px"></div>
                      </div>
                    </div>
                  </div>'
        link: (scope, element, attrs) ->

          color = '#b3dcf7'

          # init wm-select
          scope.types = [{
            value: 'init',
            text: 'int_page_read_count'
          }
          {
            value: 'origin',
            text: 'ori_page_read_count'
          }
          {
            value: 'share',
            text: 'share_count'
          }
          {
            value: 'favor',
            text: 'addto_favcount'
          }]
          scope.type = scope.types[0].value

          richText = scope.richText
          dailyStatistics = richText.dailyStatistics

          # render echart
          _displayhAreaLineChart = ->
            scope.hAreaLineChartOptions =
            {
              color: color
              categories: ['', '', '', '']
              series: [{
                  name: 'user_count',
                  data: [richText.shareUser + richText.addToFavUser, richText.oriPageReadUser, richText.intPageReadUser, richText.sentUser]
                }
              ]
            }
          _displayhAreaLineChart()

          # formate echart data
          _formateData = ->
            categories = []
            intPageReadUser = []
            oriPageReadUser = []
            shareUser = []
            addToFavUser = []
            data = {
              userCount: {}
              readCount: {}
            }
            for item in dailyStatistics
              categories.push item.refDate
              intPageReadUser.push item.intPageReadUser
              oriPageReadUser.push item.oriPageReadUser
              shareUser.push item.shareUser
              addToFavUser.push item.addToFavUser

            data.categories = categories
            data.userCount.init = intPageReadUser
            data.userCount.origin = oriPageReadUser
            data.userCount.share = shareUser
            data.userCount.favor = addToFavUser

            scope.data = data

          _formateData()

          # display rich text read count
          _displayChart = (seriesData) ->
            startDate = richText.sentDate
            startSevenDay = moment(startDate).add(7, 'days').startOf('day').valueOf()
            yesterday = moment().subtract(1, 'days').startOf('day').valueOf()
            endDate = if startSevenDay > yesterday then moment.unix(yesterday / 1000).format('YYYY-MM-DD') else moment.unix(startSevenDay / 1000).format('YYYY-MM-DD')
            scope.data.endDate = endDate

            scope.lineChartOptions =
            {
              color: color
              categories: scope.data.categories,
              series: [{
                name: 'user_count',
                data: seriesData
              }]
              startDate: startDate
              endDate: endDate
              config:
                grid:
                  x: 30
                  y: 30
                  x2: 40
                  y2: 30
              hideLegend: true
            }

          _displayChart scope.data.userCount.init

          scope.changeType = (value, index) ->
            scope.lineChartOptions.series[0].data = scope.data.userCount[value]

          #transfer process data to modal
          scope.view = ->
            scope.viewDetail()(scope.data, richText) if scope.viewDetail

          scope.$watch 'richText', (newVal) ->
            richText = scope.richText
            dailyStatistics = richText.dailyStatistics
            _displayhAreaLineChart
            _formateData()
            _displayChart scope.data.userCount.init

          return

    )
  ]

  return
