'use strict'

module.exports =
  options:
    type: 'es6'
  mobile:
    expand: true
    cwd: '<%= resourceRoot %>'
    src: 'webapp/**/{,*/}*.tag'
    dest: '<%= buildDir %>/'
    ext: '.js'
