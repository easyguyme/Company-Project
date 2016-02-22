define [
  'core/coreModule'
], (mod) ->
  mod.directive('wmVerticalNav', [
    '$state'
    '$location'
    '$filter'
    'storeService'
    '$rootScope'
    ($state, $location, $filter, storeService, $rootScope) ->
      return (
        restrict: 'EA'
        replace: true
        scope:
          currentState: '='
          currentChannel: '='
          channels: '='
          menus: '='
        template: '<ul class="navbar-vertical col-xs-2">
                    <li ng-if="currentState.isChannel&&channel" class="row channel-info">
                      <div class="channel-menu-box clearfix" ng-click="showChannels()">
                        <div class="col-md-2 col-xs-2 channelmenu-icon channelmenu-{{channel.type}}-icon">
                          <img class="channel-avatar" ng-src="{{channel.avatar}}">
                          <img class="channel-from" ng-src="{{channel.img}}">
                        </div>
                        <div class="col-md-9 col-xs-9 channel-tag-mg">
                          <div class="tags" ng-if="channel.type == \'wechat\'">
                            <span class="tag channel-tag" ng-style="channel.typeBackground">{{channel.typeName | translate}}</span>
                            <span class="tag channel-tag" ng-style="channel.authBackground">{{channel.authName | translate}}</span>
                          </div>
                          <div class="we-type-placeholder" ng-if="channel.type == \'weibo\'"></div>
                          <div class="we-type-placeholder" ng-if="channel.type == \'alipay\'"></div>
                          <div class="channel-name">{{channel.name}}</div>
                        </div>
                        <div class="dropdown-arrow-icon"></div>
                      </div>
                      <div class="channel-base-wrap" ng-if="isCollapsedChannels" ng-click="showChannels()"></div>
                      <div ng-class="{\'dropdown-channel-show\':isCollapsedChannels}">
                        <div class="dropdown-channel">
                          <div class="col-md-12 choose-channel navbar-border-light-color" ng-click="showChannels()">
                            {{"choose_channel"|translate}}
                          </div>
                          <ul class="col-md-12" ng-style="{\'height\': storesHeight+\'px\'}">
                            <li bindonce ng-repeat="channel in channelsList" class="clearfix">
                              <a bo-href="channel.link" ng-click="selectChannel(channel)" class="clearfix navbar-border-light-color">
                                <div class="col-xs-2 channelmenu-icon channelmenu-{{channel.type}}-icon">
                                  <img class="channel-avatar" ng-src="{{channel.avatar}}">
                                  <img class="channel-from" ng-src="{{channel.img}}">
                                </div>
                                <div class="col-xs-9">
                                  <div class="tags" ng-if="channel.type == \'wechat\'">
                                    <span class="tag" ng-style="channel.typeBackground">{{channel.typeName | translate}}</span>
                                    <span class="tag" ng-style="channel.authBackground">{{channel.authName | translate}}</span>
                                  </div>
                                  <div class="we-type-placeholder" ng-if="channel.type == \'weibo\'"></div>
                                  <div class="we-type-placeholder" ng-if="channel.type == \'alipay\'"></div>
                                  <div class="channel-name">{{channel.name}}</div>
                                </div>
                                <div class="channel-selected-icon" ng-show="channel.isCheck">
                                  <img src="/images/core/arrow_selected.png"/>
                                </div>
                              </a>
                            </li>
                          </ul>
                        </div>
                      </div>
                    </li>
                    <li ng-if="currentState.isStore&&store" class="row channel-info">
                      <div class="channel-menu-box store-info" ng-click="showStores()">
                        <div class="store-name">
                          <img ng-if="store.image" class="store-cover" ng-src="{{store.image | qiniu:\'60,40,2\'}}">
                          <img ng-if="!store.image" class="store-cover" src="/images/content/default.png">
                          <span>{{store.name}}</span>
                        </div>
                        <div class="store-address">{{store.address}}</div>
                        <div class="store-phone">{{store.phone}}</div>
                        <div class="dropdown-arrow-icon"></div>
                      </div>
                      <div class="store-base-wrap" ng-if="isCollapsedStores" ng-click="showStores()"></div>
                      <div ng-class="{\'dropdown-channel-show\':isCollapsedStores}">
                        <div class="dropdown-channel">
                          <div class="col-md-12 choose-channel navbar-border-light-color" ng-click="showStores()">
                            {{"store" | translate}}
                          </div>
                          <ul class="col-md-12" ng-style="{\'height\': storesHeight+\'px\'}">
                            <li bindonce ng-repeat="store in stores" class="clearfix">
                              <a bo-href="store.link" ng-click="selectStore(store)" class="clearfix navbar-border-light-color">
                                <div class="channel-menu-box store-info">
                                  <div class="store-name">
                                    <img ng-if="store.image" class="store-cover" ng-src="{{store.image | qiniu:\'60,40,2\'}}">
                                    <img ng-if="!store.image" class="store-cover" src="/images/content/default.png">
                                    <span>{{store.name}}</span>
                                  </div>
                                  <div class="store-address">{{store.address}}</div>
                                  <div class="store-phone">{{store.phone}}</div>
                                </div>
                                <div class="channel-selected-icon" ng-show="store.isCheck">
                                  <img src="/images/core/arrow_selected.png"/>
                                </div>
                              </a>
                            </li>
                          </ul>
                        </div>
                      </div>
                    </li>
                    <li bindonce ng-repeat="menu in subMenus" ng-class="{active:menu.active,centered:menu.centered}"
                    ng-switch="menu.name != \'qrcode\' || currentChannel.isService || currentChannel.type != \'wechat\'">
                      <a ng-if="!menu.class" bo-href="menu.link" ng-click="navigate(menu.link)" ng-class="{bordered:currentChannel&&!$index}">
                        <span class="nav-icon {{menu.name}}-icon"></span>
                        <span class="nav-title" translate="{{menu.title}}"></span>
                      </a>
                      <a ng-if="menu.class" target="_blank" bo-href="menu.link" class="{{menu.class}}" translate="{{menu.title}}"></a>
                    </li>
                  </ul>'
        link: (scope) ->
          #Define the menus
          stateMap = {}
          menuMap = {}

          scope.isCollapsedChannels = false

          generate = (menus) ->
            for modName, subMenus of menus
              menuMap[modName] = []
              for subMenu in subMenus
                # Transform state to url path
                subMenu.link = '/' + subMenu.state.replace(/-\{.+\}/g, '').split('-').join('/') if not subMenu.link
                menuMap[modName].push(subMenu)
                stateMap[subMenu.state] = modName if subMenu.state
            return

          # Generate correct menus based on the current module
          genSubMenu = (modName, menus, params) ->
            if modName is 'channel' or modName is 'store'
              for menu in menus
                # For channel state, the second item of parts is the channel type
                menu.link = menu.link + '/' + params.id
            menus

          render = (scope, state) ->
            #Strip the parameters in the state name
            stateName = state.name.replace(/-\{.+\}/g, '')
            modName = stateMap[stateName]
            #Check if it is the sub page within a menu (can not been seen at first sight)
            if not modName
              parts = stateName.split('-')
              stateName = [parts[0], parts[parts.length - 1]].join('-')
              modName = stateMap[stateName]
            #Generate the sub menu on the left
            if not scope.hideVerticalNav and modName
              subMenus = genSubMenu(modName, angular.copy(menuMap[modName]), state.params)
              # Render active sub menu
              if subMenus
                for menu in subMenus
                  menu.active = menu.state is stateName
                scope.subMenus = subMenus

          renderChannel = (channel) ->
            typeName = 'subscription_account'
            authName = 'nav_channel_verified'
            typeBackground = '#9b78cd'
            authBackground = '#e1a028'
            if channel.isService
              typeName = 'service_account'
              typeBackground = '#50a0e6'
            if not channel.isAuthed
              authName = 'nav_channel_unverified'
              authBackground = '#bebebe'
            {
              type: channel.type
              img: "/images/core/#{channel.type}-icon.png"
              name: channel.name
              avatar: channel.avatar
              typeName: typeName
              authName: authName
              typeBackground: {'background-color': typeBackground}
              authBackground: {'background-color': authBackground}
            }

          renderChannels = (currentChannel, channels) ->
            channelsList = []
            for channel in channels
              parts = channel.title.split '_'

              channel =
                isService: parts[0] is 'service'
                isAuthed: parts.length is 3
                type: channel.type
                img: "/images/core/#{channel.type}-icon.png"
                link: channel.link
                name: channel.name
                avatar: channel.avatar
                appId: channel.appId
                isCheck: if currentChannel? then channel.appId is currentChannel.appId else false

              channelsList.push $.extend channel, renderChannel(channel)
            scope.channelsList = channelsList;

          scope.$watch 'channels', (channels) ->
            if angular.isArray channels
              renderChannels(scope.currentChannel, channels)

          scope.$watch 'currentState', (state) ->
            scope.curState = state if state
            # Regenerate the sub menus when the current state changes
            render(scope, scope.curState) if state and stateMap

          scope.$watch 'currentChannel', (channel) ->
            ### the channel types
            'subscription_account'
            'subscription_auth_account'
            'service_account'
            'service_auth_account'
            ###
            if channel
              if channel.title
                parts = channel.title.split '_'
                channel.isService = parts[0] is 'service'
                channel.isAuthed = parts.length is 3
              scope.channel = renderChannel channel
              renderChannels(channel, scope.channels)
              $rootScope.currentChannel = channel

          storeService.watchCurStore((link, store) ->
            if store
              scope.store = store
              if scope.subMenus
                for menu in scope.subMenus
                  slashPos = menu.link.lastIndexOf('/') + 1
                  menu.link = menu.link.slice(0, slashPos) + store.id
          )

          scope.$watch 'menus', (menus) ->
            # Generate state map and sub menu configuration
            if menus
              generate menus
              render(scope, scope.curState)

          calculateHeight = ->
            $target = $('.dropdown-channel').find('ul')
            scope.storesHeight = $(document).height() - $target.offset().top

          scope.isCollapsedStores = false

          scope.showChannels = ->
            scope.isCollapsedChannels = not scope.isCollapsedChannels
            calculateHeight()

          scope.selectChannel = (channel) ->
            scope.isCollapsedChannels = not scope.isCollapsedChannels
            scope.currentChannel = angular.copy channel

          scope.showStores = ->
            stores = storeService.stores
            for store in stores
              store.isCheck = (store.id is scope.store.id)
              store.link = "/store/info/#{store.id}"
            scope.stores = stores
            scope.isCollapsedStores = not scope.isCollapsedStores
            calculateHeight()

          scope.selectStore = (store) ->
            scope.isCollapsedStores = not scope.isCollapsedStores
            storeService.setCurStore store

          scope.navigate = (path) ->
            # Force the page reloading for tab state
            $state.reload() if $location.url() isnt path
      )
  ])
