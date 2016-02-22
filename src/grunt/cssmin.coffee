'use strict'

module.exports =
  options:
    keepSpecialComments: 0
    aggressiveMerging: false
  build:
    files:
      '<%= buildDir %>/app.css': ['<%= buildDir %>/app.css']
  landing:
    files:
      '<%= buildDir %>/landing/css/app.css': ['<%= buildDir %>/landing/css/app.css']
      '<%= buildDir %>/landing/css/font.css': ['<%= buildDir %>/landing/css/font.css']
  webapp:
    files: [
      {
        expand: true,
        cwd: '<%= webappBuildDir %>',
        src: '**/*.css',
        dest: '<%= webappBuildDir %>'
      }
    ]
