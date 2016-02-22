'use strict'

module.exports =
  options:
    force: true
  main:
    src: ['<%= buildDir %>', '<%= distDir %>']
  webapp:
    src: ['<%= webappBuildDir %>', '<%= webappBuildDir %>']
  landing:
    src: ['<%= buildDir %>/landing', '<%= distDir %>/landing']
