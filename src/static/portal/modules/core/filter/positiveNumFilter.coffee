define [
  'core/coreModule'
], (mod) ->
  mod.filter 'positiveNum', ->
    (number) ->
      number = Math.abs number.toFixed 0
      number
  return
