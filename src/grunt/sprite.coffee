'use strict'

module.exports =
  options:
    imagepath_map: (imgUrl) ->
      return '../' + imgUrl
    padding: 0
    useimageset: false
    newsprite: false
    spritestamp: false
    cssstamp: false
    algorithm: 'binary-tree'
    engine: 'pixelsmith'
  nav:
    options:
      imagepath: '<%= imageDistDir %>nav/'
      spritedest: '<%= imageDistDir %>nav/'
      spritepath: '/images/nav/'
    files: [
      expand: true
      cwd: '<%= distDir %>'
      src: '*.css'
      dest: '<%= distDir %>'
    ]
