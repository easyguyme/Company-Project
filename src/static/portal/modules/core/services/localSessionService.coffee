define [
  "core/coreModule"
  "wm/config"
], (mod, config) ->
  mod.factory "localSessionService", [
    '$interval'
    ($interval) ->
      vm = {}

      vm.setItem = (key, value) ->
        valueJson = JSON.stringify(value)
        valueJson = Base64.encode valueJson
        key = Base64.encode key
        window.sessionStorage.setItem key, valueJson
        return

      vm.getItem = (key) ->
        key = Base64.encode key
        itemJson = window.sessionStorage.getItem(key)
        itemJson = Base64.decode itemJson if itemJson
        item = JSON.parse(itemJson)
        item

      vm.removeItem = (key) ->
        key = Base64.encode key
        window.sessionStorage.removeItem key
        return

      vm.updateItem = (key, value) ->
        key = Base64.encode key
        window.sessionStorage.removeItem key
        valueJson = JSON.stringify(value)
        valueJson = Base64.encode valueJson
        window.sessionStorage.setItem key, valueJson
        return

      vm
  ]
