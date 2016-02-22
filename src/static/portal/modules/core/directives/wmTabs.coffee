define ["core/coreModule"], (mod) ->

  mod.directive "wmTabs", [
    "$location"
    ($location) ->
      return (
        replace: true
        scope:
          tabs: '='
          inner: '@'
          change: '&wmTabs'
          hideName: '='
          hasTooltip: '='
        require: '?ngModel'

        template: '<ul class="tabs clearfix" ng-class="{\'full\':inner}">
                    <li ng-if="hasTooltip" class="tab" ng-style="widthStyle" wm-tooltip="{{tab.name|translate}}"
                    ng-class="{\'active\': tab.active}" ng-repeat="tab in tabs" ng-click="changeTab($index)" ng-hide="tab.disabled">
                      <span ng-show="!hideName">{{tab.name|translate}}</span>
                    </li>
                    <li ng-if="!hasTooltip" class="tab" ng-style="widthStyle"
                    ng-class="{\'active\': tab.active}" ng-repeat="tab in tabs" ng-click="changeTab($index)" ng-hide="tab.disabled">
                      <span ng-show="!hideName">{{tab.name|translate}}</span>
                    </li>
                  </ul>'

        link: (scope, iElement, iAttrs, ctrl) ->

          scope.$watch 'tabs', (newVal) ->
            if ($.isArray newVal) and newVal.length > 0

              angular.forEach scope.tabs, (tab) ->
                tab.active = false
                return

              scope.widthStyle = {width: (100.0 / scope.tabs.length) + '%'} if scope.inner
              curTab = parseInt($location.search().active)
              curTab = 0 if scope.inner
              scope.curTab = (if not isNaN(curTab) then curTab else 0)
              scope.tabs[scope.curTab].active = true

          scope.changeTab = (index) ->
            angular.forEach scope.tabs, (tab) ->
              tab.active = false
              return

            scope.curTab = index
            $location.search active: index if not scope.inner
            scope.tabs[index].active = true
            ctrl?.$setViewValue scope.tabs[index]
            scope.change()
            return

          return
      )
  ]
  mod.directive "wmTabPanes", [ ->
    replace: true
    scope:
      tabs: "="
      module: "@"
      fullPath: "@"

    template: '<ul class="tab-panes">
                <li class="tab-pane" ng-class="{\'active\': tab.active}" ng-if="tab.active" ng-include="templatePath + tab.template" ng-repeat="tab in tabs">
                </li>
              </ul>'
    link: (scope, iElement, iAttrs) ->
      ("true" is iAttrs.noBorder) and iElement.css("border-color": "#fff")

      scope.templatePath = if scope.fullPath then scope.fullPath else '/build/modules/' + scope.module + '/partials/'

      return
  ]

  mod.directive "wmHorizontalList", [ ->
    replace: true
    scope:
      tabs: "="
      change: '&wmHorizontalList'
    template: '<ul class="c-horizontal-list clearfix">
                <li class="list-item" ng-class="{\'active\': tab.active}" ng-repeat="tab in tabs track by $index" ng-click="changeTab($index)">
                  {{tab.name|translate}}
                </li>
              </ul>'

    link: (scope, iElement, iAttrs, ctrl) ->
      originTabIndex = 0

      scope.changeTab = (index) ->
        newTabIndex = index
        for item, i in scope.tabs
          item.active = i is index

        if index isnt originTabIndex
          originTabIndex = index
          scope.change()(scope.tabs[index])
  ]
  return
