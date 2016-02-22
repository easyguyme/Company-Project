define [
  'wm/app'
  'wm/config'
], (app, config) ->
  app.registerController 'wm.ctrl.management.upgrade', [
    'restService'
    (restService) ->
      vm = this
  ]
