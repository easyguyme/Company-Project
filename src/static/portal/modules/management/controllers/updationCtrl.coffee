define [
  'wm/app'
  'wm/config'
], (app, config) ->
  app.registerController 'wm.ctrl.management.updation', [
    'restService'
    (restService) ->
      vm = this
  ]