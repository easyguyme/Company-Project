'use strict'

module.exports =
  options:
    force: true
    configFile: '../coffeelint.json'
  buildModule:
    expand: true
    cwd: '<%= resourceRoot %>/portal'
    src: 'modules/**/*.coffee'
  buildLoader:
    expand: true
    cwd: '<%= angularLoaderDir %>'
    src: '**/{,*/}*.coffee'
  chat:
    expand: true
    cwd: '<%= resourceRoot %>'
    src: 'chat/**/{,*/}*.coffee'
  buildMobile:
    expand: true
    cwd: '<%= resourceRoot %>'
    src: 'webapp/**/{,*/}*.coffee'
  webapp:
    expand: true
    cwd: '<%= webappModuleDir %>'
    src: '**/static/{,*/}*.coffee'

