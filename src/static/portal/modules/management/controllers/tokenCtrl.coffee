define [
  'wm/app'
  'wm/config'
], (app, config) ->
  app.registerController 'wm.ctrl.management.token', [
    ->
      vm = this

      vm.breadcrumb = [
        'token_management'
      ]

      vm.tabs = [
        {
          active: true
          name: 'account_basic_info'
          template: 'token.html'
        }
        {
          active: false
          name: 'user_notification_tpl'
          template: 'notification.html'
        }
      ]
      vm.curTab = vm.tabs[0]

      vm
  ]

  app.registerController 'wm.ctrl.management.token.token', [
    'restService'
    '$modal'
    'notificationService'
    '$window'
    '$timeout'
    '$location'
    (restService, $modal, notificationService, $window, $timeout, $location) ->
      vm = this

      vm.currentPage = $location.search().currentPage or 1
      vm.pageSize = $location.search().pageSize or 10

      vm.privateTokens =
        selectable: false
        refreshTitle: 'management_private_token_refresh_info'
        columnDefs: [
          {
            field: 'name'
            label: 'management_private_token_name'
            type: 'tokenIcon'
          }, {
            field: 'privateKey'
            label: 'management_private_token_content'
            type: 'copy'
          }
        ],
        data: []
        operations: [
          {
            name: 'view'
          }
          {
            name: 'edit'
          }
          {
            name: 'delete'
          }
        ],
        deleteTitle: 'management_token_delete'
        editHandler: (idx) ->
          vm.editPrivateToken idx

        viewHandler: (idx) ->
          vm.detialPrivateToken idx

        deleteHandler: (idx) ->
          restService.del config.resources.appkey + '/' + vm.privateTokens.data[idx].id, (data) ->
            vm.privateTokens.data.splice idx, 1
            notificationService.success 'management_token_private_delete_success'

      vm.webHooks =
        selectable: false
        columnDefs: [
          {
            field: 'status'
            label: 'status'
            type: 'status'
          },
          {
            field: 'url'
            label: 'URL'
            type: 'copy'
          }
        ],
        data: []
        operations: [
          {
            name: 'edit'
          }
          {
            name: 'delete'

          }
        ],
        deleteTitle: 'management_hook_delete_tip'
        editHandler: (idx) ->
          vm.editWebHook idx

        deleteHandler: (idx) ->
          restService.del config.resources.webHook + '/' + vm.webHooks.data[idx].id, (data) ->
            vm.webHooks.data.splice idx, 1
            notificationService.success 'management_web_hook_delete_success'

        switchHandler: (idx) ->
          webHook = vm.webHooks.data[idx]
          webHook.isEnabled = not webHook.isEnabled
          restService.put config.resources.webHook + '/' + webHook.id, {isEnabled: webHook.isEnabled}, (data) ->
            notificationService.success 'management_web_hook_update_success'

      vm.tokens =
        selectable: false
        refreshTitle: 'management_token_refresh_info'
        columnDefs: [
          {
            field: 'keyCreatedAt'
            label: 'customer_card_create_time'
            type: 'date'
            format: 'yyyy-MM-dd'
          }, {
            field: 'accessKey'
            label: 'Access Key'
          }, {
            field: 'secretKey'
            label: 'Secret Key'
          }
        ],
        data: []

      vm.testAccounts =
        selectable: false
        columnDefs: [
          {
            field: 'name'
            label: 'management_wechat_test_account_name'
          }, {
            field: 'appId'
            label: 'management_wechat_test_account_app_id'
          }, {
            field: 'appSecret'
            label: 'management_wechat_test_account_app_secret'
            type: 'copy'
          }, {
            field: 'channelAccount'
            label: 'management_wechat_test_account_original_id'
          }
        ]
        data: []
        operations: [
          {
            name: 'view'
          }
          {
            name: 'delete'
          }
        ]
        deleteTitle: 'management_wechat_test_account_delete'
        viewHandler: (idx) ->
          $modal.open(
            templateUrl: 'viewTestAccount.html'
            controller: 'wm.ctrl.management.viewTestAccount'
            windowClass: 'wechat-account-dialog'
            resolve:
              modalData: -> vm.testAccounts.data[idx]
          )
        deleteHandler: (idx) ->
          restService.post config.resources.deleteTestWechat, {'accountId': vm.testAccounts.data[idx].id}, (data) ->
            vm.testAccounts.data.splice idx, 1
            notificationService.success 'management_wechat_test_account_delete_success'
            _refreshPage()

      vm.editWebHook = (idx) ->
        vm.data = null
        if idx?
          vm.data =
            webHook: vm.webHooks.data[idx]
            idx: idx

        modalInstance = $modal.open(
          templateUrl: 'addWebHook.html'
          controller: 'wm.ctrl.management.addWebhook'
          # windowClass: 'token-dialog'
          resolve:
            modalData: -> vm.data
        ).result.then( (data) ->
          if data
            if idx?
              for webHook in vm.webHooks.data
                if data.id is webHook.id
                  webHook.url = data.url
                  webHook.channels = data.channels
              notificationService.success 'management_web_hook_update_success'
            else
              # Create new web hook
              data.status = 'ENABLE'
              vm.webHooks.data.unshift data
              notificationService.success 'management_web_hook_create_success'
        )

      vm.editPrivateToken = (idx) ->
        vm.data = null
        if idx?
          vm.data =
            privateToken: vm.privateTokens.data[idx]
            idx: idx

        modalInstance = $modal.open(
          templateUrl: 'addTokens.html'
          controller: 'wm.ctrl.management.addTokens'
          windowClass: 'token-dialog'
          resolve:
            modalData: -> vm.data
        ).result.then( (data) ->
          if data
            if idx?
              for privateToken in vm.privateTokens.data
                if data.id is privateToken.id
                  vm.privateTokens.data.splice idx, 1
                  vm.privateTokens.data.splice idx, 0, data
                  notificationService.success 'management_token_private_update_success'
            else
              vm.privateTokens.data.splice 0, 0, data
              if vm.privateTokens.data.length > vm.pageSize
                vm.privateTokens.data.splice (vm.privateTokens.data.length - 1), 1
              notificationService.success 'management_token_private_create_success'
        )

      vm.detialPrivateToken = (idx) ->
        vm.data = null
        if idx?
          vm.data =
            privateToken: vm.privateTokens.data[idx]
            idx: idx
        modalInstance = $modal.open(
          templateUrl: 'detialToken.html'
          controller: 'wm.ctrl.management.detialToken'
          windowClass: 'token-dialog'
          resolve:
            modalData: -> vm.data
        ).result.then((data) ->
          return
        )

      vm.addTestAccount = (idx) ->
        $modal.open(
          templateUrl: 'addTestAccount.html'
          controller: 'wm.ctrl.management.addTestAccount'
          windowClass: 'wechat-account-dialog'
        ).result.then((data) ->
          _refreshPage()
        )

      _init = ->
        restService.get config.resources.key, (data) ->
          if data
            data.keyCreatedAt = data.keyCreatedAt * 1000
            vm.tokens.data = angular.copy [data]

        _getPrivateTokens()
        _getWebHooks()
        _getTestAccounts()

      _refreshPage = ->
        $timeout (->
          $window.location.href = '/management/token'
        ), 500

      _getPrivateTokens = ->
        params =
          'per-page': vm.pageSize
          'page': vm.currentPage

        restService.get config.resources.appkeys, params, (data) ->
          if data
            vm.totalItems = data._meta.totalCount
            vm.privateTokens.data = angular.copy data.items if data.items

      _getTestAccounts = ->
        params =
          'per-page': vm.testAccountPageSize
          'page': vm.testAccountCurrentPage

        restService.get config.resources.testWechatList, {}, (data) ->
          vm.testAccounts.data = data

      _getWebHooks = ->
        restService.get config.resources.webHooks, {}, (data) ->
          if data and data.items.length
            for item in data.items
              item.status = if item.isEnabled then 'ENABLE' else 'DISABLE'
            vm.webHooks.data = data.items

      vm.changeSize = (pageSize) ->
        vm.pageSize = pageSize
        vm.currentPage = 1
        _getPrivateTokens()

      vm.changePage = (currentPage) ->
        vm.currentPage = currentPage
        _getPrivateTokens()

      _init()
      vm
  ]

  app.registerController 'wm.ctrl.management.addWebhook', [
    '$scope'
    '$modalInstance'
    'restService'
    'notificationService'
    'channelService'
    'modalData'
    ($scope, $modalInstance, restService, notificationService, channelService, modalData) ->
      vm = $scope
      vm.webHook =
        url: ''
        isEnabled: true
        channels: []

      _getChannels = (gChannels) ->
        channels = angular.copy gChannels
        if vm.webHook.channels.length
          for channel in channels
            channel.checked = $.inArray(channel.id, vm.webHook.channels) >= 0
        vm.channels = channels

      vm.webHook = modalData.webHook if modalData
      channelService.getChannels().then(_getChannels)

      vm.submit = ->
        # Create token
        webHook = angular.copy vm.webHook
        webHook.channels = []
        for channel in vm.channels
          webHook.channels.push(channel.id) if channel.checked
        delete webHook['createdAt']
        method = 'post'
        url = config.resources.webHooks
        # Update token
        if vm.webHook.id
          method = 'put'
          url = config.resources.webHook + "/#{vm.webHook.id}"

        restService[method] url, webHook, (data) ->
          $modalInstance.close(data) if data.url

      vm.hideModal = ->
        $modalInstance.close()
      vm
  ]

  app.registerController 'wm.ctrl.management.detialToken', [
    '$scope'
    '$modalInstance'
    'modalData'
    ($scope, $modalInstance, modalData) ->
      vm = $scope

      vm.token =
        icon: '/images/management/default_image_square.png'
        name: ''
        content: ''
        createdAt: 0
        privateKey: ''

      if modalData
        vm.token = angular.copy modalData.privateToken

      vm.hideModal = ->
        $modalInstance.close()

      vm
  ]

  app.registerController 'wm.ctrl.management.viewTestAccount', [
    '$scope'
    '$modalInstance'
    'modalData'
    ($scope, $modalInstance, modalData) ->
      vm = $scope

      _init = ->
        angular.extend vm, modalData

      _init()

      vm.hideModal = ->
        $modalInstance.dismiss()
  ]

  app.registerController 'wm.ctrl.management.addTestAccount', [
    '$scope'
    '$modalInstance'
    'restService'
    ($scope, $modalInstance, restService) ->
      vm = $scope

      vm.isAdd = true

      vm.submit = ->
        params =
          name: vm.name
          appId: vm.appId
          appSecret: vm.appSecret
          originalId: vm.originalId
        restService.post config.resources.createTestWechat, params, (data) ->
          info = data.data
          vm.isAdd = false
          vm.serviceUrl = info.serviceUrl
          vm.token = info.token
          vm.encodingAESKey = info.encodingAESKey
          # Add the .access-dialog class to make the dialog wider
          angular.element('.wechat-account-dialog').addClass('access-dialog')

      vm.close = ->
        if vm.isAdd
          $modalInstance.dismiss()
        else
          $modalInstance.close()
  ]

  app.registerController 'wm.ctrl.management.addTokens', [
    '$scope'
    '$modalInstance'
    'restService'
    'notificationService'
    'modalData'
    ($scope, $modalInstance, restService, notificationService, modalData) ->
      vm = $scope
      vm.options = []
      vm.showTips = false

      vm.token =
        icon: '/images/management/default_image_square.png'
        name: ''
        content: ''
        privateKey: ''

      if modalData
        vm.showTips = true
        vm.token = angular.copy modalData.privateToken

      vm.submit = ->
        # Create token
        token = angular.copy vm.token
        delete token['createdAt']
        method = 'post'
        url = config.resources.appkeys
        # Update token
        if vm.token.id
          method = 'put'
          url = config.resources.appkey + '/' + vm.token.id

        restService[method] url, token, (data) ->
          vm.token = data
          $modalInstance.close(vm.token) if vm.token.name

      vm.hideModal = ->
        $modalInstance.close()
  ]

  app.registerController 'wm.ctrl.management.token.notification', [
    'restService'
    'notificationService'
    '$location'
    '$filter'
    (restService, notificationService, $location, $filter) ->
      vm = this

      vm.list =
        columnDefs: [
          {
            field: 'nameLink'
            label: 'management_tpl_name'
            type: 'transLink'
          }, {
            field: 'updatedAt'
            label: 'management_edit_time'
          }
        ]
        operations: [
          {
            name: 'edit'
          }
        ]
        data: []
        selectable: false

        editHandler: (idx) ->
          $location.url '/management/edit/token/' + vm.list.data[idx].id

      _getList = ->
        restService.get config.resources.notificationTpls, (data) ->
          for item in data
            item.nameLink =
              text: $filter('translate')(item.name)
              link: '/management/view/token/' + item.id
          vm.list.data = data

      _getList()

      vm

  ]
