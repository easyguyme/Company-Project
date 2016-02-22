define ['core/coreModule'], (mod) ->

  mod.directive 'wmTooltip', [
      '$rootScope'
      '$interval'
      '$sanitize'
      '$timeout'
      ($rootScope, $interval, $sanitize, $timeout) ->
        return (
          link: (scope, elem, attrs) ->

            isAddTooltip = true
            # origin tip
            oldTip = ''
            newTip = ''

            $(elem).mouseenter ->
              updateTooltip()

            addTooltip = ->
              oldTip = attrs.wmTooltip
              if not attrs.tipChecked and oldTip
                isAddTooltip = false
                position = attrs.position or 'bottom'
                $(elem).tooltipster
                  content: $sanitize oldTip
                  position: position
                  theme: 'tooltipster-wm'
                  contentAsHTML: true
                  maxWidth: attrs.tooltipMaxWidth
              return

            updateTooltip = ->
              newTip = attrs.wmTooltip

              # Only when new tip is different from old tip, update tooltip
              if newTip and newTip isnt oldTip
                # replace old tip to new tip
                oldTip = newTip

                timer = $interval ->
                  $interval.cancel timer

                  # if $(elem).data('tooltipster-ns')
                  # If without the condition, tooltip will throw the exception as below:
                  # You called Tooltipster\'s method on an uninitialized element
                  if not $(elem).attr('tip-checked') and oldTip
                    if isAddTooltip
                      addTooltip()
                    else if $(elem).data('tooltipster-ns')
                      $(elem).tooltipster 'content', oldTip
                      $(elem).tooltipster 'enable'
                  else if $(elem).data('tooltipster-ns')
                    $(elem).tooltipster 'disable'

                , 80

              return

            addTooltip()

            $rootScope.$on '$translateChangeSuccess', ->
              updateTooltip()
              return

            return
      )
  ]
