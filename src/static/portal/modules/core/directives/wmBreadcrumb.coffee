
define ['core/coreModule'], (mod) ->
  mod.directive("wmBreadcrumb", [
    '$rootScope'
    'moduleService'
    ($rootScope, moduleService) ->
      restrict: "A"
      replace: true
      scope:
        wmBreadcrumb: '='
      transclude: true
      template: '<ol class="breadcrumb">
                  <li bindonce ng-repeat="breadcrumb in wmBreadcrumb">
                    <span class="arrow-icon" bo-if="$index >= 1">
                    </span><span class="crumb-icon {{icon}}-icon default-icon" bo-if="$index === 0 && !breadcrumb.href">
                    </span><a class="crumb-icon {{icon}}-icon" bo-if="$index === 0 && breadcrumb.href" bo-href="breadcrumb.href">
                    </a><a class="crumb-text href-text" bo-if="breadcrumb.href" bo-href="breadcrumb.href" translate="{{breadcrumb.text}}">
                    </a><span class="crumb-text" bo-if="!breadcrumb.href" translate="{{breadcrumb.text ? breadcrumb.text : breadcrumb}}">
                    </span><div class="help-info-wrapper crumb-help" bo-if="breadcrumb.help">
                      <i class="help-info" ng-class="{\'active\': help}" ng-click="help = !help"></i>
                      <div class="help-main-info" ng-class="{\'active\': help}">
                        <span translate="{{breadcrumb.help}}"></span>
                        <span class="info-close" ng-click="help = false"></span>
                      </div>
                    </div>
                  </li>
                  <div ng-transclude class="pull-right clearfix transclude-wrapper"></div>
                 </ol>'
      link: (scope, element, attrs) ->
        rvm = $rootScope
        vm = scope
        stateName = rvm.currentState.name.replace /-\{.+\}/g, ''
        parts = stateName.split '-'
        moduleName = parts[0]
        menuName = [parts[0], parts[parts.length - 1]].join '-'
        vm.icon = ''
        menus = []

        _initIcon = ->
          if vm.wmBreadcrumb[0]?.icon
            vm.icon = vm.wmBreadcrumb[0].icon
          else
            for menu in menus
              if menu.state is menuName
                vm.icon = menu.name
                break

        moduleService.getConfig().then (conf) ->
          menus = conf.menus[moduleName]
          _initIcon()

        vm
  ]).directive('wmListHeader', [ ->
    restrict: 'EA'
    replace: true
    scope:
      content: '@'
      icon: '='
      height: '@'
      headClass: '@'
      contents: '='
      icons: '='
    transclude: true
    template: '<div class="listheader clearfix {{headClass}}" ng-style="{\'height\': height?height+\'px\':\'40px\'}">
                <span ng-if="content" class="listheader-content pull-left" ng-style="{\'background-image\': \'url({{icon&&icon.url?icon.url:\'\'}})\',
                  \'background-size\': \'{{icon&&icon.width?icon.width+\'px\':\'16px\'}} {{icon&&icon.height?icon.height+\'px\':icon&&icon.width?icon.width+\'px\':\'16px\'}}\',
                  \'line-height\':height?height+\'px\':\'40px\',
                  \'padding-left\':icon?icon.width?(icon.width+20)+\'px\':\'40px\':\'15px\'}">
                  {{content?content:\'\' | translate}}
                </span>
                <span ng-if="contents" ng-repeat="content in contents track by $index">
                  <span class="listheader-content pull-left" ng-style="{\'background-image\': \'url({{icons[$index]&&icons[$index].url?icons[$index].url:\'\'}})\',
                    \'background-size\': \'{{icons[$index]&&icons[$index].width?icons[$index].width+\'px\':\'20px\'}}
                    {{icons[$index]&&icons[$index].height?icons[$index].height+\'px\':icons[$index]&&icons[$index].width?icons[$index].width+\'px\':\'20px\'}}\',
                    \'line-height\':height?height+\'px\':\'40px\',
                    \'padding-left\':icons[$index]?icons[$index].width?(icons[$index].width+20)+\'px\':\'40px\':\'15px\'}">
                    {{content?content:\'\' | translate}}
                  </span>
                </span>
                <div ng-transclude class="pull-right clearfix" ng-style="{\'width\': content || icon ? \'auto\' : \'100%\'}"></div>
              </div>'
  ])
