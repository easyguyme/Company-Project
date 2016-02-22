'use strict'

module.exports =
  build:
    src: '<%= moduleDir %>/*/*.json'
    dest: '<%= buildDir %>/config.js'
