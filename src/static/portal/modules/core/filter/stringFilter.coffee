define [
  'core/coreModule'
], (mod) ->
  mod.filter 'string', ->
    (src) ->
      src.toString()
  return
