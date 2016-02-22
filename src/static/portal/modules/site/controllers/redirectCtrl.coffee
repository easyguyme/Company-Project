define [
  'wm/app'
  'wm/config'
], (app, config) ->
  app.registerController 'wm.ctrl.site.redirect', [
    '$rootScope'
    '$location'
    'channelService'
    'moduleService'
    ($rootScope, $location, channelService, moduleService) ->

      rvm = $rootScope

      MEMBER_URL = '/member/member'
      CHANNEL_URL = '/management/channel'
      FOLLOWER_URL = '/channel/follower'
      NO_ACCOUNT = config.noAccount

      _toDashboard = (menus) ->
        dashbordPath = ''

        channelService.getChannels().then ->
          if not menus.channel
            dashbordPath = MEMBER_URL
          else if rvm.channels and rvm.channels.length > 0
            dashbordPath = "#{FOLLOWER_URL}/#{rvm.channels[0].id}"
          else
            dashbordPath = NO_ACCOUNT

          $location.path dashbordPath

      moduleService.getConfig().then (conf) ->
        rvm.conf = conf if not rvm.conf
        menus = rvm.conf.menus
        _toDashboard(menus)
  ]
