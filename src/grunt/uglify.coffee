'use strict'

module.exports =
  options:
    compress:
      drop_console: true
    sourceMap: false
  portal:
    files: [
      {
        expand: true,
        cwd: '<%= buildDir %>',
        src: '**/*.js',
        dest: '<%= distDir %>'
      }
      {
        src: '<%= requirejsPath %>/require.js'
        dest: '<%= requirejsPath %>/require.min.js'
      }
    ]
  landing:
    files:
      '<%= buildDir %>/landing/script/app.js': ['<%= buildDir %>/landing/script/app.js']
  webapp:
    files: [
      {
        expand: true,
        cwd: '<%= webappBuildDir %>',
        src: '**/*.js',
        dest: '<%= webappBuildDir %>'
      }
    ]
  vendor:
    files: [
      {
        src: '<%= buildDir %>/vendor.js'
        dest: '<%= distDir %>/vendor.min.js'
      }
      {
        src: '<%= buildDir %>/jquery.js'
        dest: '<%= distDir %>/jquery.min.js'
      }
    ]
