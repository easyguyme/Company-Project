define [
  'core/coreModule'
  'wm/config'
 ], (mod, config) ->
  mod.directive 'wmLinkSelect', [
    'restService'
    '$modal'
    '$rootScope'
    '$q'
    'channelService'
    '$filter'
    (restService, $modal, $rootScope, $q, channelService, $filter) ->
      return (
        restrict: 'EA'
        scope:
            ngModel: '='
            horizontal: '@'
            noEmpty: '@'
        template: '<div class="link-select-url clearfix">
                    <div ng-class="{\'col-md-5\':horizontal==\'true\', \'link-select\':horizontal==\'true\'}">
                      <div wm-select ng-model="linkType" items="linkTypes" text-field="text" value-field="value" on-change="changeLinkType"></div>
                    </div>

                    <div ng-show="linkType==\'in\'" ng-class="{\'col-md-7\':horizontal==\'true\', \'pd0\':horizontal==\'true\'}" class="station-link">
                      <input onkeydown="return false;" class="form-control station-link-input" type="text" ng-model="linkPage.title" form-tip="{{\'wechat_menu_station_link_tip\'|translate}}"
                      ng-click="showStationLink()" />
                      <div class="station-link-icon" ng-click="showStationLink()"></div>
                      <span ng-if="hasOauth" class="oauth-in" wm-tooltip="{{channelTip}}"></span>
                    </div>

                    <div ng-show="linkType==\'out\'" class="linkInput link-out" ng-class="{\'col-md-7\':horizontal==\'true\', \'pd0\':horizontal==\'true\'}">
                      <input class="form-control" type="url" ng-model="ngModel" wm-url maxlength="250" placeholder="{{\'input_url\'|translate}}"/>
                      <span ng-if="hasOauth&&ngModel" class="oauth-out" wm-tooltip="{{channelTip}}"></span>
                    </div>
                    <div style="clear:left" ng-show="linkType!=\'empty\'&&ngModel">
                      <div wm-channel-btn url="{{ngModel}}" on-select="generateOauth" ng-model="hasOauth"></div>
                    </div>
                  </div>'

        link: (scope, element, attrs) ->
          # Get domain name to distinct inner link or outer link
          domains = ['u.omnisocials.com', 'u.quncrm.com']
          scope.channelId = ''

          scope.linkPage = {}

          LINK_TYPES =
            IN: 'in'
            OUT: 'out'
            EMPTY: 'empty'

          scope.linkTypes = [
            {
              text: 'internal_link'
              value: LINK_TYPES.IN
            }
            {
              text: 'outer_link'
              value: LINK_TYPES.OUT
            }
          ]

          if scope.noEmpty isnt 'true'
            scope.linkTypes.push { text: 'no_link', value: LINK_TYPES.EMPTY }

          scope.generateOauth = (checked, oauthLink) ->
            scope.ngModel = if checked then oauthLink else decodeURIComponent(_getOriginalLink(oauthLink or scope.ngModel))
            _updateChannelTip(oauthLink) if oauthLink
            return

          scope.changeLinkType = (value, index) ->
            if scope.linkType is LINK_TYPES.OUT
              scope.outLint = scope.ngModel
            switch value
              when LINK_TYPES.IN then scope.ngModel = scope.linkPage.url or ''
              when LINK_TYPES.OUT then scope.ngModel = scope.outLint or ''
              when LINK_TYPES.EMPTY then scope.ngModel = ''
            scope.typeChanged = true if value isnt LINK_TYPES.EMPTY
            scope.hasOauth = false
            scope.linkType = value
            # remove hilight when validate error
            if element.find('.linkInput').hasClass('ng-hide')
              element.find('.link-select-url .ng-hide').find('input').removeClass('form-control-error').next().text('')

          _isOauthLink = (link) ->
            link.match(/redirect=(.+)&?/)?

          _getOriginalLink = (link) ->
            matches = link.match(/redirect=(.+)&?/)
            link = if matches? then matches[1] else encodeURIComponent link

          _isInnerLink = (link) ->
            for domain in domains
              if link.indexOf(domain) isnt -1
                return true
            return false

          _fillUrl = (scope, link) ->
            if not link
              if scope.noEmpty isnt 'true'
                scope.linkType = LINK_TYPES.EMPTY
              else
                scope.linkType = LINK_TYPES.IN
            else if _isInnerLink(link)
              scope.linkType = LINK_TYPES.IN
              # scope.linkPage.url = link
              condition =
                'url': _getOriginalLink(link)
              restService.get config.resources.searchTitle, condition, (data) ->
                if data
                  scope.linkPage =
                    title: angular.copy data.title
                    url: link
            else
              scope.linkType = LINK_TYPES.OUT
            scope.hasOauth = _isOauthLink(link)
            _updateChannelTip(link) if scope.hasOauth

          _getOauthLink = (channelId, url) ->
            url = encodeURIComponent(url)
            url = "#{location.origin}/api/mobile/base-oauth?channelId=#{channelId}&redirect=#{url}"
            url

          _updateChannelTip = (link) ->
            linkArray = link.slice(1).split '&'
            linkArray.forEach (item) ->
              if item.indexOf('channelId') isnt -1
                scope.channelId = item.split('=')[1]
            channelService.getChannels().then((channels) ->
              channels.forEach (channel) ->
                if channel.id is scope.channelId
                  channelName = channel.name
                  scope.channelTip = channelName + $filter('translate')('oauth_link')
            )

          scope.showStationLink = ->
            element.find('.station-link-input').focus()
            modalInstance = $modal.open(
              templateUrl: '/build/modules/core/partials/selectPages.html'
              controller: 'wm.ctrl.core.selectPages'
              windowClass: 'station-link-dialog'
              resolve:
                modalData: ->
                  url: if scope.hasOauth then decodeURIComponent(_getOriginalLink(scope.ngModel)) else scope.ngModel
            )
            modalInstance.result.then (selectedContent) ->
              scope.linkPage = angular.copy selectedContent
              scope.ngModel = if scope.hasOauth then _getOauthLink(scope.channelId, scope.linkPage.url) else scope.linkPage.url
              element.find('.station-link-input').focus()
            return

          scope.$watch('ngModel', (newVal, oldVal) ->
            if not scope.typeChanged and newVal? and typeof newVal isnt 'undefined'
              _fillUrl(scope, newVal)
              scope.typeChanged = false
          )

          return
    )
  ]
