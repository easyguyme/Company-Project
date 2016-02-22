define [
  'wm/app'
  'wm/config'
], (app, config) ->
  app.registerController 'wm.ctrl.management.ding', [
    '$scope'
    '$modal'
    'restService'
    '$filter'
    ($scope, $modal, restService, $filter) ->
      vm = this
      vm.searchKey = ''
      vm.currentPage = 1
      vm.pageSize = 10
      vm.selectedAuth = 0
      vm.showModal = false
      vm.modalStyle = {top: 0}
      vm.selectedNone = true
      vm.userSeleted = false
      vm.auths = [
        {
          name: 'mobile_pos'
          checked: false
        }
        {
          name: 'helpdesk'
          checked: false
        }
      ]
      # Table definitions
      vm.list = {
        columnDefs: [
          {
            field: 'name'
            label: 'nickname'
          }, {
            field: 'mobile'
            label: 'telephone_number'
          }, {
            field: 'auth'
            label: 'user_auth'
          }
        ],
        data: []
        emptyMessage: 'search_no_data'
        operations: [{
            title: 'customer_select_add_tag'
            name: 'privilege'
          }
        ],
        selectable: true
        selectHandler: (checked, idx) ->
          if checked
            vm.userSeleted = true
          else
            vm.userSeleted = $filter('filter')(vm.list.data, {checked: true}).length isnt 0
        # Need to refine with modalHandler
        privilegeHandler: (idx, $event) ->
          vm.selectedUsers = [vm.list.data[idx]]
          vm.showModal = true
          vm.modalStyle = {top: "#{$event.pageY-30}px", right: '30px'}
          vm.renderUserAuth(vm.selectedUsers)
      }

      vm.getUsers = (keyword) ->
        params = {}
        if keyword
          params.where =
            "or": [
              {dingId: keyword}
              {name: keyword}
            ]
        # Search users
        restService.get config.resources.dingUsers, params, (data) ->
          vm.list.data = data.items
          angular.forEach vm.list.data, (item) ->
            auths = []
            angular.forEach item.enableActions, (enableAction) ->
              auths.push $filter('translate')(enableAction)
            item.auth = auths.join(', ') or '-'

      vm.syncUser = (id) ->
        restService.post config.resources.dingSync, {departmentId: id}, (resp) ->
          vm.getUsers() if resp.message is 'OK'

      vm.fetch = ->
        restService.get config.resources.dingDep, (departments) ->
          $modal.open(
            templateUrl: 'department.html'
            controller: 'wm.ctrl.management.selectDepartment'
            backdrop: 'static'
            resolve:
              modalData: ->
                departments
          ).result.then( (data) ->
            vm.syncUser data
          )

      vm.openAuthModal = ->
        vm.showModal = true
        vm.modalStyle =
          top: '120px'
          left: '135px'
        checked = []
        for user in vm.list.data
          checked.push(user) if user.checked
        vm.selectedUsers = checked
        vm.renderUserAuth(checked)

      vm.selectNone = ->
        for auth in vm.auths
          auth.checked = not vm.selectedNone

      vm.selectAuth = ->
        checkedAll = false
        for auth in vm.auths
          checkedAll = checkedAll or auth.checked
        vm.selectedNone = not checkedAll

      vm.renderUserAuth = (users) ->
        if users.length is 1
          user = users[0]
          vm.selectedNone = not user.enableActions.length
          for auth in vm.auths
            auth.checked = $.inArray(auth.name, user.enableActions) isnt -1
        else
          vm.selectedNone = true
          for auth in vm.auths
            auth.checked = false

      vm.ensureAuth = ->
        selectedAuths = []
        ids = []
        for auth in vm.auths
          selectedAuths.push(auth.name) if auth.checked
        for user in vm.selectedUsers
          ids.push(user.id)
        data =
          users: ids
          authorities: selectedAuths
        restService.put config.resources.dingAuth, data, ->
          for user in vm.list.data
            if $.inArray(user.id, data.users) isnt -1
              user.enableActions = selectedAuths
          vm.showModal = false
          vm.getUsers()
          return

      vm.search = ->
        vm.getUsers(vm.searchKey)

      vm.changeSize = (size) ->
        console.log(size)

      vm.changePage = (page) ->
        console.log(page)

      # Init methods
      vm.getUsers()

      vm
    ]

  app.registerController 'wm.ctrl.management.selectDepartment', [
    '$scope'
    '$modalInstance'
    'modalData'
    ($scope, $modalInstance, modalData) ->
      vm = $scope
      vm.selected = modalData[0].id if modalData.length
      vm.departments = modalData

      vm.submit = ->
        $modalInstance.close vm.selected

      vm.hideModal = ->
        $modalInstance.close()

      vm
    ]
