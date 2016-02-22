define [
  'wm/app'
], (app, config) ->
  app.registerController 'wm.ctrl.channel.noaccount', [
    '$rootScope'
    ($rootScope) ->
      rvm = $rootScope

      rvm.isFullScreen = false
      rvm.isHideVerticalNav = true
  ]
