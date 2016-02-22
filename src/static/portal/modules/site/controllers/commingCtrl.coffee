define [
  'wm/app'
], (app, config) ->
  app.registerController 'wm.ctrl.site.comming', [
    '$rootScope'
    ($rootScope) ->
      $rootScope.isFullScreen = true
  ]
