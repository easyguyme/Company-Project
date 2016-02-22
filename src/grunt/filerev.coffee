'use strict'

module.exports =
  options:
    algorithm: 'md5'
    length: 8
  landing:
    src: ['<%= buildDir %>/landing/{**,}/*.*']
  webapp: #Only optimize images for now
    src: ['<%= webappBuildDir %>/{**,}/*.{png,jpg,gif,svg}']
