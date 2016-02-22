define [
  'core/coreModule'
  'wm/config'
  'md5'
], (mod, config, md5) ->
  mod.controller 'wm.ctrl.core.user.password', [
    '$modalInstance'
    '$scope'
    'restService'
    'localStorageService'
    'notificationService'
    'userService'
    ($modalInstance, $scope, restService, localStorageService, notificationService, userService) ->
      vm = $scope

      re = new RegExp('^\\S{6,20}$')

      _getUserInfo = ->
        localCurrentUser = userService.getInfo()
        restService.get config.resources.commonUser + '/' + localCurrentUser.id, {}, (data) ->
          vm.currentUserId = data.id

      vm.checkNewPwd = ->
        formTip_newPwd = ''
        if not vm.userData.newPwd
          formTip_newPwd = 'required_field_tip'
        else if not re.test(vm.userData.newPwd)
          formTip_newPwd = 'management_new_password_formaterror'
        formTip_newPwd

      vm.checkPassword = ->
        formTip_twoPwd = ''
        if not vm.userData.password
          formTip_twoPwd = 'required_field_tip'
        else if vm.userData.newPwd isnt vm.userData.password
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
            id: vm.currentUserId
          restService.put config.resources.updatepwd, params, (data) ->
            $modalInstance.close()
            notificationService.success 'management_user_updatepwd_success', false
        return

      vm.closeDialog = ->
        $modalInstance.dismiss()
        return

      _getUserInfo()

      vm
    ]
  .controller 'wm.ctrl.core.user.info', [
    '$modalInstance'
    '$scope'
    'localStorageService'
    'restService'
    '$rootScope'
    '$translate'
    'notificationService'
    'userService'
    ($modalInstance, $scope, localStorageService, restService, $rootScope, $translate, notificationService, userService) ->
      vm = $scope

      _translateRoleName = (role) ->
        name = if role is 'admin' then 'management_user_role_admin' else 'management_user_role_operator'
        name

      _getUserInfo = ->
        localCurrentUser = userService.getInfo()
        restService.get config.resources.commonUser + '/' + localCurrentUser.id, {}, (data) ->
          userRole = _translateRoleName(data.role)
          vm.userData =
            id: data.id
            name: data.name
            email: data.email
            avatar: data.avatar
            role: userRole
            language: data.language
        return

      _syncLocalData = (user) ->
        rvmUser = $rootScope.user
        currentUser = userService.getInfo()
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

      vm.submit = ->
        user =
          name: vm.userData.name
          language: vm.userData.language
          avatar: vm.userData.avatar
        restService.put config.resources.commonUser + '/' + vm.userData.id, user, (data) ->
          $translate.use data.language
          _syncLocalData user

          notificationService.success 'management_user_save_success', false
          vm.showViwDialog()
          return
        return

      vm
    ]
