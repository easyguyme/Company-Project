define ['core/coreModule'], (mod) ->
  mod.directive 'wmSelectTable', [
    ->
      return (
        restrict: 'E,A'
        replace: true
        scope: {
          table: '=ngModel'
        }
        template: '<section>
                    <table bindonce class="table wm-data-table data-select-table">
                      <thead>
                        <tr>
                          <th bo-if="!table.selectable" class="checkbox-col"></th>
                          <th ng-repeat="colDef in table.columnDefs track by $index" bo-class="{cp:colDef.sortable}" class="{{colDef.headClass}}" ng-click="sortHandler(colDef)">
                            <span class="table-title-word">{{colDef.label|translate}}</span>
                            <span class="icon-sortable" bo-if="colDef.sortable" ng-class="{up:colDef.desc}"></span>
                          </th>
                        </tr>
                      </thead>
                      <tbody>
                        <tr ng-repeat="item in table.data">
                          <td ng-if="!table.selectable" class="checkbox-col">
                            <wm-checkbox ng-if="item[\'enabled\'] && table.isCheckBox" ng-click="checkItem($index, item.checked)" ng-model="item.checked"></wm-checkbox>
                            <wm-checkbox ng-if="!item[\'enabled\'] && table.isCheckBox" ng-model="item.checked" is-disabled="true"></wm-checkbox>
                            <wm-radio ng-if="item[\'enabled\'] && !table.isCheckBox" ng-click="checkItem($index, item[table.radioColumn])"
                             ng-model="table.radioValue" value="{{item[table.radioColumn]}}"></wm-radio>
                            <wm-radio ng-if="!item[\'enabled\'] && !table.isCheckBox" ng-model="table.radioValue" is-disabled="true" value="{{item[table.radioColumn]}}"></wm-radio>
                          </td>
                          <td ng-repeat="colDef in table.columnDefs" bo-switch="colDef.type" class="{{colDef.cellClass}}">
                            <span bo-switch-when="mark">
                              <span>{{item[colDef.field]}}</span>
                              <span class="mark-text" ng-if="!item[\'enabled\']" translate="{{colDef.markText}}" wm-tooltip="{{colDef.markTip | translate}}"></span>
                            </span>
                            <span bo-switch-when="number">
                              <span>{{item[colDef.field] | number}}</span>
                            </span>
                            <span bo-switch-default>{{item[colDef.field]}}</span>
                          </td>
                        </tr>
                      </tbody>
                    </table>
                  </section>'
        link: (scope, elem, attr) ->
          for row in scope.table.data
            row.checked = row.checked or false
            row.enabled = if row.enabled? then row.enabled else true

          scope.checkItem = (idx, value) ->
            scope.table.checkHandler.call null, idx, value if scope.table.checkHandler
            return

      )
  ]
