define [
  'wm/app'
  'wm/config'
], (app, config) ->
  app.registerController 'wm.ctrl.management.view.extension', [
    'restService'
    '$stateParams'
    '$location'
    '$rootScope'
    'notificationService'
    'localStorageService'
    'moduleService'
    'channelService'
    (restService, $stateParams, $location, $rootScope, notificationService, localStorageService, moduleService, channelService) ->
      vm = this
      rvm = $rootScope

      _init = ->
        channelService.getChannels().then((channels) ->
          vm.channelId = channels[0]?.id or ''
        )
        vm.breadcrumb = [
          {
            text: 'management_extension_function',
            href: '/management/extension'
          }
          'management_extension_function_detail'
        ]

        enabledModules = angular.copy rvm.enabledModules
        moduleName = $location.search().name
        vm.moduleName = moduleName

        modIntro = config.introduction[moduleName]
        modIntro.active = if $.inArray(moduleName, enabledModules) > -1 then true else false
        if modIntro.havePartial
          vm.hasModIntroPage = true
          vm.modIntroPageUrl = "/build/modules/#{moduleName}/introduction.html"
        else
          vm.hasModIntroPage = false
        vm.detail = modIntro

      _init()

      vm.activateModule = ->
        moduleName = vm.moduleName
        data =
          name: moduleName
        restService.put config.resources.activateModule, data, (data) ->
          notificationService.success 'management_extension_activate_successfully', false
          currentUser = localStorageService.getItem('currentUser')
          currentUser.enabledModules.push moduleName
          rvm.enabledModules.push moduleName
          localStorageService.setItem 'currentUser', currentUser
          vm.detail.active = 1  # 1 means the module has activated successfully
          delete rvm.conf # delete the conf key for get conf from the backend in next step
          moduleService.getConfig().then((conf) ->
            rvm.conf = conf if not rvm.conf
          )
        return

      vm
  ]

