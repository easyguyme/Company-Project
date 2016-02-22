define [
  'wm/app'
  'wm/config'
], (app, config) ->
  app.registerController 'wm.ctrl.helpdesk.helpdesk', [
    'restService'
    '$modal'
    'notificationService'
    '$interval'
    '$location'
    '$scope'
    'messageService'
    (restService, $modal, notificationService, $interval, $location, $scope, messageService) ->
      vm = this

      vm.pageSize = $location.search().pageSize or 10
      vm.currentPage = $location.search().currentPage or 1
      vm.order = 'desc'

      vm.breadcrumb = [
        'helpdesk_account'
      ]

      vm.list =
        columnDefs: [
          {
            field: 'account'
            label: 'helpdesk_account_account'
            cellClass: 'text-el'
          }
          {
            field: 'number'
            label: 'helpdesk_account_number'
            cellClass: 'text-el'
          }
          {
            field: 'busy'
            label: 'helpdesk_account_busy'
            sortable: true
            desc: true
            sortHandler: ->
              vm.order = if vm.order is 'desc' then 'asc' else 'desc'
              _getAccountList()
            type: 'iconText'
          }
          {
            field: 'online'
            label: 'helpdesk_account_online_status'
            type: 'translate'
          }
          {
            field: 'status'
            label: 'channel_wechat_status'
            type: 'status'
          }
          {
            field: 'operations'
            label: 'operations'
            type: 'operation'
          }
        ]
        data: []
        selectable: false
        deleteTitle: "helpdesk_autoreply_delete_confirm"

        editHandler: (idx) ->
          modalInstance = $modal.open(
            templateUrl: 'updateAccount.html'
            controller: 'wm.ctrl.helpdesk.accountUpdate'
            windowClass: 'user-dialog'
            resolve:
              modalData: ->
                vm.accountList[idx]
          ).result.then( (data) ->
            if data
              _getAccountList()
              return
        )

        deleteHandler: (idx) ->
          if not vm.accountList[idx].isOnline
            id = vm.accountList[idx].id
            restService.del config.resources.helpdesk + '/' + id, (data) ->
              _getAccountList()
          else
            notificationService.warning 'helpdesk_account_online_delete_warning', false

        sendHandler: (idx) ->
          data =
            id: vm.accountList[idx].id
          restService.post config.resources.helpdeskEmail, data, (data) ->
            values =
              email: data.email
            notificationService.success 'management_email_success', false, values
            return
          return

        switchHandler: (idx) ->
          if not vm.accountList[idx].isOnline
            isEnabled = if vm.accountList[idx].isEnabled then false else true
            vm.accountList[idx].isEnabled = isEnabled
            data =
              isEnabled: isEnabled
              id: vm.accountList[idx].id
            restService.put config.resources.heldeskChangeStatus, data, (data) ->
              notificationService.success 'helpdesk_status_success', false
              return
          else
            notificationService.warning 'helpdesk_account_online_disable_warning', false
            _getAccountList()

      _getAccountList = ->
        listParams =
          'per-page': vm.pageSize
          page: vm.currentPage
          orderBy:
            clientCount: vm.order

        restService.noLoading().get config.resources.helpdesks, listParams, (data) ->
          vm.totalItems = data._meta.totalCount
          vm.accountList = data.items
          _transferToTable(vm.accountList)
          return
        return

      _transferToTable = (data) ->
        items = []
        for item in data
          listItem = {
            busy: {}
          }
          operations = [
            {
              name: 'delete'
            }
          ]
          operation = {}
          listItem.account = item.email
          listItem.number = item.badge
          listItem.online = if item.isOnline then 'helpdesk_account_online' else 'helpdesk_account_offline'
          listItem.status = if item.isEnabled then 'ENABLE' else 'DISABLE'

          operation.name = if item.isActivated then 'edit' else 'send'
          operation.title = if not item.isActivated then 'helpdesk_helpdesk_send'
          operations.splice 0, 0, operation
          listItem.operations = operations

          conversationCount = item.conversationCount
          maxClient = item.maxClient
          percent = conversationCount / maxClient

          if 0 <= percent <= 0.2
            icon = 'notbusy'
          if 0.2 < percent <= 0.8
            icon = 'alittlebusy'
          if percent > 0.8
            icon = 'busy'
          img = "/images/helpdesk/#{icon}.png"

          listItem.busy =
            icon: img
            text: conversationCount + '/' + maxClient

          items.push listItem
        vm.list.data = items

        return

      _getAccountList()

      messageService.bind config.push.event.onlineStatusChange, ->
        if $location.absUrl().indexOf('/helpdesk/helpdesk') isnt -1
          _getAccountList()

      vm.changePage = (currentPage) ->
        vm.currentPage = currentPage
        _getAccountList()

      vm.changeSize = (pageSize) ->
        vm.pageSize  = pageSize
        _getAccountList()

      vm.create = ->
        modalInstance = $modal.open(
          templateUrl: 'createAccount.html'
          controller: 'wm.ctrl.helpdesk.accountCreate'
          windowClass: 'user-dialog'
          resolve:
            modalData: ->
        ).result.then( (data) ->
          if data
            values =
              email: data.email
            notificationService.success 'management_email_success', false, values
            _getAccountList()
            return
        )

      return
  ]

  .registerController 'wm.ctrl.helpdesk.accountCreate', [
    'modalData'
    'restService'
    '$modalInstance'
    '$scope'
    '$timeout'
    (modalData, restService, $modalInstance, $scope, $timeout) ->
      vm = $scope

      vm.badgeTip = 'helpdesk_account_number_tip'

      vm.checkBadge = ->
        badgeTip = ''
        if not vm.badge
          badgeTip = 'required_field_tip'
        badgeTip

      vm.save = ->
        if vm.checkBadge()
          return

        data =
          email: vm.email
          badge: vm.badge

        restService.post config.resources.helpdesks, data, (data) ->
          $modalInstance.close data

      vm.hideModal = ->
        $modalInstance.close()
      vm
    ]

  .registerController 'wm.ctrl.helpdesk.accountUpdate', [
    'modalData'
    'restService'
    'notificationService'
    '$modalInstance'
    '$scope'
    '$timeout'
    (modalData, restService, notificationService, $modalInstance, $scope, $timeout) ->
      vm = $scope
      vm.email = modalData.email
      badge = vm.badge = modalData.badge
      vm.badgeTip = 'helpdesk_account_number_tip'

      vm.checkBadgeSame = ->
        badgeTip = ''
        if not vm.badge
          badgeTip = 'required_field_tip'
        else if vm.badge is badge
          badgeTip = 'helpdesk_account_sameas_old'
        badgeTip

      vm.save = ->
        if vm.checkBadgeSame()
          return

        if vm.badge isnt badge
          data =
            badge: vm.badge
          restService.put config.resources.helpdesk + '/' + modalData.id , data, (data) ->
            notificationService.success 'helpdesk_modify_success'
            $modalInstance.close data
            return
          return

      vm.hideModal = ->
        $modalInstance.close()
      vm
    ]
