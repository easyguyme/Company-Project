define ['wm/app', 'flesColorPicker'], (app) ->
  app.registerDirective "wmColorPicker", [
    ->
      return (
          restrict: "E,A"
          replace: true
          scope:
            ngModel: '='
          template: '<div class="color-picker">
            <input onkeydown="return false;" class="form-control color-swap" ng-click="showColorPicker($event)" ng-model="ngModel" />
            <div id="default" class="cp cp-default" ng-style="{\'left\': left}"></div>
          </div>'
          link: (scope, elem) ->

            safeApply = (scope, fn) ->
              phase = if scope.$root then scope.$root.$$phase else ''
              if phase is '$apply' or phase is '$digest'
                fn() if fn and ( typeof fn is 'function')
              else
                scope.$apply(fn)

            pickerHandler = (hex) ->
              safeApply scope, ->
                scope.ngModel = hex

            picker = ColorPicker(document.getElementById('default'), pickerHandler)

            scope.showColorPicker = ($event) ->
              scope.left = '100px'
              scope.ngModel = '#fefefe' if not scope.ngModel
              picker.setHex scope.ngModel

              $body = $($event.target).parent()
              $confirmMask = $ '<div class="mask-confirm"></div>'
              $body.append($confirmMask)
              $confirmMask.click (event) ->
                event.preventDefault()
                $('.mask-confirm').remove()
                safeApply scope, ->
                  scope.left = '-9999px'
                return
              $confirmMask.show()
              return

        )
  ]
