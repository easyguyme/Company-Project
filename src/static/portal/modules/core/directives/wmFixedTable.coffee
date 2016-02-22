define ['core/coreModule'], (mod) ->
  mod.directive 'wmFixedTable', [
    '$filter'
    '$rootScope'
    'notificationService'
    ($filter, $rootScope, notificationService) ->
      return (
        restrict: 'E,A'
        replace: true
        scope: {
          table: '=ngModel'
          isSelectAll: '@'
        }

        template: '<div>

                    <div class="goods-all-item" style="margin-bottom: 15px;" ng-show="!table.noCheckbox && isSelectAll == \'true\'">
                      <div class="operate-tags-items clear-container-padding">
                        <label class="check-all-items goods-check">
                          <wm-checkbox ng-model="table.checkAll" ng-click="checkAll(table.checkAll)" class="follower-checkbox-style"></wm-checkbox>{{\'core_all_select\' | translate}}
                        </label>
                      </div>
                    </div>

                    <div class="colored-table">
                      <table bindonce class="table wm-data-table">
                        <thead>
                          <tr>
                            <th bo-if="table.selectable || table.switchedable" class="checkbox-col">&nbsp;
                            </th>
                            <th ng-repeat="colDef in table.columnDefs track by $index" bo-class="{cp:colDef.sortable}" class="{{colDef.headClass}}" ng-click="sortHandler(colDef)">
                              <span class="table-title-word">{{colDef.label|translate}}</span>
                              <span class="icon-sortable cp" bo-if="colDef.sortable" ng-class="{up:colDef.desc}"></span>
                            </th>
                            <th bo-if="!table.noOptText && table.operations" class="operation-col">{{1==table.operations.length?\"action\":\"operations\"|translate}}</th>
                            <th bo-if="table.noOptText" class="operation-col"></th>
                          </tr>
                        </thead>
                      </table>
                      <div class="tbody-wrapper" ng-class="{\'loading-tbody-wrapper\': table.hasLoading}">
                        <div class="fixed-table-nodata" ng-if="!table.hasLoading && table.data.length <= 0">{{table.nodata | translate}}</div>
                        <div class="fixed-table-loading" ng-show="table.hasLoading">
                          <div class="loading-wrapper absolute-center">
                            <span class="store-synchronize-icon loading-icon"></span>
                            <span class="loading-text">{{table.loadingText || \'loading\' | translate}}</span>
                          </div>
                        </div>
                        <table bindonce class="table wm-data-table" ng-if="table.data.length > 0" ng-hide="table.hasLoading">
                          <tbody>
                            <tr ng-repeat="item in table.data track by $index" class="fixed-table-border {{item.rowClass}}">
                              <td ng-if="table.selectable" class="checkbox-col">
                                <wm-checkbox ng-if="item[\'enabled\']" ng-click="checkItem($index, item.checked)" ng-model="item.checked"></wm-checkbox>
                                <wm-checkbox ng-if="!item[\'enabled\']" ng-model="item.checked" is-disabled="true"></wm-checkbox>
                              </td>
                              <td ng-if="table.switchedable" class="checkbox-col">
                                <wm-radio ng-if="item[\'enabled\']" ng-click="switchItem($index)" ng-model="item.switched" value="true"></wm-radio>
                                <wm-radio ng-if="!item[\'enabled\']" is-disabled="true"></wm-radio>
                              </td>
                              <td ng-repeat="colDef in table.columnDefs" bo-switch="colDef.type" class="{{colDef.cellClass}}">
                                <span bo-switch-when="goodsIcon" wm-tooltip="{{item[colDef.field].name}}">
                                  <div class="panel panel-icon-default social-display goods-icon-show">
                                    <img class="goods-icon-show" ng-src="{{item[colDef.field].url|qiniu:60}}"/>
                                  </div>
                                  {{item[colDef.field].name}}
                                </span>
                                <input bo-switch-when="input" class="form-control {{colDef.inputClass}}" type="text" maxlength="6" ng-model="item[colDef.field]"
                                  placeholder="{{colDef.placeholder | translate}}" />
                                <span bo-switch-when="inputText">
                                  <span class="input-text-wrapper">
                                    <input class="form-control {{colDef.inputClass}}" type="text" maxlength="{{colDef.maxlength}}" ng-model="item[colDef.field]" />
                                  </span>
                                  <span class="input-text-unit">{{colDef.text | translate}}</span>
                                </span>
                                <span bo-switch-when="mark">
                                  <div class="mark-icon" ng-if="item[colDef.field].url != \'\'">
                                    <img class="goods-icon-show" ng-src="{{item[colDef.field].url|qiniu:50}}" wm-tooltip="{{item[colDef.field].name}}"/>
                                  </div>
                                  <div class="mark-content text-el">{{item[colDef.field].name}}</div><span class="mark-text"
                                    ng-if="!item[\'enabled\']" translate="{{colDef.markText}}"></span>
                                </span>
                                <span bo-switch-when="date">{{item[colDef.field]|date:colDef.format?colDef.format:"yyyy-MM-dd HH:mm:ss"}}</span>
                                <span bo-switch-when="tooltip">
                                  <span wm-tooltip="{{item[colDef.field].tooltip}}" position="right">{{item[colDef.field].text|string|translate}}</span>
                                  <span ng-bind-html="item[colDef.field].suffix" ng-if="item[colDef.field].suffix"></span>
                                </span>
                                <span bo-switch-when="translateValues" wm-tooltip="{{item[colDef.field].key|string|translate:item[colDef.field].values}}">
                                  {{item[colDef.field].key|string|translate:item[colDef.field].values}}
                                </span>
                                <span bo-switch-when="textColor" class="{{item[colDef.field].color}}">{{item[colDef.field].text|string|translate}}</span>
                                <span bo-switch-when="operation">
                                  <span ng-repeat="operation in item[colDef.field]">
                                    <a ng-if="!operation.disable" bo-href="operation.link" wm-tooltip="{{operation.title || operation.name|translate}}"
                                    ng-click="optHandler($parent.$parent.$parent.$parent.$index, operation.name, $event)"
                                    class="operate-icon {{operation.name}}-icon-table" ng-hide="operation.hidden"></a>
                                    <a ng-if="operation.disable" class="operate-icon {{operation.name}}-icon-disable" wm-tooltip="{{operation.title | translate}}"></a>
                                  </span>
                                </span>
                                <span bo-switch-default wm-tooltip="{{item[colDef.field]}}">{{item[colDef.field]}}</span>
                              </td>
                              <td bo-if="table.operations" class="operation-col">
                                <span class="rel" ng-repeat="operation in table.operations">
                                  <a bo-href="operation.link" wm-tooltip="{{operation.title || operation.name|translate}}"
                                    ng-click="optHandler($parent.$index, operation.name, $event)" class="operate-icon {{operation.name}}-icon-table"></a>
                                </span>
                              </td>
                            </tr>
                          </tbody>
                        </table>
                      </div>
                    </div>
                  </div>
                  '
        link: (scope, elem, attrs) ->

          # check if selectAll inside.

          for row in scope.table.data
            row.checked = false
          if scope.table.operations
            for operation in scope.table.operations
              operation.link = '#' if not operation.link
              # Specified for the more operation type
              if operation.name is 'more'
                operation.extra = false
                scope.table.moreHandler = (idx, $event) ->
                  $body = $(document.body)
                  $confirmMask = $ '<div class="mask-confirm"></div>'
                  $body.append($confirmMask)
                  $confirmMask.click (event) ->
                    event.preventDefault()
                    $('.mask-confirm').remove()
                    scope.$apply ->
                      scope.table.data[idx].extra = not scope.table.data[idx].extra
                    return
                  $confirmMask.show()
                  scope.table.data[idx].extra = not scope.table.data[idx].extra
                  scope.moreHandlerEvent = $event


          scope.optHandler = (idx, name, $event) ->
            if $event?
              $($event.target).attr 'tip-checked', true

            switch name
              when 'delete'
                notificationService.confirm scope.moreHandlerEvent or $event,{
                  'title': scope.table.deleteTitle,
                  'submitCallback': scope.deleteSubmitHandler,
                  'params': [idx]
                }
              else
                handlerName = name + 'Handler'
                parameters = [idx, $event]
                parameters.push $event if name is 'tag' or name is 'qrcode'
                if scope.table[handlerName]
                  scope.table[handlerName].apply null, parameters
                else
                  throw new Error(handlerName + ' should be specified for the table configuration!')
            return

          scope.sortHandler = (colDef) ->
            desc = '-'
            desc = '' if colDef.desc
            colDef.desc = not colDef.desc
            #table's own sort and needn't call api to get data
            if colDef.sortable and not colDef.sortHandler and not scope.table.sortHandler
              scope.table.data = $filter('orderBy')(scope.table.data, desc + colDef.field)
            #one column has sorthander callback
            colDef.sortHandler(colDef) if colDef.sortHandler
            #table has a sorthandler callback
            scope.table.sortHandler(colDef) if scope.table.sortHandler and colDef.sortable
            return

          scope.checkAll = (checked) ->
            for row in scope.table.data
              row.checked = checked
            scope.table.selectHandler checked if scope.table.selectHandler
            return

          scope.checkItem = (idx, checked) ->
            # scope.table.checkAll = checked if not checked
            items = scope.table
            index = 0
            if checked
              for item in items.data
                if item.checked is true
                  index++
                  if index is items.data.length
                    scope.table.checkAll = true
                  else
                    scope.table.checkAll = false
            else
              scope.table.checkAll = false
            scope.table.selectHandler checked, idx if scope.table.selectHandler
            return

          scope.switchItem = (idx) ->
            for item in scope.table.data
              item.switched = false
            scope.table.data[idx].switched = true
            scope.table.switchedHandler idx if scope.table.switchedHandler
            return

          scope.deleteSubmitHandler = (id) ->
            parameters = [id]
            scope.table['deleteHandler'].apply null,parameters
            return

      )
  ]
