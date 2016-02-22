define [
  'wm/app'
  'wm/config'
  'wm/modules/microsite/controllers/page/componentsCtrl'
], (app, config) ->
  # in order to highlight webpage, in fact is page edit controller
  app.registerController 'wm.ctrl.microsite.page.edit.webpage', [
    '$stateParams'
    '$scope'
    'restService'
    '$modal'
    ($stateParams, $scope, restService, $modal) ->
      vm = this
      vm.page =
        id: $stateParams.id
      vm.breadcrumb = [
        icon: 'webpage'
        text: 'content_pages_management'
        href: '/microsite/webpage'
      ,
        'content_page_edit'
      ]

      # Get the title and description for page
      $scope.$on 'cptPageLoaded', ->
        restService.get config.resources.page + '/' + vm.page.id, (page) ->
          vm.page = page
          $scope.$broadcast 'pageDataLoaded', page

      vm.edit = ->
        modalInstance = $modal.open(
          templateUrl: 'editWebpage.html'
          controller: 'wm.ctrl.microsite.editWebpage'
          windowClass: 'user-dialog'
          resolve:
              modalData: ->
                vm.page
          ).result.then( (data) ->
            if data
              vm.page = data
              return
        )
      return
      vm
  ]
  .registerController 'wm.ctrl.microsite.editWebpage', [
    'restService'
    '$modalInstance'
    '$scope'
    '$timeout'
    'notificationService'
    'modalData'
    (restService, $modalInstance, $scope, $timeout, notificationService, modalData) ->
      vm = $scope
      vm.title = modalData.title
      vm.description = modalData.description
      _checkUnique = (page) ->
        $timeout (->
            $('.form-control-error').focus()
        ), 2000
        return

      vm.save = ->
        page =
          title: vm.title
          description: vm.description
          type: modalData.type

        restService.put config.resources.page + '/' + modalData.id, page, (data) ->
          notificationService.success 'content_page_update_success', false
          $modalInstance.close data
          return
        return

      vm.hideModal = ->
        $modalInstance.close()
      vm
  ]
