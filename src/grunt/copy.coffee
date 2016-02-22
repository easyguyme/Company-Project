'use strict'

module.exports =
  build:
    expand: true
    cwd: '<%= resourceRoot %>/portal'
    src: ['modules/**/*.html', 'modules/**/*.js']
    dest: '<%= buildDir %>/'
  chat:
    expand: true
    cwd: '<%= resourceRoot %>/'
    src: 'chat/**/*.html'
    dest: '<%= buildDir %>/'
  landing:
    expand: true
    cwd: '<%= resourceRoot %>/'
    src: ['landing/{images,fonts}/*.*', 'landing/css/font.css']
    dest: '<%= buildDir %>/'
  mockData:
    expand: true
    src: '<%= moduleDir %>/**/*.json'
    dest: '<%= buildDir %>/'
  appCss:
    expand: true
    cwd: '<%= buildDir %>'
    src: ['**/**.css', '**/**.css.map']
    dest: '<%= distDir %>'
  images:
    expand: true
    cwd: '<%= resourceRoot %>'
    src: 'images/**/*.*'
    dest: '<%= webRoot %>'
  moduleImages:
    cwd: '<%= moduleDir %>'
    src: '**/images/{,*/}*.*'
    dest: '<%= imageDistDir %>'
    expand: true
    rename: (dest, src) ->
      dest + src.replace('images/','')
  navImages:
    expand: true
    cwd: '<%= moduleDir %>'
    src: '**/images/nav/*.*'
    dest: '<%= webRoot %>/images/nav/'
    rename: (dest, src) ->
      dest + src.slice(src.lastIndexOf('/') + 1)
  audios:
    expand: true
    cwd: '<%= resourceRoot %>'
    src: '**/audios/**/*.*'
    dest: '<%= webRoot %>'
  webappCss:
    cwd: '<%= webappModuleDir %>'
    src: '**/static/{,*/}*.css'
    dest: '<%= webappBuildDir %>'
    expand: true
    ext: '.css'
    rename: (dest, src) ->
      dest + '/' + src.replace(/\/.+\//, '/css/')
  webappJs:
    cwd: '<%= webappModuleDir %>'
    src: '**/static/{,*/}*.js'
    dest: '<%= webappBuildDir %>'
    expand: true
    ext: '.js'
    rename: (dest, src) ->
      dest + '/' + src.replace(/\/.+\//, '/js/')
  webappImage:
    cwd: '<%= webappModuleDir %>'
    src: '**/static/images/*.*'
    dest: '<%= webappBuildDir %>'
    expand: true
    rename: (dest, src) ->
      dest + '/' + src.replace(/\/.+\//, '/images/')
  webappFont:
    cwd: '<%= webappModuleDir %>'
    src: '**/static/fonts/*.*'
    dest: '<%= webappBuildDir %>'
    expand: true
    rename: (dest, src) ->
      dest + '/' + src.replace(/\/.+\//, '/fonts/')
  meta:
    cwd: '<%= moduleDir %>'
    src: 'core/meta/*.*'
    dest: '<%= buildDir %>/modules/'
    expand: true
