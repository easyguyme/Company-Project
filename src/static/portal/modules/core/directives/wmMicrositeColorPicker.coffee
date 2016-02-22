define ['core/coreModule', 'flesColorPicker'], (mod) ->
  mod.directive('wmMicrositeColorPicker', [ ->
    restrict: 'A'
    replace: true
    scope:
      selectedColor: '=wmMicrositeColorPicker'
      disabled: '=isDisabled'
      colors: '='
      pickColor: '&'
    template:
      '<div class="color-container">
        <div class="color-picker" ng-click="colorPicker()" ng-class="{\'cp\': !disabled}">
          <div class="color" ng-style="{\'background-color\':selectedColor}"></div>
        </div>
        <div class="color-panel" ng-show="showPanel">
          <div class="color-block" ng-class="{\'selected\': selectedColor === color}" ng-repeat="color in colors" ng-style="{\'background-color\':color}" ng-click="selectColor(color)"></div>
        </div>
      </div>'
    link: (scope, elem, attrs) ->
      vm = scope

      vm.colorPicker = ->
        return if vm.disabled
        vm.showPanel = true
        if vm.showPanel
          $confirmMask = $ '<div class="mask-confirm"></div>'
          $(elem).append $confirmMask
          $('.mask-confirm').click( ->
            vm.$apply ->
              vm.showPanel = false
              return

            $('.mask-confirm').remove()
            return
          )
        return

      vm.selectColor = (color) ->
        return if vm.disabled
        vm.selectedColor = color
        vm.pickColor() color
        vm.showPanel = false
        $('.mask-confirm').remove()
        return
  ]).directive('wmPageColorPicker', [ ->
    restrict: 'A'
    replace: true
    scope:
      ngModel: '=wmPageColorPicker'
      disabled: '=isDisabled'
      pickHandler: '&'
    template:
        '<div class="color-container page-color-container">
          <div class="color-picker" ng-click="showColorPicker()" ng-class="{\'cp\': !disabled}">
            <div class="color" ng-style="{\'background-color\':ngModel}"></div>
          </div>
          <div class="color-panel" ng-show="showPanel">
            <section id="colorPicker" class="cp cp-default color-picker-wrapper clearfix"></section>
            <section class="color-input-wrapper">
              <span class="color-display" ng-style="{\'background-color\':pickedColor}"></span>
              <input class="form-control color-input" wm-input-reg data-reg="^#[a-fA-F0-9]{0,6}$"
                placeholder="{{\'content_choose_channel_enter_color\' | translate}}"
                ng-model="pickedColor" wm-validate="validateColor" required without-star/>
            </section>
            <section class="color-operation-wrapper">
              <span class="btn btn-success" translate="submit" ng-click="submit()"></span>
              <span class="btn btn-default" translate="cancel" ng-click="cancel()"></span>
            </section>
          </div>
        </div>'
      link: (scope, elem, attrs) ->
        picker = undefined
        DEFAULTCOLOR = '#6ab3f7'
        reg = /^#([a-fA-F0-9]{3}|[a-fA-F0-9]{6})$/

        equalColors = (first, secord) ->
          if not angular.isString(first) or not angular.isString(secord) or typeof(first) isnt typeof(secord)
            return false

          if first.length is secord.length
            return first is secord
          else
            sourceColor = ''
            compareColor = '#'
            if first.length is 4
              sourceColor = secord
              i = 1
              while i < first.length
                compareColor += first.slice(i, i + 1).concat(first.slice(i, i + 1))
                i += 1
            else if secord.length is 4
              sourceColor = first
              i = 1
              while i < secord.length
                compareColor += secord.slice(i, i + 1).concat(secord.slice(i, i + 1))
                i += 1

            return sourceColor is compareColor

        pickerHandler = (color) ->
          if not equalColors(scope.pickedColor, color)
            safeApply scope, ->
              scope.pickedColor = color

        setPickedColor = (color) ->
          if color and reg.test(color) and not equalColors(color, scope.pickedColor)
            scope.pickedColor = color
            picker.setHex scope.pickedColor

        init = ->
          picker = ColorPicker(document.getElementById('colorPicker'), pickerHandler)
          setPickedColor(scope.ngModel or DEFAULTCOLOR)

        hideColorPicker = ->
          # remove input error tip
          $inputWrapper = $('.color-input-wrapper')
          if $inputWrapper.length and $inputWrapper.hasClass('highlight')
            $inputWrapper.find('.form-tip').remove()
            $inputWrapper.find('.color-input').removeClass('form-control-error')

          safeApply scope, ->
            scope.showPanel = false
            return

          $('.mask-confirm').remove()
          return

        safeApply = (scope, fn) ->
          phase = if scope.$root then scope.$root.$$phase else ''
          if phase is '$apply' or phase is '$digest'
            fn() if fn and ( typeof fn is 'function')
          else
            scope.$apply(fn)

        scope.showColorPicker = ->
          return if scope.disabled
          scope.showPanel = true
          if scope.showPanel
            if scope.ngModel and not equalColors(scope.ngModel, scope.pickedColor)
              setPickedColor scope.ngModel

            $confirmMask = $ '<div class="mask-confirm"></div>'
            $(elem).append $confirmMask
            $('.mask-confirm').click( ->
              hideColorPicker()
              return
            )
          return

        scope.validateColor = ->
          tip = ''
          if not (scope.pickedColor and reg.test scope.pickedColor)
            tip = 'content_enter_color_tip'
          tip

        scope.submit = ->
          if not scope.validateColor()
            safeApply scope, ->
              scope.ngModel = scope.pickedColor
              scope.pickHandler() scope.ngModel
            hideColorPicker()

        scope.cancel = ->
          hideColorPicker()

        scope.$watch 'pickedColor', (newVal, oldVal) ->
          if newVal and reg.test(newVal) and oldVal isnt newVal
            if newVal.length is 4
              colorNew = '#'
              i = 1
              while i < newVal.length
                colorNew += newVal.slice(i, i + 1).concat(newVal.slice(i, i + 1))
                i += 1
              newVal = colorNew
            picker.setHex newVal

        init()
  ])
