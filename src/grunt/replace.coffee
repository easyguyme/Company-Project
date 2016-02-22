'use strict'

module.exports =
  webapp:
    options:
      patterns: [
        {
          match: /(\W)\/webapp(.+\..+\W)/g
          replacement: '$1$2'
        }
      ]
    files:[
      {
        expand: true
        cwd: '<%= webappBuildDir %>'
        src: '**/*.{css,js}'
        dest: '<%= webappBuildDir %>'
      }
      {
        expand: true
        cwd: '<%= webappModuleDir %>'
        src: '**/*.php'
        dest: '<%= webappModuleDir %>'
      }
    ]
  webappJSCDN:
    options:
      patterns: [
        {
          match: /(\W)\/build(.+\..+\W)/g
          replacement: '$1//dn-quncrm.qbox.me/build$2'
        }
      ]
    files:[
      {
        expand: true
        cwd: '<%= webappBuildDir %>'
        src: '**/*.js'
        dest: '<%= webappBuildDir %>'
      }
    ]
  revertWebapp:
    options:
      patterns: [
        {
          match: /(\W)\/\/.+\/build(.+)\..+\.(.+\W)/g
          replacement: '$1/webapp/build$2.$3'
        }
      ]
    files:[
      {
        expand: true
        cwd: '<%= webappBuildDir %>'
        src: '**/*.{css,js}'
        dest: '<%= webappBuildDir %>'
      }
      {
        expand: true
        cwd: '<%= webappModuleDir %>'
        src: '**/*.php'
        dest: '<%= webappModuleDir %>'
      }
    ]
