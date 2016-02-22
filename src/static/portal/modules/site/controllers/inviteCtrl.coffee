define [
  'wm/app'
  'wm/config'
  'md5'
], (app, config, md5) ->
  app.registerController 'wm.ctrl.site.invite', [
    'restService'
    '$http'
    '$rootScope'
    'localStorageService'
    '$stateParams'
    '$location'
    '$window'
    'heightService'
    (restService, $http, $rootScope, localStorageService, $stateParams, $location, $window, heightService) ->
      vm = this
      types =
        helpdesk: '1'
        user: '2'
      heightService.beforeLogin '.viewport', 'min-height'
      $rootScope.isLogined = false
      vm.type = $location.search().type
      vm.avatar = '/images/site/img_activate_account_avatar.png'

      data =
        code: $stateParams.code
        type: vm.type

      restService.get config.resources.invite, data, (data) ->
        if data.msg
          if data.msg is '1'
            vm.message = 'site_link_invalid'
            vm.invalid_title = 'site_link_invalid_title'
          if data.msg is '2'
            vm.message = 'site_link_expired'
            vm.invalid_title = 'site_link_expired_title'
          if data.msg is '3'
            vm.message = 'site_link_invalid'
            vm.invalid_title = 'site_link_invalid_title'
          if data.msg is '4'
            vm.message = 'site_user_activated'
            vm.invalid_title = 'site_user_activated_title'

          vm.display = true
        else
          vm.email = data.email
          vm.id = data.id
          vm.display = false

      vm.checkPwd = ->
        formTip = ''
        if vm.password and (vm.password.length < 6 or vm.password.length > 20)
          formTip = 'site_password_error'
        formTip

      vm.checkNewPwd = ->
        pwdErr = ''
        if vm.repassword and (vm.repassword.length < 6 or vm.repassword.length > 20)
          pwdErr = 'site_password_error'
        else if vm.repassword isnt vm.password
          pwdErr = 'site_password_inconsistent'
        pwdErr

      vm.submit = ->
        if vm.checkPwd() or vm.checkNewPwd()
          return false

        # check required field
        if not vm.name or not vm.password or not vm.repassword
          return

        data =
          id: vm.id
          name: vm.name
          password: md5 vm.password
          code: $stateParams.code
          type: vm.type

        if vm.avatar isnt '/images/site/img_activate_account_avatar.png'
          data.avatar = vm.avatar
        else
          data.avatar = config.defaultAvatar

        restService.post config.resources.updateInfo, data, (data) ->
          if data.type is types.helpdesk
            $window.location.href = config.chatLoginPath
          else
            $location.path config.loginPath
          return
        return
      return
  ]
