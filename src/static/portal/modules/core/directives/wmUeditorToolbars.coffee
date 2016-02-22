define [
  'core/coreModule'
], (mod) ->
  mod.directive('wmAuthorize', [
    '$modal'
    '$q'
    ($modal, $q) ->
      restrict: 'A'
      require: 'ngModel'
      link: (scope, elem, attr, ctrl) ->

        _openModal = ->
          deferred = $q.defer()

          modalInstance = $modal.open(
            templateUrl: '/build/modules/core/partials/selectChannelModal.html'
            controller: 'wm.ctrl.core.selectChannel'
            windowClass: "select-channel-dialog"
            resolve:
              modalData: ->
                params =
                  target: 'ueditor'
                params
          )

          modalInstance.result.then (data) ->
            oauthLink = data
            deferred.resolve oauthLink

          deferred.promise

        _registerToolbars = ->
          UE.registerUI 'authorize', (editor, uiName) ->
            editor.registerCommand uiName, execCommand: ->
              _self = this
              _openModal().then (link) ->
                modelValue = ctrl.$modelValue or ''
                value = modelValue + "<a href=\"#{link}\" _src=\"#{link}\">#{link}</a>"
                ctrl.$setViewValue(value)
                ctrl.$render()
                return
            btn = new (UE.ui.Button)(
              name: uiName
              title: '授权链接'
              onclick: ->
                editor.execCommand uiName
                return
            )

            #scope.$broadcast 'registered toolbar'
            btn

        _registerToolbars()

  ]).directive('wmButtonLink', [
    '$modal'
    '$q'
    ($modal, $q) ->
      restrict: 'A'
      require: 'ngModel'
      link: (scope, elem, attr, ctrl) ->

        _openModal = ->
          deferred = $q.defer()

          modalInstance = $modal.open(
            templateUrl: '/build/modules/core/partials/editButtonLinkModal.html'
            controller: 'wm.ctrl.core.selectChannel'
            windowClass: "select-channel-dialog"
            resolve:
              modalData: ->
                params =
                  target: 'ueditor'
                params
          )

          modalInstance.result.then (data) ->
            oauthData = data
            deferred.resolve oauthData

          deferred.promise

        _registerToolbars = ->
          UE.registerUI 'buttonlink', (editor, uiName) ->
            editor.registerCommand uiName, execCommand: ->
              _self = this
              _openModal().then (data) ->
                link = data[0]
                color = data[1]
                text = data[2] or '链接'
                modelValue = ctrl.$modelValue or ''
                value = modelValue + "<a id=\"aaa\" style=\"background-color: #{color}; border-radius: 2px; color: #fff; border: 1px solid transparent;" +
                                     "display: inline-block; padding: 6px 12px; text-decoration: none; cursor: pointer;\" href=#{link} " +
                                     "onmousedown=\"return false;\">" +
                                     "#{text}</a>"
                ctrl.$setViewValue(value)
                ctrl.$render()
                return
            btn = new (UE.ui.Button)(
              name: uiName
              title: '按钮'
              onclick: ->
                editor.execCommand uiName
                return
            )

            btn
          ,22

        _registerToolbars()
  ])
