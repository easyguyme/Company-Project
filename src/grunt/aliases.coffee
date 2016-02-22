'use strict'

module.exports =
  compile: [
    'newer:coffee:buildModule'
    'newer:coffee:buildLoader'
    'sass:build'
    'css_import:build'
    'cssmin:build'
    'newer:copy:build'
  ]
  landingopt: [
    'uglify:landing'
    'cssmin:landing'
    'filerev:landing'
    'filerev_replace:landingCss'
    'filerev_replace:landingPhp'
    'cdn:landingCss'
    'cdn:landingLayout'
    'cdn:landingPhp'
    'qiniu:landing'
  ]
  landing: [
    'clean:landing'
    'copy:landing'
    'css_import:landing'
    'concat:landing'
    'imageEmbed:landing'
  ]
  portalOpt: [
    'uglify:portal'
    'minifyHtml'
    'sprite'
  ]
  portal: [
    'shell:getIconNames'
    'loadsasses:build'
    'compile'
    'mergeamd'
    'i18n:build'
    'mergeconf:build'
    'newer:copy:images'
    'newer:copy:moduleImages'
    'newer:copy:navImages'
    'newer:copy:audios'
    'concat:angularVendor'
    'concat:jqueryVendor'
    'uglify:vendor'
    'copy:meta'
  ]
  chat: [
    'newer:coffee:chat'
    'sass:chat'
    'newer:copy:chat'
  ]
  feedback: [
    'newer:coffee:feedback'
  ]
  mobile: [
    'newer:coffee:buildMobile'
    'sass:buildMobile'
    'newer:riot:mobile'
  ]
  webappOpt: [
    'uglify:webapp'
    'cssmin:webapp'
    'filerev:webapp'
    'replace:webapp'
    'filerev_replace:webappStatic'
    'filerev_replace:webappPhp'
    'cdn:webappStatic'
    'cdn:webappPhp'
    'replace:webappJSCDN'
    'qiniu:webapp'
  ]
  webapp: [
    'newer:coffee:webapp'
    'sass:webapp'
    'newer:copy:webappCss'
    'newer:copy:webappJs'
    'newer:copy:webappImage'
    'newer:copy:webappFont'
  ]
  build: [
    'feedback'
    'landing'
    'portal'
    'chat'
    'mobile'
    'webapp'
  ]
  cbuild: [
    'clean'
    'build'
  ]
  dist: [
    'cbuild'
    'genversion'
    'landingopt'
    'portalOpt'
    #'webappOpt'
  ]
  klp: [
    'clean'
    'portal'
    'chat'
    'mobile'
    'genversion'
    'portalOpt'
  ]
  dev: [
    'watch'
  ]
  default: [
    'build'
    'dev'
  ]
  raml: [
    'raml2html'
    'connect'
    'watch:theme'
  ]
