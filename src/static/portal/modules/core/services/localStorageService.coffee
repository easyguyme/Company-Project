define [
  "core/coreModule"
  "wm/config"
], (mod, config) ->
  mod.factory "localStorageService", [
    '$interval'
    ($interval) ->
      vm = {}

      vm.setItem = (key, value) ->
        valueJson = JSON.stringify(value)
        valueJson = Base64.encode valueJson
        key = Base64.encode key
        window.localStorage.setItem key, valueJson
        return

      vm.getItem = (key) ->
        key = Base64.encode key
        itemJson = window.localStorage.getItem(key)
        itemJson = Base64.decode itemJson if itemJson
        item = JSON.parse(itemJson)
        item

      vm.removeItem = (key) ->
        key = Base64.encode key
        window.localStorage.removeItem key
        return

      vm.updateItem = (key, value) ->
        key = Base64.encode key
        window.localStorage.removeItem key
        valueJson = JSON.stringify(value)
        valueJson = Base64.encode valueJson
        window.localStorage.setItem key, valueJson
        return

      vm
  ]
