define ['core/coreModule', 'masonry'], (mod, Masonry) ->
  mod.directive "wmWaterfall", [
    '$interval'
    ($interval) ->
      return (
          restrict: "AC"
          scope:
            wmWaterfall: '='
          link: (scope, elem, attrs) ->
            container = elem[0]
            options = angular.extend(
              itemSelector: ".item"
            , scope.wmWaterfall)
            masonry = scope.masonry = new Masonry(container, options)
            cancelRefresh = undefined
            scope.update = ->
              cancelRefresh = $interval(->
                items = $(elem).find(options.itemSelector)
                if items && items.length && items.length > 0
                  masonry.reloadItems()
                  masonry.layout()
                  elem.children(options.itemSelector).css "visibility", "visible"
                  $interval.cancel cancelRefresh
              , 120)
              return

            scope.$on "$destroy", (e) ->
              $interval.cancel cancelRefresh
              return

            scope.update()

            scope.$on 'reform-waterfall', ->
              scope.update()

            return
        )
  ]
