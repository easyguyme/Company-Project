###
 @see https://github.com/RubaXa/Sortable/blob/0.7.1/ng-sortable.js
###
define ['wm/app', 'sortable'], (app, Sortable) ->
  app.registerDirective 'wmSortable', [
    '$parse'
    '$rootScope'
    ($parse, $rootScope) ->
      getSource = (el) ->
        scope = angular.element(el).scope()
        ngRepeat = [].filter.call(el.childNodes, (node) ->
          (node.nodeType is 8) and (node.nodeValue.indexOf('ngRepeat:') isnt -1)
        )[0]
        ngRepeat = ngRepeat.nodeValue.match(/ngRepeat:\s*([^\s]+)\s+in\s+([^\s|]+)/)
        item = $parse(ngRepeat[1])
        items = $parse(ngRepeat[2])
        item: (el) ->
          item angular.element(el).scope()

        items: items(scope)
        upd: ->
          items.assign scope, @items
          return
      'use strict'
      removed = undefined
      return (
        restrict: 'AC'
        link: (scope, $el, attrs) ->
          _sync = (evt) ->
            sortable.toArray().forEach (id, i) ->
              if _order[i] isnt id
                idx = _order.indexOf(id)
                if idx is -1
                  remoteSource = getSource(evt.from)
                  idx = remoteSource.items.indexOf(remoteSource.item(evt.item))
                  removed = remoteSource.items.splice(idx, 1)[0]
                  _order.splice i, 0, id
                  source.items.splice i, 0, removed
                  remoteSource.upd()
                  evt.from.appendChild evt.item # revert element
                else
                  _order.splice i, 0, _order.splice(idx, 1)[0]
                  source.items.splice i, 0, source.items.splice(idx, 1)[0]
              return

            source.upd()
            scope.$apply()
            return
          el = $el[0]
          options = scope.$eval(attrs.wmSortable) or {}
          _order = []
          source = getSource(el)
          'Start End Add Update Remove Sort'.split(' ').forEach (name) ->
            options['on' + name] = options['on' + name] or ->

            return

          sortable = Sortable.create(el, Object.keys(options).reduce((opts, name) ->
            opts[name] = opts[name] or options[name]
            opts
          ,
            onStart: ->
              $rootScope.$broadcast 'sortable:start', sortable
              options.onStart()
              return

            onEnd: ->
              $rootScope.$broadcast 'sortable:end', sortable
              options.onEnd()
              return

            onAdd: (evt) ->
              _sync evt
              options.onAdd source.items, removed
              return

            onUpdate: (evt) ->
              source = getSource(el)
              _sync evt
              options.onUpdate source.items, source.item(evt.item)
              return

            onRemove: (evt) ->
              options.onRemove source.items, removed
              return

            onSort: ->
              options.onSort source.items
              return
          ))
          $rootScope.$on 'sortable:start', ->
            _order = sortable.toArray()  if arguments[1] is sortable
            return

          $el.on '$destroy', ->
            el.sortable = null
            sortable.destroy()
            return

          return
      )
  ]
  return
