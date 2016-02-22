define [
  'core/coreModule'
  'chat/config'
], (mod, config) ->
  mod.factory 'sessionService', [
    'localStorageService'
    'restService'
    '$location'
    'issueService'
    'chatService'
    '$rootScope'
    (localStorageService, restService, $location, issueService, chatService, $rootScope) ->
      rvm = $rootScope
      session = {}

      session.getCurrentUser = ->
        localStorageService.getItem config.keys.currentUser

      session.removeLoginInfo = (isForcedOffline) ->
        #clear the login info in local Storage
        localStorageService.removeItem config.keys.currentUser
        localStorageService.removeItem config.keys.accessToken
        if not isForcedOffline
          rvm.isLogined = false
        delete rvm.user

      session.helpdeskLogout = ->
        self = this
        restService.get config.resources.logout, {}, ->
          if rvm.user
            self.removeLoginInfo()
            chatService.unbindEvent("force_offline")
            chatService.dismissChat()

          # redirect to the login page
          $location.path config.paths.login

      session.issueLogout = ->
        self = this
        rvm.logoutFrom = config.paths.issue
        issueService.destory(rvm.user)
        if rvm.user
          self.removeLoginInfo()

        # redirect to the login page
        $location.path config.paths.login

      session
  ]
