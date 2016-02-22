'use strict'

module.exports =
  options:
    config: '../scsslint.yml'
    force: true
    maxBuffer: 1024 * 1024 * 1024
    colorizeOutput: true
  buildModule:
    src: '<%= moduleDir %>/{**/,*/}*.scss'
  buildLoader:
    src: '<%= styleDir %>/{,*/}*.scss'
  chat:
    src: '<%= chatDir %>/{,*/}*.scss'
  buildMobile:
    src: '<%= mobileDir %>/**/*.scss'
  webapp:
    src: '<%= webappModuleDir %>/**/static/{,*/}*.scss'
