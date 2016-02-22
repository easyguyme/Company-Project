define [
  'core/coreModule'
], (mod) ->
  mod.filter 'fixColor', ->
    (color, opacity) ->
      hexadecimalReg = /^#([a-fA-F0-9]{3}|[a-fA-F0-9]{6})$/
      result = ''
      r = g = b = 0
      if hexadecimalReg.test color
        if color.length is 4
          r = parseInt color.slice(1, 2), 16
          g = parseInt color.slice(2, 3), 16
          b = parseInt color.slice(3, 4), 16
        else if color.length is 7
          r = parseInt color.slice(1, 3), 16
          g = parseInt color.slice(3, 5), 16
          b = parseInt color.slice(5, 7), 16

        if opacity
          result = "rgba(#{r}, #{g}, #{b}, #{opacity})"
        else
          result = "rgb(#{r}, #{g}, #{b})"
      result

