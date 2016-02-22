define [
  'wm/app'
  'wm/config'
], (app, config) ->
  app.registerController 'wm.ctrl.microsite.viewGraphic', ['restService'
  '$modal'
  '$scope'
  '$sce'
  '$stateParams'
    (restService, $modal, $scope, $sce, $stateParams) ->
      vm = this

      graphicId = $stateParams.id
      vm.graphic  = []

      _init = () ->
        restService.get config.resources.graphic + "/" + graphicId, (data)->
          vm.graphic = data
          vm.content = $sce.trustAsHtml vm.graphic.articles[0]?.content
          return
        return
      _init()

      vm
  ]

