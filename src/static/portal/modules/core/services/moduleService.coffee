define [
  'core/coreModule'
  'wm/config'
], (mod, config) ->
  mod.factory 'moduleService', [
    '$rootScope'
    'restService'
    '$q'
    ($rootScope, restService, $q) ->
      module = {}
      rvm = $rootScope
      module.getConfig = ->
        defered = $q.defer()
        if rvm.conf
          defered.resolve rvm.conf
        else
          restService.get config.resources.moduleConfig, {}, (data) ->
            defered.resolve data

        defered.promise

      module
  ]
