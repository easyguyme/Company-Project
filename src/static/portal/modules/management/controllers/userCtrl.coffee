define [
  'wm/app'
  'wm/config'
], (app, config) ->
  app.registerController 'wm.ctrl.management.user', ['restService'
  '$scope'
  '$modal'
  '$filter'
  '$timeout'
  'notificationService'
  'localStorageService'
  '$rootScope'
  (restService, $scope, $modal, $filter, $timeout, notificationService, localStorageService, $rootScope) ->
    vm = this
    vm.userList = []

    map = ['all', 'admin', 'operator']

    vm.filterType = 0

    vm.breadcrumb = [
      'management_users'
    ]

    userInfo = localStorageService.getItem config.keys.currentUser
    vm.showDelete = if userInfo.role isnt 'operator' then true else false

    getUserList = (filterType) ->
      restService.get config.resources.users, (data) ->
        vm.userList = data
        vm.admin = $filter('filter')(data, {role: 'admin', isActivated: true})
        vm.operator = $filter('filter')(data, {role: 'operator', isActivated: true})
        vm.all = data
        vm.totalCount = vm.all.length
        vm.adminCount = vm.admin.length
        vm.operatorCount = vm.operator.length
        return
      return

    getUserList(vm.filterType)

    vm.filter = (filterType) ->
      vm.filterType = filterType
      if filterType is 0
        vm.userList = vm.all
      if filterType is 1
        vm.userList = vm.admin
      if filterType is 2
        vm.userList = vm.operator
      return

    vm.deleteMember = (id, index, $event) ->
      $($event.target).attr 'tip-checked', true
      notificationService.confirm $event,{
        title: 'management_user_delete'
        submitCallback: _deleteMemberHandler
        params: [id, index]
      }

    _deleteMemberHandler = (id, index) ->
      data =
        id: id
      restService.del config.resources.user + '/' + id, data, (data) ->
        if vm.filterType is 0
          vm.all.splice index, 1

          result = findIndexByUser(vm.admin, id)
          flag = findIndexByUser(vm.operator, id)
          if result is false and flag isnt false
            vm.operator.splice flag, 1

          if result isnt false and flag is false
            vm.admin.splice result, 1

          vm.userList = vm.all
          vm.totalCount = vm.all.length
          vm.operatorCount = vm.operator.length
          vm.adminCount = vm.admin.length

        if vm.filterType is 1
          vm.admin.splice index, 1
          result = findIndexByUser(vm.all, id)
          vm.all.splice result, 1
          vm.userList = vm.admin
          vm.adminCount = vm.admin.length
          vm.totalCount = vm.all.length

        if vm.filterType is 2
          vm.operator.splice index, 1
          result = findIndexByUser(vm.all, id)
          vm.all.splice result, 1
          vm.userList = vm.operator
          vm.operatorCount = vm.operator.length
          vm.totalCount = vm.all.length
        return
      return

    findIndexByUser = (userList, userId) ->
      index = -1
      for user in userList
        index++
        if user.id is userId
          return index
      return false

    vm.sendEmail = (id) ->
      data =
        id: id
      restService.post config.resources.email, data, (data) ->
        values =
          email: data.email
        notificationService.success 'management_email_success', false, values
        return
      return

    vm.open = (size) ->
      modalInstance = $modal.open(
        templateUrl: 'createUser.html'
        controller: 'wm.ctrl.management.userCreate'
        size: size
        windowClass: 'user-dialog'
        resolve:
          modalData: ->
      ).result.then( (data) ->
        if data
          vm.all.splice 0, 0, data.user
          vm.userList = vm.all
          vm.totalCount = vm.all.length
          values =
            email: data.user.email
          notificationService.success 'management_email_success', false, values
          return
      )
    return
  ]
  .registerController 'wm.ctrl.management.userCreate', ['modalData'
    'restService'
    '$modalInstance'
    '$scope'
    (modalData, restService, $modalInstance, $scope) ->
      vm = $scope
      vm.data =
        role: 'operator'
        email: ''

      vm.save = ->
        if vm.data.email
          restService.post config.resources.users, vm.data, (data) ->
            $modalInstance.close data
          return

      vm.hideModal = ->
        $modalInstance.close()
      vm
    ]
