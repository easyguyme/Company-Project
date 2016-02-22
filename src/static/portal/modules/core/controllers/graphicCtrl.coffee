define [
  'core/coreModule'
], (mod) ->
  mod.controller 'wm.ctrl.core.graphic', [
    'restService'
    '$scope'
    '$timeout'
    'debounceService'
    '$modalInstance'
    'path'
    (restService, $scope, $timeout, debounceService, $modalInstance, path) ->

      $scope.graphicList = []
      $scope.isload = false       # mark lazyload.
      $scope.currentPage = 0
      $scope.pageSize = 7
      $scope.pageCount = 1

      _fetch = ->
        if $scope.currentPage < $scope.pageCount
          params =
            'per-page': $scope.pageSize
            'page': $scope.currentPage + 1
          params.where = JSON.stringify {type: $scope.currentType} if $scope.currentType isnt "all"
          params.search = JSON.stringify {"articles.title": $scope.searchKey} if $scope.searchKey
          restService.get path, params, (data) ->
            $scope.currentPage = data._meta.currentPage
            $scope.pageCount = data._meta.pageCount
            $scope.graphicList = $scope.graphicList.concat(data.items)
            $scope.$broadcast "reform-waterfall"
            return
          return

      _init = ->
        $scope.currentType = "all"
        $scope.types = [
          text: "content_graphics_all",
          value: "all"
        ,
          text: "content_graphics_single_graphics",
          value: "single"
        ,
          text: "content_graphics_multiple_graphics",
          value: "multiple"
        ]
        _fetch()
        return

      _init()

      $scope.waterfallOptions =
        transitionDuration: "0.4s"
        itemSelector: ".waterfall-item"

      $scope.cancel = ->
        $modalInstance.dismiss()
        return

      $scope.select = (graphic, index) ->
        $scope.selectedIndex = index
        $scope.selectedGraphic = graphic

      $scope.submit = ->
        $modalInstance.close($scope.selectedGraphic)

      $scope.selectType = (value) ->
        $scope.graphicList = []
        $scope.currentPage = 0
        $scope.pageSize = 7
        $scope.pageCount = 1
        $scope.currentType = value
        _fetch()

      $scope.search = ->
        params =
          'per-page': $scope.pageSize
          'page': 1
        params.where = JSON.stringify {type: $scope.currentType} if $scope.currentType isnt "all"
        params.search = JSON.stringify {"articles.title": $scope.searchKey} if $scope.searchKey
        restService.get path, params, (data) ->
          $scope.currentPage = data._meta.currentPage
          $scope.pageCount = data._meta.pageCount
          $scope.graphicList = data.items
          $scope.$broadcast "reform-waterfall"
          return
        return

      $timeout( ->
        $('.modal-waterfall-wrap').scroll debounceService.callback(->
          if $(this).scrollTop() + $(this).height() + 200 > $('.waterfall').height()
            $scope.isload = true
            _fetch()
            $scope.$broadcast 'reform-waterfall'
            return $scope.isload = false
        )
      , 1000)

      return
  ]
