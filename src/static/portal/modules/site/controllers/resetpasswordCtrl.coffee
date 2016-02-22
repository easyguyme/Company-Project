define [
  'wm/app'
  'wm/config'
  'md5'
], (app, config, md5) ->
  app.registerController 'wm.ctrl.site.resetpassword', [
    'restService'
    'notificationService'
    '$location'
    '$timeout'
    'heightService'
    (restService, notificationService, $location, $timeout, heightService) ->
      vm = this
      heightService.beforeLogin '.viewport', 'min-height'
      validate = ->
        code = $location.search().code
        if(code)
          params =
            'code': code
            'type': 3
          restService.get config.resources.invite, params, (data) ->
            if data.msg
              switch data.msg
                when '1'
                  vm.message = 'site_link_invalid'
                  vm.invalid_title = 'site_link_invalid_title'
                  vm.invalid = true
                when '2'
                  vm.message = 'site_link_expired'
                  vm.invalid_title = 'site_link_expired_title'
                  vm.invalid = true
                when '3'
                  vm.message = 'site_user_deleted'
                  vm.invalid_title = 'site_user_deleted_title'
                  vm.invalid = true
            else
              vm.invalid = false
            return
        else
          vm.invalid = true

        return

      _isPasswordValid = (password) ->
        return false if password.length < 6 or password.length > 20
        for index, value of password
          if value.charCodeAt(0) > 299
            return false
        return true

      vm.checkPassword = ->
        pwdError = ''
        if not _isPasswordValid vm.password
          pwdError = 'site_password_length_error'
        pwdError

      vm.checkResetpassword = ->
        confirmError = ''
        if not _isPasswordValid vm.passwordConfirm
          confirmError = 'site_password_length_error'
        if vm.password isnt vm.passwordConfirm
          confirmError = 'site_password_not_match'
        confirmError

      vm.submit = ->
        if vm.checkPassword() or vm.checkResetpassword()
          return
        else
          code = $location.search().code
          params =
            password: md5 vm.password
            code: code
          restService.post config.resources.resetPassword, params, (data) ->
            notificationService.success 'site_update_password_success'
            $timeout ->
              $location.path '/site/login'
            , 1000
        return

      validate()
      return
  ]
