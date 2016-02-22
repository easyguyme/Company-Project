define ['core/coreModule'], (mod) ->
  mod.directive 'wmBeforeUnload', [
    '$window'
    '$state'
    '$translate'
    '$rootScope'
    ($window, $state, $translate, $rootScope) ->
      return (
        restrict: 'A'
        scope: {
          wmBeforeUnload: '=' # boolean, true means changes have been saved
        }
        link: (scope, elem, attr) ->
          shareTip = ''
          rvm = $rootScope
          rvm.$on '$translateChangeSuccess', ->
            $translate('reload_tip').then (tip) ->
              shareTip = tip
              return
            return
          cacheParams = rvm.currentState.params # cache the current params

          $translate('reload_tip').then (tip) ->
            shareTip = tip
            not $window.onbeforeunload and ($window.onbeforeunload = (e) ->
              needVerify = $('html').html().indexOf('wm-before-unload') > -1
              saved = scope.wmBeforeUnload # true if the page have been saved (clicked ok/save/publish button) otherwise false
              if needVerify and not saved
                e = e or window.event
                e.returnValue = shareTip if e
                shareTip
            )
            # $on will return a deregistration function for itself
            cancelListen = scope.$on '$stateChangeStart', (e, next) ->
              needVerify = $('html').html().indexOf('wm-before-unload') > -1
              saved = scope.wmBeforeUnload # true if the page have been saved (clicked ok/save/publish button) otherwise false
              curState = $state.current.name
              ok = true
              if curState isnt next.name
                if needVerify and not saved and $window.confirm
                  ok = $window.confirm(shareTip)
                if not ok
                  e.preventDefault()
                  # do not edit currentState's params, or the id will be undefined
                  rvm.currentState =
                    isChannel: curState.split('-')[0] is 'channel'
                    name: curState
                    params: cacheParams
                  $state.go curState
                else # leave currentPage
                  $window.onbeforeunload = null
                  cancelListen()
            return

          return
      )
  ]
  return
