define [
  'wm/app'
  'wm/config'
  'md5'
], (app, config, md5) ->
  app.registerController 'wm.ctrl.site.login', [
    'restService'
    '$rootScope'
    'localStorageService'
    '$location'
    'notificationService'
    '$translate'
    'heightService'
    '$timeout'
    'moduleService'
    (restService, $rootScope, localStorageService, $location, notificationService, $translate, heightService, $timeout, moduleService) ->

      rvm = $rootScope
      rvm.isShowList = false
      vm = this

      REDIRECT_URL = 'site/redirect'

      _init = ->
        _toDashboardPage(moduleService) if rvm.isLogined

        #clear nav's height because portal message
        $('.main-content-view').attr({'style': ''})

        #load the login information in local storage
        #this is for "remember me"
        #vm.email = localStorageService.getItem config.keys.loginEmail
        #vm.password = localStorageService.getItem config.keys.loginPassword
        #remove the modal dialogs if exsisted
        $('.modal, .modal-backdrop').remove()
        # show the error message in login page
        if rvm.errorMessage
          notificationService.info rvm.errorMessage, true
          delete rvm.errorMessage

        heightService.beforeLogin '.viewport', 'min-height'

        if rvm.isLogined then $translate.use rvm.user?.language else $translate.use $('html').attr('lang')

      _toDashboardPage = (moduleService) ->
        moduleService.getConfig().then((conf) ->
            rvm.conf = conf if not rvm.conf
            dashboardPath = REDIRECT_URL
            $location.path dashboardPath
        )

      _init()

      vm.submit = ->
        if vm.email and vm.password
          login =
            email: vm.email
            password: md5 vm.password
          restService.post config.resources.login, login, (data) ->
            # persistence access token and user information in localStorage
            localStorageService.setItem config.keys.currentUser, data.userInfo if data.userInfo
            # redirect to the operation pages
            if data.userInfo and data.userInfo.role is 'customer_service'
              window.location.href = '/chat/index'
            else
              _toDashboardPage(moduleService)
            # save the password and email in local storage
            #localStorageService.setItem config.keys.loginEmail, vm.email
            #localStorageService.setItem config.keys.loginPassword, vm.password
            # save the login status in rootscope
            $timeout ( ->
              rvm.isLogined = true
            ), 200
            heightService.afterLogin '.viewport', 'min-height'
            return
          return

      vm.forgetPassword = ->
        vm.showForgetPassword = true
        return

      vm.back = ->
        vm.showForgetPassword = false
        return

      vm.resetPassword = ->
        # check required field
        if not vm.email
          return

        restService.post config.resources.resetPasswordEmail, {email: vm.email}, (data) ->
          $location.path '/site/resetpasswordresult'
      return
  ]
