define [
  'core/coreModule'
  'wm/config'
], (mod, config) ->
  mod.directive('wmTopNav', [
    '$state'
    '$location'
    'storeService'
    ($state, $location, storeService) ->
      return (
        restrict: 'E'
        replace: true
        scope:
          currentState: '='
          channels: '='
          channelLink: '@'
          mods: '='
        template: '<ul class="nav navbar-nav">
                    <li class="nav-item" ng-repeat="mod in tops">
                      <a ng-href="{{mod.stateUrl}}" ng-class="{active:mod.active}" ng-click="navigate(mod.stateUrl)" translate="nav_{{mod.name}}"></a>
                    </li>
                    <li ng-if="extras.length > 0" class="dropdown nav-item" dropdown on-toggle="toggled(open)">
                      <a href class="dropdown-toggle" ng-class="{active:highlightExtra}" dropdown-toggle>{{"more"|translate}}<i class="unfold"></i></a>
                      <ul class="dropdown-menu">
                        <li ng-repeat="mod in extras">
                          <a ng-href="{{mod.stateUrl}}" ng-class="{active:mod.active}" ng-click="navigate(mod.stateUrl)" translate="nav_{{mod.name}}"></a>
                        </li>
                      </ul>
                    </li>
                  </ul>'
        link: (scope) ->

          MAX_TOP_NAV = 6

          highlightMore = (scope) ->
            scope.highlightExtra = false
            if scope.extras
              for extra in scope.extras
                if extra.active
                  scope.highlightExtra = true
                  break

          splitMods = (scope, max) ->
            mods = scope.mods
            if mods and mods.length
              scope.tops = mods.slice(0, max)
              scope.extras = mods.slice(max)
              highlightMore scope

          resetChannelLink = (scope) ->
            if scope.channelLink and scope.mods
              for mod in scope.mods
                if 'channel' is mod.name
                  mod.stateUrl = scope.channelLink
                  break
            return

          resetStoreLink = (scope, link) ->
            link = link or '/management/edit/store'

            if scope.mods
              for mod in scope.mods
                if 'store' is mod.name
                  mod.stateUrl = link
                  break
            return

          scope.$watch 'channels', (channels) ->
            if channels and not channels.length
              # Navgigate to the channel management page for adding if there is no channels
              scope.channelLink = '/management/channel'

          scope.$watch 'channelLink', ->
            resetChannelLink scope

          storeService.watchCurStore((link) ->
            resetStoreLink scope, link if scope.mods
          )

          scope.$watch 'mods', ->
            splitMods scope, MAX_TOP_NAV
            resetChannelLink scope
            resetStoreLink scope, storeService.getCurLink()

          scope.$watch 'extras', ->
            highlightMore scope
          , true

          scope.$watch 'currentState', (state) ->
            modName = state?.name.split('-')[0]
            highlightActiveMods(scope, modName)
            highlightMore(scope)

          # check which mod is activated now, and highlight it
          highlightActiveMods = (scope, modName) ->
            tops = scope.tops
            extras = scope.extras
            return if not tops or not extras
            for top in tops
              top.active = parseStateUrl(top.stateUrl) is modName
            for extra in extras
              extra.active = parseStateUrl(extra.stateUrl) is modName

          # get a stateUrl, and return it's root name
          # Example: '/game/list' => 'game', '/content/graphics' => 'content'
          parseStateUrl = (stateUrl) ->
            return stateUrl.split('/')[1]

          scope.navigate = (path) ->
            # Force the page reloading for tab state
            $state.reload() if $location.url() isnt path
      )
  ])
