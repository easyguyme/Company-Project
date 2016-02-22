bowerPath = '../../vendor/bower/'
require.config
  paths:
    angular: bowerPath + 'angular/angular.min'
    angularSanitize: bowerPath + 'angular-sanitize/angular-sanitize.min'
    angularUIRouter: bowerPath + 'angular-ui-router/release/angular-ui-router.min'
    angularTranslate: bowerPath + 'angular-translate/angular-translate.min'
    angularTranslateLoader: bowerPath + 'angular-translate-loader-static-files/angular-translate-loader-static-files.min'
    angularFileUpload: bowerPath + 'ng-file-upload/angular-file-upload.min'
    angularUeditor: bowerPath + 'angular-ueditor/dist/angular-ueditor.min'
    uiBootstrap: bowerPath + 'angular-bootstrap/ui-bootstrap.min'
    tooltipster: bowerPath + 'tooltipster/js/jquery.tooltipster.min'
    angularBindonce: bowerPath + 'angular-bindonce/bindonce.min'
    uiBootstrapTpls: '../../build/modules/core/partials/wm-bootstrap-tpls'
    jqueryBundle: '../../build/jquery.min'
    jqueryDotdotdot: bowerPath + 'jquery.dotdotdot/src/js/jquery.dotdotdot.min'
    datetimepicker: bowerPath + 'eonasdan-bootstrap-datetimepicker/src/js/bootstrap-datetimepicker'
    md5: bowerPath + 'blueimp-md5/js/md5.min'
    base64: bowerPath + 'js-base64/base64.min'
    moment: bowerPath + 'moment/min/moment.min'
    alog: bowerPath + 'alogs/alog.min'
    titleNoty: bowerPath + 'titleNoty/dist/titleNoty'
    chat: '../../build/chat'
    wm: '../../build'
    core: '../../build/modules/core'

    ueditor: bowerPath + 'angular-ueditor/ueditor/ueditor.all.min'
    ueditorConfig: bowerPath + 'angular-ueditor/ueditor/ueditor.config'


  shim:
    angular:
      exports: 'angular'
    angularUIRouter: ['angular']
    angularTranslate: ['angular']
    angularTranslateLoader: [
      'angular'
      'angularTranslate'
    ]
    angularFileUpload: ['angular']
    ueditor: [
      'jqueryBundle'
      'ueditorConfig'
    ]
    angularUeditor: ['angular', 'ueditor']
    angularSanitize: ['angular']
    uiBootstrapTpls: ['angular']
    uiBootstrap: ['uiBootstrapTpls']
    tooltipster: ['jqueryBundle']
    jqueryDotdotdot: ['jqueryBundle']
    datetimepicker: ['jqueryBundle']
    angularBindonce: ['angular']
    alog:
      exports: 'alog'

  deps: ['chat/bootstrap']
  urlArgs: 'v={timestamp}'
