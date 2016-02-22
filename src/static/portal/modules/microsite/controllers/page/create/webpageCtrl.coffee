define [
  'wm/app'
  'wm/config'
  'wm/modules/microsite/controllers/page/basicCtrl'
  'wm/modules/microsite/controllers/page/componentsCtrl'
  'wm/modules/microsite/controllers/page/completionCtrl'
], (app, config) ->
  # in order to highlight webpage, in fact is page create controller
  app.registerController 'wm.ctrl.microsite.page.create.webpage', [
    '$stateParams'
    '$location'
    '$scope'
    'restService'
    ($stateParams, $location, $scope, restService) ->
      vm = this
      constants =
        basePath: '/build/modules/microsite/partials/page/step/'
      vm.breadcrumb = [
        icon: 'webpage'
        text: 'content_pages_management'
        href: '/microsite/webpage'
      ,
        'content_page_create'
      ]

      vm.steps = [
        {
          text: 'content_set_basic_info'
          active: true
          template: 'basic.html'
        },
        {
          text: 'content_add_page_component'
          template: 'components.html'
        },
        {
          text: 'new_complete'
          template: 'completion.html'
        }
      ]

      vm.changeStep = (index = 0) ->
        angular.forEach(vm.steps, (step) ->
          step.active = false
        )
        $location.search 'step', index
        currentStep = vm.steps[index]
        currentStep.active = true
        vm.stepPage = constants.basePath + vm.steps[index].template

      params = $location.search()
      $scope.$on 'showEditPage', (e, page) ->
        vm.changeStep(1)
        if page
          $scope.$on 'cptPageLoaded', ->
            $scope.$broadcast 'pageDataLoaded', page
        return

      if params.id
        vm.changeStep params.step if params.step
        $scope.$on 'cptPageLoaded', ->
          restService.get config.resources.page + '/' + params.id, (page) ->
            $scope.$broadcast 'pageDataLoaded', page
            return
          return
      else
        # Set default page, 0 stands for the basic information page
        vm.stepPage = constants.basePath + vm.steps[0].template

      vm
  ]
