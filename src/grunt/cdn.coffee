'use strict'

module.exports =
  landingCss:
    cwd: '<%= buildDir %>/landing/css'
    dest: '<%= buildDir %>/landing/css'
    src: '*.css'
  landingLayout:
    cwd: 'frontend/views/layouts'
    dest: 'frontend/views/layouts'
    src: ['landing.php', 'faq.php']
  landingPhp:
    cwd: 'frontend/views/site'
    dest: 'frontend/views/site'
    src: ['case.php', 'feature.php', 'landing.php', 'message.php']
  webappStatic:
    cwd: '<%= webappBuildDir %>'
    dest: '<%= webappBuildDir %>'
    src: '**/*.{css,js}'
  webappPhp:
    cwd: '<%= webappModuleDir %>'
    dest: '<%= webappModuleDir %>'
    src: '**/*.php'
  options:
    cdn: '//dn-quncrm.qbox.me'
    supportedTypes:
      php: 'html'
      js: 'css'
