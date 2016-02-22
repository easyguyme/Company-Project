define [
  'wm/app'
  'wm/config'
], (app, config) ->
  app.registerController 'wm.ctrl.management.view.token', [
    'restService'
    '$stateParams'
    'notificationService'
    '$location'
    '$translate'
    '$sce'
    (restService, $stateParams, notificationService, $location, $translate, $sce) ->
      vm = this
      listPath = '/management/token?active=1'
      tplId = $stateParams.id

      vm.breadcrumb = [
        {
          text: 'user_notification_tpl'
          href: listPath
        }
        'management_view_tpl'
      ]

      _getNotificationTpl = ->
        restService.get config.resources.notificationTpl + '/' + tplId, (data) ->
          vm.data = angular.copy data
          vm.data.email.content = $sce.trustAsHtml data.email.content if data.email?.content

      _getNotificationTpl()

      vm
  ]
