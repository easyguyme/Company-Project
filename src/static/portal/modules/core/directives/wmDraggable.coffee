define ['wm/app', 'jqueryUI'], (app) ->

  app.registerDirective 'wmDraggable', [
    'dragDropService'
    (dragDropService)->
      return (
        scope:
          draggable: '=wmDraggable'
        link: (scope, elem, attrs) ->
          options =
            connectToSortable: attrs.draggableTarget
            helper: "clone"
            revert: "invalid"
            cursor: "move"
            zIndex: 999
            start: () ->
              dragDropService.dragObject = scope.draggable
              return
            stop: () ->
              dragDropService.dragObject = undefined
              return

          $(elem).draggable(options).disableSelection()
          return
    )
  ]
