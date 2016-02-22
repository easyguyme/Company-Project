'use strict'

module.exports =
  landing:
    files:
      '<%= buildDir %>/landing/css/app.css': ['<%= buildDir %>/landing/css/app.css']
    options:
      baseDir: '<%= webRoot %>'
      deleteAfterEncoding: false
