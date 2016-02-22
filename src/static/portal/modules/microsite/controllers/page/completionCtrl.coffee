define [
  'wm/app'
  'wm/config'
], (app, config) ->
  app.registerController 'wm.ctrl.microsite.page.completion', [
    '$scope'
    '$location'
    'restService'
    'notificationService'
    ($scope, $location, restService, notificationService) ->
      vm = this
      $scope.$on 'pageDataLoaded', (e, page) ->
        vm.pageId = page.id
        vm.url = page.url + '?s=1'
        return
      $scope.$emit 'cptPageLoaded'

      vm.back = ->
        $scope.$emit 'showEditPage'
        return

      vm.finish = ->
        restService.put config.resources.pagePublish + '/' + vm.pageId, (data) ->
          notificationService.success 'content_page_create_finished', false
          $location.path 'microsite/webpage'
          return
        return
      vm
  ]
