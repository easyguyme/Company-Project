'use strict'

module.exports =
  build:
    src: ['<%= moduleDir %>/**/i18n/*.json', '<%= moduleDir %>/core/*-location/*.json']
    dest: '<%= i18nDistDir %>'
