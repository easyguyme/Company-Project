define [
  'wm/app'
  'wm/config'
], (app, config) ->
  app.registerController 'wm.ctrl.mall.sendMessage', [
    '$scope'
    '$modalInstance'
    'restService'
    'modalData'
    'notificationService'
    '$sce'
    '$filter'
    'validateService'
    ($scope, $modalInstance, restService, modalData, notificationService, $sce, $filter, validateService) ->
      vm = $scope
      # promocode redemption

      vm.member = modalData.member
      type = modalData.type
      language = modalData.language

      vm.successfulTip = 'product_redemption_goods_successful_tip'
      vm.successfulTitle = 'product_redemption_goods_successful_title'

      vm.hasEmail = if vm.member.email and vm.member.email isnt '-' then true else false

      if type is 'redemption'
        vm.items = modalData.redemptionItems
      else if type is 'promocode'
        vm.promocode =
          total: modalData.total
          totalScore: modalData.totalScore
          data: modalData.codes
        vm.successfulTip = 'product_redemption_codes_successful_tip'
        vm.successfulTitle = 'product_redemption_codes_successful_title'

      _formatItemsEmailData = ->
        items = []
        for item in vm.items
          quantity = Number(item.quantity)
          point = Number(item.score)
          items.push({
            productName: item.productName
            quantity: quantity
            point: point
            totalPoint: quantity * point
          })
        items

      _getParams = ->
        params =
          memberId: vm.member.id
          type: type
          language: language
        if type is 'redemption'
          params.data = _formatItemsEmailData()
          params.usedScore = modalData.usedScore
          params.address = modalData.address
          params.postcode = modalData.postcode
        else if type is 'promocode'
          params.total = vm.promocode.total
          params.totalScore = vm.promocode.totalScore
          params.data = vm.promocode.data
        params

      _getMessageTpl = ->
        params = _getParams()

        restService.post config.resources.getMessageTpl, params, (data) ->
          vm.message = data.template

      _getEmailTpl = ->
        params = _getParams()

        restService.post config.resources.getEmailTpl, params, (data) ->
          vm.email = $sce.trustAsHtml data.template
          vm.emailData = data.template

      _sendEmail = ->
        params = _getParams()

        restService.post config.resources.sendExchangeEmail, params, (data) ->

      _sendMessage = ->
        params = _getParams()

        restService.post config.resources.sendExchangeMessage, params, (data) ->
          if data isnt 'false'
            if type is 'redemption'
              notificationService.success 'product_exchange_goods_successfully', false

      _sendWechat = ->
        params = _getParams()
        restService.post config.resources.sendWechatMessage, params, (data) ->

      _init = ->
        _getMessageTpl()

        if vm.hasEmail
          _getEmailTpl()

      _init()

      vm.hideModal = ->
        $modalInstance.close()

      vm.submit = ->
        _sendMessage()
        _sendEmail() if vm.hasEmail
        _sendWechat()

        $modalInstance.close('ok')

      vm

  ]
