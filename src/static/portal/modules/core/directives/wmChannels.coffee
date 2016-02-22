define [
  'core/coreModule'
  'wm/config'
], (mod, config) ->
  mod.directive 'wmChannels', [
    'restService'
    (restService) ->
      return (
        restrict: 'EA'
        scope:
          ngModel: '='
          channels: '='

        template: '<div class="wm-channels-wrapper clearfix">
                    <div class="channel-item pull-left"><div wm-checkbox ng-click="selectAll()" ng-model="checkedAll"></div>{{\'customer_follower_all\' | translate}}</div>
                    <ul class="channel-list pull-left clearfix">
                      <li ng-repeat="channel in channels" class="channel-item pull-left">
                        <div wm-checkbox ng-model="channel.checked" ng-click="selectItem(channel)"></div>
                        <img wm-tooltip="{{channel.tip | translate}}" ng-src="/images/customer/{{channel.icon}}.png" />
                        {{channel.name | translate}}<span class="fs12" translate="member_status_disable" ng-show="channel.status==\'disable\'"></span>
                      </li>
                    </ul>
                   </div>'

        link: (scope, elem, attrs) ->

          vm = scope
          vm.channels = []
          vm.ngModel = []

          channelArr = ['portal', 'app:android', 'app:ios', 'app:webview', 'app:web']
          disableChannels = []

          origins =
            WECHAT: 'wechat'
            WEIBO: 'weibo'
            ALIPAY: 'alipay'
            PORTAL: 'portal' # offline
            APP_ANDROID: 'app:android'
            APP_IOS: 'app:ios'
            APP_WEB: 'app:web' # mobile browser
            APP_WEBVIEW: 'app:webview' # mobile browser
            OTHERS: 'others' # others

          vm.selectAll = ->
            for channel in vm.channels
              channel.checked = vm.checkedAll
              _cacheSelect(channel)

          vm.selectItem = (channel) ->
            vm.checkedAll = vm.channels.filter((item) ->
              return item.checked
            ).length is vm.channels.length
            _cacheSelect(channel)

          vm.$watch 'ngModel', (newVal, oldVal) ->
            _fillCheck(newVal)
          , true

          _getStaticChannel = (channel) ->
            tip = channel.replace ':', '_'
            vm.channels.push {id: channel, name: tip, icon: tip, tip: tip}

          _fillCheck = (channelIds) ->
            for channel in vm.channels
              if($.inArray(channel.id, channelIds) isnt -1)
                channel.checked = true
              else
                channel.checked = false
            if $.isArray channelIds
              vm.checkedAll = if channelIds.length is vm.channels.length then true else false

          _cacheSelect = (channel) ->
            vm.ngModel = vm.ngModel or []
            position = $.inArray(channel.id, vm.ngModel)
            if channel.checked and position is -1
              vm.ngModel.push channel.id

            if not channel.checked and position isnt -1
              vm.ngModel.splice position, 1

          _getStaticChannels = ->
            for channel in channelArr
              _getStaticChannel(channel)

          _getChannels = ->
            restService.noLoading().get config.resources.channelsAll, (data) ->
              for channel in data
                status = if channel.status is 'enable' then '' else '_disabled'
                switch channel.origin
                  when origins.WECHAT
                    channel.type = channel.type.toLowerCase()
                    icon = if channel.type.indexOf('service') isnt -1 then 'service' else 'subscription'
                    channel.icon = "wechat_#{icon}"
                    channel.icon = "wechat#{status}" if status
                    channel.tip = "channel_wechat_#{icon}_tip"
                  when origins.WEIBO, origins.ALIPAY
                    channel.type = channel.icon = "#{channel.origin}#{status}"
                    channel.tip = "channel_#{channel.origin}_tip"

                channel.id = channel.channelId
                if not status then vm.channels.push channel else disableChannels.push channel

              _getStaticChannel(origins.OTHERS)
              vm.channels = vm.channels.concat disableChannels

          _getStaticChannels()
          _getChannels()


      )
  ]
