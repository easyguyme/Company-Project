'use strict'

module.exports =
  build:
    files:
      '<%= buildDir %>/app.css': ['<%= buildDir %>/app.css']
  landing:
    files:
      '<%= buildDir %>/landing/css/app.css': ['<%= resourceRoot %>/landing/css/app.css']
