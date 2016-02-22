bowerPath = '../../../vendor/bower/'
require.config
  baseUrl: '/build'
  paths:
    angular: bowerPath + 'angular/angular.min'
    angularLazyLoad: bowerPath + 'angular-couch-potato/dist/angular-couch-potato'
    angularUeditor: bowerPath + 'angular-ueditor/dist/angular-ueditor.min'
    zeroclipboard: bowerPath + 'zeroclipboard/ZeroClipboard.min'
    sortable: bowerPath + 'Sortable/Sortable.min'
    uiBootstrapTpls: 'modules/core/partials/wm-bootstrap-tpls'
    jqueryUI: bowerPath + 'jquery-ui/minified/jquery-ui.custom.min'
    md5: bowerPath + 'blueimp-md5/js/md5.min'
    masonry: bowerPath + 'masonry/dist/masonry.pkgd.min'
    qrcode: bowerPath + 'jquery-qrcode/jquery.qrcode.min'
    bootstrapSlider: bowerPath + 'seiyria-bootstrap-slider/dist/bootstrap-slider.min'
    flesColorPicker: bowerPath + 'FlexiColorPicker/colorpicker.min'
    feedback: 'chat/feedback'
    vendorBundle: 'vendor.min'
    jqueryBundle: 'jquery.min'
    wm: '../build'
    core: 'modules/core'
    module: 'modules'

    ueditor: bowerPath + 'angular-ueditor/ueditor/ueditor.all.min'
    ueditorConfig: bowerPath + 'angular-ueditor/ueditor/ueditor.config'

    echartsBasic: bowerPath + 'echarts/build/dist/echarts-basic'
    echartsMap: bowerPath + 'echarts/build/dist/echarts-map'
  shim:
    angular:
      deps: ['jqueryBundle'] # Ensure that jqlite use jquery as selector
      exports: 'angular'
    zeroclipboard:
      exports: 'zeroclipboard'
    echartsBasic:
      exports: 'echarts'
    echartsMap:
      exports: 'echarts'
    uiBootstrapTpls: ['angular']
    vendorBundle: ['uiBootstrapTpls']
    angularLazyLoad: ['angular']
    jqueryUI: ['jqueryBundle']
    ueditor: [
      'jqueryBundle'
      'ueditorConfig'
    ]
    angularUeditor: ['angular', 'ueditor']

  deps: ['wm/bootstrap']
  urlArgs: 'v={timestamp}'
