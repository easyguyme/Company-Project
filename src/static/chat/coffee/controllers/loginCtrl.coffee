define [
  'core/coreModule'
  'chat/config'
  'md5'
], (app, config, md5) ->
  app.controller 'wm.ctrl.login', [
    '$scope'
    'restService'
    '$rootScope'
    'localStorageService'
    'notificationService'
    '$location'
    '$translate'
    'heightService'
    'sessionService'
    '$interval'
    ($scope, restService, $rootScope, localStorageService, notificationService, $location, $translate, heightService, sessionService, $interval) ->
      vm = this
      rvm = $rootScope

      _init = ->
        $location.path config.paths.helpdesk if $rootScope.isLogined
        #login page language change by navigator lauguage
        if not $rootScope.isLogined
          checkCookies()
          $translate.use $('html').attr('lang')
        #load the login information in local storage
        #this is for "remember me"
        #vm.email = localStorageService.getItem config.keys.loginEmail
        #vm.password = localStorageService.getItem config.keys.loginPassword
        #remove the modal dialogs if exsisted
        $('.modal, .modal-backdrop').remove()
        heightService.beforeLogin '.content', 'height'

      checkCookies = ->
        timer = $interval ->
          if sessionService.getCurrentUser() and $location.path().search('/login')
            rvm.isLogined = true
            if rvm.logoutFrom is config.paths.helpdesk
              rvm.isHelpdeskPage = true
            if rvm.logoutFrom is config.paths.issue
              rvm.isHelpdesk = true
              rvm.isIssuePage = true
              rvm.isHelpdeskPage = false
            $location.path rvm.logoutFrom
            $interval.cancel(timer)
        , 3000

      vm.submit = ->
        login =
          email: vm.email
          password: md5 vm.password
          device: 'browser'
        restService.post config.resources.login, login, (data) ->
          # persistence access troken and user information in localStorage
          localStorageService.setItem config.keys.accessToken, data.accessToken if data.accessToken
          localStorageService.setItem config.keys.currentUser, data.userInfo if data.userInfo
          # save the password and email in local storage
          #localStorageService.setItem config.keys.loginEmail, vm.email
          #localStorageService.setItem config.keys.loginPassword, vm.password
          # save the login status in rootscope
          $rootScope.isLogined = true
          # redirect to the operation pages
          if rvm.logoutFrom
            $location.path rvm.logoutFrom
          else
            $location.path config.paths.helpdesk
          heightService.afterLogin '.content', 'height'
          return
        return

      vm.forgetPassword = ->
        vm.showForgetPassword = true
        return

      vm.back = ->
        vm.showForgetPassword = false
        return

      vm.resetPassword = ->
        restService.post config.resources.resetPasswordEmail, {email: vm.email}, (data) ->
          $location.path '/chat/resetpasswordresult'
          return
        return

      _init()
  ]
