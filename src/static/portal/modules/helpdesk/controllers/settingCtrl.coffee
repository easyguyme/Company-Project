define [
  'wm/app'
  'wm/config'
], (app, config) ->
  app.registerController 'wm.ctrl.helpdesk.setting', [
    '$rootScope'
    'restService'
    'channelService'
    '$modal'
    'notificationService'
    '$location'
    ($rootScope, restService, channelService, $modal, notificationService, $location) ->
      vm = this
      rvm = $rootScope

      vm.tabs =
        [
          {
            active: true
            name: "helpdesk_setting_system_reminder"
          }
          {
            active: false
            name: "helpdesk_setting_servicetime_capacity"
          }
          {
            active: false
            name: "helpdesk_setting_service_channel"
          }
        ]

      vm.breadcrumb = [
        'helpdesk_settings'
      ]

      vm.curTab = vm.tabs[0]

      vm.btnDisabled = true
      vm.timeError = false

      vm.tableReminders = []

      vm.times =
        [
          {
            text: "1"
            value: 1
          }
          {
            text: "2"
            value: 2
          }
          {
            text: "3"
            value: 3
          }
          {
            text: "4"
            value: 4
          }
          {
            text: "5"
            value: 5
          }
          {
            text: "6"
            value: 6
          }
          {
            text: "7"
            value: 7
          }
          {
            text: "8"
            value: 8
          }
          {
            text: "9"
            value: 9
          }
          {
            text: "10"
            value: 10
          }
        ]

      vm.persons = angular.copy vm.times

      vm.hours =
        [
          {
            text: "1"
            value: "1"
          }
          {
            text: "2"
            value: "2"
          }
          {
            text: "3"
            value: "3"
          }
          {
            text: "4"
            value: "4"
          }
          {
            text: "5"
            value: "5"
          }
          {
            text: "6"
            value: "6"
          }
          {
            text: "7"
            value: "7"
          }
          {
            text: "8"
            value: "8"
          }
          {
            text: "9"
            value: "9"
          }
          {
            text: "10"
            value: "10"
          }
          {
            text: "11"
            value: "11"
          }
          {
            text: "12"
            value: "12"
          }
          {
            text: "13"
            value: "13"
          }
          {
            text: "14"
            value: "14"
          }
          {
            text: "15"
            value: "15"
          }
          {
            text: "16"
            value: "16"
          }
          {
            text: "17"
            value: "17"
          }
          {
            text: "18"
            value: "18"
          }
          {
            text: "19"
            value: "19"
          }
          {
            text: "20"
            value: "20"
          }
          {
            text: "21"
            value: "21"
          }
          {
            text: "22"
            value: "22"
          }
          {
            text: "23"
            value: "23"
          }
          {
            text: "24"
            value: "24"
          }
        ]

      vm.minutes =
        [
          {
            text: "00"
            value: "00"
          }
          {
            text: "15"
            value: "15"
          }
          {
            text: "30"
            value: "30"
          }
          {
            text: "45"
            value: "45"
          }
        ]

      vm.time = vm.times[0].value

      vm.changeTime = (value, index) ->
        vm.btnDisabled = false

      vm.person = vm.persons[0].value

      vm.changePerson = (value, index) ->
        vm.btnDisabled = false

      vm.startHour = vm.hours[0].value

      vm.changeStartHour = (value, index) ->
        vm.btnDisabled = false
        vm.validateTime("startHour", value)

      vm.endHour = vm.hours[11].value

      vm.changeEndHour = (value, index) ->
        vm.btnDisabled = false
        vm.validateTime("endHour", value)

      vm.startMinute = vm.minutes[0].value

      vm.changeStartMinute = (value, index) ->
        vm.btnDisabled = false
        vm.validateTime("startMinute", value)

      vm.endMinute = vm.minutes[0].value

      vm.changeEndMinute = (value, index) ->
        vm.btnDisabled = false
        vm.validateTime("endMinute", value)

      _init = ->
        vm.flag = 0
        vm.active = $location.search().active
        vm.flag = parseInt vm.active if !!vm.active
        vm.getSettingList()
        return

      vm.changeTab = ->
        angular.forEach vm.tabs, (tab, index) ->
          if tab.active is true
            vm.flag = index
        vm.getSettingList()
        return

      vm.addChannel = ->
        modalInstance = $modal.open(
          templateUrl: 'addChannel.html'
          controller: 'wm.ctrl.helpdesk.addChannel'
          windowClass: 'setting-channel-dialog'
          resolve:
            modalData: ->
              addedChannels: vm.channels
              settingId: vm.reminders.id
              totalChannels: rvm.channels
        ).result.then((data) ->
          if data
            for channelInfo in data
              for channel in rvm.channels
                if channel.id is channelInfo.id
                  channelInfo = angular.extend channelInfo, channel
                  channelInfo.avatar = channelInfo.avatar or config.defaultAvatar
                  vm.channels.push channelInfo
        )
        return

      deleteChannelHandler = (idx) ->
        restService.del config.resources.settingDeleteChannel, {
          settingId: vm.reminders.id
          channelId: vm.channels[idx].id
        }, ->
          vm.channels.splice idx, 1
          notificationService.success 'helpdesk_setting_delete_channel'
          return
        return

      vm.deleteChannel = (idx, $event) ->
        notificationService.confirm $event, {
          "params": [idx]
          "submitCallback": deleteChannelHandler
        }
        return

      vm.addWeChatCp = ->
        if vm.enterprise? and vm.enterprise.id?
          return
        else
          url = config.resources.settingBindWeChatCp
          window.location.href = url
        return

      deleteWeChatCpHandler = ->
        restService.del config.resources.settingRemoveWeChatCp, {
          settingId: vm.reminders.id
          wechatcpId: vm.enterprise.id
        }, ->
          vm.enterprise = {}
          notificationService.success 'helpdesk_setting_delete_website'
          return
        return

      vm.deleteWeChatCp = ->
        modalInstance = $modal.open(
              templateUrl: 'deleteWechatcp.html'
              controller: 'wm.ctrl.helpdesk.deleteWechatcp'
              windowClass: 'helpdesk-wechatcp-dialog'
            )
        return

      vm.addWebsite = ->
        modalInstance = $modal.open(
          templateUrl: 'addWebsite.html'
          controller: 'wm.ctrl.helpdesk.addWebsite'
          windowClass: 'user-dialog'
          resolve:
            modalData: ->
              settingId: vm.reminders.id
        ).result.then((data) ->
          if data
            vm.websites.push data
        )
        return

      vm.openClient = (idx) ->
        $script = $(vm.websites[idx].code)
        host = $script.attr 'host'
        account = $script.attr 'account'
        path = "#{host}/chat/client?cid=#{account}#bottom"
        width = 400
        height = 450
        left = window.innerWidth - width
        top = window.innerHeight - height
        params = "height=#{height},width=#{width},left=#{left},top=#{top},toolbar=no,menubar=no,scrollbars=no,resizable=no,location=no,status=no"
        win = window.open(path, 'newwindow', params)
        win.focus()

      deleteWebsiteHandler = (idx) ->
        restService.del config.resources.settingDeleteWebsite, {
          settingId: vm.reminders.id
          websiteId: vm.websites[idx].id
        }, ->
          vm.websites.splice idx, 1
          notificationService.success 'helpdesk_setting_delete_website'
          return
        return

      vm.deleteWebsite = (idx, $event) ->
        notificationService.confirm $event, {
          "params": [idx]
          "submitCallback": deleteWebsiteHandler
        }
        return

      vm.enableHelpDesk = (accountId) ->
        $location.path('/channel/menu/' + accountId).search 'active', 'helpdesk'
        return

      vm.getSettingList = ->
        restService.get config.resources.settingDetail, (data) ->
          vm.reminders = data
          switch vm.flag
            # choose system reminder page
            when 0 then vm.translateToTableParam(data)
            # choose service time/apacity page
            when 1 then vm.translateToSettingParam(data)
            # choose service channel page
            when 2 then vm.translateToChannelParam(data)
            # defalut page is system reminder page
            else vm.translateToTableParam(data)
          return
        return

      vm.translateToTableParam = (data) ->
        vm.tableReminders.length = 0
        if typeof(data.systemReplies) isnt undefined
          for item, i in data.systemReplies
            vm.tableReminders[i] = {}
            vm.tableReminders[i].name = "helpdesk_setting_" + item.name
            vm.tableReminders[i].replyText = item.replyText
            if item.isEnabled is true
              vm.tableReminders[i].isEnabled = "ENABLE"
            else
              vm.tableReminders[i].isEnabled = "DISABLE"
        return

      vm.translateToSettingParam = (data) ->
        vm.time = data.maxWaitTime
        vm.person = data.maxClient
        vm.startHour = data.ondutyTime.split(":")[0]
        vm.startMinute = data.ondutyTime.split(":")[1]
        vm.endHour = data.offdutyTime.split(":")[0]
        vm.endMinute = data.offdutyTime.split(":")[1]
        return

      vm.translateToChannelParam = (data) ->
        vm.websites = []
        vm.channels = []
        vm.enterprise = {}
        vm.websites = data.websites if !!data.websites
        vm.channels = data.channels if !!data.channels
        vm.enterprise = data.wechatcp if !!data.wechatcp

        channelInfo = (channels) ->
          angular.forEach vm.channels, (channel) ->
            angular.forEach channels, (channelInfo) ->
              if channelInfo.type is 'weibo'
                channelInfo.menu = 'helpdesk_setting_weibo_menu_set'
                channelInfo.accountCat = 'weibo_type'
              else if channelInfo.type is 'alipay'
                channelInfo.menu = 'helpdesk_setting_alipay_menu_set'
                channelInfo.accountCat = channelInfo.title
              else if channelInfo.type is 'wechat'
                channelInfo.menu = 'helpdesk_setting_wechat_menu_set'
                channelInfo.accountCat = channelInfo.title
              angular.extend channel, channelInfo if channelInfo.id is channel.id
            return
          return

        channelService.getChannels().then(channelInfo)
        return

      _init()

      vm.update = ->
        vm.reminders.maxWaitTime = vm.time
        vm.reminders.maxClient = vm.person
        vm.reminders.ondutyTime = vm.startHour + ':' + vm.startMinute
        vm.reminders.offdutyTime = vm.endHour + ':' + vm.endMinute
        restService.put config.resources.setting + '/' + vm.reminders.id, vm.reminders, (data) ->
          notificationService.success "helpdesk_setting_update_servicetime_capacity"
          vm.btnDisabled = true
          return
        return

      vm.validateTime = (key, value) ->
        if key is "startHour"
          vm.startHour = value
        else if key is "endHour"
          vm.endHour = value
        else if key is "startMinute"
          vm.startMinute = value
        else if key is "endMinute"
          vm.endMinute = value
        if parseInt(vm.endHour) < parseInt(vm.startHour) or (parseInt(vm.endHour) is parseInt(vm.startHour) and parseInt(vm.endMinute) <= parseInt(vm.startMinute))
          vm.timeError = true
          notificationService.warning "helpdesk_setting_time_error"
        else
          vm.timeError = false
        return

      vm.reminderList =
      {
        columnDefs: [
          {
            field: 'name'
            label: 'helpdesk_setting_reminder_status'
            type: 'translate'
          }, {
            field: 'replyText'
            label: 'helpdesk_setting_reply'
            tooltipMaxWidth: 583
            cellClass: 'default-td'
          }, {
            field: 'isEnabled'
            label: 'helpdesk_setting_status'
            type: 'status'
          }
        ],
        data: vm.tableReminders
        operations: [
          {
            name: 'edit'
          }
        ],
        editHandler: (idx) ->
          vm.data =
            reminders: vm.reminders
            idx: idx
          modalInstance = $modal.open(
            templateUrl: 'updateReminder.html'
            controller: 'wm.ctrl.helpdesk.settingUpdate'
            windowClass: 'reminder-dialog'
            resolve:
              modalData: -> vm.data
          ).result.then( (data) ->
            vm.translateToTableParam(data)
          )

        switchHandler: (idx) ->
          if vm.tableReminders[idx].isEnabled is "ENABLE"
            vm.reminders.systemReplies[idx].isEnabled = false
          else
            vm.reminders.systemReplies[idx].isEnabled = true
          restService.put config.resources.setting + '/' + vm.reminders.id, vm.reminders, (data) ->
            notificationService.success "helpdesk_setting_update_system_reminder_status"

        selectable: false
      }

      vm
  ]
  .registerController 'wm.ctrl.helpdesk.settingUpdate', [
    'modalData'
    'restService'
    '$modalInstance'
    '$scope'
    'notificationService'
    (modalData, restService, $modalInstance, $scope, notificationService) ->
      vm = $scope
      vm.reminderReplyText = modalData.reminders.systemReplies[modalData.idx].replyText

      vm.updateReminder = ->
        modalData.reminders.systemReplies[modalData.idx].replyText = vm.reminderReplyText
        restService.put config.resources.setting + '/' + modalData.reminders.id, modalData.reminders, (data) ->
          notificationService.success "helpdesk_setting_update_system_reminder"
        $modalInstance.close modalData.reminders

      vm.hideReminderUpdate = ->
        $modalInstance.close modalData.reminders
      vm
    ]
  .registerController 'wm.ctrl.helpdesk.addChannel', [
    'modalData'
    'restService'
    '$modalInstance'
    '$scope'
    'notificationService'
    (modalData, restService, $modalInstance, $scope, notificationService) ->
      vm = $scope

      _contain = (channel, channels) ->
        for item in channels
          if channel.id is item.id
            return true
        return false

      addedChannels = modalData.addedChannels
      totalChannels = angular.copy modalData.totalChannels
      vm.notAddChannels = []

      angular.forEach totalChannels, (channelInfo) ->
        if not _contain(channelInfo, addedChannels)
          if channelInfo.title isnt 'weibo'
            if channelInfo.title.indexOf("auth") is -1
              channelInfo.authType = 'nav_channel_unverified'
              channelInfo.authBackground = '#bebebe'
            else
              channelInfo.authType = 'nav_channel_verified'
              channelInfo.authBackground = '#ffa028'
            if channelInfo.title.indexOf("subscription") is -1
              channelInfo.wechatType = 'service_account'
              channelInfo.wechatBackground = '#50a0e6'
            else
              channelInfo.wechatType = 'subscription_account'
              channelInfo.wechatBackground = '#9b78cd'

          vm.notAddChannels.push channelInfo

      vm.hide = ->
        $modalInstance.close()
        return

      vm.submit = ->
        channelIds = []
        for channel in vm.notAddChannels
          if channel.checked is true
            channelIds.push channel.id

        if channelIds.length is 0
          notificationService.warning 'helpdesk_setting_not_choose_channel', false
        else
          data =
            settingId: modalData.settingId
            channelId: channelIds.join(',')
          restService.put config.resources.settingAddChannel, data, (data) ->
            vm.notAddChannels = []
            $modalInstance.close data
            return
          return
      vm
    ]
  .registerController 'wm.ctrl.helpdesk.addWebsite', [
    'modalData'
    'restService'
    '$modalInstance'
    '$scope'
    'notificationService'
    (modalData, restService, $modalInstance, $scope, notificationService) ->
      vm = $scope

      vm.save = ->
        data =
          settingId: modalData.settingId
          website:
            name: vm.websiteName
            url: vm.linkUrl
        restService.put config.resources.settingAddWebsite, data, (data) ->
          $modalInstance.close data
          return
        return

      vm.hide = ->
        $modalInstance.close()
        return
      vm
    ]
  .registerController 'wm.ctrl.helpdesk.deleteWechatcp', [
    'restService'
    '$modalInstance'
    '$scope'
    'notificationService'
    (restService, $modalInstance, $scope, notificationService) ->
      vm = $scope

      vm.hideModal = ->
        $modalInstance.close()
      vm
  ]
