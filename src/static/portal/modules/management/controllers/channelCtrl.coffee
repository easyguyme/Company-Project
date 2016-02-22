define [
  'wm/app'
  'wm/config'
], (app, config) ->
  app.registerController 'wm.ctrl.management.channel', [
    'restService'
    '$interval'
    'notificationService'
    '$rootScope'
    '$location'
    '$modal'
    '$scope'
    '$filter'
    '$q'
    (restService, $interval, notificationService, $rootScope, $location, $modal, $scope, $filter, $q) ->
      vm = this
      rvm = $rootScope
      vm.channels =
        weibo: []
        wechat: []
        alipay: []
      vm.accountLength = 0

      vm.breadcrumb = [
        'channel_management'
      ]

      ###
      # init function to query bound channel list
      ###
      _init = ->
        restService.get config.resources.channels, (data) ->
          vm.channels = data
          for wechat in vm.channels.wechat
            wechat.authType = if wechat.accountType.indexOf('AUTH') is -1 then 'table_channel_unverified_value' else 'table_channel_verified_value'
            wechat.wechatType = if wechat.accountType.indexOf('SUBSCRIPTION') is -1 then 'service_account' else 'subscription_account'
            wechat.avatar = if wechat.headImageUrl then wechat.headImageUrl else config.defaultAvatar
            wechat.qrcode = wechat.qrcode
            wechat.hasPayment = if wechat.accountType.indexOf('SERVICE_AUTH') is -1 then false else true

          _initWeiboAccount()
          _countOverplusTime()
          _initAlipay()
          _getPaymentStatus()
          errorMessage = $location.search().errmsg
          if errorMessage
            notificationService.error errorMessage, true
            $location.search({})
          else
            vm.accountLength = vm.channels.weibo.length + vm.channels.wechat.length + vm.channels.alipay.length
            values = count: vm.accountLength.toString(), overplusCount: (4 - vm.accountLength).toString()
            notificationService.info 'management_bind_account_count', false, values
        return

      _getPaymentStatus = ->
        types = ['wechat', 'alipay']
        _getPayment().then (payment) ->
          for type in types
            if vm.channels and vm.channels[type] and vm.channels[type].length > 0
              for item in vm.channels[type]
                if item.id is payment.weconnectAccountId
                  item.paymentStatus = 'enable'
                else
                  item.paymentStatus = 'disable'
                  item.hasPayment = false if payment.weconnectAccountId # ensure this account has payment information

      _getPayment = ->
        defered = $q.defer()
        restService.get config.resources.viewWechatPayment, (data) ->
          vm.payment = data # judge the account whether open payment
          defered.resolve(data)
        defered.promise

      _calcWeiboOverplusTime = (weiboTokenExpireTime) ->
        overplusTime = 0
        overplusTime = weiboTokenExpireTime - new Date().getTime() if weiboTokenExpireTime
        if overplusTime < 0
          overplusTime = 0
        return overplusTime

      _countOverplusTime = ->
        countTime = $interval( ->
          for weibo in vm.channels.weibo
            weibo.overplusTime = _calcWeiboOverplusTime weibo.weiboTokenExpireTime
            if weibo.overplusTime is 0
              $interval.cancel(countTime)
        , 60000)

        return

      _initWeiboAccount = ->
        if vm.channels.weibo.length > 0
          for weibo in vm.channels.weibo
            weibo.isShowAccess = true
            weibo.isUnaccess = false
            weibo.avatar = if weibo.headImageUrl then weibo.headImageUrl else config.defaultAvatar

            weibo.overplusTime = _calcWeiboOverplusTime weibo.weiboTokenExpireTime

            if not weibo.accessStatus or weibo.accessStatus.toLowerCase() is 'non_connect'
              weibo.accessIcon = 'warning.png'
              weibo.accessContent = 'management_channel_weibo_unaccess'
              weibo.isUnAccess = true
            else if weibo.accessStatus.toLowerCase() is 'success'
              weibo.accessIcon = 'success.png'
              weibo.accessContent = 'management_channel_weibo_access'
              weibo.isShowAccess = false
            else if weibo.accessStatus.toLowerCase() is 'failed'
              weibo.accessIcon = 'error.png'
              weibo.accessContent = 'management_channel_weibo_access_failed'

      _initAlipay = ->
        if vm.channels.alipay.length > 0
          for alipay in vm.channels.alipay
            alipay.isShowAccess = true
            alipay.isUnAccess = false
            alipay.avatar = if alipay.headImageUrl then alipay.headImageUrl else alipay.defaultAvatar
            alipay.accessStatus = alipay.accessStatus.toLowerCase()
            switch alipay.accessStatus
              when 'success'
                alipay.accessIcon = 'success.png'
                alipay.accessContent = 'management_channel_weibo_access'
                alipay.isShowAccess = false
              when 'non_connect'
                alipay.accessIcon = 'warning.png'
                alipay.accessContent = 'management_channel_weibo_unaccess'
                alipay.isUnAccess = true


      _init()

      ###
      # determine whether was bound
      ###
      vm.hasAccount = (accounts) ->
        if accounts.length isnt 0 then false else true

      ###
      # determine whether has bind quota
      ###
      vm.hasBindQuota = ->
        if vm.accountLength < 4 then true else false

      ###
      # delete bound channel
      # @param channel string "weibo" or "wechat"
      # @param index int the index of the delete bound channel in the list
      ###
      vm.deleteChannel = (channel, index, $event) ->
        switch channel
          when 'weibo'
            notificationService.confirm $event, {
              submitCallback: _deleteChannelHandler
              params: [channel, index]
              title: 'management_channel_delete_confirm'
            }
          when 'wechat'
            modalInstance = $modal.open(
              templateUrl: 'deleteChannel.html'
              controller: 'wm.ctrl.channel.deleteChannel'
              windowClass: 'channel-wechat-dialog'
            )
          when 'alipay'
            notificationService.confirm $event, {
              submitCallback: _deleteChannelHandler
              params: [channel, index]
              title: 'management_alipay_delete_confirm'
            }

      _deleteChannelHandler = (channel, index) ->
        data =
          type: channel
        data['weiboToken'] = vm.channels[channel][index].weiboToken if channel is 'weibo'
        restService.del config.resources.channel + '/' + vm.channels[channel][index].id, data, (data) ->
          # Remove the top nav channel accounts
          rvm.channels = $filter('filter')(rvm.channels, (item) ->
            item.id isnt vm.channels[channel][index].id
          )
          #update currentChannel
          if rvm.currentChannel.id is vm.channels[channel][index].id
            rvm.currentChannel = rvm.channels[0]
          vm.accountLength += -1
          #Remove deleted channel on current page
          vm.channels[channel].splice index, 1
          return
        return


      _bindChannelHandler = (channel) ->
        if channel is 'weibo'
          url = config.resources.bindWeibo
        else if channel is 'wechat'
          url = config.resources.bindWechat

        restService.get url, (data) ->
          window.location.href = data.bindPath

      ###
      # add bound weibo or wechat or extended authorization time
      # @param channel string "weibo" or "wechat"
      # @param type string "new" or "extended"
      ###
      vm.bindChannel = (channel, type) ->
        if vm.accountLength < 4
          if channel is 'weibo'
            _bindChannelHandler channel
          else
            if not vm.hasBindQuota()
              return
            _bindChannelHandler channel
        else if channel is 'weibo' and type is 'extended'
          _bindChannelHandler channel



      ###
      # Access weibo account
      # @param index int the index of the weibo channel in the list
      ###
      vm.accessWeibo = (index) ->
        modalInstance = $modal.open(
          templateUrl: 'accessWeibo.html'
          controller: 'wm.ctrl.channel.accessWeibo'
          windowClass: 'channel-weibo-dialog'
          resolve:
            modalData: ->
              id: vm.channels.weibo[index].id
              weiboToken: vm.channels.weibo[index].weiboToken
              url: vm.channels.weibo[index].serviceUrl
              appkey: vm.channels.weibo[index].appkey
              weiboAccount: vm.channels.weibo[index].channelAccount
        ).result.then( (data) ->
          if data and data.accessStatus.toLowerCase() isnt 'non_connect'
            _init()
        )

      vm.addAlipy = ->
        if vm.accountLength < 4
          modalInstance = $modal.open(
            templateUrl: 'addAlipy.html'
            controller: 'wm.ctrl.channel.addAlipay'
            windowClass: 'channel-alipy-dialog'
          ).result.then( (data) ->
            if data
              _init()
          )

      vm.accessAlipay = (index) ->
        modalInstance = $modal.open(
          templateUrl: 'accessAlipay.html'
          controller: 'wm.ctrl.channel.accessAlipay'
          windowClass: 'channel-alipy-dialog'
          resolve:
            modalData: ->
              alipay: vm.channels.alipay[index]
        )

      vm.editChannel = (channel, index) ->
        if channel is 'alipay'
          modalInstance = $modal.open(
            templateUrl: 'editAlipay.html'
            controller: 'wm.ctrl.channel.editAlipay'
            windowClass: 'channel-alipy-dialog'
            resolve:
              modalData: ->
                alipay: vm.channels.alipay[index]
          ).result.then( (data) ->
            if data
              _init()
          )

      vm.viewChannel = (channel, index) ->
        if channel is 'alipay'
          modalInstance = $modal.open(
            templateUrl: 'viewAlipay.html'
            controller: 'wm.ctrl.channel.viewAlipay'
            windowClass: 'channel-alipy-dialog'
            resolve:
              modalData: ->
                alipay: vm.channels.alipay[index]
          )

      vm.openWechatPay = (appId, channelId) ->
        if vm.payment?.appId
          notificationService.error 'management_bind_one_payment', false
          return
        modalInstance = $modal.open(
          templateUrl: 'setWechatPay.html'
          controller: 'wm.ctrl.channel.setWechatPay'
          resolve:
            modalData: ->
              appId: appId
              channelId: channelId
          ).result.then( (data) ->
            if data
              _getPaymentStatus()
          , (data) ->
            item = angular.element('.wechat-pay-dialog').scope()
            if item.steps[1].active
              _getPaymentStatus()
          )

      vm.eidtWechatPay = (appId, channelId) ->
        modalInstance = $modal.open(
          templateUrl: 'eidtWechatPay.html'
          controller: 'wm.ctrl.channel.eidtWechatPay'
          resolve:
            modalData: ->
              appId: appId
              channelId: channelId
          ).result.then( (data) ->

          )

      vm.testWechatPay = (appId) ->
        modalInstance = $modal.open(
          templateUrl: 'testWechatPay.html'
          controller: 'wm.ctrl.channel.testWechatPay'
          resolve:
            modalData: ->
              appId: appId
          ).result.then( (data) ->
            if data
              console.log 12
          )

      vm
  ]

  app.registerController 'wm.ctrl.channel.deleteChannel', [
    '$scope'
    '$modalInstance'
    ($scope, $modalInstance) ->
      vm = $scope

      vm.hideModal = ->
        $modalInstance.close()
      vm

  ]

  app.registerController 'wm.ctrl.channel.accessWeibo', [
    'modalData'
    'restService'
    '$modalInstance'
    '$scope'
    'notificationService'
    'judgeDeviceService'
    (modalData, restService, $modalInstance, $scope, notificationService, judgeDeviceService) ->
      vm = $scope
      vm.url = modalData.url
      vm.appkey = modalData.appkey
      vm.assessToken = ''
      vm.weiboLink = 'http://weibo.com/p/100606' + modalData.weiboAccount + '/manage?iframe_url=http://e.weibo.com/v1/public/devcenter/main#place'

      vm.isMobile = judgeDeviceService.isMobile()

      vm.hideModal = ->
        $modalInstance.close()
        return

      vm.save = ->
        data =
          channelId: modalData.id
          fansServiceToken: vm.assessToken
        restService.put config.resources.accessWeibo, data, (data) ->
          if data.accessStatus.toLowerCase() is 'success'
            notificationService.success 'management_channel_weibo_access'
          $modalInstance.close data
          return
        return

      vm.selectAll = ($event) ->
        $event.target.setSelectionRange(0, $event.target.value.length)

      vm

  ]

  app.registerController 'wm.ctrl.channel.addAlipay', [
    'restService'
    '$modalInstance'
    '$scope'
    'notificationService'
    '$rootScope'
    (restService, $modalInstance, $scope, notificationService, $rootScope) ->
      vm = $scope

      vm.steps = [
        name: 'management_alipay_add_info'
        active: true
        complete: false
        class: ''
      ,
        name: 'management_alipay_add_appid'
        active: false
        complete: false
        class: ''
      ,
        name: 'management_alipay_complete'
        active: false
        complete: false
        class: ''
      ]

      vm.changeStep = (idx) ->
        switch idx
          when '1'
            if vm.params.headImageUrl is '/images/management/default_image_square.png'
              vm.showError = true
              return
            if not vm.params.name
              return
            vm.steps[0].active = false
            vm.steps[0].complete = true
            vm.steps[1].active = true
            vm.steps[1].complete = false
          when '2'
            if not vm.params.appId
              return
            if _addAlipay()
              vm.steps[1].active = false
              vm.steps[1].complete = true
              vm.steps[2].active = true
              vm.steps[2].complete = false
          when '3'
            $modalInstance.close('ok')

      vm.checkPicture = (url) ->
        if url
          vm.showError = false

      vm.params =
        appId: ''
        name: ''
        headImageUrl: '/images/management/default_image_square.png'
        description: ''

      vm.hideModal = ->
        $modalInstance.close('ok')
        return

      _addAlipay = ->
        restService.post config.resources.alipay, vm.params, (data) ->
          if data
            vm.serviceUrl = data.serviceUrl
            vm.publicKey = data.publicKey
            #Add alipay in top nav channel accounts
            $rootScope.channels.push
              appId: data.appId
              avatar: data.headImageUrl
              channel: 'ALIPAY'
              id: data.id
              link: "/channel/broadcast/#{data.id}"
              name: data.name
              title: 'alipay'
              type: 'alipay'

      vm
  ]

  app.registerController 'wm.ctrl.channel.editAlipay', [
    'restService'
    'notificationService'
    'modalData'
    '$modalInstance'
    '$scope'
    '$rootScope'
    (restService, notificationService, modalData, $modalInstance, $scope, $rootScope) ->
      vm = $scope
      vm.alipay = angular.copy modalData.alipay

      vm.save = ->
        restService.post config.resources.alipay, vm.alipay, (data) ->
          if data
            if $rootScope.channels.length > 0
              angular.forEach $rootScope.channels, (channel) ->
                if channel.id is data.id
                  channel.name = data.name
                  channel.avatar = data.headImageUrl
            $modalInstance.close data
          return
        return

      vm.hideModal = ->
        $modalInstance.close()
        return
      vm
  ]

  app.registerController 'wm.ctrl.channel.viewAlipay', [
    'modalData'
    '$modalInstance'
    '$scope'
    (modalData, $modalInstance, $scope) ->
      vm = $scope
      vm.alipay = modalData.alipay

      vm.hideModal = ->
        $modalInstance.close()

      vm
  ]

  app.registerController 'wm.ctrl.channel.accessAlipay', [
    'modalData'
    '$modalInstance'
    '$scope'
    'judgeDeviceService'
    (modalData, $modalInstance, $scope, judgeDeviceService) ->
      vm = $scope
      alipay = modalData.alipay
      vm.serviceUrl = alipay.serviceUrl
      vm.publicKey = alipay.publicKey

      vm.isMobile = judgeDeviceService.isMobile()

      vm.hideModal = ->
        $modalInstance.close()
        return

      vm.selectAll = ($event) ->
        $event.target.setSelectionRange(0, $event.target.value.length)

      vm
  ]

  app.registerController 'wm.ctrl.channel.setWechatPay', [
    'modalData'
    'restService'
    'notificationService'
    '$modalInstance'
    '$scope'
    '$rootScope'
    '$upload'
    'validateService'
    '$filter'
    (modalData, restService, notificationService, $modalInstance, $scope, $rootScope, $upload, validateService, $filter) ->
      vm = $scope

      vm.fileNames = ['', '', '', '']
      vm.btnTip = 'next'

      vm.steps = [
        name: 'management_bind_store_info'
        active: 1,
      ,
        name: 'management_configure_interface'
        active: 0
      ]

      appId = modalData.appId
      channelId = modalData.channelId
      files = {} # store multi files
      fileNamesArr = ['apiclient_cert.p12', 'apiclient_cert.pem', 'apiclient_key.pem', 'rootca.pem']
      filesAlreadyUpload = [] # uploaded file name
      fileFieldMap =
        'apiclient_cert.p12': 'p12Credential'
        'apiclient_cert.pem': 'pemCredential'
        'apiclient_key.pem': 'pemCredentialKey'
        'rootca.pem': 'caCredential'

      # unbind event delegate
      $('body').off('change')
      # bind event delegate
      $('body').on('change', '.fileUpload', (e) ->
        validateService.restore $(this).parent().parent().find('.upload'), $filter('translate')('management_certification_tip')

        name = $(e.target).attr('name')
        index = Number name.slice(-1)
        file = e.target.files[0]
        fileName = file.name
        if $.inArray(fileName, fileNamesArr) isnt -1 # ensure file name is right
          if $.inArray(fileName, filesAlreadyUpload) is -1 # ensure this file does not upload
            vm.fileNames[index] = fileName
            filesAlreadyUpload.push fileName
            files[fileFieldMap[fileName]] = file

            phase = if vm.$root then vm.$root.$$phase else ''
            if phase isnt '$digest' and phase isnt '$apply'
              vm.$digest()
          else
            notificationService.error 'management_payment_file_uploaded', false
        else
          notificationService.error 'management_payment_upload_file_error', false
      )

      vm.hideModal = ->
        data = ''
        if vm.steps[1].active
          data = 'ok'
        $modalInstance.close(data)

      vm.save = ->
        if vm.steps[0].active
          if not _checkUpload()
            return
          _createPayment()
        else
          $modalInstance.close('ok')

      _checkUpload = ->
        result = true
        $input = $('.upload')
        for item in $input
          if $(item).val() is ''
            validateService.highlight $(item), $filter('translate')('required_field_tip')
            result = false
        return result

      _createPayment = ->
        filesArr = []
        for key of files
          filesArr.push(files[key])

        $upload.upload(
          url: config.resources.openWechatPayment
          headers:
            'Content-Type': 'multipart/form-data'
          data:
            appId: appId
            sellerId: vm.sellerId
            apiKey: vm.apiKey
            weconnectAccountId: channelId
          method: "POST"
          fileFormDataName: Object.keys files # array, file names for getting field backend
          file: filesArr # array, multi files
        ).success((data) ->
          vm.authDirectory = data.authDirectory
          vm.steps[1].active = true
          vm.steps[0].active = false
          vm.btnTip = 'finish'
        ).error ->
          notificationService.error 'management_payment_open_fail', false
  ]

  app.registerController 'wm.ctrl.channel.eidtWechatPay', [
    'modalData'
    'restService'
    'notificationService'
    '$modalInstance'
    '$scope'
    '$rootScope'
    'validateService'
    '$filter'
    '$upload'
    (modalData, restService, notificationService, $modalInstance, $scope, $rootScope, validateService, $filter, $upload) ->
      vm = $scope

      appId = modalData.appId
      channelId = modalData.channelId

      fileNamesArr = ['apiclient_cert.p12', 'apiclient_cert.pem', 'apiclient_key.pem', 'rootca.pem']

      # unbind event delegate
      $('body').off('change')
      # bind event delegate
      $('body').on('change', '.fileUpload', (e) ->
        validateService.restore $(this).parent().parent().find('.upload'), $filter('translate')('management_certification_tip')

        name = $(e.target).attr('name')
        index = Number name.slice(-1)
        file = e.target.files[0]
        fileName = file.name
        if $.inArray(fileName, fileNamesArr) isnt -1 # ensure file name is right
          if not _checkUpload(fileName, index)
            notificationService.error 'management_payment_file_uploaded', false
          else
            vm.payment.files[index].isUpdated = true # distinguish whether updated
            vm.payment.files[index].file = file
        else
          notificationService.error 'management_payment_upload_file_error', false

      )

      vm.hideModal = ->
        $modalInstance.close()

      vm.save = ->
        _updatePayment()

      _getPayment = ->
        restService.get config.resources.viewWechatPayment, (data) ->
          _formatDetail(data)

      _formatDetail = (data) ->
        apiKeys = ['p12Credential', 'pemCredential', 'pemCredentialKey', 'caCredential']

        data.files = []
        for key of data
          if $.inArray(key, apiKeys) isnt -1
            data.files.push({key: key, id: data[key].id, filename: data[key].name})
        vm.payment = data

      _checkUpload = (filename, index) ->
        uploadFilename = vm.payment.files[index].filename
        if filename is uploadFilename
          return true
        return false

      _updatePayment = ->
        data =
          appId: appId
          sellerId: vm.payment.sellerId
          apiKey: vm.payment.apiKey
          weconnectAccountId: channelId

        fileNames = [] # array, filenames
        filesArr = [] # array, multi files
        for item in vm.payment.files
          if item.isUpdated
            fileNames.push item.key
            filesArr.push item.file
          else
            data["#{item.key}Id"] = item.id

        $upload.upload(
          url: config.resources.editWechatPayment
          headers:
            'Content-Type': 'multipart/form-data'
          data: data
          method: "POST"
          fileFormDataName: fileNames # array, file names for getting field backend
          file: filesArr # array, multi files
        ).success((data) ->
          notificationService.success 'management_edit_payment_successfully', false
          $modalInstance.dismiss()
        ).error ->
          notificationService.error 'management_edit_payment_failed', false

      _getPayment()

  ]

  app.registerController 'wm.ctrl.channel.testWechatPay', [
    'modalData'
    'restService'
    'notificationService'
    '$modalInstance'
    '$scope'
    '$rootScope'
    (modalData, restService, notificationService, $modalInstance, $scope, $rootScope) ->
      vm = $scope

      vm.curTab = 0
      vm.tip = 'management_test_money_tip'
      vm.oldPrice = 0 # orgin price
      vm.payment = {}

      vm.tabs = [
        name: 'management_test_pay'
        active: true
        value: 'pay'
      ,
        name: 'management_test_refund'
        active: false
        value: 'refund'
      ]

      vm.checkMoney = (price) ->
        vm.price = price
        tip = ''
        reg = /(^[1-9]\d*(\.\d{1,2})?$)|(^0\.(([1-9]\d?)|(0[1-9]))$)/
        if not reg.test(price)
          tip = 'management_price_error'
        else if(vm.price isnt vm.oldPrice or not vm.payment.codeUrl)
          _checkPayment()
          vm.oldPrice = vm.price
        tip

      vm.changeTab = (index) ->
        for tab, i in vm.tabs
          tab.active = index is i
        vm.curTab = index

      vm.hideModal = ->
        $modalInstance.close()

      vm.save = ->
        if vm.curTab is 1
          _refundPayment()

      _checkPayment = ->
        params =
          price: vm.price
        restService.post config.resources.checkPayment, params, (data) ->
          $qrcodes = $('#payment-qrcode').find('canvas')
          if $qrcodes.length > 0
            $qrcodes.remove()
          vm.payment =
            codeUrl: data.extension.codeUrl
            outTradeNo: data.outTradeNo
            refundFee: data.totalFee
          phase = if vm.$root then vm.$root.$$phase else ''
          if phase isnt '$digest' and phase isnt '$apply'
            vm.$digest()
        , (error) ->
          notificationService.error 'management_payment_check_fail', false

      _refundPayment = ->
        restService.post config.resources.refundPayment, vm.payment, (data) ->
          if not data.failureCode
            notificationService.success 'management_payment_refund_successfully', false
            $modalInstance.close()
          else
            notificationService.error 'management_payment_refund_fail', false
        , (error) ->
          notificationService.error 'management_payment_refund_fail', false

  ]
