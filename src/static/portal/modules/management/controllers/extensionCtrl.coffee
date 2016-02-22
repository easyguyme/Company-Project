define [
  'wm/app'
  'wm/config'
], (app, config) ->
  app.registerController 'wm.ctrl.management.extension', [
    'restService'
    '$rootScope'
    '$location'
    '$scope'
    (restService, $rootScope, $location, $scope) ->
      vm = this
      rvm = $rootScope

      vm.breadcrumb = [
        'extension_management'
      ]

      _setLanguage = ->
        vm.language = $scope.user.language or 'zh_cn'
        rvm.$on '$translateChangeSuccess', (event, data) ->
          vm.language = data.language
        return

      _init = ->
        vm.extensionModules = []
        vm.enabledModuleNames = rvm.enabledModules

        intros = config.introduction
        # Get extension modules
        restService.get config.resources.extensionModules, (data) ->
          if data
            vm.extensionModuleNames = data
            angular.forEach vm.extensionModuleNames, (name) ->
              modIntro = intros[name]
              if modIntro
                modIntro.active = if ($.inArray(name, vm.enabledModuleNames) > -1) then true else false
                vm.extensionModules.push modIntro

        _setLanguage()

      _init()

      vm.getDetail = (name) ->
        $location.url '/management/view/extension?name=' + name

      vm
  ]
