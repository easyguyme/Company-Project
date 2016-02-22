define [
  'wm/app'
  'wm/config'
], (app, config) ->
  app.registerController 'wm.ctrl.content.graphics', [
    'restService'
    '$modal'
    '$scope'
    '$location'
    'notificationService'
    'debounceService'
    (restService, $modal, $scope, $location, notificationService, debounceService) ->
      vm = this

      vm.isShowCreateIcon = false
      vm.graphics = []        # init grapgics.
      vm.isload = false       # mark lazyload.
      currentPage = 0         # current page.
      pageSize = 8            # page size.
      pageCount = 1
      vm.tabVal = 0           # init tabs`s link.

      vm.breadcrumb = [
        'graphics_content'
      ]

      vm.tabs =
        [
          {
            active: true
            name: 'content_graphics_all'
            template: 'graphicsAll.html'
          }
          {
            active: false
            name: 'content_graphics_single_graphics'
            template: 'graphicsSingle.html'
          }
          {
            active: false
            name: 'content_graphics_multiple_graphics'
            template: 'graphicsMultiple.html'
          }
        ]
      active = if $location.search().active then parseInt $location.search().active else 0
      $location.search {active: active}
      vm.curTab = if active then vm.tabs[active] else vm.tabs[0]

      vm.waterfallOptions =
        transitionDuration: '0.1s'
        itemSelector: '.waterfall-item'
        gutter: 10,
        isFitWidth: true

      getGraphics = (where) ->
        if currentPage < pageCount
          condition =
            'per-page': pageSize
            'page': currentPage + 1
          condition.where = where if where
          restService.get config.resources.graphics, condition, (data) ->
            pageCount = data._meta.pageCount
            currentPage = data._meta.currentPage
            vm.graphics = vm.graphics.concat(data.items)
            vm.isShowCreateIcon = vm.graphics.length is 0
            return
        return

      getAllGraphics = ->
        getGraphics()
        return

      getSingleGrapgics = ->
        getGraphics({'type': 'single'})
        return

      getMultipleGraphics = ->
        getGraphics({'type': 'multiple'})
        return

      _init = ->
        getAllGraphics() if active is 0
        getSingleGrapgics() if active is 1
        getMultipleGraphics() if active is 2
        return
      _init()

      vm.changeTab = ->
        vm.isShowCreateIcon = false
        vm.graphics = []
        vm.tabVal = $location.search().active.toString()
        currentPage = 0
        pageCount = 1
        if vm.tabVal is '0'
          getAllGraphics()
        else if vm.tabVal is '1'
          getSingleGrapgics()
        else
          getMultipleGraphics()
        $scope.$broadcast 'reform-waterfall'
        return

      vm.editGraphic = (id) ->
        $location.path '/content/edit/graphics/' + id

      vm.createGraphic = ->
        vm.tabVal = $location.search().active
        if vm.tabVal is 0
          vm.openGraphic()
        else if vm.tabVal is 1
          $location.path('/content/edit/graphics').search('type', 0)
        else
          $location.path('/content/edit/graphics').search('type', 1)

      vm.deleteGraphic = (id, $event) ->
        notificationService.confirm $event,{
          submitCallback: deleteGraphicHandler,
          params: [id]
        }

      deleteGraphicHandler = (id) ->
        for idx, graphic of vm.graphics
          if graphic.id is id
            restService.del config.resources.graphic + '/' + id, ->
              vm.graphics.splice idx, 1
              notificationService.success 'content_graphics_delete_success'
            break
          vm.isShowCreateIcon = vm.graphics.length is 0
        return

      $(window).scroll debounceService.callback( ->
        vm.isload = true
        rootRout = $location.path()
        vm.tabVal = parseInt $location.search().active
        if rootRout is '/content/graphics'
          if $(document).height() - $(this).scrollTop() - $(this).height() <= 10
            if vm.tabVal is 0
              getAllGraphics()
            else if vm.tabVal is 1
              getSingleGrapgics()
            else
              getMultipleGraphics()
            $scope.$broadcast 'reform-waterfall'
            return
          return vm.isload = false
      , 200)

      vm.openGraphic = ->
        modalInstance = $modal.open(
          templateUrl: 'createGraphic.html'
          controller: 'wm.ctrl.microsite.createGraphic'
          windowClass: 'graphics-dialog'
          resolve:
            modalData: ->
        ).result.then( (data) ->
          console.log data
          return
        )
      vm
  ]
  .registerController 'wm.ctrl.microsite.createGraphic', [
    '$scope'
    '$modalInstance'
    '$location'
    ($scope, $modalInstance, $location) ->
      vm = $scope

      vm.hideModal = ->
        $modalInstance.close()

      vm.editSingleGraphic = ->
        $modalInstance.dismiss()
        $location.path('/content/edit/graphics').search('type', 0)

      vm.editMutilpleGraphic = ->
        $modalInstance.dismiss()
        $location.path('/content/edit/graphics').search('type', 1)
      vm
    ]
