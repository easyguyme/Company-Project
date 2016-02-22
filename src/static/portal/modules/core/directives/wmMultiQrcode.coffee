define ['core/coreModule'], (mod) ->
  mod.directive 'wmMultiQrcode', [
    'canvasService'
    '$rootScope'
    'downloadService'
    '$timeout'
    (canvasService, $rootScope, downloadService, $timeout) ->
      return (
        restrict: 'EA'
        replace: true
        scope:
          index: '='
          qrcodeList: '='
          isShow: '='
          editHandler: '&'
          channel: '='
          selfStyle: '='
          disableEdit: '='
          qrcodeTitle: '@'

        template: '<div>
                    <div ng-if="isShow" class="multi-qrcode-wrapper"
                      ng-class="{\'multi-large-width\':qrcodeList.length > 1, \'multi-small-width\':qrcodeList.length == 1}"
                        ng-style="selfStyle">
                      <div class="head">
                        <span class="qrcode-title" translate="{{qrcodeTitle}}"></span>
                        <span class="close-btn cp pull-right" ng-click="hideQrcodeModal()"></span>
                      </div>
                      <div class="content clearfix">
                        <div ng-if="qrcodeList.length > 1">
                          <div class="qrcode-wrapper pull-left" ng-repeat="qrcode in qrcodeList track by $index">
                            <img class="icon-img" ng-if="qrcode.icon" ng-src="{{qrcode.icon}}" />
                            <span wm-tooltip="{{qrcode.title | translate}}" ng-class="{\'qrcode-type\': !qrcode.icon, \'qrcode-name\': qrcode.icon}">{{qrcode.title | translate}}</span>
                            <span class="download-icon cp" ng-click="downloadQrcode(qrcode)"></span>
                            <img class="qrcode-img" ng-class="{\'weibo-qrcode-img\': qrcode.title==\'weibo_qrcode\'}" ng-src="{{qrcode.link}}">
                          </div>
                        </div>
                        <div class="qrcode-wrapper" ng-if="qrcodeList.length == 1" ng-repeat="qrcode in qrcodeList track by $index">
                          <img class="icon-img" ng-if="qrcode.icon" ng-src="{{qrcode.icon}}" />
                          <span wm-tooltip="{{qrcode.title | translate}}" ng-class="{\'qrcode-type\': !qrcode.icon, \'qrcode-name\': qrcode.icon}">{{qrcode.title | translate}}</span>
                          <span class="download-icon cp" ng-click="downloadQrcode(qrcode)"></span>
                          <img class="qrcode-img" ng-src="{{qrcode.link}}">
                        </div>
                        <div class="qrcode-channel" ng-if="channel">
                          <span>{{"social_channels" | translate}}</span>&nbsp;<i>{{channel}}</i>
                        </div>
                      </div>
                      <div class="multi-footer" ng-if="!channel">
                        <div class="edit-icon cp" ng-click="editQrcode()" wm-tooltip="{{\'edit\' | translate}}" ng-show="!disableEdit"></div>
                        <div class="edit-icon edit-icon-disabled cp" ng-show="disableEdit"></div>
                      </div>
                    </div>
                    <div class="modal-no-color" ng-show="isShow" ng-click="hideQrcodeModal()"></div>
                  </div>'

        link: (scope, elem, attrs) ->

          WEIXIN = 'weixin'
          firstLoad = true
          viewHeight = 0 # cache origin view height
          $view = null # get dom

          scope.downloadQrcode = (qrcode) ->
            qrcodeName = qrcode.name or 'qrcode'
            # Weixin's qrcode is '.jpg' but others is '.png'
            ext = if qrcode.link.indexOf(WEIXIN) isnt -1 then 'jpg' else 'png'
            downloadService.download qrcode.link, ("#{qrcodeName}.#{ext}")
            return

          scope.hideQrcodeModal = ->
            scope.isShow = false
            $("a[tip-checked='true']").removeAttr 'tip-checked'
            return

          scope.editQrcode = ->
            scope.editHandler()(scope.index) if scope.editHandler

          # fix bug when qrcode at the bottom of the page, edit button is hidden
          scope.$watch 'isShow', (newVal, oldVal) ->
            if newVal
              $timeout ->
                if firstLoad
                  $view = $($('.viewport').find('div').get(0))
                  viewHeight = $view.height()
                  firstLoad = false
                bodyHeight = $('body').height()
                qrcodeTop = $('.multi-qrcode-wrapper ').offset().top
                qrcodeHeight = $('.multi-qrcode-wrapper ').height()
                diff = qrcodeTop + qrcodeHeight - bodyHeight
                if diff > 40
                  $view.height(viewHeight + diff - 30)
              , 100
            else
              $view.height(viewHeight) if $view

      )
  ]

#<div class="delete-icon cp" ng-click="deleteQrcode()" wm-tooltip="delete"></div>
