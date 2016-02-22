define [
  'core/coreModule'
  'wm/config'
], (mod, config) ->
  mod.factory 'dragDropService', [
    ->
      dataHandler =
        dragObject: undefined
        objects: []

      dataHandler.addAllObjects = (objects, tabId, tabIndex) ->
        self = this
        if tabId
          angular.forEach this.objects, (object, index) ->
            if object.id and tabId is object.id
              self.objects[index]['jsonConfig']['tabs'][tabIndex]['cpts'] = objects
        else
          self.objects = objects

      dataHandler.addObject = (object, to, tabId, tabIndex) ->
        self = this
        if tabId
          angular.forEach self.objects, (component, index) ->
            if component.id and tabId is component.id
              tab = self.objects[index]['jsonConfig']['tabs'][tabIndex]
              tab['cpts'] = [] unless tab['cpts']
              tab['cpts'].splice to, 0, object
        else
          self.objects.splice to, 0, object
        return

      dataHandler.moveObject = (from, to, tabId, tabIndex) ->
        self = this
        if tabId
          angular.forEach self.objects, (component, index) ->
            if component.id and tabId is component.id
              cpts = self.objects[index]['jsonConfig']['tabs'][tabIndex]['cpts']
              cpts.splice to, 0, cpts.splice(from, 1)[0]
              #self.objects[index]['tabs'][tabIndex]['cpts'] = _updateOrder cpts
        else
          self.objects.splice to, 0, self.objects.splice(from, 1)[0]
          #self.objects = _updateOrder self.objects
        return

      dataHandler.removeObject = (index, tabId, tabIndex) ->
        self = this
        if tabId
          angular.forEach self.objects, (component, componentIndex) ->
            if component.id and tabId is component.id
              self.objects[componentIndex]['jsonConfig']['tabs'][tabIndex]['cpts'].splice index, 1
            return
        else
          self.objects.splice index, 1

      dataHandler.updateObject = (id, object) ->
        angular.forEach self.objects, (component, index) ->
          if component.id and id is component.id
            self.objects[index]['jsonConfig']['tabs'][tabIndex]['cpts'].splice index, 1

      dataHandler.getObject = (index, tabId, tabIndex) ->
        self = this
        result = null
        if tabId
          angular.forEach self.objects, (component, index) ->
            if component.id and tabId is component.id
              result = self.objects[index]['jsonConfig']['tabs'][tabIndex]['cpts'][index]
            return
        else
          result = self.objects[index]
        result

      _updateOrder = (objects) ->
        angular.forEach objects, (object, index) ->
          object.order = index
          return
        objects

      dataHandler
  ]
