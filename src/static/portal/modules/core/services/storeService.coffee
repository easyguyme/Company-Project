define [
  'core/coreModule'
  'wm/config'
], (mod, config) ->
  mod.factory 'storeService', [
    'restService'
    '$q'
    'utilService'
    '$filter'
    (restService, $q, utilService, $filter) ->
      store =
        curLink: ''
        curStore: {}
        stores: []
        callbacks: []
        memory: {} # the memory is use to buffer key-values data
      # Harry suggestion here
      storePath = '/store/info/'

      store.getStores = ->
        defered = $q.defer()
        self = this
        if not @stores or not @stores.length
          stores = []
          params =
            unlimited: true
          restService.get config.resources.stores, params, (data) ->
            if data.items
              for store in data.items
                location = store.location
                stores.push(
                  id: store.id
                  name: store.name
                  branchName: store.branchName
                  phone: store.telephone
                  image: store.image
                  address: $filter('translate')(location.province) + $filter('translate')(location.city) + $filter('translate')(location.district) + location.detail
                )
              self.stores = stores
              defered.resolve(stores)
              return
        else
          defered.resolve(@stores)
        defered.promise

      store.setStore = (store) ->
        index = utilService.getArrayElemIndex @stores, store, 'id'
        if index is -1
          location = store.location
          item =
            id: store.id
            name: store.name
            phone: store.telephone
            image: store.image
            address: location.province + location.city + location.district + location.detail
            link: storePath + store.id
          @stores.splice 0, 0, item
        else
          location = store.location
          address = location.province + location.city + location.district + location.detail
          @stores[index].name = store.name
          @stores[index].phone = store.telephone
          @stores[index].image = store.image
          @stores[index].address = address

      store.delStore = (store) ->
        index = utilService.getArrayElemIndex @stores, store, 'id'
        if index isnt -1
          @stores.splice index, 1

      store.setCurStore = (store) ->
        @curStore = store
        @curLink = storePath + store.id
        for cb in @callbacks
          cb(@curLink, store)

      store.getCurStore = ->
        @curStore

      store.getCurLink = ->
        @curLink

      store.destory = ->
        @curLink = ''
        @curStore = {}
        @stores = []

      store.watchCurStore = (cb) ->
        @callbacks.push cb

      store.setMemoryItem = (key, value) ->
        @memory[key] = value
        return

      store.getMemoryItem = (key) ->
        item = @memory[key]
        item

      store.removeMemoryItem = (key) ->
        delete @memory[key] if @memory[key]
        return

      store.updateMemoryItem = (key, value) ->
        @removeMemoryItem key
        @setMemoryItem key, value
        return

      store
  ]
