# wmMultiSelect include wmSelect function and also can excute call back
define ['core/coreModule'], (mod) ->
  mod.directive 'wmMultiSelect', [
      ->
        return (
          restrict: 'EA'
          scope:
            choices: '='
            ngModel: '='
            onChange: '&'
            textField: '@'
            valueField: '@'

          template: '<div class="select fs12">
                      <div class="select-btn gray3 clearfix" ng-click="show=!show;">
                        <div class="select-left">{{(currentItemLabel||"")|translate}}</div>
                        <div class="select-right">
                          <span class="custom-caret"></span>
                        </div>
                      </div>
                      <ul class="select-dropdown" ng-show="show" ng-class="{scroll: choices.valueChioces.length > 10}">
                        <li ng-repeat="choice in choices.valueChioces track by $index" ng-click="selectVal(choice, $index)">
                          <div class="value-item" value="{{choice[valueField]}}">{{choice[textField]|translate}}</div>
                        </li>
                        <li ng-repeat="choice in choices.funChioces" ng-click="excuteOperation(choice)">
                          <div class="value-item"><i class="glyphicon glyphicon-plus cp" style="margin-right:10px;"></i><span>{{choice.text | translate}}</span></div>
                        </li>
                      </ul>
                    </div>'

          link: (scope, elem, attrs) ->

            scope.textField = 'text' if not scope.textField
            scope.valueField = 'value' if not scope.valueField
            scope.show = false
            scope.ngModel = '' if not scope.ngModel?

            $(elem).mouseleave( ->
              scope.$apply ->
                scope.show = false
                return
              return
            )

            scope.$watch 'show', (newVal) ->
              if newVal and scope.choices.valueChioces and scope.choices.valueChioces.length > 10
                dropdown = $(elem).find('.select-dropdown')
                dropdown.removeClass 'ng-hide'
              return

            # If model is empty, display default text in select input
            # If model has value, display select item text with this value
            scope.$watch 'ngModel', (model) ->
              if not model? or model is ''
                scope.currentItemLabel = attrs.defaultText or ''
              else
                matchedItem = {}
                if scope.choices.valueChioces
                  for item in scope.choices.valueChioces
                    if item[scope.valueField] is model
                      matchedItem = item
                      break
                  scope.currentItemLabel = matchedItem[scope.textField]

            scope.selectVal = (item, idx) ->
              scope.ngModel = item[scope.valueField]
              scope.onChange()(scope.ngModel, idx) if scope.onChange()
              scope.show = false
              return

            # Excute call back such as pop up a modal
            scope.excuteOperation = (choice) ->
              choice.action() if choice.action
              scope.show = false

            return
      )
    ]
