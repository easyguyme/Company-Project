define [
  'wm/app'
  'wm/config'
], (app, config) ->
  app.registerController 'wm.ctrl.management.oauth2.accesstoken', [
    'restService'
    '$location'
    (restService, $location) ->
      vm = this

      ###
      # init function to query bound channel list
      ###
      _init = ->
        authCode = $location.search().code
        if authCode
          condition=
            code: authCode
          restService.get config.resources.createWeibo, condition, (data) ->
            if data && data.errmsg
              $location.url '/management/channel?errmsg=' + data.errmsg;
            else
              $location.url '/management/channel'
            return
        else
         $location.url '/management/channel'

      _init()
      vm
  ]
