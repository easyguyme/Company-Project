define ['core/coreModule'], (mod) ->

  ###*

  This is a directive for checkbox

  <div wm-switch="change(item)" on-value="true" off-value="false" ng-model="item.status"></div>
  ###
  mod.directive 'wmSwitch', [
    '$rootScope'
    '$translate'
    ($rootScope, $translate) ->
      directive =
        restrict: 'A'
        #replace: true
        scope:
          onValue: '@'
          offValue: '@'
          switch: '&wmSwitch'
          model: '=ngModel'
          isDisabled: '@'


        template: '<label>
                    <input ng-if="isDisabled != \'true\'" class="switch" ng-click="click()" type="checkbox" ng-checked="model===onValue"/>
                    <input ng-if="isDisabled == \'true\'" class="switch" ng-click="click()" type="checkbox" ng-checked="model===onValue" disabled/>
                    <div class="clearfix"><div></div></div>
                  </label>'
        link: (scope, elem, attrs) ->

          scope.click = ->
            if scope.model is scope.offValue
              model = scope.onValue
            else
              model = scope.offValue
            scope.model = model
            scope.switch()
            return

      return directive
  ]
