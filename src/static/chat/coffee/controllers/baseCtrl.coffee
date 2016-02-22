define [
  'core/coreModule'
  'chat/config'
], (app, config) ->
  app.controller 'wm.ctrl.base', [
    '$rootScope'
    '$translate'
    '$location'
    '$modal'
    'chatService'
    'notificationService'
    'sessionService'
    'issueService'
    'restService'
    '$interval'
    '$scope'
    ($rootScope, $translate, $location, $modal, chatService, notificationService, sessionService, issueService, restService, $interval, $scope) ->
      rvm = $rootScope
      vm = $scope

      _init = ->
        # get the user information from localStorage
        # the information is persist after login
        userInfo = sessionService.getCurrentUser()

        if userInfo
          rvm.isIssuePage = false
          # chatService.init()
          rvm.isLogined = true
          rvm.isHelpdeskPage = true
          # popup the notification enabled tipbox when first login
          rvm.notificationTipboxStatus = if userInfo.isFirstLogin then 'enabled' else ''
          # popup the browser not support tipbox when browser do not support desktop notification
          rvm.notificationSupport = _judgeBrowserSupport()
          # trigger dirty check for object reference
          $translate.use userInfo.language
          rvm.user =
            id: userInfo.id
            name: userInfo.name
            avatar: userInfo.avatar
            language: userInfo.language
            badge: userInfo.badge
            accountId: userInfo.accountId
            notificationType: userInfo.notificationType
            isFirstLogin: userInfo.isFirstLogin
            actions: [
                title: 'my_account'
                handler: 'updateAccount'
            ,
                title: 'change_password'
                handler: 'updatePwd'
            ,
                title: 'helpdesk_notifications',
                handler: 'setNotification'
            ,
                title: 'logout'
                handler: 'logout'
            ]
            updateAccount: (size) ->
              if sessionService.getCurrentUser()
                modalInstance = $modal.open(
                  templateUrl: 'personalData.html'
                  controller: 'wm.ctrl.user.info'
                  size: size
                  windowClass: 'user-dialog'
                  resolve:
                    modalData: ->
                ).result.then( (data) ->
                  chatService.log data
                )
              else
                _logout()
            updatePwd: (size) ->
              if sessionService.getCurrentUser()
                modalInstance = $modal.open(
                  templateUrl: 'updatePwd.html'
                  controller: 'wm.ctrl.user.password'
                  size: size
                  windowClass: 'user-dialog'
                  resolve:
                    modalData: ->
                ).result.then( (data) ->
                  chatService.log data
                )
              else
                _logout()
            setNotification: ->
              if sessionService.getCurrentUser()
                $modal.open(
                  templateUrl: 'setNotification.html'
                  controller: 'wm.ctrl.user.notification'
                  windowClass: 'user-dialog'
                  resolve:
                    modalData: ->
                      vm.user
                ).result.then( (data) ->
                  if data.type is config.chat.notificationType.desktopAndMark
                    rvm.notificationTipboxStatus = 'enabled'
                  else if data.type is config.chat.notificationType.mark
                    rvm.notificationTipboxStatus = 'disabled'
                )
            logout: ->
              _logout()
            openIssuePage: ->
              if sessionService.getCurrentUser()
                window.open '/chat/issue'
                return
              else
                _logout()
          return

        else
          $location.path config.paths.login

      _logout = ->
        $location.path config.paths.logout

      _judgeBrowserSupport = ->
        if window.Notification then true else false

      timer = {}

      rvm.checkCookies = ->
        timer = $interval ->
          if not sessionService.getCurrentUser()
            if rvm.isHelpdeskPage
              sessionService.helpdeskLogout()
            if rvm.isIssuePage
              sessionService.issueLogout()
            rvm.stopTimer()
        , 3000

      rvm.stopTimer = ->
        $interval.cancel(timer)

      _init()

  ]
