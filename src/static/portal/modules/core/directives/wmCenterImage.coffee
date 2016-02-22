define ["core/coreModule"], (mod) ->
  mod.directive "wmCenterImg", [
    "$interval"
    ($interval) ->
      return (
        restrict: "A"
        link: (scope, elem, attr) ->
          centerImage = (elem) ->
            timer = $interval(->
              pNode = elem.parentNode
              pNode.style.position = "relative"
              pNode.style.overflow = "hidden"
              elem.style.position = "absolute"
              elem.style.width = "100%"
              pHeight = pNode.clientHeight or pNode.height
              if (not pHeight or pHeight <= 0 or not elem.height or elem.height <= 0) and maxTime > i
                i++
                delayTime *= 5  if i is maxTime / 2
              else
                if elem.height <= pHeight
                  elem.style.width = null
                  elem.style.height = "100%"
                  pNodeWidth = pNode.clientWidth or pNode.width
                  elem.style.top = "0"
                  elem.style.left = (pNodeWidth - elem.clientWidth) / 2.0 + "px"
                else
                  elem.style.left = "0"
                  elem.style.top = (pHeight - elem.clientHeight) / 2.0 + "px"
                $interval.cancel timer
              return
            , delayTime)

          maxTime = 20
          i = 0
          delayTime = 100
          elem.on "load", ->
            $(elem).removeAttr "style"
            centerImage elem[0]
            return

          scope.$on "centerImage", (e) ->
            centerImage elem[0]
            return

          return
      )
  ]
  return
