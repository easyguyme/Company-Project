define ['core/coreModule'], (mod) ->
  mod.directive('wmMaxlength', [
    ->
      return(
        restrict: 'A'
        require: 'ngModel'
        link: (scope, elem, attr, ctrl) ->
          maxlength = parseInt(attr.wmMaxlength, 10)
          ctrl.$parsers.push((value) ->
            if value.length > maxlength
              value = value.substr(0, maxlength)
              ctrl.$setViewValue(value)
              ctrl.$render()
            return value
          )
      )
  ])
