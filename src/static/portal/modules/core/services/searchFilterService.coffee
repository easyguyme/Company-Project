define [
  'core/coreModule'
  'wm/config'
], (mod, config) ->
  mod.factory 'searchFilterService', [
    '$location'
    ($location) ->
      search = {}

      search.setFilter = (key, value) ->
        search[key] = JSON.stringify(value)
        $location.search('search', 't')

      search.getFilter = (key) ->
        JSON.parse(search[key]) if search[key]

      search.clearFilter = (key) ->
        search[key] = null if search[key]

      search
  ]
