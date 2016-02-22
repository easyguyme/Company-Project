define [
  'wm/app'
  'wm/config'
], (app, config) ->
  app.registerController 'wm.ctrl.management.edit.token', [
    'restService'
    '$stateParams'
    'notificationService'
    '$location'
    '$translate'
    '$window'
    '$timeout'
    '$modal'
    (restService, $stateParams, notificationService, $location, $translate, $window, $timeout, $modal) ->
      vm = this
      vm.data = {}
      vm.displayNews = false
      vm.mpnews = vm.e_mail = vm.sms = 'up'
      listPath = '/management/token?active=1'
      tplId = $stateParams.id

      vm.isShowHelper =
        wechat: false
        email: true
        sms: true

      vm.breadcrumb = [
        {
          text: 'user_notification_tpl'
          href: listPath
        }
        'management_edit_tpl'
      ]

      _getNotificationTpl = ->
        restService.get config.resources.notificationTpl + '/' + tplId, (data) ->
          vm.data = data
          vm.isShowHelper.sms = data.name isnt 'staff_template'

      vm.showContent = (type) ->
        vm[type] = if vm[type] is 'up' then 'down' else 'up'

      vm.submit = ->
        params =
          data: vm.data
        restService.put config.resources.notificationTpl + '/' + tplId, params, (data) ->
          notificationService.success 'management_edit_tpl_successfully', false
          $timeout(->
            $window.location = listPath
          , 1000)

      vm.cancel = ->
        $window.location = listPath

      vm.showHelper = (type) ->
        params =
          name: vm.data.name
          type: type
        modalInstance = $modal.open
          templateUrl: 'messageHelp.html'
          controller: 'wm.ctrl.management.edit.token.messageHelp'
          windowClass: 'message-help-dialog'
          resolve:
            modalData: ->
              params
        .result.then (data) ->

      _getNotificationTpl()

      vm

  ]

  app.registerController 'wm.ctrl.management.edit.token.messageHelp', [
    '$scope'
    '$modalInstance'
    'modalData'
    ($scope, $modalInstance, modalData) ->

      vm = $scope

      _init = ->
        name = vm.name = modalData.name.replace('_template', '')
        type = vm.type = modalData.type

        vm.fix =
          email:
            prefix: '%'
            suffix: '%'
          sms:
            prefix: '%'
            suffix: '%'
          wechat:
            prefix: '{{'
            suffix: '.DATA}}'

        redemJson = '{"username":"username","gender":"gender","email":"email","mobile":"phone","birthday":"birthday"}'

        commonJson =
          redemption: redemJson
          promotioncode: redemJson

        params =
          redemption:
            wechat: '{}'
            sms: '{"product":"product","total_number":"number","amount":"amount","point_balance":"pointBalance","address":"address"}'
            email: '{"product":"product","product_goods_total_amount":"quantity","price":"price","total_price":"totalPrice","total_number":"number","amount":"amount","point_balance":"pointBalance","address":"address"}'
          promotioncode:
            wechat: '{}'
            sms: '{"product_promotion_code":"promoCode","product_promo_code_quantity":"total","product_code_redem_total_points":"totalScore","point_balance":"pointBalance"}'
            email: '{"product_promotion_code":"promoCode","product_promo_code_quantity":"total","product_code_redem_total_points":"totalScore","point_balance":"pointBalance"}'

        commonParams = []

        if commonJson[name]
          for key, value of JSON.parse commonJson[name]
            item =
              title: key
              content: value
            commonParams.push item

        severalParams = []
        if params[name] and params[name][type]
          for key, value of JSON.parse params[name][type]
            item =
              title: key
              content: value
            severalParams.push item
        vm.messageParams = angular.copy commonParams.concat severalParams

      vm.hideModal = ->
        $modalInstance.close()

      _init()

      vm

  ]
