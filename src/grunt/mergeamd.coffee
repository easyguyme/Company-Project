'use strict'

module.exports =
  build:
    exclude: ['coreLoader.js', 'coreModule.js', 'wm-bootstrap-tpls.js']
    src: '<%= distCoreDir %>/**/*.js'
    dest: '<%= distCoreDir %>/coreLoader.js'
