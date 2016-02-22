define [
  'wm/app'
  'wm/config'
  'wm/modules/microsite/controllers/articleChannelCtrl'
], (app, config) ->
  app.registerController 'wm.ctrl.microsite.webpage', [
    'restService'
    (restService) ->
      vm = this
      vm.tabs = [
        {
          active: true
          name: 'content_page_management'
          template: 'pages.html'
        }
        {
          active: false
          name: 'content_articles_management'
          template: 'articles.html'
        }
      ]

      vm.breadcrumb = [
        icon: 'webpage'
        text: 'webpage_content'
      ]

      vm
  ]
  .registerController 'wm.ctrl.microsite.articles', [
    'restService'
    '$modal'
    'notificationService'
    '$filter'
    'canvasService'
    '$location'
    '$scope'
    (restService, $modal, notificationService, $filter, canvasService, $location, $scope) ->
      vm = this

      _init = ->
        vm.isShowQrcodeDropdown = false
        vm.currentPage = $location.search().currentPage or 1
        vm.totalItems = 0
        vm.pageSize = $location.search().pageSize or 10

        vm.channelId = $location.search().channel if $location.search()?.channel?

        vm.channelManagement = 'content_articles_channel_management'

        vm.articlesList =
          columnDefs: [
            {
              field: 'name'
              label: 'content_articles_title'
              type: 'link'
            }, {
              field: 'url'
              label: 'content_articles_url'
              type: 'description'
            }, {
              field: 'createdBy'
              label: 'content_articles_author'
              type: 'description'
            }, {
              field: 'createdAt'
              label: 'customer_card_create_time'
              sortable: true
              desc: true
              type: 'date'
            }
          ],
          data: []
          operations: [
            {
              name: 'edit'
            }, {
              name: 'qrcode'
            }, {
              name: 'delete'
            }
          ],
          selectable: false
          sortHandler: (colDef) ->
            key = colDef.field
            value = if colDef.desc then 'desc' else 'asc'
            vm.orderBy = '{"' + key + '":' + '"' + value + '"}'
            vm.currentPage = 1
            _getArticles()
          editHandler: (idx) ->
            $location.url '/microsite/article/edit/webpage/' + vm.articlesList.data[idx].id + '?channel=' + vm.activedChannel.id
            return
          qrcodeHandler: (idx, $event) ->
            vm.isShowQrcodeDropdown = not vm.isShowQrcodeDropdown
            if vm.isShowQrcodeDropdown
              article = vm.articlesList.data[idx]
              vm.qrcodePaneTop = $($event.target).offset().top - 20 - $('.portal-message').height()
              if article
                vm.downQrcodeName = article.name.text
                vm.qrcodeUrl = article.url
            return
          deleteHandler: (idx) ->
            id = vm.articlesList.data[idx]?.id
            restService.del config.resources.article + '/' + id, (data) ->
              notificationService.success 'microsite_article_delete_success'
              _getArticles()
            return

        _getChannels()

        return

      _getChannels = ->
        condition =
          'orderBy': {'createdAt': 'asc'}
          'per-page': 100
        restService.get config.resources.articleChannels, condition, (data) ->
          if data.items
            vm.channels = angular.copy data.items
            for channel in vm.channels
              if channel.isDefault
                channel.name = $filter('translate')(channel.name)
                vm.defaultChannel = angular.copy channel
                vm.activedChannel = angular.copy channel if not vm.channelId?
              if vm.channelId and vm.channelId is channel.id
                vm.activedChannel = angular.copy channel
            _getArticles()

      _getArticles = ->
        channels = []
        channels.push vm.activedChannel.id if vm.activedChannel
        condition =
          'channels': JSON.stringify channels
          'per-page': vm.pageSize
          'page': vm.currentPage
        condition.orderBy = angular.copy vm.orderBy if vm.orderBy
        restService.get config.resources.articles, condition, (data) ->
          if data.items
            articles = []
            angular.forEach data.items, (item) ->
              if item.name
                item.name =
                  text: item.name
                  link: '/microsite/article/view/webpage/' + item.id
              articles.push item
            vm.articlesList.data = angular.copy articles
            vm.currentPage = data._meta.currentPage
            vm.totalItems = data._meta.totalCount
            vm.pageSize = data._meta.perPage
            vm.pageCount = data._meta.pageCount
        return

      vm.createArticle = ->
        $location.url '/microsite/article/edit/webpage?channel=' + vm.activedChannel.id

      vm.changePage = (currentPage) ->
        vm.currentPage = currentPage
        _getArticles()

      vm.changeSize = (pageSize) ->
        vm.pageSize = pageSize
        vm.currentPage = 1
        _getArticles()

      vm.changeTab = ->
        if vm.tabs[0].active is true
          console.log vm.curTab
        else
          console.log vm.curTab

      vm.changeChannel = (index) ->
        if not vm.channelEdit
          vm.activedChannel = vm.channels[index]
          _getArticles()
        return

      vm.editChannel = (index) ->
        param =
          channels: angular.copy vm.channels
          index: index
          isEdit: true
        modalInstance = $modal.open(
          templateUrl: '/build/modules/microsite/partials/articleChannelModal.html'
          controller: 'wm.ctrl.microsite.editArticles'
          windowClass: 'tagedit-dialog'
          resolve:
            modalData: -> param
        ).result.then( (data) ->
          if data?
            vm.activedChannel = data if vm.activedChannel.id is vm.channels[index].id
            vm.channels[index] = data
        )

      _deleteChannel = (index) ->
        deleteChannel = vm.channels[index]
        restService.del config.resources.articleChannel + '/' + vm.channels[index].id, (data) ->
          if data.length is 0
            vm.activedChannel = vm.defaultChannel if vm.activedChannel.id is deleteChannel.id
            notificationService.success 'content_articles_channel_delete'
            vm.channels.splice index, 1
          else
            values =
              articles: data
            notificationService.error 'cannot_delete_channel', false, values
      vm.deleteChannel = (index, $event) ->
        notificationService.confirm $event,{
          submitCallback: _deleteChannel
          params: [index]
        }

      vm.newChannel = ->
        param =
          channels: angular.copy vm.channels
          index: -1
          isEdit: false
        modalInstance = $modal.open(
          templateUrl: '/build/modules/microsite/partials/articleChannelModal.html'
          controller: 'wm.ctrl.microsite.editArticles'
          windowClass: 'tagedit-dialog'
          resolve:
            modalData: -> param
        ).result.then( (data) ->
          vm.channels.push data if data?
        )
      vm.editTags = ->
        vm.channelEdit = not vm.channelEdit
        if vm.channelEdit
          vm.channelManagement = 'content_articles_cancel_management'
        else
          vm.channelManagement = 'content_articles_channel_management'

      vm.channelEdit = false

      _init()
      return vm
  ]

  .registerController 'wm.ctrl.microsite.pages', [
    'restService'
    'notificationService'
    '$location'
    'canvasService'
    '$scope'
    (restService, notificationService, $location, canvasService, $scope) ->
      vm = this

      _init = ->
        vm.isShowQrcodeDropdown = false
        vm.currentPage = $location.search().currentPage or 1
        vm.totalItems = 0
        vm.pageSize = $location.search().pageSize or 10

        vm.pagesList =
          columnDefs: [
            {
              field: 'title'
              label: 'content_page_name'
              type: 'link'
            }, {
              field: 'shortUrl'
              label: 'content_articles_url'
              type: 'description'
            }, {
              field: 'createdBy'
              label: 'content_articles_author'
              type: 'translate'
            }, {
              field: 'createdAt'
              label: 'customer_card_create_time'
              sortable: true
              desc: true
              type: 'date'
            }, {
              field: 'operations'
              label: 'operations'
              type: 'operation'
            }
          ],
          data: []
          selectable: false
          sortHandler: (colDef) ->
            key = colDef.field
            value = if colDef.desc then 'desc' else 'asc'
            vm.orderBy = '{"' + key + '":' + '"' + value + '"}'
            vm.currentPage = 1
            _getPages()
          editHandler: (idx) ->
            $location.url '/microsite/page/edit/webpage/' + vm.pagesList.data[idx].id
            return
          qrcodeHandler: (idx, $event) ->
            vm.isShowQrcodeDropdown = not vm.isShowQrcodeDropdown
            if vm.isShowQrcodeDropdown
              page = vm.pagesList.data[idx]
              vm.qrcodePaneTop = $($event.target).offset().top - 20 - $('.portal-message').height()
              if page
                vm.downQrcodeName = page.title.text
                vm.qrcodeUrl = page.shortUrl
            return
          deleteHandler: (idx) ->
            id = vm.pagesList.data[idx]?.id
            restService.del config.resources.page + '/' + id, (data) ->
              notificationService.success 'microsite_pages_delete_success'
              _getPages()
            return
        _getPages()
        return

      _getPages = ->
        condition =
          'per-page': vm.pageSize
          'page': vm.currentPage
          'where':
            'isFinished': true
        condition.orderBy = angular.copy vm.orderBy if vm.orderBy
        restService.get config.resources.pages, condition, (data) ->
          if data.items
            pages = []
            angular.forEach data.items, (item) ->
              if item.title
                item.title =
                  text: item.title
                  link: '/microsite/page/view/webpage/' + item.id
              item.operations = [
                {
                  name: 'edit'
                }, {
                  name: 'qrcode'
                }
              ]
              item.operations.push {name: 'delete'} if item.type isnt 'cover'
              if item.creator
                if item.creator.name
                  item.createdBy = item.creator.name
                else
                  item.createdBy = 'content_no_creator'
              pages.push item
            vm.pagesList.data = angular.copy pages
            vm.currentPage = data._meta.currentPage
            vm.totalItems = data._meta.totalCount
            vm.pageSize = data._meta.perPage
            vm.pageCount = data._meta.pageCount

        return

      vm.changePage = (currentPage) ->
        vm.currentPage = currentPage
        _getPages()

      vm.changeSize = (pageSize) ->
        vm.pageSize = pageSize
        vm.currentPage = 1
        _getPages()

      _init()
      vm

  ]
