define ['core/coreModule'], (mod) ->
  mod.directive "wmPercentBar", [
    ->
      return (
        restrict: "A"
        replace: true
        scope:
          wmPercentBar: '='
        template: '<div class="percent-wrap">
            <ul class="percent-base" ng-style="{\'height\':wmPercentBar.height, \'background-color\':wmPercentBar.baseColor}"
            ng-class="{\'with-text\':wmPercentBar.showTip, \'without-text\':!wmPercentBar.showTip}">
              <li class="percent-above" ng-style="{\'width\':percent, \'height\':wmPercentBar.height, \'background-color\':wmPercentBar.aboveColor}"></li>
            </ul>
            <span class="percent-tip" ng-if="wmPercentBar.showTip">{{percent}}&nbsp;({{wmPercentBar.count}})</span>
            <span class="percent-star-icon" ng-if="wmPercentBar.showStar"
            ng-style="{\'height\':wmPercentBar.height, \'background-image\':\'url(\' + wmPercentBar.starIcon + \')\', \'background-size\':\'20% \' + wmPercentBar.height}"
            ng-class="{\'with-text\':wmPercentBar.showTip, \'without-text\':!wmPercentBar.showTip}"></span>
          </div>'
        link: (scope, elem, attrs) ->
          _calculate = ->
            scope.percent = if scope.wmPercentBar? and scope.wmPercentBar.total isnt 0 then parseInt(scope.wmPercentBar?.count / scope.wmPercentBar?.total * 100) + '%' else '0%'

          scope.$watch 'wmPercentBar', (newVal) ->
            _calculate() if newVal

          _calculate()

      )
  ]
