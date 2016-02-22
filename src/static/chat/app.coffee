define [
  'angular'
  'chat/config'
  'uiBootstrap'
  'angularUIRouter'
  'angularFileUpload'
  'angularUeditor'
  'angularTranslate'
  'angularTranslateLoader'
  'jqueryDotdotdot'
  'angularSanitize'
  'angularBindonce'
  'datetimepicker'
  'chat/coffee/coreLoader'
], (ng, config) ->

  forbiddenPage = 'chat/forbidden'

  checkAccess = (next) ->
    # Login logic here
    return true

  # Define module dependencies
  app = ng.module('wm', [
    'ui.router'
    'ui.bootstrap.tpls'
    'ui.bootstrap'
    'ng.ueditor'
    'angularFileUpload'
    'pascalprecht.translate'
    'ngSanitize'
    'pasvaz.bindonce'
    'wm.core'
  ])
  # Enable lazyloading for app module

  app.config([
    '$stateProvider'
    '$urlRouterProvider'
    '$locationProvider'
    '$translateProvider'
    ($stateProvider, $urlRouterProvider, $locationProvider, $translateProvider) ->
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

      $urlRouterProvider.otherwise(forbiddenPage)

      # Set up the states
      $stateProvider
      .state 'login',
        url: '/chat/login'
        templateUrl: '../build/modules/site/partials/login.html'
        controller: 'wm.ctrl.login'
        controllerAs: 'login'

      .state 'logout',
        url: '/chat/logout'
        controller: 'wm.ctrl.logout'
        controllerAs: 'logout'

      .state 'resetpasswordresult',
        url: '/chat/resetpasswordresult'
        templateUrl: '../build/modules/site/partials/resetpasswordresult.html'

      .state 'resetpassword',
        url: '/chat/resetpassword'
        controller: 'wm.ctrl.resetpassword'
        controllerAs: 'resetpassword'
        templateUrl: '../build/modules/site/partials/resetpassword.html'

      .state 'forbidden',
        url: '/chat/forbidden'
        templateUrl: '../build/chat/partials/forbidden.html'

      .state 'client',
        url: '/chat/client'
        templateUrl: '../build/chat/partials/client.html'
        controller: 'wm.ctrl.client'

      .state 'feedback',
        url: '/chat/feedback'
        templateUrl: '/build/chat/partials/feedback.html'
        controller: 'wm.ctrl.feedback'

      .state 'feedbacksuccess',
        url: '/chat/feedback/success'
        templateUrl: '/build/chat/partials/success.html'

      .state 'base',
        abstract: true
        url: ''
        templateUrl: '/build/chat/partials/base.html'
        controller: 'wm.ctrl.base'

      .state 'helpdesk',
        parent: 'base'
        url: '/chat/helpdesk'
        templateUrl: '/build/chat/partials/helpdesk.html'
        controller: 'wm.ctrl.helpdesk'

      .state 'issue',
        parent: 'base'
        url: '/chat/issue'
        templateUrl: '/build/chat/partials/issue.html'
        controller: 'wm.ctrl.issue'

      .state 'issue.detail',
        url: '/{id:[A-Za-z0-9]{24}}'
        templateUrl: '/build/chat/partials/view/issue.html'
        controller: 'wm.ctrl.issue.detail'

      .state 'issue.add',
        url: '/add'
        templateUrl: '/build/chat/partials/add/issue.html'
        controller: 'wm.ctrl.issue.add'

      .state 'wechatcplogin',
        url: '/chat/wechatcp/helpdesk'
        templateUrl: '/build/chat/partials/wechatcphelpdesk.html'
        controller: 'wm.ctrl.wechatcphelpdesk'

  ]).run [
    '$rootScope'
    '$translate'
    '$modal'
    'localStorageService'
    'issueService'
    ($rootScope, $translate, $modal, localStorageService, issueService) ->
      # Define root view model
      rvm = $rootScope
      # Default status is logout
      rvm.isLogined = false
      rvm.isHelpdesk = false
      rvm.isIssuePage = false

      # Hook for state change
      rvm.$on '$stateChangeStart', (event, next, params, from) ->
        if checkAccess next
          rvm.isHelpdesk = next.name is 'helpdesk' or next.name is 'issue'
          issueService.clearIssueDetailData() if from.name is 'issue.detail'
        else
          $location.path forbiddenPage
        return
      return
  ]
  app
