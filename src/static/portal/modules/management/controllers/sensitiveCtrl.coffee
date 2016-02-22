define [
  'wm/app'
  'wm/config'
], (app, config) ->
  app.registerController 'wm.ctrl.management.sensitive', [
    'restService'
    'notificationService'
    '$modal'
    '$scope'
    (restService, notificationService, $modal, $scope) ->
      vm = this

      _populateTableData = (data) ->
        result = []
        if data and data.length
          angular.forEach data, (item) ->
            item.operators = '-'
            item.operators = item.users.join(', ') if item.users.length
            item.status = if item.isActivated then 'ENABLE' else 'DISABLE'
            item.operations = [
              {
                name: 'privilege',
                title: 'management_sensitive_operations_set_privilege'
                disable: not item.isActivated
              }
            ]
            result.push item
        result

      _getList = ->
        restService.get config.resources.sensitiveOperations, {}, (data) ->
          vm.list.data = _populateTableData data.items
        return

      _init = ->
        vm.breadcrumb = [
          text: 'sensitive_management'
          help: 'management_sensitive_operations_tip'
        ]

        vm.list =
          columnDefs: [
            {
              field: 'name'
              label: 'management_sensitive_operations_name'
              type: 'translate'
            }
            {
              field: 'status'
              label: 'management_sensitive_operations_privilege_protection'
              type: 'status'
            }
            {
              field: 'operators'
              label: 'management_sensitive_operations_authorized_operators'
            }
            {
              field: 'operations'
              label: 'operations'
              type: 'operation'
              cellClass: 'goods-operations-cell goods-cell-vertical'
              headClass: 'goods-operations-cell'
            }
          ]
          data: []
          switchHandler: (idx) ->
            activatedStatus = not vm.list.data[idx].isActivated
            restService.put config.resources.sensitiveOperation + '/' + vm.list.data[idx].id, {isActivated: activatedStatus}, (data) ->
              notificationService.success 'management_sensitive_operations_status_update_success'
              _getList()
            return
          privilegeHandler: (idx) ->
            params =
              id: vm.list.data[idx].id
              name: vm.list.data[idx].name
            modalInstance = $modal.open(
              templateUrl: 'privilegeSettings.html'
              controller: 'wm.ctrl.management.sensitive.privilegeSettings'
              windowClass: 'assign-helpdesk-dialog'
              resolve:
                modalData: ->
                  params
            ).result.then( (data) ->
              _getList()
            )

        _getList()
        return

      vm.showTip = ->
        vm.isShowTip = true
        return

      vm.hideTip = ->
        vm.isShowTip = false
        return

      _init()

      vm
  ]

  app.registerController 'wm.ctrl.management.sensitive.privilegeSettings', [
    '$scope'
    'restService'
    '$modalInstance'
    'modalData'
    'notificationService'
    ($scope, restService, $modalInstance, modalData, notificationService) ->
      vm = $scope

      _getIdxInArray = (selectedOperator, operators) ->
        selectedIdx = -1
        if angular.isArray operators
          angular.forEach operators, (operator, idx) ->
            if operator.id is selectedOperator.id
              selectedIdx = idx
        selectedIdx

      _init = ->
        vm.sensitiveOperationName = modalData.name
        vm.selectedOperators = []
        sensitiveOperationId = modalData.id
        if sensitiveOperationId
          restService.get config.resources.sensitiveOperationListUser + '/' + sensitiveOperationId, {}, (data) ->
            vm.allOperators = data.unselectedUsers
            vm.selectedOperators = data.selectedUsers

      _init()

      vm.selectOperator = (operator) ->
        idx = _getIdxInArray operator, vm.allOperators
        if idx isnt -1
          vm.selectedOperators.push operator
          vm.allOperators.splice idx, 1

      vm.unSelectHelpdesk = (operator) ->
        idx = _getIdxInArray operator, vm.selectedOperators
        if idx isnt -1
          vm.selectedOperators.splice idx, 1
          vm.allOperators.push operator

      vm.submit = ->
        addPrivilegeOperatorIds = []
        angular.forEach vm.selectedOperators, (operator) ->
          addPrivilegeOperatorIds.push operator.id
        params =
          users: addPrivilegeOperatorIds
        restService.put config.resources.sensitiveOperationUpdateUser + '/' + modalData.id, params, (data) ->
          $modalInstance.close()
          notificationService.success 'management_sensitive_operations_set_update_success'

      vm.hideModal = ->
        $modalInstance.close()
  ]
