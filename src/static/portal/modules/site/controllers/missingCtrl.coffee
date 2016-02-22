define [
  'wm/app'
], (app, config) ->
  app.registerController 'wm.ctrl.site.missing', [
    '$rootScope'
    ($rootScope) ->
      $rootScope.isFullScreen = true

      if history.length < 2
        angular.element('.prompt-text-back').hide()
        angular.element('.missing-icon-back').hide()
  ]
