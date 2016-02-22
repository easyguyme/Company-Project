'use strict'

module.exports =
  all:
    options:
      mainTemplate: 'layout.nunjucks',
      templatesPath: '../document/api/themes'
    files:
      '../document/api/api.html': ['../document/api/api.raml'],
