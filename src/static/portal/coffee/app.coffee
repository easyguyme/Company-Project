define [
  'angular'
  'wm/config'
  'angularLazyLoad'
  'vendorBundle'
  'feedback'
  'angularUeditor'
  'wm/modules/core/coreLoader'
], (ng, config, lazyLoad) ->
  # Get the real module partial path based on module and page name
  getPath = (moduleName, pageName) ->
    config.modulePath + '/' + moduleName + '/partials/' + pageName + '.html'

  # Define module dependencies
  app = ng.module('wm', [
    'ui.router'
    'ui.bootstrap.tpls'
    'ui.bootstrap'
    'pascalprecht.translate'
    'scs.couch-potato'
    'angularFileUpload'
    'pasvaz.bindonce'
    'ng.ueditor'
    'ngSanitize'
    'wm.core'
  ])
  # Enable lazyloading for app module
  lazyLoad.configureApp app
  # Config app module

  app.config([
    '$stateProvider'
    '$urlRouterProvider'
    '$locationProvider'
    '$translateProvider'
    '$couchPotatoProvider'
    ($stateProvider, $urlRouterProvider, $locationProvider, $translateProvider, $couchPotatoProvider) ->
      $locationProvider.html5Mode true
      # Init i18n loader
      $translateProvider.useStaticFilesLoader
        prefix: '/i18n/locate-'
        suffix: '.json'

      language = document.documentElement.lang.replace(/-.+$/, (s) ->
        language = '_' + s.toLowerCase().slice(1)
      )
      language = 'zh_tr' if language is 'zh_tw' or language is 'zh_hk'
      $translateProvider.preferredLanguage language

      $urlRouterProvider.otherwise config.forbiddenPage
      # Define the state router pattern
      for state in config.states
        parts = state.split '-'
        params = null
        ctrlName = parts[parts.length - 1]
        if ctrlName.match /^{.+}$/
          params = ctrlName
          parts = parts.slice(0, parts.length - 1)
          ctrlName = parts[parts.length - 1]
        url = '/' + parts.join('/')
        url += '/' + params if params
        subCtrlPath = ''
        subCtrlPath = parts.slice(1, parts.length - 1).join('/') + '/' if parts.length > 2
        $stateProvider.state state,
          url: url
          templateUrl: getPath(parts[0], parts.slice(1).join('/'))
          controller: 'wm.ctrl.' + parts.join '.'
          controllerAs: ctrlName
          resolve:
            l: $couchPotatoProvider.resolveDependencies(['wm/modules/' + parts[0] + '/controllers/' + subCtrlPath + ctrlName + 'Ctrl'])

      return
  ]).run [
    '$couchPotato'
    '$rootScope'
    '$location'
    '$state'
    'authService'
    'messageService'
    'channelService'
    'storeService'
    'exportService'
    'moduleService'
    'localStorageService'
    ($couchPotato, $rootScope, $location, $state, authService, messageService, channelService, storeService, exportService, moduleService, localStorageService) ->
      # Define root view model
      rvm = $rootScope
      # Watch for the login status
      rvm.$watch 'isLogined', (logined) ->
        messageService.init() if logined
      # Store route to go back history
      filterRoutes = ['/site/forbidden', '/site/missing', '/site/error']
      CACHE_ROUTE_KEY = 'routeRepository'

      # enable lazyloading for controllers
      app.lazy = $couchPotato
      # Render page without header, nav and footer
      rvm.isLogined = false
      rvm.highlightManagement = false
      rvm.channels = {}
      rvm.channelSuccess = []

      # clear mask(such as datetimepicker, modal dialog) when change state
      clearMask = ->
        # clear bootstrap-datetimepicker-widget
        $datetimepicker = $('.bootstrap-datetimepicker-widget')
        if $datetimepicker.length > 0
          $datetimepicker.remove()

        # clear modal dialog
        $modalDialog = $('.modal')
        if $modalDialog.length > 0
          angular.forEach $modalDialog, (dialog) ->
            $dialog = $ dialog
            $dialog.remove() if $dialog.attr('role') is 'dialog'

        # clear modal backdrop
        $modalBackdrop = $('.modal-backdrop')
        if $modalBackdrop.length > 0
          $modalBackdrop.remove()

        # clear confirm div
        $confirm = $('.confirm')
        if $confirm.length > 0 and $('.confirm').children('.confirm-buttons')
          $confirm.remove()

      # Reload export jobs when states changed
      reloadExportJobs = ->
        exportService.refreshReloadJobs()

      # Highlight the top nav
      highlightNav = (rvm, curState) ->
        if rvm.conf
          curMod = ''
          menus = rvm.conf.menus
          # Remove the placeholders in the state string
          curState = curState.replace('-{id}', '')
          for mod, subMenus of menus
            for subMenu in subMenus
              if subMenu.state is curState
                curMod = mod
                break
            break if curMod isnt ''
          rvm.highlightManagement = curMod is 'management'
          if curMod isnt ''
            mods = rvm.conf.mods
            for mod in mods
              mod.active = mod.name is curMod

      _hideUnaccessChannel = (channels) ->
        rvm.channelSuccess.length = 0
        for channel in channels
          switch channel.type
            when 'weibo'
              if channel.accessStatus.toLowerCase() is 'success'
                rvm.channelSuccess.push channel
            when 'alipay'
              if channel.accessStatus.toLowerCase() is 'success'
                rvm.channelSuccess.push channel
            else
                rvm.channelSuccess.push channel

      # Handle channel related things:
      # 1. Refresh current channel
      # 2. Redirect to no account page if no channel provided
      refreshCurChannel = (rvm, mod) ->
        curState = rvm.currentState
        if mod isnt 'site'
          channelService.getChannels().then((channels) ->
            # Hide account that failed to access the relate of channel platform.
            _hideUnaccessChannel(channels)
            if curState.isChannel
              # Refresh current channel info when states changed
              for channel in rvm.channelSuccess
                if channel.id is curState.params.id
                  parts = channel.title.split '_'
                  rvm.currentChannel =
                    id: channel.id
                    appId: channel.appId
                    type: channel.type
                    name: channel.name
                    avatar: channel.avatar
                    link: channel.link
                    isService: parts[0] is 'service'
                    isAuthed: parts.length is 3
                  break
            else
              # Set default channel for app
              rvm.currentChannel = channels[0] if not rvm.currentChannel
          )

      refreshCurStore = (mod, params) ->
        if mod isnt 'site'
          storeService.getStores().then((stores) ->
            if stores.length and not storeService.getCurStore().id
              curStore = stores[0]
              if mod is 'store' and params.id
                for store in stores
                  if store.id is params.id
                    curStore = store
                    break
              storeService.setCurStore curStore
          )

      handleBackendConf = (state, storedConf, mod) ->
        return if mod is 'site'
        if storedConf
          if state and $.inArray(state, storedConf.forbiddenStates) >= 0
            $location.path config.forbiddenPage
        else
          moduleService.getConfig().then((conf) ->
            rvm.conf = conf if not rvm.conf
            highlightNav rvm, state
            #Redirect the page to forbidden page
            if state and $.inArray(state, conf.forbiddenStates) >= 0
              $location.path config.forbiddenPage
          )

      # Hook for state change
      rvm.$on '$stateChangeStart', (event, next, params) ->
        if authService.checkAccess next
          rvm.isFullScreen = false
          rvm.isHideTopNav = false
          rvm.isHideVerticalNav = false

          rvm.currentState =
            isChannel: next.name.indexOf('channel') is 0
            isStore: next.name.indexOf('store') is 0
            name: next.name
            params: params
          mod = next.name.split('-')[0]
          highlightNav rvm, next.name
          # refresh the value for current channel on root scope
          refreshCurChannel rvm, mod
          refreshCurStore mod, params
          # refresh the valu for nav and menu info on root scope
          # and forbid the pages without permission
          handleBackendConf next.name, rvm.conf, mod
        else
          event.preventDefault()
          return $state.go('site-forbidden')
        # Make scroll to screen left, issue #2624
        $('body').scrollLeft(0)

        # clear mask when change state
        clearMask()
        return

      # Cache route when change state successfully and exclude 403, 404, error html
      rvm.$on '$stateChangeSuccess', (toState, toParams, fromState, fromParams) ->
        path = $location.url()
        valid = true
        for route in filterRoutes
          if path.indexOf(route) > -1
            valid = false
            break
        routes = localStorageService.getItem(CACHE_ROUTE_KEY) or []
        routes.push path if valid
        routes = routes.slice(-5)
        localStorageService.setItem(CACHE_ROUTE_KEY, routes)

      rvm.$on '$stateChangeError', (e, toState, toParams, fromState, fromParams, err) ->
        # HACK: fix weird 'Mismatch anonymous define() module: function(){ return ZeroClipboard } '
        # error occurs when jumping from a page containing ueditor
        # Skip the error is occurs
        $state.go(toState, toParams) if err.message.indexOf('ZeroClipboard') isnt -1

      reloadExportJobs()
  ]
  app
