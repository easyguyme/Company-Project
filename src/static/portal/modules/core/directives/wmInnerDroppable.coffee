define ['wm/app', 'jqueryUI'], (app) ->

  app.registerDirective 'wmInnerDroppable', [
    'dragDropService'
    (dragDropService)->
      return (
        scope:
          innerDroppable: '=wmInnerDroppable',
          update: '&',
          create: '&',
          options: '='
        link: (scope, elem, attrs) ->
          $elem = $(elem)
          options =
            placeholder : 'placeholder'
            accept      : '.component'
            axis        : 'y'
            cursor      : 'move'
            cancel      : '.cover-cpt'
            cursorAt    :
              left      : 133

          angular.extend options, scope.options

          sortDeactivate = (e, ui) ->
            angularScope = angular.element(ui.item).scope()
            if angularScope
              tabId = attrs.tabId
              tabIndex = angularScope.tabIndex

              if ui.item.hasClass('tab-component') and tabId
                $elem.find('.tab-component').remove()
                return

              from = angularScope.$index
              to = $elem.children("[ng-repeat]").index(ui.item)

              if to >= 0
                # handle the component can only be dragged once
                cptCount = $('.mobile-content .cpt-body[name="' + ui.item.attr('name') + '"]').length
                if ui.item.hasClass('drag-once') and cptCount
                  ui.item.remove()
                else
                  scope.$apply ()->
                    if from >= 0 and !ui.item.hasClass 'component'
                      dragDropService.moveObject(from, to, tabId, tabIndex);
                      scope.update(
                        from: from
                        to: to
                        tabId: tabId
                        tabIndex: tabIndex
                      )
                    else
                      scope.create(
                        object: dragDropService.dragObject
                        to: to
                        tabId: tabId
                        tabIndex: tabIndex
                      )
                      ui.item.remove()

            return

          $elem.sortable(options).disableSelection().on("sortdeactivate", sortDeactivate)
      )
  ]
