define [
  'wm/app'
  'wm/config'
], (app, config) ->
  app.registerController 'wm.ctrl.store.staff', [
    'restService'
    '$stateParams'
    '$modal'
    '$scope'
    'notificationService'
    '$location'
    'utilService'
    (restService, $stateParams, $modal, $scope, notificationService, $location, utilService) ->
      vm = this
      vm.storeId = $stateParams.id
      vm.defaultLab = '-'

      STAFF = 'staff_template'

      utilService.webhooksObj().then (maps) ->
        vm.useWebhook = maps[STAFF]

      _init = ->
        vm.currentPage = $location.search().currentPage or 1
        vm.pageSize = $location.search().pageSize or 10
        vm.totalCount = 0
        vm.isShowQrcodeDropdown = false

        vm.breadcrumb = [
          'staff_management'
        ]

        vm.staffList = []
        vm.list = {
          columnDefs: [
            {
              field: 'phone'
              label: 'tel'
            }, {
              field: 'badge'
              label: 'management_badge'
            }, {
              field: 'name'
              label: 'name'
            }, {
              field: 'gender'
              label: 'gender'
              type: 'translate'
            }, {
              field: 'birthday'
              label: 'birthday'
              type: 'date'
              format: 'yyyy-MM-dd'
            }, {
              field: 'channel'
              label: 'channel'
              type: 'icon'
              cellClass: 'staff-list-icon'
            }, {
              field: 'isActivated'
              label: 'status'
              type: 'status'
            }
          ],
          data: vm.staffList
          selectable: false
          operations: [
            {
              name: 'qrcode'
            }, {
              name: 'edit'
            }, {
              name: 'delete'
            }
          ],
          qrcodeHandler: (idx, $event) ->
            vm.isShowQrcodeDropdown = not vm.isShowQrcodeDropdown
            if vm.isShowQrcodeDropdown
              vm.qrcode = []
              vm.qrcode.push {
                title: 'store_staff_qrcode',
                link: vm.list.data[idx].qrcodeUrl,
                channel: vm.list.data[idx].channel.channelName,
                name: 'salesclerk_QR_' + vm.list.data[idx].id
              }
              vm.style =
                'top': $($event.target).offset().top - 15,
                'right': 40
            return

          editHandler: (idx) ->
            staff = angular.copy vm.list.data[idx]
            staff.name = '' if staff.name is vm.defaultLab
            staff.birthday = '' if staff.birthday is vm.defaultLab
            modalInstance = $modal.open(
              templateUrl: 'staff.html'
              controller: 'wm.ctrl.store.updateStaff'
              windowClass: 'staff-dialog'
              resolve:
                modalData: ->
                  newFlag: false
                  storeId: vm.storeId
                  staff: staff
            )
            modalInstance.result.then ->
              _getList()
            return

          deleteHandler: (idx) ->
            restService.del config.resources.staff + '/' + vm.list.data[idx].id, (data) ->
              notificationService.success 'store_delete_staff_success'
              vm.currentPage -= 1 if (vm.totalCount - 1) is (vm.currentPage - 1) * vm.pageSize
              _getList()
            return

          switchHandler: (idx) ->
            param =
              isActivated: vm.list.data[idx].isActivated isnt 'ENABLE'
            restService.put config.resources.staff + '/' + vm.list.data[idx].id, param, (data) ->
              notificationService.success 'store_update_staff_status_success'
            return
        }

      _getList = ->
        param =
          'page': vm.currentPage,
          'per-page': vm.pageSize
          'where': {
            'storeId': vm.storeId
          }
        restService.get config.resources.staffs, param, (data) ->
          if data
            _formateList angular.copy data.items
            vm.pageSize = data._meta.perPage
            vm.totalCount = data._meta.totalCount
          return

      _formateList = (data) ->
        vm.staffList = []
        for item in data
          if item.channel and item.channel.channelType
            item.channel.text = item.channel.channelName
            item.channel.status = true
            if (item.channel.channelType.indexOf 'SERVICE') >= 0
              item.channel.icon = '/images/customer/wechat_service.png'
              item.channel.type = 'service_account'
            else
              item.channel.icon = '/images/customer/weibo.png'
              item.channel.type = 'weibo_account'
          item.name = vm.defaultLab if not item.name
          item.gender = vm.defaultLab if not item.gender
          item.birthday = vm.defaultLab if not item.birthday
          vm.staffList.push item
        vm.list.data = vm.staffList
        return

      vm.newStaff = ->
        modalInstance = $modal.open(
          templateUrl: 'staff.html'
          controller: 'wm.ctrl.store.updateStaff'
          windowClass: 'staff-dialog'
          resolve:
            modalData: ->
              newFlag: true
              storeId: vm.storeId
              useWebhook: vm.useWebhook
        )
        modalInstance.result.then ->
          vm.currentPage = 1
          _getList()
        return

      vm.changePageSize = (pageSize) ->
        vm.currentPage = 1
        vm.pageSize = pageSize
        _getList()

      vm.changePage = (currentPage) ->
        vm.currentPage = currentPage
        _getList()

      _init()
      _getList()
      vm
  ]
  .registerController 'wm.ctrl.store.updateStaff', [
    'restService'
    '$scope'
    '$modalInstance'
    'modalData'
    'notificationService'
    'validateService'
    (restService, $scope, $modalInstance, modalData, notificationService, validateService) ->
      vm = $scope

      _init = ->
        vm.storeId = modalData.storeId
        vm.newFlag = modalData.newFlag
        vm.useWebhook = modalData.useWebhook
        if vm.newFlag
          _getChannels()
        else
          vm.staff = angular.copy modalData.staff
          _getChannel()
        return

      _getChannels = ->
        vm.channels = []
        restService.get config.resources.channels, (data) ->
          if data
            for wechat in data.wechat
              if (wechat.accountType.indexOf 'SERVICE') >= 0
                newChannel =
                  channelType: wechat.accountType,
                  channelName: wechat.name,
                  channelId: wechat.id,
                  headImageUrl: if wechat.headImageUrl then wechat.headImageUrl else '/images/management/image_hover_default_avatar.png',
                  type: 'service_account'
                  typeBackground: {'background-color': 'rgb(80, 160, 230)'}
                vm.channels.push newChannel
            for weibo in data.weibo
              newChannel =
                channelType: weibo.channel,
                channelName: weibo.name,
                channelId: weibo.id,
                headImageUrl: if weibo.headImageUrl then weibo.headImageUrl else '/images/management/image_hover_default_avatar.png',
                type: 'weibo_account'
                typeBackground: {'background-color': 'rgb(225, 160, 40)'}
              vm.channels.push newChannel
            vm.channelId = vm.channels[0].channelId
            vm.channelIndex = 0

      _getChannel = ->
        if vm.staff and vm.staff.channel
          param =
            channelId: vm.staff.channel.channelId
          restService.get config.resources.channelInfo, param, (data) ->
            if data
              vm.staff.channel.name = data[0].name
              vm.staff.channel.headImageUrl = if data[0].headImageUrl then data[0].headImageUrl else '/images/management/image_hover_default_avatar.png'
              if data[0].accountType
                vm.staff.channel.type = 'service_account'
                vm.staff.channel.typeBackground = {'background-color': 'rgb(80, 160, 230)'}
              else
                vm.staff.channel.type = 'weibo_account'
                vm.staff.channel.typeBackground = {'background-color': 'rgb(225, 160, 40)'}

      vm.changeChannel = (idx) ->
        vm.channelId = vm.channels[idx].channelId
        vm.channelIndex = idx
        return

      vm.checkTel = ->
        validateService.checkTelNum vm.mobile

      vm.checkName = ->
        nameFormTip = ''
        if vm.staff.name
          if vm.staff.name.length < 2 or vm.staff.name.length > 10
            nameFormTip = 'store_member_name_tip'
        nameFormTip

      vm.newStaff = ->
        if not vm.checkTel()
          param =
            phone: vm.mobile,
            badge: vm.empID,
            storeId: vm.storeId,
            useWebhook: vm.useWebhook
            channel: {
              channelType: vm.channels[vm.channelIndex].channelType,
              channelName: vm.channels[vm.channelIndex].channelName,
              channelId: vm.channels[vm.channelIndex].channelId
            }
          restService.post config.resources.staffs, param, (data) ->
            if data.result is 'success'
              if not vm.useWebhook
                notificationService.success 'store_new_staff_' + data.result
              else
                notificationService.success 'store_new_staff_use_webhook_success'
            else if data.result is 'fail'
              if not vm.useWebhook
                notificationService.warning 'store_new_staff_' + data.result
              else
                notificationService.warning 'store_new_staff_use_webhook_fail'
            $modalInstance.close()
            return

      vm.updateStaff = ->
        if not vm.checkName()
          param =
            gender: vm.staff.gender
            username: vm.staff.name
            birthday: vm.staff.birthday
          restService.put config.resources.staff + '/' + vm.staff.id, param, (data) ->
            notificationService.success 'store_update_staff_success'
            $modalInstance.close()
            return

      vm.hideModal = ->
        $modalInstance.dismiss('cancel')
        return

      _init()
      return
  ]
