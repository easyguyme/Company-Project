define ["core/coreModule"], (mod) ->
  mod.directive 'wmEnter', [ ->
    return (
      scope:
        enter: '&wmEnter'
      link: (scope, elem, attr) ->
        elem.bind 'keyup', (e)->
          scope.enter() if e.keyCode == 13
          return
        return
      )
  ]

