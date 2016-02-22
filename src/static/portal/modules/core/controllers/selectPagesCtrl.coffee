define [
  'core/coreModule'
  'wm/config'
], (mod, config) ->
  mod.controller 'wm.ctrl.core.selectPages', [
    'restService'
    '$scope'
    '$modalInstance'
    'modalData'
    'debounceService'
    '$timeout'
    'notificationService'
    (restService, $scope, $modalInstance, modalData, debounceService, $timeout, notificationService) ->
      vm = $scope

      _init = ->
        _initPagination()
        vm.tabs = [
          {
            name: 'wechat_menu_page'
          },{
            name: 'wechat_menu_article'
          },{
            name: 'wechat_menu_search'
          }
        ]
        vm.tabActive = vm.tabs[0]
        if modalData
          vm.selectedContent = modalData.url

        _getPages()

      _getPages = ->
        condition =
          'per-page': vm.pageSize
          'page': vm.currentPage
          'where':
            'isFinished': true
        location = config.resources.pages
        if vm.tabActive is vm.tabs[1]
          delete condition.where
          if vm.channel isnt 'all'
            channels = []
            channels.push vm.channel
            condition.channels = JSON.stringify channels
          location = config.resources.articles
        else if vm.tabActive is vm.tabs[2]
          delete condition.where
          delete condition.page
          condition.searchKey = encodeURIComponent vm.content
          condition.timeFrom = vm.timeFrom if vm.timeFrom
          location = config.resources.searchPagesAndArticles
        restService.get location, condition, (data) ->
          if data.items
            if vm.tabActive in [vm.tabs[0], vm.tabs[1]]
              vm.totalPages = data._meta.pageCount
              for item in angular.copy data.items
                if vm.tabActive is vm.tabs[0]
                  vm.pages.push {
                    title: item.title,
                    url: item.shortUrl,
                    type: 'page'
                  }
                else if vm.tabActive is vm.tabs[1]
                  vm.pages.push {
                    title: item.name,
                    url: item.url,
                    type: 'article'
                  }
            else
              if data.timeFrom
                vm.timeFrom = data.timeFrom
                for item in angular.copy data.items
                  vm.pages.push item
              else
                vm.searchEnd = true
            _checkContent()

      _checkContent = ->
        if vm.selectedContent
          for page, index in vm.pages
            if vm.selectedContent is page.url
              vm.pageFlag = index
              vm.trueData =
                title: page.title
                url: page.url
          return
        return

      vm.changeTab = ->
        _initPagination()
        if vm.tabActive is vm.tabs[0]
          _getPages()
        else if vm.tabActive is vm.tabs[1]
          _selectDropdown()
        else
          vm.showSearchInit = true
          vm.searchContent = ''
          vm.content = ''
        return

      vm.selectPage = (idx) ->
        if vm.pageFlag is idx
          delete vm.pageFlag
          delete vm.trueData
          vm.selectedContent = ''
        else
          vm.pageFlag = idx
          vm.selectedContent = vm.pages[idx].url
          vm.trueData = vm.pages[idx]

      _selectDropdown = ->
        condition =
          'orderBy': {'createdAt': 'asc'}
        restService.get config.resources.articleChannels, condition, (data) ->
          if data.items
            vm.channels = [
              {
                text: 'core_all_channels',
                value: 'all'
              }
            ]
            for item in angular.copy data.items
              vm.channels.push {
                text: item.name,
                value: item.id
              }
            vm.channel = vm.channels[0].value
            _getPages()

      vm.changeChannel = (value, index) ->
        vm.channel = value
        _initPagination()
        _getPages()

      vm.searchLink = ->
        if vm.searchContent
          vm.showSearchInit = false
          vm.content = vm.searchContent
          _initPagination()
          _getPages()

      vm.ok = ->
        if vm.trueData
          $modalInstance.close vm.trueData
        else
          notificationService.warning 'store_staff_select_tip', false
        return

      _initPagination = ->
        vm.currentPage = 1
        vm.totalPages = 0
        vm.pageSize = 10
        delete vm.pageFlag
        vm.pages =  []
        delete vm.timeFrom
        vm.searchEnd = false
        vm.showSearchInit = false

      vm.hideModal = ->
        $modalInstance.dismiss('cancel')
        return

      $timeout( ->
        $('.body-block').scroll debounceService.callback( ->
          if $(".body-block")[0].scrollHeight - $(".body-block")[0].clientHeight - $(".body-block")[0].scrollTop < 20
            if vm.tabActive in [vm.tabs[0], vm.tabs[1]]
              if vm.currentPage < vm.totalPages
                vm.currentPage += 1
                _getPages()
            else
              if not vm.searchEnd and vm.searchContent
                _getPages()
        )
      , 1000)

      _init()

  ]
