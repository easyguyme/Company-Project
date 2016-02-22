define ['core/coreModule'], (mod) ->
  mod.directive "wmFooter", [
    '$interval'
    ($interval) ->
      return (
        restrict: "A"
        replace: true
        template: '<div class="footer">\
                    <div class="copyright ng-hide">© Copyright 2013-{{curYear}} Augmentum</div>\
                    <div class="top-btn cp" ng-click="scrollTop()" ng-hide="hideBtn"></div>\
                  </div>'
        link: (scope, elem, attrs) ->
          scope.curYear = new Date().getFullYear()
          scope.hideBtn = true
          scope.scrollTop = ->
           $(document).scrollTop(0)
           return
          timer = $interval (->
            viewportHeight = $('.viewport').outerHeight()
            navHeight = $('.navbar').outerHeight()
            docHeight = $(window).outerHeight()
            if viewportHeight > (docHeight - navHeight)
              scope.hideBtn = false
              $interval.cancel timer
              timer = null
          ), 1000
      )
  ]
