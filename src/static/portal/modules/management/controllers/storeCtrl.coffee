define [
  'wm/app'
  'wm/config'
], (app, config) ->
  app.registerController 'wm.ctrl.management.store', [
    'restService'
    'notificationService'
    '$rootScope'
    '$location'
    '$modal'
    '$scope'
    '$interval'
    '$q'
    'channelService'
    '$filter'
    'storeService'
    (restService, notificationService, $rootScope, $location, $modal, $scope, $interval, $q, channelService, $filter, storeService) ->
      vm = this
      rvm = $rootScope

      vm.currentPage = $location.search().currentPage or 1
      vm.pageSize = $location.search().pageSize or 10
      vm.showChannelModal = false
      vm.cacheCheckRows = []
      vm.firstTime = true
      vm.fromWechat = false
      vm.toWechat = false
      emptyStr = ''

      notificationList =
        fromWechat:
          fail: 'management_sychronize_from_wechat_fail'
          success: 'management_sychronize_from_wechat_success'
        toWechat:
          fail: 'management_sychronize_to_wechat_fail'
          success: 'management_sychronize_to_wechat_success'

      vm.breadcrumb = [
        'store_management'
      ]

      vm.tableData =
      {
        columnDefs: [
          {
            field: 'title'
            label: 'management_channel_store_name'
            type: 'link'
          }
          {
            field: 'address'
            label: 'address'
          }
          {
            field: 'telephone'
            label: 'content_component_tel'
          }
          {
            field: 'operations'
            label: 'operations'
            type: 'operationText'
          }
        ]
        data: []
        selectable: true
        noCheckbox: true
        deleteTitle: 'management_channel_delete_store'

        selectHandler: (checked, idx) ->
          if idx?
            vm.list[idx].checked = checked
            _cacheCheck(vm.list[idx])
            _whetherCheckAll()
          else
            if checked is false
              vm.cacheCheckRows = []
            else
              _rememberCheck()

        editHandler: (idx) ->
          $location.url '/management/edit/store/' + vm.tempList[idx].id

        deleteHandler: (idx) ->
          restService.del config.resources.store + '/delete/' + vm.tempList[idx].id, (data) ->
            storeService.delStore {
              id: vm.tempList[idx].id
            }
            _displayList()

        qrcodeHandler: (idx, $event) ->
          vm.isShowQrcodeDropdown = not vm.isShowQrcodeDropdown
          if vm.isShowQrcodeDropdown
            store = vm.tempList[idx]
            qrcodePaneTop = $($event.target).offset().top - 30 - $('.portal-message').height()
            vm.position =
              right: '220px'
              top: qrcodePaneTop + 'px'
            qrcodeList = []
            if store.wechat
              qrcodeList.push {title: 'wechat_qrcode', name: 'wechat_' + store.name + '_' + (store.branchName or '无分店'), link: store.wechat.qrcode}
            if store.weibo
              qrcodeList.push {title: 'weibo_qrcode', name: 'weibo_' + store.name + '_' + (store.branchName or '无分店'), link: store.weibo.qrcode}
            if store.alipay
              qrcodeList.push {title: 'alipay_qrcode', name: 'alipay_' + store.name + '_' + (store.branchName or '无分店'), link: store.alipay.qrcode}
            vm.qrcodeList = qrcodeList
            vm.storeIndex = idx
          return

        newqrcodeHandler: (idx) ->
          if rvm.channels.length is 0
            notificationService.warning 'management_channels_unbind', false
            return
          modalInstance = $modal.open(
            templateUrl: 'createQrcode.html'
            controller: 'wm.ctrl.management.createQrcode'
            windowClass: 'qrcode-dialog'
            resolve:
              modalData: ->
                edit: 0
                store: vm.tempList[idx]
          ).result.then( (data) ->
            if data
              _displayList()
              return
          )
      }

      _clearChannelCheckbox = ->
        if vm.wechatChannels
          for channel in vm.wechatChannels
            channel.checked = false

      #cache selected items
      _rememberCheck = ->
        for row in vm.list
          _cacheCheck(row)

      _cacheCheck = (row) ->
        position = $.inArray(row.id, vm.cacheCheckRows)
        if row.checked and position is -1
          vm.cacheCheckRows.push row.id
        if not row.checked and position isnt -1
          vm.cacheCheckRows.splice position, 1

      _whetherCheckAll = ->
        if vm.cacheCheckRows.length is vm.totalItems
          vm.tableData.checkAll = true

      _fetchMessage = (params) ->
        defered = $q.defer()
        restService.noLoading().get config.resources.checkSyncFinish, params, (data) ->
          defered.resolve data
        defered.promise

      _pollingMessage = (params, type) ->
        pollingStatus = {}
        data = {}
        timer = $interval( ->
          if $location.absUrl().indexOf('/management/store') is -1
            $interval.cancel timer
            return
          if pollingStatus.fail or pollingStatus.finished
            if pollingStatus.fail
              if not pollingStatus.data
                notificationService.error notificationList[type].fail, false
              else
                notificationService.error _setFailMessage(pollingStatus.data), true
            else
              if vm.channelNames? and vm.channelNames.length > 0
                data.channelNames = vm.channelNames.join ', '
              notificationService.success notificationList[type].success, false, data
            if type is 'fromWechat'
              _displayList()
            vm[type] = false
            $interval.cancel timer

          else
            _fetchMessage(params).then (status) ->
              pollingStatus = status
              if status.finished is false
                vm[type] = true

        , 2000)

      _synFromWechat = ->
        restService.post config.resources.syncFromWechat, (data) ->
          token = data.token
          if not data.finished
            vm.fromWechat = true
            _pollingMessage({token: token, type: 'sync'}, 'fromWechat')

      _synToWechat = ->
        params =
          channelIds: vm.channelIds
          storeIds: vm.cacheCheckRows
        if vm.tableData.checkAll
          params.isAllStores = true

        restService.post config.resources.syncToWechat, params, (data) ->
          token = data.token
          if not data.finished
            vm.toWechat = true
            _pollingMessage({token: token, type: 'push'}, 'toWechat')

      _setFailMessage = (data) ->
        message = '<ul style="list-style-type: disc;padding-left: 15px;">'
        for item in data
          message += '<li>' + $filter('translate')('management_offline_synchronize') +
          item.storeName + ' ' + $filter('translate')('management_offline_data') +
          $filter('translate')('service_account') + vm.wechatMap[item.channelId] +
          $filter('translate')('management_offline_fail') + ' !</li>'
        message + '</ul>'

      _getWechatChannels = ->
        #render wechat name and type
        channelService.getChannels().then((gChannels) ->
          vm.wechatChannels = []
          vm.wechatMap = {}
          channels = angular.copy gChannels
          for channel in channels
            if channel.type is 'wechat'
              vm.wechatMap[channel.id] = channel.name
              channel.typeName = 'service_account'
              channel.typeBackground = '#50a0e6'
              if channel.title.indexOf('subscription') isnt -1
                channel.typeName = 'subscription_account'
                channel.typeBackground = '#9b78cd'
              vm.wechatChannels.push channel
        )

      _displayList = ->
        params =
          'per-page': vm.pageSize
          page: vm.currentPage

        restService.get config.resources.store + '/index', params, (data) ->
          vm.tempList = data.items
          vm.totalItems = data._meta.totalCount
          vm.list = angular.copy vm.tempList

          for item in vm.list
            operation = [
              {
                name: 'edit'
              }
              {
                name: 'delete'
              }
            ]
            address = ''

            if not item.wechat and not item.weibo and not item.alipay
              operation.splice 1, 0, {name: 'newqrcode'}
            else
              operation.splice 1, 0, {name: 'qrcode', title: 'qrcodes'}

            if item.location.city and item.location.city isnt $filter('translate')('management_store_city')
              item.location.city = item.location.city
            else
              item.location.city = emptyStr

            if item.location.district and item.location.district isnt $filter('translate')('management_store_county')
              item.location.district = item.location.district
            else
              item.location.district = emptyStr

            address = ($filter('translate')(item.location.province) or emptyStr) +
                      $filter('translate')(item.location.city) +
                      $filter('translate')(item.location.district) +
                      (item.location.detail or emptyStr)
            item.address = address
            item.title =
              text: item.name
              link: '/management/view/store/' + item.id

            item.operations = operation

            # add check row when check all selected or part of select
            if vm.tableData.checkAll
              item.checked = true
            else
              item.checked = false
              if $.inArray(item.id, vm.cacheCheckRows) isnt -1
                item.checked = true

          vm.tableData.data = vm.list

      _displayList()
      _synFromWechat()

      vm.changeSize = (pageSize) ->
        vm.pageSize = pageSize
        vm.currentPage = 1
        _rememberCheck()
        _displayList()

      vm.changePage = (currentPage) ->
        _rememberCheck()
        vm.currentPage = currentPage
        _displayList()

      vm.checkAllItems = (checked) ->
        #vm.checkAll = checked
        for row in vm.list
          row.checked = checked
        vm.tableData.selectHandler(checked)
        return

      vm.create = ->
        $location.url '/management/edit/store'

      vm.editQrcode = (index) ->
        vm.isShowQrcodeDropdown = false
        modalInstance = $modal.open(
          templateUrl: 'createQrcode.html'
          controller: 'wm.ctrl.management.createQrcode'
          windowClass: 'qrcode-dialog'
          resolve:
            modalData: ->
              edit: 1
              store: vm.tempList[index]
        ).result.then( (data) ->
          if data
            _displayList()
            return
        )

      $scope.$watch 'channel.showChannelModal', (newVal) ->
        if not newVal
          _clearChannelCheckbox()

      vm.hideTagModal = ->
        vm.showChannelModal = false

      vm.sychronizeStore = ->
        vm.channelIds = []
        vm.channelNames = []
        for channel in vm.wechatChannels
          if channel.checked
            vm.channelIds.push channel.id
            vm.channelNames.push channel.name
        _rememberCheck()
        if (vm.tableData.checkAll isnt true and vm.cacheCheckRows.length is 0) or vm.channelIds.length is 0
          notificationService.error 'management_sychronize_miss_params', false
          return
        _synToWechat()
        vm.showChannelModal = false

      vm.cancelModal = ->
        vm.showChannelModal = false

      vm.showModal = ->
        vm.showChannelModal = true
        if vm.firstTime
          _getWechatChannels()
          vm.firstTime = false

      vm
  ]

  app.registerController 'wm.ctrl.management.createQrcode', [
    'modalData'
    'restService'
    'notificationService'
    '$modalInstance'
    '$scope'
    '$rootScope'
    (modalData, restService, notificationService, $modalInstance, $scope, $rootScope) ->
      vm = $scope
      rvm = $rootScope

      vm.edit = modalData.edit
      vm.storeId = modalData.store.id

      vm.steps = [
        {
          name: 'management_store_choose_account'
          active: 1,
          complete: 0
        }
        {
          name: 'management_store_add_reply'
          active: 0
          complete: 0
        }
      ]

      if vm.edit
        vm.title = 'channel_wechat_qrcode_edit'
        vm.steps[0].active = 0
        vm.steps[1].active = 1
        restService.get config.resources.qrcodeDetail, {storeId: vm.storeId}, (data) ->
          vm.message = data.content if data
      else
        vm.title = 'newqrcode'

      channels = angular.copy rvm.channels

      ## if checked, each element add an attribute named checked: true
      vm.allChannels = []

      wechat = []
      weibo = []
      alipay = []
      for channel in channels
        switch channel.type
          when 'wechat'
            if channel.title.indexOf('service') isnt -1
              wechat.push channel
          when 'weibo'
            weibo.push channel
          when 'alipay'
            alipay.push channel


      if wechat.length > 0
        vm.allChannels.push {type: 'wechat', channels: wechat}
      else
        vm.allChannels.push ''

      if weibo.length > 0
        vm.allChannels.push {type: 'weibo', channels: weibo}
      else
        vm.allChannels.push ''

      if alipay.length > 0
        vm.allChannels.push {type: 'alipay', channels: alipay}
      else
        vm.allChannels.push ''

      vm.channelIds =
        wechat: ''
        weibo: ''
        alipay: ''

      vm.$watch 'allChannels[0].checked', (newVal, oldVal) ->
        if newVal isnt oldVal
          vm.channelIds['wechat'] = '' if not newVal
          vm.channelIds['wechat'] = vm.channelIds['wechat'] or vm.allChannels[0].channels[0].id if newVal

      vm.$watch 'allChannels[1].checked', (newVal, oldVal) ->
        if newVal isnt oldVal
          vm.channelIds['weibo'] = '' if not newVal
          vm.channelIds['weibo'] = vm.channelIds['weibo'] or vm.allChannels[1].channels[0].id if newVal

      vm.$watch 'allChannels[2].checked', (newVal, oldVal) ->
        if newVal isnt oldVal
          vm.channelIds['alipay'] = '' if not newVal
          vm.channelIds['alipay'] = vm.channelIds['alipay'] or vm.allChannels[2].channels[0].id if newVal

      vm.$watch 'channelIds', (newVal, oldVal) ->
        if newVal isnt oldVal
          vm.allChannels[0].checked = true if newVal.wechat
          vm.allChannels[1].checked = true if newVal.weibo
          vm.allChannels[2].checked = true if newVal.alipay
      , true

      _checkChannel = ->
        if not vm.allChannels[0]?.checked and not vm.allChannels[1]?.checked and not vm.allChannels[2]?.checked
          notificationService.error 'management_choose_channel', false
          return false
        return true

      _checkMessage = ->
        if not vm.message
          notificationService.error 'channel_empty_reply_message', false
          return false
        return true

      _createQrcode = ->
        messageType = 'TEXT' if typeof vm.message is 'string'
        messageType = 'NEWS' if typeof vm.message is 'object'

        vm.params =
          storeId: vm.storeId
          msgType: messageType
          content: vm.message

        if not vm.edit
          vm.params.wechatId = vm.channelIds.wechat
          vm.params.weiboId = vm.channelIds.weibo
          vm.params.alipayId = vm.channelIds.alipay
          restService.post config.resources.createQrcode, vm.params, (data) ->
            $modalInstance.close('ok') if data
        else
          restService.put config.resources.updateQrcode, vm.params, (data) ->
            $modalInstance.close('ok') if data

      vm.save = ->
        if vm.steps[0].active is 1
          if _checkChannel()
            vm.steps[0].active = 0
            vm.steps[0].complete = 1
            vm.steps[1].active = 1
        else if vm.steps[1].active is 1
          if _checkMessage()
            _createQrcode()

      vm.hideModal = ->
        $modalInstance.close()

      vm
  ]
