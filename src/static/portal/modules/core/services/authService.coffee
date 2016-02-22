define [
  'core/coreModule'
  'wm/config'
], (mod, config) ->
  mod.factory 'authService', [
    '$rootScope'
    'channelService'
    '$translate'
    'userService'
    'localStorageService'
    ($rootScope, channelService, $translate, userService, localStorageService) ->
      auth = {}
      auth.checkAccess = (next) ->
        canAccess = false
        rvm = $rootScope
        parts = next.name.split '-'
        targetModName = parts[0]
        subCtrlName = if parts.length > 1 then parts[1] else ''

        # Mark the user login status
        currentUser = localStorageService.getItem config.keys.currentUser
        if currentUser
          rvm.isAdmin = currentUser.role is config.role.admin
          rvm.isLogined = true

        accessibleSubSiteCtrls = ['forbidden', 'noaccount', 'comming', 'error', 'missing']
        # Make sure site module does not have to check access
        return true if targetModName is 'site' and $.inArray(subCtrlName, accessibleSubSiteCtrls) is -1

        # if the current user information is not in the root scope
        _loadUser(rvm) if not rvm.user

        accessableModules = []
        enabledModules = rvm.enabledModules
        if enabledModules and enabledModules.length > 0
          accessableModules = angular.copy(enabledModules)
          # Remove management authority for operator
          if not rvm.isAdmin
            idx = $.inArray('management', accessableModules)
            accessableModules.splice(idx, 1) if idx > -1
        # Add states without access check
        accessableModules.push('site')
        for modName in accessableModules
          if modName is targetModName
            canAccess = true
            break
        canAccess

      _loadUser = (rvm) ->
        # get the user information from locatStorage
        # the informations is persist after login
        userInfo = userService.getInfo()

        if userInfo
          # trigger dirty check for object reference
          $translate.use userInfo.language
          rvm.user = userInfo
          userService.initSettingHandler()

          if userInfo.enabledModules
            # Get enabled module list
            rvm.enabledModules = userInfo.enabledModules
            rvm.isAdmin = userInfo.role is config.role.admin
            rvm.isLogined = true

        return

      auth
  ]
