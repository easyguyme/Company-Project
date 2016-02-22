define [
  'wm/app'
  'wm/config'
], (app, config) ->
  app.registerController 'wm.ctrl.member.view.card', [
    'restService'
    '$stateParams'
    '$location'
    '$sce'
    (restService, $stateParams, $location, $sce) ->
      vm = this
      vm.breadcrumb = [
        {
          text: 'customer_card',
          href: '/member/card'
        },
        'customer_card_detail'
      ]

      _init = ->
        vm.cardId = $stateParams.id
        if vm.cardId
          restService.get config.resources.card + '/' + vm.cardId, (data) ->
            vm.card = data
            vm.privilege = $sce.trustAsHtml(data.privilege)
            vm.usageGuide = $sce.trustAsHtml(data.usageGuide)

      _init()

      vm
  ]
