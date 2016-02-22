define [
  'core/coreModule'
  'core/controllers/oauthQrcodeCtrl'
], (mod) ->

  mod.directive 'wmChannelQrcode', [
    '$modal'
    '$timeout'
    ($modal, $timeout) ->
      return (
        restrict: 'EA'
        scope:
          qrcodeList: '='
          channelIds: '='
          apiInfo: '='
          disableEdit: '='
          modalTip: '@'
          createCallback: '&'
        template: '<div>
                    <a wm-tooltip="{{iconTip || \'download_qrcode\'| translate}}" ng-click="qrcodeHandler($event)" class="operate-icon"
                    ng-class="{\'channel-qrcode-icon\':qrcodeList, \'channel-newqrcode-icon\':!qrcodeList}"></a>
                    <div wm-multi-qrcode qrcode-list="qrcodeList" is-show="isShowQrcodeDropdown" self-style="position"
                    disable-edit="disableEdit" edit-handler="editQrcode" qrcode-title="content_article_qrcode"></div>
                  </div>'
        link: (scope, element, attrs) ->

          CREATE_QRCODE = 'create'
          EDIT_QRCODE = 'edit'

          scope.qrcodeHandler = (event) ->
            if _isCreateQrcode()
              _createEditQrcode(CREATE_QRCODE)
            else
              scope.isShowQrcodeDropdown = true
              qrcodePaneTop = $(event.target).offset().top - 15 - $('.portal-message').height()
              qrcodePaneRight = $('body').width() - $(event.target).offset().left + $('body').scrollLeft() - 30
              scope.position =
                right: qrcodePaneRight
                top: qrcodePaneTop

          scope.editQrcode = ->
            _createEditQrcode(EDIT_QRCODE)

          _createEditQrcode = (type) ->
            modalData =
              tip: scope.modalTip
              params: scope.apiInfo[type].params
              resource: scope.apiInfo[type].resource
              edit: false

            if type is EDIT_QRCODE
              modalData.edit = true
              modalData.channels = scope.channelIds

            modalInstance = $modal.open(
              templateUrl: '/build/modules/core/partials/oauthQrcode.html'
              controller: 'wm.ctrl.core.oauthQrcode'
              windowClass: 'qrcode-dialog'
              resolve:
                modalData: ->
                  modalData
              ).result.then( (data) ->
                if data and scope.createCallback
                  scope.isShowQrcodeDropdown = false
                  scope.createCallback()
            )

          _isCreateQrcode = ->
            if $.isArray(scope.qrcodeList) and scope.qrcodeList.length > 0
              return false
            return true

          scope.iconTip = if _isCreateQrcode() then 'newqrcode'

      )
  ]
