define ['wm/app', 'jqueryUI'], (app) ->

  app.registerDirective 'wmDroppable', [
    'dragDropService'
    (dragDropService)->
      return (
        scope:
          droppable: '=wmDroppable',
          update: '&',
          create: '&',
          options: '='
        link: (scope, elem, attrs) ->
          dragDropService.addAllObjects scope.droppable

          $elem = $(elem)
          options =
            placeholder : 'placeholder'
            axis        : 'y'
            cursor      : 'move'
            cancel      : '.cover-cpt'
            cursorAt    :
              left      : 133

          angular.extend options, scope.options

          sortDeactivate = (e, ui) ->
            angularScope = angular.element(ui.item).scope()
            if angularScope
              from = angularScope.$index;
              to = $elem.children().index(ui.item);

              if to >= 0
                # handle the component can only be dragged once
                cptCount = $('.mobile-content .cpt-body[name="' + ui.item.attr('name') + '"]').length
                if ui.item.hasClass('drag-once') and cptCount
                  ui.item.remove()
                else
                  scope.$apply ()->
                    if from >= 0
                      dragDropService.moveObject(from, to);
                      scope.update(
                        from: from
                        to: to
                      )
                    else
                      scope.create(
                        object: dragDropService.dragObject
                        to: to
                      )
                      ui.item.remove()

            return

          $elem.sortable(options).disableSelection().on("sortdeactivate", sortDeactivate)
          return
      )
  ]
