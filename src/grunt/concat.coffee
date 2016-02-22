'use strict'

module.exports =
  options:
    stripBanners: true
  landing:
    files:
      '<%= buildDir %>/landing/script/app.js': [
        'vendor/bower/jquery/dist/jquery.js'
        'vendor/bower/js-base64/base64.js'
        'vendor/bower/fullpage.js/jquery.fullPage.js'
        'vendor/bower/mobile-detect/mobile-detect.js'
        'vendor/bower/jquery.lazyload/jquery.lazyload.js'
        '<%= resourceRoot %>/landing/script/app.js'
        '<%= resourceRoot %>/landing/script/case.js'
        '<%= resourceRoot %>/landing/script/landing.js'
        '<%= resourceRoot %>/landing/script/signup.js'
        '<%= resourceRoot %>/landing/script/handlewechat.js'
        '<%= buildDir %>/chat/feedback.js'
      ]
  angularVendor:
    files:
      '<%= buildDir %>/vendor.js': [
        'vendor/bower/angular-bootstrap/ui-bootstrap.js'
        'vendor/bower/angular-ui-router/release/angular-ui-router.js'
        'vendor/bower/angular-translate/angular-translate.js'
        'vendor/bower/angular-translate-loader-static-files/angular-translate-loader-static-files.js'
        'vendor/bower/ng-file-upload/angular-file-upload.js'
        'vendor/bower/angular-bindonce/bindonce.js'
        'vendor/bower/angular-sanitize/angular-sanitize.js'
      ]
  jqueryVendor:
    files:
      '<%= buildDir %>/jquery.js': [
        'vendor/bower/jquery/dist/jquery.js'
        'vendor/bower/bootstrap/js/collapse.js'
        'vendor/bower/tooltipster/js/jquery.tooltipster.js'
        'vendor/bower/moment/min/moment-with-locales.js'
        'vendor/bower/eonasdan-bootstrap-datetimepicker/src/js/bootstrap-datetimepicker.js'
        'vendor/bower/js-base64/base64.js'
        'vendor/bower/alogs/alog.js'
      ]
