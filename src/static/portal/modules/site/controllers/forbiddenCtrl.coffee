define [
  'wm/app'
  'wm/config'
], (app, config) ->
  app.registerController 'wm.ctrl.site.forbidden', [
    '$rootScope',
    '$location'
    'localStorageService'
    ($rootScope, $location, localStorageService) ->
      vm = this
      rvm = $rootScope

      DEFAULT_URL = '/site/redirect'
      CACHE_ROUTE_KEY = 'routeRepository'

      rvm.isFullScreen = true
      rvm.isHideTopNav = true

      states = localStorageService.getItem(CACHE_ROUTE_KEY) or []

      vm.goBack = ->
        len = states.length
        if len > 0
          path = states[len - 1]
          $location.url path
        else
          $location.url DEFAULT_URL

      vm

  ]
