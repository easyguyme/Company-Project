define [
  'wm/app'
  'wm/config'
  'wm/modules/analytic/controllers/wechat/followersPropertyCtrl'
  'wm/modules/analytic/controllers/weibo/followersPropertyCtrl'
], (app, config) ->
  app.registerController 'wm.ctrl.analytic.property', [
    'restService'
    '$stateParams'
    '$location'
    '$rootScope'
    'channelService'
    (restService, $stateParams, $location, $rootScope, channelService) ->
      vm = this
      rvm = $rootScope

      vm.breadcrumb = [
        'analytic_followers_property'
      ]

      path = '/build/modules/analytic/partials/'

      vm.changeChannel = (channel) ->
        vm.currentChannelId = channel.id
        $location.search 'channel', channel.id
        _getTemplate channel.id

      _setCurrentChannel = (channels) ->
        if not $location.search().channel
          vm.currentChannelId = channels[0].id
          $location.search 'channel', vm.currentChannelId
        else
          vm.currentChannelId = $location.search().channel

      _getTemplate = (id) ->
        for channel in rvm.channels
          if channel.id is id
            if channel.type is 'wechat'
              vm.fileName = path + '/wechat/followersProperty.html'
            else
              vm.fileName = path + '/weibo/followersProperty.html'
            break

      _init = ->
        channelService.getChannels().then((channels) ->
          if not channels.length
            $location.path config.noAccount
          else
            vm.allChannels = channels
            _setCurrentChannel(channels)
            _getTemplate vm.currentChannelId
        )

      _init()

      vm
  ]
