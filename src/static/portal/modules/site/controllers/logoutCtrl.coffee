define [
  'wm/app'
  'wm/config'
], (app, config) ->
  app.registerController 'wm.ctrl.site.logout', [
    'restService'
    '$rootScope'
    'localStorageService'
    '$location'
    'messageService'
    'exportService'
    'storeService'
    (restService, $rootScope, localStorageService, $location, messageService, exportService, storeService) ->
      vm = this
      _logout = ->
        restService.get config.resources.logout, {}, ->
          messageService.destory()
          #clear the login info in local Storage
          localStorageService.removeItem config.keys.currentUser
          localStorageService.removeItem config.keys.loginEmail
          localStorageService.removeItem config.keys.loginPassword
          localStorageService.removeItem config.keys.exportJobs
          exportService.destory()
          storeService.destory()

          rootScopeKeys = ['isLogined', 'user', 'channels', 'enabledModules', 'isAdmin', 'currentState', 'currentChannel', 'conf']
          angular.forEach rootScopeKeys, (key) ->
            delete $rootScope[key]

          # redirect to the login page
          $location.path config.loginPath

      _logout()
      return
  ]
