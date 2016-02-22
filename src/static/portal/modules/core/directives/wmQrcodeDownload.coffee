define ['core/coreModule'], (mod) ->
  mod.directive 'wmQrcodeDownload', [
    'canvasService'
    '$rootScope'
    'downloadService'
    (canvasService, $rootScope, downloadService) ->
      return (
        restrict: 'EA'
        replace: true
        scope:
          urlLink: '='
          imageLink: '='
          qrcodeTitle: '='
          isShow: '='
          locationTop: '='
          locationRight: '='
          isUrl: '@'
        template: '<div>
                            <div class="qrcode-modal" ng-if="isShow" ng-style="{top: locationTop, right: locationRight}">
                              <div class="qrcode-panel-header">
                                <label class="qrcode-title" translate="management_wechat_qrcode"></label>
                                <span class="btn-download cp" ng-click="downloadQrcode()"></span>
                                <button type="button" class="close popup-close btn-qrcode-close cp" ng-click="hideQrcodeModal()"></button>
                              </div>
                              <div class="qrcode-content">
                                <div ng-if="isUrl == \'true\'" class="qrcode-icon-box" style="padding: 15px;">
                                  <div id="qrcode-container" class="qrcode-icon-canvas" wm-qrcode text="urlLink"></div>
                                </div>
                                <div ng-if="isUrl == \'false\'" class="qrcode-icon-box" style="background-image:url(\'{{imageLink}}\');background-size: cover;"></div>
                              </div>
                            </div>

                            <div class="modal-no-color" ng-show="isShow" ng-click="hideQrcodeModal()"></div>
                          </div>'
        link: (scope, elem, attrs) ->

          WEIXIN = 'weixin'

          scope.downloadQrcode = ->
            qrcodeName = scope.qrcodeTitle or 'qrcode'
            canvasService.download $('#qrcode-container canvas')[0], (qrcodeName + '.png'), 'png', scope.urlLink if scope.isUrl is 'true'
            if scope.isUrl is 'false'
              # Weixin's qrcode is '.jpg' but others is '.png'
              ext = if scope.imageLink.indexOf(WEIXIN) isnt -1 then 'jpg' else 'png'
              downloadService.download scope.imageLink, ("#{qrcodeName}.#{ext}")
            return
          scope.hideQrcodeModal = ->
            scope.isShow = false
            $("a[tip-checked='true']").removeAttr 'tip-checked'
      )
  ]
