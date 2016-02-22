'use strict'

module.exports =
  options:
    livereload: true
    nospawn: true
  coffeelintModule:
    files: ['<%= moduleDir %>/**/*.coffee']
    tasks: ['coffeelint:buildModule']
  coffeelintLoader:
    files: ['<%= angularLoaderDir %>/**/*.coffee']
    tasks: ['coffeelint:buildLoader']
  coffeeModule:
    files: ['<%= moduleDir %>/**/*.coffee']
    tasks: ['coffee:buildModule']
  coffeeLoader:
    files: ['<%= angularLoaderDir %>/**/{,*/}*.coffee']
    tasks: ['coffee:buildLoader']
  coffeeChat:
    files: ['<%= resourceRoot %>/chat/**/{,*/}*.coffee']
    tasks: ['coffee:chat']
  coffeeMobile:
    files: ['<%= resourceRoot %>/webapp/**/{,*/}*.coffee']
    tasks: ['coffee:buildMobile']
  scsslintModule:
    files: ['<%= moduleDir %>/{**/,*/}*.scss']
    tasks: ['scsslint:buildModule']
  scsslintLoader:
    files: ['<%= styleDir %>/{,*/}*.scss']
    tasks: ['scsslint:buildLoader']
  scsslintChat:
    files: ['<%= chatDir %>/{,*/}*.scss']
    tasks: ['scsslint:chat']
  scsslintMobile:
    files: ['<%= mobileDir %>/**/*.scss']
    tasks: ['scsslint:buildMobile']
  htmllintModule:
    files: ['<%= moduleDir %>/{**/,*/}*.html']
    tasks: ['htmllint:buildModule']
  htmllintChat:
    files: ['<%= chatDir %>/{,*/}*.html']
    tasks: ['htmllint:chat']
  sass:
    files: ['<%= moduleDir %>/**/*.scss', '<%= styleDir %>/{,*/}*.scss']
    tasks: ['sass:build']
  sassChat:
    files: ['<%= resourceRoot %>/chat/**/*.scss']
    tasks: ['sass:chat']
  sassMobile:
    files: ['<%= mobileDir %>/**/*.scss']
    tasks: ['sass:buildMobile']
  partial:
    files: ['<%= moduleDir %>/**/*.html']
    tasks: ['copy:build']
  mockData:
    files: ['<%= moduleDir %>/**/json/*.json']
    tasks: ['copy:mockData']
  i18n:
    files: ['<%= moduleDir %>/**/i18n/*.json']
  mergeConf:
    files: ['<%= moduleDir %>/*/config.json', '<%= moduleDir %>/*/introduction.json']
    tasks: ['mergeconf:build']
  loadSasses:
    files: ['<%= moduleDir %>/*/index.scss']
    tasks: ['loadsasses:build']
  chat:
    files: ['<%= resourceRoot %>/chat/**/*.html']
    tasks: ['copy:chat']
  mobileTag:
    files: ['<%= resourceRoot %>/webapp/**/{,*/}*.tag']
    tasks: ['riot:mobile']
  theme:
    files: ['../document/api/themes/*.nunjucks'],
    tasks: ['raml2html'],
    options:
      livereload: true
