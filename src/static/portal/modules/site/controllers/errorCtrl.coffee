define [
  'wm/app'
], (app, config) ->
  app.registerController 'wm.ctrl.site.error', [
    '$rootScope'
    ($rootScope) ->
      $rootScope.isFullScreen = true
  ]
