define [
  'core/coreModule'
], (mod) ->
  mod.filter 'commaNumber', ->
    (src) ->
      str = parseInt(src).toString()
      len = str.length
      if len <= 3
        return str
      remainder = len % 3
      return if remainder > 0 then str.slice(0, remainder) + "," + str.slice(remainder, len).match(/\d{3}/g).join(",") else str.slice(0, len).match(/\d{3}/g).join(",")
  return
