define ['core/coreModule'], (mod) ->
  mod.directive('wmInputReg', [ ->
    restrict: 'A'
    require: 'ngModel'
    link: (scope, elem, attr, ctrl) ->
      regx = attr.reg or 'S+'
      reg = new RegExp(regx)

      ctrl.$parsers.unshift (value) ->
        if value and not reg.test value
          ctrl.$setViewValue(ctrl.$modelValue)
          ctrl.$render()
          return ctrl.$modelValue
        else
          return value

  ])
