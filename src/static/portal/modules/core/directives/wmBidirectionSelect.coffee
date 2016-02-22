define ['core/coreModule'], (mod) ->

  mod.filter 'itemFilter', ->
    (items, filterCond, filterFields) ->
      if filterFields
        filterFieldsArr = filterFields.split(',')
        if items and items.length and filterFieldsArr.length
          if filterCond
            matchedItems = []
            angular.forEach items, (item) ->
              isMatch = false
              angular.forEach filterFieldsArr, (field) ->
                if item[field].indexOf(filterCond) > -1
                  isMatch = true
              matchedItems.push item if isMatch
            matchedItems
          else
            items

  mod.directive 'wmBidirectionSelect', [
    ->
      return (
          restrict: 'EA'
          scope:
            allChoices: '='
            allChoicesTitle: '@'
            selectedChoices: '='
            selectedChoicesTitle: '@'
            filterPlaceholder: '@'
            orderBy: '@'
            textField: '@'
            subTextField: '@'
            filterFields: '@'
          replace: true
          template: '<div class="bidirection-select clearfix">
                      <div class="col-md-5">
                        <h5 class="choices-title">{{allChoicesTitle|translate}} ({{allChoices.length}})</h5>
                        <input type="text" class="all-choices-filter form-control" ng-model="filterName" placeholder="{{filterPlaceholder|translate}}"/>
                        <ul class="all-choices-box">
                          <li class="clearfix" ng-repeat="choice in allChoices | itemFilter:filterName:filterFields | orderBy:orderBy" ng-click="select(choice)">
                            <div class="text-field text-el pull-left">{{choice[textField]}}</div>
                            <div class="sub-text-field text-el pull-right" wm-tooltip="{{choice[subTextField]}}">{{choice[subTextField]}}</div>
                          </li>
                        </ul>
                      </div>
                      <div class="text-center rel col-md-2">
                        <span class="add-arrow glyphicon glyphicon-arrow-right" aria-hidden="true"></span>
                      </div>
                      <div class="col-md-5">
                        <h5 class="choices-title">{{selectedChoicesTitle|translate}} ({{selectedChoices.length}})</h5>
                        <ul class="selected-choices-box">
                          <li class="rel clearfix" ng-repeat="choice in selectedChoices | orderBy:orderBy">
                            <div class="text-field text-el pull-left">{{choice[textField]}}</div>
                            <div class="sub-text-field text-el pull-right" wm-tooltip="{{choice[subTextField]}}">{{choice[subTextField]}}</div>
                            <button type="button" class="close" data-dismiss="alert" aria-label="Close" ng-click="unSelect(choice)"><span aria-hidden="true">×</span></button>
                          </li>
                        </ul>
                      </div>
                    </div>'
          link: (scope, elem, attrs) ->

            _getIdxInArray = (selectedChoice, choices) ->
              selectedIdx = -1
              if angular.isArray choices
                angular.forEach choices, (choice, idx) ->
                  if choice.id is selectedChoice.id
                    selectedIdx = idx
              selectedIdx

            scope.select = (choice) ->
              idx = _getIdxInArray choice, scope.allChoices
              if idx isnt -1
                scope.selectedChoices.push choice
                scope.allChoices.splice idx, 1

            scope.unSelect = (choice) ->
              idx = _getIdxInArray choice, scope.selectedChoices
              if idx isnt -1
                scope.selectedChoices.splice idx, 1
                scope.allChoices.push choice
        )
  ]

  return
