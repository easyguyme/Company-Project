define ['core/coreModule'], (mod) ->
  mod.directive('wmCheckbox', [
    ->
      return (
        restrict: 'EA'
        replace: true
        scope:
          model: '=ngModel'
          isDisabled: '@'
        # template: '<span class="wm-checkbox" ng-click="model=!model" ng-class={checked:model}></span>'
        template: '<label class="wm-checkbox" ng-class="{checked:checked, \'wm-checkbox-disabled\': isDisabled == \'true\'}">
                    <input style="display:none" type="checkbox" ng-model="model">
                  </label>'
        link: (scope, elem) ->
          $elem = $(elem)
          # $elem.find('input').attr 'disabled', 'disabled' if scope.isDisabled is 'true'
          scope.$watch 'model', (val) ->
            scope.checked = val if val?
            return
          # scope.$watch 'isDisabled', (val) ->
          #   if val is 'false' and $elem.find('input').attr 'disabled'
          #     $elem.find('input').removeAttr 'disabled'
          #   return
      )
  ]).directive('wmRadio', [
    ->
      return (
        restrict: 'EA'
        replace: true
        scope:
          model: '=ngModel'
          value: '@'
          isDisabled: '@'
        template: '<label class="wm-radio {{value}}-label-position" ng-class="{checked:checked, \'wm-radio-disabled\': isDisabled == \'true\'}">
                  <input style="display:none" type="radio" ng-model="model" ng-value="value"></label>'
        link: (scope, elem) ->
          scope.$watch 'model', (val) ->
            scope.checked = ("#{val}" is elem.find('input').val())
            return
      )
  ])
  return
