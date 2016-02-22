define [
  'wm/app'
  'wm/config'
  'core/directives/wmCharts'
  'wm/modules/analytic/controllers/wechat/followersGrowthCtrl'
  'wm/modules/analytic/controllers/weibo/followersGrowthCtrl'
], (app, config) ->
  app.registerController 'wm.ctrl.analytic.growth', [
    'restService'
    '$stateParams'
    '$location'
    '$rootScope'
    'channelService'
    (restService, $stateParams, $location, $rootScope, channelService) ->
      vm = this
      rvm = $rootScope

      vm.breadcrumb = [
        'analytic_followers_growth'
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
              vm.fileName = path + '/wechat/followersGrowth.html'
            else
              vm.fileName = path + '/weibo/followersGrowth.html'
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
