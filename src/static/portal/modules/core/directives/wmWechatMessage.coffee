define ['core/coreModule', 'wm/config'], (mod, config) ->
  mod.directive "wmWechatMessage", [
    'restService'
    '$modal'
    '$rootScope'
    (restService, $modal, $rootScope) ->
      return (
        restrict: 'A'
        scope:
          ngModel: '='
          disabledField: '='
          remainCharacter: '='
          preview: '@'
          previewFunc: '&'
          maxLength: '@'
          path: '@'
          placeholder: '@'
          keyupHandler: '&'
        transclude: true
        template: '<div class="wechat-message-wrap" ng-class="{\'message-wrap-disabled\': disabledField}">
                    <div class="message-input">
                      <div class="type-select">
                        <i class="messageicon messageicon-text" ng-class="{selected:!isGraphic}" ng-click="selectText()"
                         title=\'{{"channel_wechat_text_message" | translate}}\' ng-class="{\'cp\': !disabledField}"></i>
                        <i class="messageicon messageicon-graphic" ng-class="{selected:isGraphic}" ng-click="selectGraphic()"
                         title=\'{{"channel_wechat_graphic_message" | translate}}\' ng-class="{\'cp\': !disabledField}"></i>
                        <a ng-if="preview" class="pull-right message-preview" ng-click="previewFunc()">
                          {{preview == \'wechat\' ? "channel_wechat_preview" : preview == \'weibo\' ? "channel_weibo_preview" : "channel_alipay_preview" |translate}}
                        </a>
                      </div>
                      <div class="content">
                        <textarea class="message message-text form-control-message" ng-show="!isGraphic"
                         ng-model="textMessage" ng-disabled="disabledField" ng-keyup="keyupHandler()($event, textMessage)"
                         placeholder="{{placeholder}}">
                        </textarea>
                        <div class="message message-graphic clearfix" ng-show="isGraphic">
                          <div wm-wechat-graphic graphic="graphic"></div>
                        </div>
                      </div>
                    </div>
                    <div class="text-tip" ng-show="!isGraphic">
                      <span ng-show="currentChannel == \'alipay\' && !characterOverflow">{{\'channel_alipay_remain_character_tip\' | translate: \'{ length: asciiLength }\'}}</span>
                      <span ng-show="currentChannel != \'alipay\' && !characterOverflow">{{\'channel_wechat_remain_character_tip\' | translate: \'{ ascii: asciiLength, utf8: chineseLength }\'}}</span>
                      <span ng-show="currentChannel == \'alipay\' && characterOverflow">{{\'channel_alipay_character_overflow_tip\' | translate: \'{ length:-asciiLength }\'}}</span>
                      <span ng-show="currentChannel != \'alipay\' && characterOverflow">{{\'channel_wechat_character_overflow_tip\'|translate: \'{ ascii:-asciiLength, utf8:-chineseLength }\'}}</span>
                    </div>
                    <div class="message-error-tip hide" translate="required_field_tip"></div>
                  </div>'
        link: (scope, elem, attrs) ->
          _maxWechatByteLength = 2048 # the max message length in wechat. unit: byte, encode: utf8
          _maxWeiboByteLength = 1200 # the max message length in weibo. unit: byte, encode: gb2312
          _maxAlipayCharacterLength = 2539 # the max length of character in alipay. each character, no matter it's Chinese or English will take 1 length
          _maxHelpdeskCharacterLength = 1200 # the max message length that helpdesk can input. unit: byte, encode: gb2312

          channelType =
            alipay: "alipay"
            wechat: "wechat"
            weibo: "weibo"

          scope.asciiLength = 2048
          scope.chineseLength = 682
          scope.characterOverflow = false

          _countUtf8ByteLength = (string) ->
            totalLength = 0
            for index of string
              charCode = string.charCodeAt index
              if charCode < 0x007f
                totalLength += 1
              else if charCode < 0x07ff
                totalLength += 2
              else if charCode < 0xffff
                totalLength += 3
            totalLength

          _countGb2312ByteLength = (string) ->
            totalLength = 0
            for index of string
              charCode = string.charCodeAt index
              if charCode < 299
                totalLength += 1
              else
                totalLength += 2
            totalLength

          _countCharacterLength = (string) ->
            totalLength = 0
            for index of string
              totalLength++
            totalLength

          _countRemainCharLength = (string) ->
            if $rootScope.currentChannel?.type is channelType.wechat
              remainByteLength = _maxWechatByteLength - _countUtf8ByteLength string
              asciiLength = remainByteLength
              chineseLength = Math.floor remainByteLength / 3
              [asciiLength, chineseLength]
            else if not $rootScope.currentChannel? or $rootScope.currentChannel?.type is channelType.weibo
              remainByteLength = _maxWeiboByteLength - _countGb2312ByteLength string
              asciiLength = remainByteLength
              chineseLength = Math.floor remainByteLength / 2
              [asciiLength, chineseLength]
            else if $rootScope.currentChannel?.type is channelType.alipay
              remainByteLength = _maxAlipayCharacterLength - _countCharacterLength string
              [remainByteLength, remainByteLength]

          _update = ->
            if !!scope.ngModel or scope.ngModel?
              if typeof scope.ngModel is 'string'
                scope.isGraphic = false
                scope.textMessage = scope.ngModel
                [scope.asciiLength, scope.chineseLength] = _countRemainCharLength(scope.ngModel) if scope.ngModel?
                scope.remainCharacter = scope.asciiLength if scope.remainCharacter?
                scope.characterOverflow = if scope.asciiLength < 0 then true else false
              else
                scope.isGraphic = true
                scope.graphic = scope.ngModel
            else
              scope.isGraphic = false
              scope.textMessage = ""

          _init = ->
              scope.hasError = false
              scope.currentChannel = $rootScope.currentChannel?.type
              _update()

              elem.find('.wechat-message-wrap').focusin ->
                if not scope.ngModel
                  elem.find('.wechat-message-wrap .message-input').removeClass 'form-control-error'
                  elem.find('.text-tip').removeClass 'hide'
                  elem.find('.message-error-tip').addClass 'hide'
                  return

              elem.find('.wechat-message-wrap').focusout ->
                if not scope.ngModel and attrs.requiredFlag is 'true'
                  elem.find('.wechat-message-wrap .message-input').addClass 'form-control-error'
                  elem.find('.text-tip').addClass 'hide'
                  elem.find('.message-error-tip').removeClass 'hide'
                  return

          _init()

          scope.$watch 'ngModel', ->
            _update()

          scope.selectText = ->
            if not scope.disabledField
              scope.isGraphic = false
              scope.ngModel = scope.textMessage

          scope.selectGraphic = ->
            if not scope.disabledField
              scope.isGraphic = true
              scope.ngModel = scope.graphic
              _editGraphic()

          _editGraphic = ->
            elem.find('.wechat-message-wrap .message-input').removeClass 'form-control-error'
            elem.find('.text-tip').removeClass 'hide'
            elem.find('.message-error-tip').addClass 'hide'

            modalInstance = $modal.open(
              templateUrl: '/build/modules/core/partials/graphicmodal.html'
              controller: 'wm.ctrl.core.graphic'
              windowClass: "graphic-modal"
              resolve:
                path: ->
                  scope.path or config.resources.graphicList
            )

            modalInstance.result.then (graphic) ->
              scope.ngModel = scope.graphic = graphic
            , ->
              if not scope.graphic? and attrs.requiredFlag is 'true'
                scope.isGraphic = false
                scope.ngModel = scope.textMessage
                elem.find('.wechat-message-wrap .message-input').addClass 'form-control-error'
                elem.find('.text-tip').addClass 'hide'
                elem.find('.message-error-tip').removeClass 'hide'

          scope.$watch 'textMessage', (newValue, oldValue) ->
            scope.ngModel = scope.textMessage if scope.textMessage?
            return

          return
      )
  ]
