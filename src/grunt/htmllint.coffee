'use strict'

module.exports =
  options:
    force: true
    htmllintrc: '../.htmllintrc'
  buildViews:
    src: '<%= viewsDir %>/{**/,*/}*.php'
  buildModule:
    src: '<%= moduleDir %>/{**/,*/}*.html'
  chat:
    src: '<%= chatDir %>/{,*/}*.html'
  webapp:
    src: '<%= webappModuleDir %>/**/{,*/}*.php'
