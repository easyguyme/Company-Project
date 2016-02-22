## Auto select a input or textarea content
define ['core/coreModule'], (mod) ->

  mod.directive 'wmAutoSelect', [
    ()->
      return (
        restrict: 'EA'
        link: (scope, element, attrs) ->
          $(element).mouseleave ()->
            $(this).blur()
            return
          $(element).mouseenter ()->
            $(this).focus().select()
            return
          return
    )
  ]
