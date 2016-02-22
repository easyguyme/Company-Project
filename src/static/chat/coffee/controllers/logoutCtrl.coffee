define [
  'core/coreModule'
  'chat/config'
], (app, config) ->
  app.controller 'wm.ctrl.logout', [
    'restService'
    '$rootScope'
    '$location'
    'chatService'
    'issueService'
    'sessionService'
    (restService, $rootScope, $location, chatService, issueService, sessionService) ->
      vm = this
      rvm = $rootScope

      _init = ->
        if rvm.isLogined

          if rvm.isHelpdeskPage
            sessionService.helpdeskLogout()
          if rvm.isIssuePage
            sessionService.issueLogout()

          rvm.stopTimer()
        else
          $location.path config.paths.login

      _init()
  ]
