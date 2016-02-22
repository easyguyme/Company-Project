'use strict'

# Only for landing page now
module.exports =
  landingCss:
    options:
      assets_root: '<%= webRoot %>/'
      views_root: '<%= buildDir %>/landing/css'
    src: '<%= buildDir %>/landing/css/*.css'
  landingPhp:
    options:
      assets_root: '<%= webRoot %>/'
      views_root: 'frontend/views/site'
    src: ['frontend/views/site/*.php', 'frontend/views/layouts/landing.php', 'frontend/views/layouts/faq.php']
  webappStatic:
    options:
      assets_root: '<%= webappRoot %>/'
      views_root: 'webapp'
    src: '<%= webappBuildDir %>/**/*.{js,css}'
  webappPhp:
    options:
      assets_root: '<%= webappRoot %>/'
      views_root: 'webapp'
    src: '<%= webappModuleDir %>/**/views/{,*/}*.php'
