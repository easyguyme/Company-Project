define [
  'core/coreModule'
  'chat/config'
  'md5'
], (mod, config, md5) ->
  mod.controller 'wm.ctrl.user.password', [
    '$modalInstance'
    '$scope'
    'restService'
    'localStorageService'
    'notificationService'
    ($modalInstance, $scope, restService, localStorageService, notificationService) ->
      vm = $scope

      vm.currentUser = localStorageService.getItem config.keys.currentUser
      re = new RegExp('^\\S{6,20}$')

      vm.checkNewPwd = ->
        formTip_newPwd = ''
        if not re.test(vm.userData.newPwd)
          formTip_newPwd = 'management_new_password_formaterror'
        formTip_newPwd

      vm.checkPassword = ->
        formTip_twoPwd = ''
        if vm.userData.newPwd isnt vm.userData.password
          formTip_twoPwd = 'management_two_password_error'
        formTip_twoPwd

      vm.submit = ->
        validated = true

        if vm.checkNewPwd()
          validated = false

        if vm.checkPassword()
          validated = false

        if validated
          params =
            currentPwd: md5 vm.userData.currentPwd
            newPwd: md5 vm.userData.newPwd
            newPwdC: md5 vm.userData.password
            id: vm.currentUser.id
          restService.put config.resources.updatepwd, params, (data) ->
            $modalInstance.close()
            notificationService.success 'management_user_updatepwd_success', false
        return

      vm.closeDialog = ->
        $modalInstance.dismiss()
        return

      vm
    ]
  .controller 'wm.ctrl.user.info', [
    '$modalInstance'
    '$scope'
    'localStorageService'
    'restService'
    '$rootScope'
    '$translate'
    'notificationService'
    ($modalInstance, $scope, localStorageService, restService, $rootScope, $translate, notificationService) ->
      vm = $scope

      _translateRoleName = (role) ->
        name = if role is 'admin' then 'management_user_role_admin' else 'management_user_role_operator'
        name

      _getUserInfo = ->
        currentUser = localStorageService.getItem config.keys.currentUser
        userRole = _translateRoleName(currentUser.role)

        restService.get "#{config.resources.helpdesk}/#{currentUser.id}", {}, (helpdesk) ->
          vm.userData =
            id: helpdesk.id
            name: helpdesk.name
            email: helpdesk.email
            avatar: helpdesk.avatar
            role: userRole
            badge: helpdesk.badge
            language: helpdesk.language
        return

      _syncLocalData = (user) ->
        rvmUser = $rootScope.user
        currentUser = localStorageService.getItem config.keys.currentUser
        angular.forEach user, (value, key) ->
          rvmUser[key] = value
          currentUser[key] = value
        localStorageService.setItem config.keys.currentUser, currentUser
        return

      _init = ->
        vm.isShowView = true
        vm.isShowUpdate = false
        _getUserInfo()
        return

      _init()

      vm.showViwDialog = ->
        vm.isShowView = true
        vm.isShowUpdate = false
        return

      vm.showUpdateDialog = ->
        vm.isShowView = false
        vm.isShowUpdate = true
        return

      vm.closeDialog = ->
        $modalInstance.dismiss()
        return

      vm.checkNickname = ->
        nameTip = ''
        if not vm.userData.name
          nameTip = 'required_field_tip'
        nameTip

      vm.submit = ->
        if vm.checkNickname()
          return

        user =
          name: vm.userData.name
          language: vm.userData.language
          avatar: vm.userData.avatar
        restService.put config.resources.helpdesk + '/' + vm.userData.id, user, (data) ->
          $translate.use data.language
          _syncLocalData user

          notificationService.success 'management_user_save_success', false
          vm.showViwDialog()
          return
        return

      vm
    ]
