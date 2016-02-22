define [
  'wm/app'
  'wm/config'
], (app, config) ->
  app.registerController 'wm.ctrl.microsite.page.basic', [
    '$scope'
    '$location'
    'restService'
    'notificationService'
    ($scope, $location, restService, notificationService) ->
      vm = this
      vm.page = {}
      vm.page = $scope.$parent.webpage.page if $scope.$parent.webpage?.page?.id

      vm.submit = ->
        if vm.page.id
          params =
            title: vm.page.title
            description: vm.page.description
          restService.put config.resources.page + '/' + vm.page.id, params, (data) ->
            afterSave data, 'content_page_update_success'
            return
        else
          restService.post config.resources.pages, vm.page, (data) ->
            afterSave data, 'content_page_create_success'
            return
        return

      afterSave = (data, tip) ->
        $location.search 'id', data.id if data.id
        $scope.$emit 'showEditPage', data
        notificationService.success tip, false
        return
      vm
  ]
