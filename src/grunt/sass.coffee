'use strict'

module.exports =
  options:
    sourcemap: 'none'
    style: 'compressed'
    loadPath: ['vendor/bower', 'static/portal/scss']
  build:
    expand: true
    flatten: true
    cwd: '<%= styleDir %>'
    src: 'app.scss'
    dest: '<%= buildDir %>/'
    ext: '.css'
  chat:
    expand: true
    cwd: '<%= resourceRoot %>'
    src: 'chat/app.scss'
    dest: '<%= buildDir %>/'
    ext: '.css'
  buildMobile:
    expand: true
    cwd: '<%= resourceRoot %>'
    src: 'webapp/**/app.scss'
    dest: '<%= buildDir %>/'
    ext: '.css'
  webapp:
    cwd: '<%= webappModuleDir %>'
    src: '**/static/{,*/}*.scss'
    dest: '<%= webappBuildDir %>'
    expand: true
    ext: '.css'
    rename: (dest, src) ->
      dest + '/' + src.replace(/\/.+\//, '/css/')
