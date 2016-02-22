define [
  'core/coreModule'
  'wm/config'
], (mod, config) ->
  mod.factory 'userService', [
    '$rootScope'
    '$location'
    '$modal'
    'localStorageService'
    ($rootScope, $location, $modal, localStorageService) ->
      user = {}
      rvm = $rootScope

      user.getInfo = ->
        if rvm.user
          return rvm.user
        else
          currentUser = localStorageService.getItem config.keys.currentUser
          if currentUser
            return currentUser
          else
            $location.path config.loginPath
        return

      user.initSettingHandler = ->
        _self = this
        userSetting =
          actions: [
            {
              title: 'my_account'
              handler: 'updateAccount'
              link: '#'
            }
            {
              title: 'change_password'
              handler: 'updatePwd'
              link: '#'
            }
            {
              title: 'helpdesk_feedback'
              handler: 'openFeedback'
              link: '#'
            }
            {
              title: 'logout'
              handler: 'logout'
              link: '/site/logout'
            }
          ]

          updateAccount: (size) ->
            currentUser = _self.getInfo()
            originLanguage = currentUser.language if currentUser
            modalInstance = $modal.open(
              templateUrl: 'personalData.html'
              controller: 'wm.ctrl.core.user.info'
              size: size
              windowClass: 'user-dialog'
              resolve:
                modalData: ->
            ).result.then( (data) ->
              console.log data
            , ->
              # Some form-tip need location.reload to finish reloading language translation
              # issue #2940
              modifiedUser = _self.getInfo()
              if modifiedUser and modifiedUser.language isnt originLanguage
                window.location.reload()
            )

          updatePwd: (size) ->
            modalInstance = $modal.open(
              templateUrl: 'updatePwd.html'
              controller: 'wm.ctrl.core.user.password'
              size: size
              windowClass: 'user-dialog'
              resolve:
                modalData: ->
            ).result.then( (data) ->
              console.log data
            )

          openFeedback: ->
            currentUser = _self.getInfo()

            options =
              host: location.hostname
              user:
                accountId: '54f6cfef8f5e88b96a8b4567'
                language: currentUser.language
                avatar: currentUser.avatar
                origin: 'portal'
                fields: [
                  label: 'feedback_customer_email'
                  name: 'email'
                  value: currentUser.email
                  type: 'email'
                  readonly: true
                ,
                  label: 'feedback_customer_company'
                  name: 'company'
                  value: currentUser.company
                  type: 'text'
                  readonly: true
                ,
                  label: 'feedback_customer_contact'
                  name: 'name'
                  value: currentUser.name
                  type: 'text'
                  readonly: true
                ]

            Feedback.config options
            Feedback.open()
        angular.extend rvm.user, userSetting

      user
  ]
