define [
  'core/coreModule'
  'wm/config'
], (mod, config) ->
  mod.factory 'debounceService', [
    () ->

      debounce = {}

      debounce.callback = (func, wait, immediate) ->
        timeout = args = context = timestamp = result = undefined
        later = ->
          last = new Date().getTime() - timestamp
          if last < wait and last >= 0
            timeout = setTimeout(later, wait - last)
          else
            timeout = null
            unless immediate
              result = func.apply(context, args)
              context = args = null  unless timeout
          return
        ->
          context = this
          args = arguments
          timestamp = new Date().getTime()
          callNow = immediate and not timeout
          timeout = setTimeout(later, wait)  unless timeout
          if callNow
            result = func.apply(context, args)
            context = args = null
          result

      debounce
  ]
