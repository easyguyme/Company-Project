define [
  'wm/app'
  'wm/config'
], (app, config) ->
  app.registerController 'wm.ctrl.content.edit.graphics', [
    '$scope'
    '$location'
    '$stateParams'
    '$timeout'
    '$window'
    'restService'
    'notificationService'
    ($scope, $location, $stateParams, $timeout, $window, restService, notificationService) ->
      vm = this
      vm.type = 0
      vm.types =
        single: 0
        multiple: 1
      vm.isEdit = true
      vm.isSaved = false # the param which decides if the alarm will be triggered when leaving page
      host = $location.protocol() + '://' + $location.host()
      defaultAvatar = host + '/images/content/default.png'
      defaultSmallAvatar = host + '/images/content/default_small.png'
      vm.selectedIndex = 0
      vm.default =
        title: "标题"
        description: "简介"
        content: "正文"
        picUrl: defaultAvatar
        smallPicUrl: defaultSmallAvatar
      id = 1

      vm.ueditorConfig =
        initialStyle: 'p{word-wrap: break-word;word-break: break-word;}'

      uuid = ->
        id++

      _init = ->
        query = $location.search()
        vm.type = parseInt(query.type) or vm.types.single
        vm.graphicId = $stateParams.id

        vm.breadcrumb = [
          text: 'content_management'
          href: '/content/graphics'
          ,
          vm.getTitle()
        ]

        if vm.graphicId
          restService.get config.resources.graphic + '/' + vm.graphicId, (data) ->
            delete data.usedCount
            delete data.createdAt
            if data.articles.length > 1
              vm.type = vm.types.multiple
            angular.forEach data.articles, (article) ->
              article.id = uuid()
            vm.graphic = data
            _initData()
            return
        else
          if vm.type is vm.types.multiple
            vm.graphic =
              articles: [
                id: uuid()
                title: ''
                content: ''
                picUrl: defaultAvatar
                showCoverPic: true
              ,
                id: uuid()
                title: ''
                content: ''
                picUrl: defaultSmallAvatar
                showCoverPic: true
              ]
          else
            vm.graphic =
              articles: [
                id: uuid()
                title: ''
                description: ''
                content: ''
                picUrl: defaultAvatar
                showCoverPic: true
              ]
          _initData()
        return

      _initData = ->
        vm.selectedGraphic = vm.graphic.articles[0]
        if vm.type is vm.types.multiple
          setTrangle 0
        return

      vm.getTitle = ->
        bascTitle = 'content_graphics_title_'
        if vm.graphicId
          bascTitle += 'edit'
        else
          bascTitle += 'create'
        if vm.type is vm.types.multiple
          bascTitle += '_multi'
        bascTitle

      vm.submit = ->
        angular.forEach vm.graphic.articles, (article) ->
          if article.id is vm.selectedGraphic.id
            article = angular.extend article, vm.selectedGraphic
          return
        saveGraphic vm.graphic
        return

      vm.selectGraphic = (index) ->
        if vm.graphic.articles?.length isnt index
          vm.selectedGraphic = vm.graphic.articles[index]
        else
          vm.selectedGraphic =
            id: uuid()
            title: ''
            description: ''
            picUrl: host + '/images/content/default_small.png'
            showCoverPic: true
          vm.graphic.articles.push vm.selectedGraphic
        setTrangle index
        $scope.$broadcast 'clearValidityError'
        return

      vm.deleteGraphic = (index) ->
        len = vm.graphic.articles.length
        if len <= 2
          notificationService.warning "content_graphics_delete_error", false
          return
        vm.graphic.articles.splice index, 1
        notificationService.success "content_graphics_delete_success", false
        vm.selectGraphic 0
        return

      saveGraphic = (data) ->
        graphic = angular.copy data
        for article, index in graphic.articles
          if not article.title or not article.picUrl or not article.content
            vm.selectGraphic index
            if not article.title and not article.content and article.picUrl is defaultSmallAvatar
              notificationService.warning "content_graphics_need_2_items", false
            $timeout ->
              $('#graphic-form')[0].checkValidity()
            , 300
            return
          delete article.id
        # after setting this param to true, user can leave page without warning
        # move changing the value of vm.isSaved to here in order to avoid issue #4067
        vm.isSaved = true
        if graphic.id
          restService.put config.resources.graphic + '/' + vm.graphicId, graphic, (data) ->
            notificationService.success "content_graphics_save_success", false
            afterSave data
            return
          (err) ->
            vm.isSaved = false
        else
          restService.post config.resources.graphics, graphic, (data) ->
            notificationService.success "content_graphics_save_success", false
            afterSave data
            return
          (err) ->
            vm.isSaved = false
        return

      afterSave = (data) ->
        vm.graphic.id = data.id
        vm.graphicId = data.id
        $window.location.href = '/content/graphics?active=' + ($location.search().active or 0)
        return

      setTrangle = (index) ->
        vm.selectedIndex = index
        innerHeight = $('.waterfall-news-list-inner').height() + 18
        itemHeight = $('.waterfall-news-list-item:eq(' + index + ')').height()

        if index is 0
          top = innerHeight / 2 - 14
        else
          top = innerHeight + itemHeight * (index - 0.5) - 14

        if top > 1
          $('.front-triangle').animate 'top': top + 'px'
          $('.back-triangle').animate 'top': top - 1 + 'px'
        gotoTop()
        return

      gotoTop = ->
        $('body').animate {scrollTop: 0}, 200

      _init()
      vm
  ]
