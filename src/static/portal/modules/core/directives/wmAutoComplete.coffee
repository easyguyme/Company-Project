## If you need local data to finish auto complete, you should give id, localdata, ngModel
## If you need ajax data to finish auto complete, you should give id, ngModel, callbackUrl, searchKey
define ['core/coreModule'], (mod) ->

  mod.directive 'wmAutoComplete', [
      'restService'
      '$filter'
      (restService, $filter) ->
        return (
          replace: true
          restrict: 'EA'
          scope:
            id: '@'
            localdata: '='
            tags: '=ngModel'
            callbackUrl: '@'
            searchKey: '@'
            addNewTags: '='
            maxLength: '@'
            tagPlaceHolder: '@'

          template: '<div>
                      <div class="autodropdown-body" ng-click="focusToInput($event)">
                        <ul class="autodropdown-tags">
                          <li ng-repeat="tag in tags track by $index" class="autodropdown-tag">
                            <span ng-bind="tag"></span>
                            <span class="close-btn cp" ng-click="removeTag($index, $event)"></span>
                          </li>
                          <li ng-show="{{maxLength}} > 0">
                            <input autocomplete="off" onkeydown="if(event.keyCode==13){return false;}" id="{{id}}_value"
                            ng-keyup="addTagWithEnter($event)" class="autodropdown-input" ng-model="tagValue" maxlength="{{maxLength}}" placeholder="{{tagPlaceHolder}}">
                          </li>
                          <li ng-show="{{maxLength}} == 0">
                            <input autocomplete="off" onkeydown="if(event.keyCode==13){return false;}" id="{{id}}_value"
                            ng-keyup="addTagWithEnter($event)" class="autodropdown-input" ng-model="tagValue" placeholder="{{tagPlaceHolder}}">
                          </li>
                        </ul>
                      </div>
                      <ul class="autodropdown-items" ng-show="items.length > 0">
                        <li ng-repeat="item in items track by $index" ng-click="addTag(item)" class="autodropdown-item">
                          <span ng-bind="item"></span>
                        </li>
                      </ul>
                    </div>'

          link: (scope, element, attrs) ->
            #init
            scope.items = []
            scope.callbackUrl = scope.searchKey = null if not scope.callbackUrl? or not scope.searchKey
            isShow = false  ## show all items

            $(element).mouseleave( ->
              scope.$apply ->
                isShow = false
                scope.items = []
                return
              return
            )

            _checkTagExist = (items, inputValue) ->
              if items.length isnt 0
                position = $.inArray(inputValue, items)
                if position isnt -1
                  isShow = true
                else
                  isShow = false
              else
                isShow = false
              return

            _fetchList = (search) ->
              condition = {}
              condition[scope.searchKey] = search
              restService.noLoading().get scope.callbackUrl, condition, (data) ->
                scope.items = data
                _checkTagExist(data, search)
              return

            _addTag = (item) ->
              item = item.trim()
              if $.inArray(item, scope.tags) is -1
                scope.tags.push item
              delete scope.tagValue
              scope.items = []
              isShow = false
              _focus()
              return

            _focus = ->
              $('#' + scope.id + '_value').focus()
              return

            scope.addTag = (item) ->
              _addTag item

            scope.addTagWithEnter = (event) ->
              if not scope.addNewTags
                if event.which is 13
                  item = $('#' + scope.id + '_value').val().trim()
                  if $.inArray(item, scope.items) isnt -1
                    _addTag item if item
                  else
                    delete scope.tagValue
                  return false
              else
                if event.which is 13
                  item = $('#' + scope.id + '_value').val().trim()
                  _addTag item if item
                return false

            scope.removeTag = (index, event) ->
              scope.tags.splice(index, 1)
              _focus()
              event.stopPropagation()
              return

            scope.focusToInput = (event) ->
              _focus()
              scope.showAllItems(event)
              return

            scope.showAllItems = (event) ->
              isShow = not isShow
              if isShow
                if scope.localdata?
                  scope.items = $filter('filter')(scope.localdata, scope.tagValue)
                else
                  _fetchList(scope.tagValue)
              else
                scope.items = []
              event.stopPropagation()
              return

            scope.$watch 'tagValue', (newVal) ->
              if newVal?
                if scope.callbackUrl? and scope.searchKey? #autocomplete with ajax
                  _fetchList(newVal)
                else  #autocomplete with localdata
                  scope.items = $filter('filter')(scope.localdata, scope.tagValue)
                  _checkTagExist(scope.items, newVal)
              else
                delete scope.tagValue
                scope.items = []
              return

            return

      )
  ]
