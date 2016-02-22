define [
  'core/coreModule'
  'wm/config'
], (mod, config) ->
  mod.factory 'restService', [
    '$http'
    '$location'
    'validateService'
    '$rootScope'
    '$stateParams'
    'localStorageService'
    'notificationService'
    ($http, $location, validateService, $rootScope, $stateParams, localStorageService, notificationService) ->

      PREFIX = 'http://wm.com:3000'
      CACHE_ROUTE_KEY = 'routeRepository'

      defaultErrorHandler = (res) ->
        message = $.trim(res.message)
        if message and 'string' is typeof message
          #alert res.msg
          notificationService.error message, true
        else
          #alert 'Error'
          notificationService.error 'request_error'
        return
      rest = {}
      methods = [
        'get'
        'post'
        'del'
        'put'
      ]
      noLoading = false

      rest.noLoading = ->
        noLoading = true
        rest

      rest.showLoading = ->
        noLoading = false
        rest

      rest.before = (params) ->
        document.getElementById('mask-loading').style.display = 'block'  unless noLoading
        console and config.debug and console.log 'before request:', params
        ('function' is typeof (params)) and params()
        return

      rest.after = (params) ->
        noLoading = false
        document.getElementById('mask-loading').style.display = 'none'
        console and config.debug and console.log 'after request:', params
        ('function' is typeof (params)) and params()
        return

      httpConfig =
        headers:
          'Content-Type': 'application/x-www-form-urlencoded'
          'Cache-Control': 'no-cache,no-store'
          'Pragma': 'no-cache'
          'Expires': '0'
          "If-Modified-Since": "0"

        #transformRequest: (data) ->
         # $.param data

      len = methods.length
      i = 0

      while i < len
        (->
          methodName = methods[i]
          rest[methodName] = (url, data, okCallback, failCallback) ->
            throw new Error('URL must be specified')  unless url

            url = PREFIX + url if data?.mock or not config.online

            if 'function' is typeof (data)
              failCallback = okCallback
              okCallback = data
              data = {}

            path = $location.path()
            # Add keys prefix based on module
            prefix = ''
            prefix = 'chat-' if not path.search('/chat')
            prefix = 'dashboard-' if not path.search('/dashboard')
            #add access token
            accessToken = localStorageService.getItem(prefix + config.keys.accessToken)
            url += '?tmoffset=' + (new Date().getTimezoneOffset() / 60)
            url += '&accesstoken=' + accessToken if accessToken
            url += '&time=' + new Date().getTime()
            (not failCallback) and (failCallback = defaultErrorHandler)
            rest.before arguments
            httpObj = null
            if 'del' is methodName
              httpConfig.data = data
              httpObj = $http['delete'](url, httpConfig)
            else if 'get' is methodName
              httpObj = $http[methodName](url,
                params: data
              )
            else
              httpObj = $http[methodName](url, data, httpConfig)
            httpObj.success((res) ->
              okCallback and okCallback(res)
              rest.after res, status
            ).error (res, status) ->
              switch status
                when 401
                  localStorageService.removeItem prefix + config.keys.currentUser
                  localStorageService.removeItem prefix + config.keys.accessToken
                  $rootScope.isLogined = false
                  delete $rootScope.user
                  $rootScope.errorMessage = res.message if res.message
                  if path isnt config.loginPath and path isnt config.chatLoginPath and path isnt config.dashboardLoginPath
                    if /^\/chat/.test path
                      # remove redundant div when 401 logout
                      $('.issue-mask-confirm:first').remove()
                      $location.path config.chatLoginPath
                    else
                      $location.path config.loginPath
                when 403
                  if path isnt config.loginPath and path isnt config.chatLoginPath and path isnt config.dashboardLoginPath
                    # if the state has no authority, remove the state from routeRepository
                    routes = localStorageService.getItem(CACHE_ROUTE_KEY) or []
                    lastRoute = routes[routes.length - 1].replace /\?\S*/g, ''
                    currentRoute = $location.url().replace /\?\S*/g, ''
                    routes.pop() if lastRoute is currentRoute
                    localStorageService.setItem(CACHE_ROUTE_KEY, routes)
                    $location.path config.forbiddenPage
                  else
                    failCallback res
                when 440
                  # Simple check for json string
                  msg = res.message
                  msg = angular.fromJson res.message if /^\{.+\}$/.test msg
                  validateService.showErrors msg
                  failCallback res if failCallback isnt defaultErrorHandler
                when 404
                  if failCallback isnt defaultErrorHandler
                    failCallback res
                  else
                    $location.url config.missingPage

                else
                  failCallback res
              rest.after res
        )()
        i++
      rest
  ]

