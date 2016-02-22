define ['core/coreModule', 'qrcode'], (mod) ->
  mod.directive "wmQrcode", [
    () ->
      return (
        restrict  : "A"
        scope     :
          text : '=',
        link      : (scope, elem, attrs) ->
          $elem = $ elem;
          options =
            width  : $elem.width()
            height : $elem.height()
          scope.$watch 'text', (newText)->
            if newText
              options.text = newText
              $(elem).qrcode options
            return
          return
      )
  ]
