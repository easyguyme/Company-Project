define ['core/coreModule'], (mod) ->
  mod.directive 'wmTable', [
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

                    <table bindonce class="table wm-data-table">
                      <thead>
                        <tr>
                          <th bo-if="table.selectable" class="checkbox-col">&nbsp;
                          </th>
                          <th ng-repeat="colDef in table.columnDefs track by $index" bo-class="{cp:colDef.sortable}" class="{{colDef.headClass}}" ng-click="sortHandler(colDef)">
                            <span class="table-title-word">{{colDef.label|translate}}</span>
                            <span class="icon-sortable cp" bo-if="colDef.sortable" ng-class="{up:colDef.desc}"></span>
                          </th>
                          <th bo-if="table.operations" class="operation-col">{{1==table.operations.length?\"action\":\"operations\"|translate}}</th>
                        </tr>
                      </thead>
                      <tbody>
                        <tr ng-repeat="item in table.data track by $index">
                          <td bo-if="table.selectable" class="checkbox-col"><wm-checkbox ng-click="checkItem($index, item.checked)" ng-model="item.checked"></wm-checkbox></td>
                          <td ng-repeat="colDef in table.columnDefs" bo-switch="colDef.type" class="{{colDef.cellClass}}">
                            <span bo-switch-when="account" wm-tooltip="{{item[colDef.field].name}}">
                              <div class="panel panel-icon-default social-display"
                                ng-class="{\'service-account\': item[colDef.field].isServiceAccount, \'subscription-account\': item[colDef.field].isSubscriptionAccount}">
                              </div>
                              {{item[colDef.field].name}}
                            </span>
                            <span bo-switch-when="goodsIcon" class="goods-icon-block text-el" wm-tooltip="{{item[colDef.field].name}}">
                              <img ng-if="item[colDef.field].url != \'\'" class="goods-icon-show" ng-src="{{item[colDef.field].url|qiniu:50}}"/>
                              <span bo-if="!colDef.seperate">{{item[colDef.field].name}}</span>
                              <div class="text-el" bo-if="colDef.seperate">{{item[colDef.field].name}}</div>
                            </span>
                            <span bo-switch-when="tokenIcon" wm-tooltip="{{item.name}}">
                              <div class="panel panel-icon-default social-display goods-icon-show">
                                <img class="goods-icon-show" ng-src="{{item.icon|qiniu:35}}"/>
                              </div>
                              {{item.name}}
                            </span>
                            <span bo-switch-when="typeDetail" wm-tooltip="{{item[colDef.field].tooltip|translate}}">
                              <span ng-style="{{item[colDef.field].style}}">{{item[colDef.field].text|translate}}</span>
                              <span style="color: #999; margin-left: 5px; font-size: 12px;">{{item[colDef.field].detail|translate}}</span>
                            </span>
                            <span bo-switch-when="date">{{item[colDef.field]|date:colDef.format?colDef.format:"yyyy-MM-dd HH:mm:ss"}}</span>
                            <span bo-switch-when="status">
                              <div wm-switch="switchHandler($parent.$parent.$index)" style="height: 30px;"
                              on-value="ENABLE" off-value="DISABLE" ng-model="item[colDef.field]" is-disabled="{{item.switchIsDisabled}}"></div>
                            </span>
                            <span bo-switch-when="currency">{{item[colDef.field]|currency:\'￥\':2}}</span>
                            <span bo-switch-when="html" ng-if="!item[colDef.field].tooltip" ng-bind-html="item[colDef.field]"></span>
                            <span bo-switch-when="html" ng-if="item[colDef.field].tooltip" ng-bind-html="item[colDef.field].text" wm-tooltip="{{item[colDef.field].tooltip}}"></span>
                            <span bo-switch-when="translate" wm-tooltip="{{item[colDef.field]|string|translate}}">{{item[colDef.field]|string|translate}}</span>
                            <span bo-switch-when="textColor" class="{{item[colDef.field].color}}">{{item[colDef.field].text|string|translate}}</span>
                            <span bo-switch-when="description" wm-tooltip="{{item[colDef.field]|string|translate}}">{{item[colDef.field]|string|translate}}</span>
                            <span bo-switch-when="translateValues" wm-tooltip="{{item[colDef.field].key|string|translate:item[colDef.field].values}}">
                              {{item[colDef.field].key|string|translate:item[colDef.field].values}}
                            </span>
                            <span bo-switch-when="modify">
                              <span bo-if="colDef.kind!=\'plain\'" class="text-el modify-text" wm-tooltip="{{item[colDef.field].key|string|translate:item[colDef.field].values}}">
                              {{item[colDef.field].key|string|translate:item[colDef.field].values}}</span>
                              <span bo-if="colDef.kind==\'plain\'" class="text-el modify-text" wm-tooltip="{{item[colDef.field]}}">{{item[colDef.field]}}</span>
                              <span class="table-pen cp pen-position" ng-click="modifyHandler($parent.$parent.$index, colDef.field)"></span>
                            </span>
                            <span bo-switch-when="link">
                              <a ng-if="item[colDef.field].link" ng-href="{{item[colDef.field].link}}"
                              wm-tooltip="{{item[colDef.field].tooltip || item[colDef.field].text}}"
                              target="{{item[colDef.field].target}}" ng-click="linkHandler($parent.$parent.$parent.$index)">
                              {{item[colDef.field].text}}</a>
                              <span ng-if="!item[colDef.field].link" wm-tooltip="{{item[colDef.field].tooltip || item[colDef.field].text}}">{{item[colDef.field].text}}</span>
                              <span ng-if="item[colDef.field].explaination" class="link-explaination">{{item[colDef.field].explaination|translate}}</span>
                            </span>
                            <span bo-switch-when="transLink">
                              <a ng-href="{{item[colDef.field].link}}"
                              wm-tooltip="{{item[colDef.field].tooltip || item[colDef.field].text|translate}}"
                              target="{{item[colDef.field].target}}" ng-click="linkHandler($parent.$parent.$parent.$index)">
                              {{item[colDef.field].text|translate}}</a><span ng-if="item[colDef.field].explaination" class="link-explaination"
                              translate="{{item[colDef.field].explaination}}"></span>
                            </span>
                            <div bo-switch-when="multiLink">
                              <a ng-repeat="linkItem in item[colDef.field]" ng-href="{{linkItem.link}}"
                              wm-tooltip="{{linkItem.tooltip}}">{{linkItem.text|translate}}</a>
                            </div>
                            <span class="highlight-tag" ng-if="item[colDef.field].tag">{{item[colDef.field].tag|translate}}</span>
                            <span bo-switch-when="operation">
                              <span ng-repeat="operation in item[colDef.field]">
                                <a bo-if="!operation.disable" bo-href="operation.link" wm-tooltip="{{operation.title || operation.name|translate}}"
                                ng-click="optHandler($parent.$parent.$parent.$parent.$index, operation.name, $event)"
                                class="operate-icon {{operation.name}}-icon-table" ng-hide="operation.hidden"></a>
                                <a bo-if="operation.disable" class="operate-icon {{operation.name}}-icon-disable" wm-tooltip="{{operation.title | translate}}"></a>
                              </span>
                            </span>
                            <span bo-switch-when="operationText">
                              <a bo-href="operation.link" ng-repeat="operation in item[colDef.field]" ng-click="optHandler($parent.$parent.$parent.$index, operation.name, $event)"
                              class="cp operation-text operation-{{operation.name}}-text" data-confirm-target-color="{{operation.name == \'delete\'? true:false}}">{{operation.name | translate}}</a>
                            </span>
                            <span bo-switch-when="mainRecord">
                              <span class="green-text-color" ng-if="item[colDef.field].isMainRecord">
                              <img class="icon-style" src="/images/core/setupsuccessfully.png">{{\'main_record\' | translate}}</span>
                              <button ng-if="!item[colDef.field].isMainRecord" ng-click="optHandler($parent.$parent.$parent.$index, item[colDef.field].name, $event)"
                              class="cp btn btn-success">{{item[colDef.field].name | translate}}</button>
                            </span>
                            <span bo-switch-when="label">
                              <span class="icon-text text-icon" ng-if="item[colDef.field].type == \'TEXT\'" translate="channel_wechat_mass_text"></span>
                              <span class="icon-text graphic-icon" ng-if="item[colDef.field].type == \'NEWS\'" translate="channel_wechat_mass_graphic"></span>
                              {{item[colDef.field].content}}
                            </span>
                            <span bo-switch-when="iconText">
                              <span>
                                <img class="icon-style" ng-src="{{item[colDef.field].icon}}" ng-if="!colDef.isHideText">
                                <img class="icon-style" ng-src="{{item[colDef.field].icon}}" wm-tooltip="{{item[colDef.field].text | translate}}" ng-if="colDef.isHideText">
                                <span ng-hide="colDef.isHideText">{{item[colDef.field].text}}</span>
                              </span>
                            </span>
                            <span bo-switch-when="icon">
                              <span ng-if="item[colDef.field].type">
                                <img class="icon-style" ng-if="!item[colDef.field].status"
                                  ng-src="{{item[colDef.field].icon}}" wm-tooltip="{{\'account_type\' | translate}}：{{item[colDef.field].type | translate}}
                                  {{\'member_status_disable\'| translate}}<br/>
                                {{\'account_name\' | translate}}：{{item[colDef.field].text}}">
                                <img class="icon-style" ng-if="item[colDef.field].status" ng-src="{{item[colDef.field].icon}}" wm-tooltip="{{\'account_type\' | translate}}：
                                  {{item[colDef.field].type | translate}}<br/>
                                {{\'account_name\' | translate}}：{{item[colDef.field].text}}">
                              </span>
                              <span ng-if="!item[colDef.field].type"><img class="icon-style" ng-src="{{item[colDef.field].icon}}"  wm-tooltip="{{item[colDef.field].tip | translate}}"></span>
                            </span>
                            <span bo-switch-when="copy">
                              <input class="form-control copy-text" type="text" value="{{item[colDef.field]}}" readonly />
                              <i wm-copy class="icon-copy icon-table-copy" clipboard-text="item[colDef.field]" tip="{{\'management_token_copy_hover_tip\' | translate}}" tooltip-max-width="160">
                              </i>
                            </span>
                            <span bo-switch-when="scoreChannels">
                              <img ng-src="{{item[colDef.field].icon}}" ng-if="item[colDef.field].icon">
                              <span class="channels-name">{{item[colDef.field].text | translate}}</span>
                              <span class="channels-suffix" ng-if="item[colDef.field].suffix">{{item[colDef.field].suffix | translate}}</span>
                            </span>
                            <span bo-switch-when="twoStatus">
                              <a ng-href="{{item[colDef.field].link}}" ng-click="statusHandler($parent.$parent.$parent.$index, $event)"
                              ng-if="item[colDef.field].status == \'one\'">{{item[colDef.field].oneStatusText|translate}}</a>
                              <img ng-src="{{item[colDef.field].icon}}" ng-if="item[colDef.field].status == \'two\'">
                              <span ng-if="item[colDef.field].status == \'two\'">{{item[colDef.field].twoStatusText | translate}}</span>
                            </span>
                            <input bo-switch-when="input" class="form-control" type="text" ng-model="item[colDef.field]"/>
                            <span bo-switch-default ng-if="!item[colDef.field].tooltip" wm-tooltip="{{item[colDef.field]}}"
                            tooltip-max-width="{{colDef.tooltipMaxWidth || tooltipMaxWidth}}" class="default-span">{{item[colDef.field]}}</span>
                            <span bo-switch-default ng-if="item[colDef.field].tooltip" wm-tooltip="{{item[colDef.field].tooltip}}">{{item[colDef.field].text}}</span>
                          </td>
                          <td bo-if="table.operations" class="operation-col">
                            <span class="rel" ng-repeat="operation in table.operations" bo-switch="operation.name">
                              <a bo-href="operation.link" wm-tooltip="{{operation.title || operation.name|translate}}"
                                ng-click="optHandler($parent.$index, operation.name, $event)" class="operate-icon {{operation.name}}-icon-table"
                                ng-class="{\'icon-selected\' : item.extra && operation.name == \'more\'}"></a>
                              <ul bo-switch-when="more" ng-show="item.extra" class="list-group more-operations" ng-click="moreListHandler(item)">
                                <li ng-repeat="action in operation.actions">
                                  <a class="list-group-item" href="#" translate="{{action.text}}" ng-click="optHandler($parent.$parent.$parent.$parent.$index, action.name, $event)"></a>
                                </li>
                              </ul>
                            </span>
                          </td>
                        </tr>
                        <tr ng-if="table.singleRow && table.data.length > 0" ng-repeat="single in table.singleRow track by $index">
                          <td colspan="{{column}}" class="single-row {{single.rowClass}}">
                            <div class="clearfix" ng-repeat="row in single.rows">
                              <span class="pull-right {{row.contentClass}}">{{row.content}}</span>
                              <span class="pull-right {{row.titleClass}}" translate="{{row.title}}"></span>
                            </div>
                          </td>
                        </tr>
                      </tbody>
                    </table>
                    <div class="no-data" ng-show="!table.hasLoading && table.data.length <= 0">{{table.emptyMessage | translate}}</div>
                    <div ng-if="table.hasLoading" id="table-loading" class="mask-loading" style="display:block;background-color: rgba(0, 0, 0, 0.168627);">
                      <div class="loading-icon"></div>
                    </div>
                  </div>
                  '
        link: (scope, elem, attrs) ->
          scope.tooltipMaxWidth = 600
          scope.column = if scope.table.operations then scope.table.columnDefs.length + 1 else scope.table.columnDefs.length
          if not scope.table.emptyMessage
            scope.table.emptyMessage = 'no_data'

          # check if selectAll inside.
          if scope.isSelectAll?
            scope.isSelectAll = true
          else
             scope.isSelectAll = false

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
              when 'refresh'
                notificationService.confirm $event,{
                  'title': scope.table.refreshTitle,
                  'submitCallback': scope.refreshSubmitHandler,
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

          scope.switchHandler = (idx) ->
            scope.table.switchHandler idx
            return

          scope.modifyHandler = (idx, colDef) ->
            scope.table.modifyHandler idx, colDef

          scope.linkHandler = (idx) ->
            scope.table.linkHandler idx if scope.table.linkHandler

          scope.statusHandler = (idx, $event) ->
            scope.table.statusHandler idx, $event if scope.table.statusHandler

          scope.checkAll = (checked) ->
            # judge if the value of table.checkAll is already set to checked, if so then uncheck all the checkbox #3724
            if scope.table.checkAll is checked
              checked = scope.table.checkAll = not checked

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

          scope.deleteSubmitHandler = (id) ->
            parameters = [id]
            scope.table['deleteHandler'].apply null,parameters
            return

          scope.refreshSubmitHandler = (id) ->
            parameters = [id]
            scope.table['refreshHandler'].apply null,parameters
            return

          scope.moreListHandler = (item) ->
            item.extra = false
            $($('.mask-confirm')[0]).remove()
            return
      )
  ]
