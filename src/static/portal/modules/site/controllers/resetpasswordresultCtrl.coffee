define [
  'wm/app'
  'wm/config'
], (app, config) ->
  app.registerController 'wm.ctrl.site.resetpasswordresult', [
    'heightService'
    (heightService) ->
      heightService.beforeLogin '.viewport', 'min-height'
  ]
