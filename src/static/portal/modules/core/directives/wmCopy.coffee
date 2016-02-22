define ['core/coreModule', 'zeroclipboard'], (mod, ZeroClipboard) ->

  mod.directive 'wmCopy', [
      '$timeout'
      '$filter'
      'storeService'
      ($timeout, $filter, storeService) ->
        return (
          scope:
            clipboardText: '='

          link: (scope, elem, attrs) ->
            ZeroClipboard.config( { moviePath: '/vendor/bower/zeroclipboard/ZeroClipboard.swf' } )
            clipboardClient = new ZeroClipboard(elem)
            $currentElem = ''

            # if elem do not has tooltip, and add tooltip instance
            # if elem has tooltip, only need to update it
            if not $(elem).data('tooltipster-ns')
              $(elem).tooltipster
                content: attrs.tip
                position: 'bottom'
                theme: 'tooltipster-wm'
                maxWidth: attrs.tooltipMaxWidth

            # fix bug when hover the copy component, the tooltip does not show #4553
            $('#global-zeroclipboard-html-bridge').off('mouseenter')
            $('#global-zeroclipboard-html-bridge').off('mouseout')

            $(elem).mouseenter ->
              if attrs.tip
                storeService.setMemoryItem 'currentElem', $(elem)
              else
                storeService.setMemoryItem 'currentElem', ''

            $('#global-zeroclipboard-html-bridge').mouseenter ->
              $currentElem = storeService.getMemoryItem 'currentElem'
              if $currentElem and $currentElem.data('tooltipster-ns')
                $currentElem.tooltipster 'content', attrs.tip
                $currentElem.tooltipster 'show'

            $('#global-zeroclipboard-html-bridge').mouseout ->
              $currentElem.tooltipster 'hide' if $currentElem and $currentElem.data('tooltipster-ns')
            # fix bug when hover the copy component, the tooltip does not show #4553

            clipboardClient.on 'complete', (client, args) ->
              $(elem).tooltipster 'content', $filter('translate')('copy_success')
              $(elem).tooltipster 'show'
              $timeout ->
                $(elem).tooltipster 'hide' if $(elem) and $(elem).data('tooltipster-ns')
              , 3000

            clipboardClient.on 'noflash', (client, args) ->
              $(elem).hide()

            scope.$watch 'clipboardText', (newClipboardText) ->
              #clipboardClient.setText newClipboardText
              $(elem).attr 'data-clipboard-text', newClipboardText
              return
            return
      )
  ]
