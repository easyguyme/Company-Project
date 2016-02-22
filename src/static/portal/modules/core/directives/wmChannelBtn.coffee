define ['core/coreModule'], (mod) ->
  mod.directive('wmChannelBtn', [
    '$modal'
    ($modal) ->
      return (
        restrict: 'A'
        replace: true
        scope:
          # Original URL without oauth
          url: '@'
          onSelect: '&'
          ngModel: '='
        template: '<span class="rel ib" ng-click="clicked=true">
                    <wm-checkbox ng-model="ngModel"></wm-checkbox>{{"use_oauth_link"|translate}}
                  </span>'
        link: (scope, elem) ->
          openModal = ->
            modalInstance = $modal.open(
              templateUrl: '/build/modules/core/partials/selectChannelModal.html'
              controller: 'wm.ctrl.core.selectChannel'
              windowClass: "select-channel-dialog"
              resolve:
                modalData: ->
                  params =
                    channelId: scope.channelId
                    url: scope.url
                  params
            )

            modalInstance.result.then (data) ->
              scope.oauthLink = data
              scope.onSelect()(true, scope.oauthLink)
            return

          scope.$watch 'ngModel', (val) ->
            if val and scope.clicked
              openModal()
            else if val is false
              scope.onSelect()(false, scope.oauthLink)

      )
  ])
  return
