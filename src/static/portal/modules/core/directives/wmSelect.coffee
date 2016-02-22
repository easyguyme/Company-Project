define ['core/coreModule'], (mod) ->

  mod.directive 'wmSelect', [ ->
      return (
        restrict: 'EA'
        replace: true
        scope:
          items: '='
          textField: '@'
          valueField: '@'
          onChange: '&'
          ngModel: '='
          direction: '@'
          type: '@'
          isDisabled: '@'
          withoutTooltip: '@'

        template: '<div bindonce class="select fs12">
                    <div bo-switch="type" class="select-btn gray3 clearfix" wm-tooltip="{{(withoutTooltip == \'true\' ? \'\' : (currentItemLabel.text || currentItemLabel)) | translate}}"
                      ng-click="showSelectList()" ng-class="{\'select-disabled\':items.length <= 0 || isDisabled == \'true\'}">
                      <div bo-switch-when="icon" class="select-left select-icon" ng-style="{\'background-image\': \'url(\' + currentItemLabel + \')\'}"></div>
                      <div bo-switch-when="iconText" class="select-left select-icon-text" ng-style="{\'background-image\': \'url(\' + currentItemLabel.icon + \')\'}">{{currentItemLabel.text}}</div>
                      <div bo-switch-default class="select-left">{{(currentItemLabel||"")|translate}}</div>
                      <div class="select-right">
                        <span class="custom-caret"></span>
                      </div>
                    </div>
                    <ul bindonce class="select-dropdown" ng-show="show && items.length > 0" ng-class="{scroll: items.length > 10}">
                      <li bo-switch="type" ng-repeat="item in items track by $index" ng-click="selectVal(item, $index)">
                        <div bo-switch-when="icon" class="value-item select-icon" value="{{item[valueField]}}" ng-style="{\'background-image\': \'url(\' + item[textField] + \')\'}"></div>
                        <div bo-switch-when="iconText" class="value-item select-icon-text" value="{{item[valueField]}}" ng-style="{\'background-image\': \'url(\' + item[textField].icon + \')\'}">
                        {{item[textField].text}}</div>
                        <div bo-switch-default class="value-item" value="{{item[valueField]}}" wm-tooltip="{{(withoutTooltip == \'true\' ? \'\' : item[textField])| translate}}">
                          {{item[textField]|translate}}
                        </div>
                      </li>
                    </ul>
                  </div>'

        link: (scope, elem, attrs) ->
          # Set default key and value
          scope.textField = 'text' if not scope.textField
          scope.valueField = 'value' if not scope.valueField
          scope.show = false
          scope.ngModel = '' if not scope.ngModel?

          if scope.direction is 'up'
            elem.find('.select-dropdown').addClass('up')
            elem.find('.custom-caret').addClass('up')

          $(elem).mouseleave ( ->
            scope.$apply ->
              scope.show = false
              return
            return
          )

          setCurrentItemLabel = ->
            model = scope.ngModel
            items = scope.items
            matchedItem = {}
            for item in items
              if item[scope.valueField] is model
                matchedItem = item
                break
            scope.currentItemLabel = matchedItem[scope.textField]

          scope.$watch 'show', (newVal) ->
            if newVal and scope.items and scope.items.length > 10
              dropdown = $(elem).find('.select-dropdown')
              dropdown.removeClass 'ng-hide'
            return

          scope.$watch 'ngModel', (model) ->
            if not model? or model is ''
              scope.currentItemLabel = attrs.defaultText or ''
            else if scope.items
              setCurrentItemLabel()

          scope.$watch 'items', (items) ->
            if items and scope.ngModel
              setCurrentItemLabel()
          , true

          scope.selectVal = (item, idx) ->
            scope.ngModel = item[scope.valueField]
            scope.onChange()(scope.ngModel, idx) if scope.onChange()
            scope.show = false
            return

          scope.showSelectList = ->
            if scope.isDisabled isnt 'true'
              if not scope.direction
                emptyHeight = $(window).height() + $(window).scrollTop() - elem.offset().top - 30
                if emptyHeight >= (elem.find('.select-dropdown li').length * 28) or emptyHeight >= 310
                  elem.find('.select-dropdown').removeClass('up')
                  elem.find('.custom-caret').removeClass('up')
                else
                  elem.find('.select-dropdown').addClass('up')
                  elem.find('.custom-caret').addClass('up')
              scope.show = not scope.show
              return

          return
      )
  ]
  return
