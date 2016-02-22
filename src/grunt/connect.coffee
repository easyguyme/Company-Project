'use strict'

module.exports =
  server:
    options:
      port: 8002,
      hostname: '*',
      livereload: true,
      base:
        path: '../document/api',
        options:
          index: 'api.html'
