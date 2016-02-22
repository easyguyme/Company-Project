'use strict'

module.exports =
  options:
    bare: true
  buildModule:
    expand: true
    cwd: '<%= resourceRoot %>/portal'
    src: 'modules/**/*.coffee'
    dest: '<%= buildDir %>/'
    ext: '.js'
  buildLoader:
    expand: true
    cwd: '<%= angularLoaderDir %>'
    src: '**/{,*/}*.coffee'
    dest: '<%= buildDir %>/'
    ext: '.js'
  chat:
    expand: true
    cwd: '<%= resourceRoot %>/'
    src: 'chat/**/{,*/}*.coffee'
    dest: '<%= buildDir %>/'
    ext: '.js'
  feedback:
    expand: true
    cwd: '<%= resourceRoot %>/'
    src: 'chat/feedback.coffee'
    dest: '<%= buildDir %>/'
    ext: '.js'
  buildMobile:
    expand: true
    cwd: '<%= resourceRoot %>'
    src: 'webapp/**/{,*/}*.coffee'
    dest: '<%= buildDir %>/'
    ext: '.js'
  webapp:
    cwd: '<%= webappModuleDir %>'
    src: '**/static/{,*/}*.coffee'
    dest: '<%= webappBuildDir %>'
    expand: true
    ext: '.js'
    rename: (dest, src) ->
      dest + '/' + src.replace(/\/.+\//, '/js/')
