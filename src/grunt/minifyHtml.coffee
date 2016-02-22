'use strict'

module.exports =
  options:
    conditionals: true
  task:
    files: [
      expand: true,
      cwd: '<%= buildDir %>',
      src: '**/*.html',
      dest: '<%= distDir %>'
    ]
