define [
  'wm/app'
  'wm/config'
  'core/directives/wmCharts'
  'core/directives/wmSortable'
], (app, config) ->
  menuService = ( ->
    _animateDuration = 300 # miliseconds used when scrolling to top

    _allStatus =
      show: 'show'
      edit: 'edit'

    _allTypes =
      msg: 'CLICK'
      url: 'VIEW'
      ext: 'EXT'
      webhook: 'WEBHOOK'
      spread: 'SPREAD'

    _toTop = ->
      $('html,body').animate {scrollTop: 0}, _animateDuration
      return

    _getType = (menu) ->
      type = ''
      if menu.keycode isnt menu.id
        type = _allTypes.ext
      else
        switch menu.type
          when 'VIEW'
            type = _allTypes.url
          when 'CLICK'
            type = _allTypes.msg
          when 'SPREAD'
            type = _allTypes.spread
          else
            type = _allTypes.ext
      type

    _getStringLength = (str) ->
      len = 0
      arr = str.split('')
      i = 0

      while i < arr.length
        if arr[i].charCodeAt(0) < 299
          len++
        else
          len += 2
        i++
      len

    menuMaxLength = 8
    # check menu length
    _checkMenuLength = (menuName) ->
      len = _getStringLength menuName
      if len > menuMaxLength
        return 'wechat_menu_name_tip'

    # check submenu length
    submenuMaxLength = 16
    _checkSubmenuLength = (menuName) ->
      len = _getStringLength menuName
      if len > submenuMaxLength
        return 'wechat_submenu_name_tip'

    urlRegular = /^(ftp|http|https):\/\/([\w-]+\.)+(\w+)(:[0-9]+)?(\/|\/([\w#!:.?+=&%@!\-\/]+))?$/
    _checkUrl = (url) ->
      if not url or not urlRegular.test(url)
        return 'wechat_menu_link_format_error'

    allStatus: _allStatus
    allTypes: _allTypes
    toTop: _toTop
    getType: _getType
    checkUrl: _checkUrl
    checkMenuLength: _checkMenuLength
    checkSubmenuLength: _checkSubmenuLength
  )()

  app.registerController 'wm.ctrl.channel.menu', [
    'restService'
    '$scope'
    '$rootScope'
    '$stateParams'
    '$modal'
    'notificationService'
    '$filter'
    'storeService'
    '$location'
    (restService, $scope, $rootScope, $stateParams, $modal, notificationService, $filter, storeService, $location) ->
      vm = this
      rvm = $rootScope
      channelId = null
      vm.menus = []
      vm.selectedMenu = null
      vm.status = vm.type = vm.action = null
      vm.remainCharacter = 0
      vm.allStatus = menuService.allStatus
      vm.allTypes = menuService.allTypes
      # all select items
      vm.typeItems = []
      vm.extInfos = null
      vm.enablePublishButton = true

      # is not a spread menu which will create before long
      vm.isSpreadMenu = not not $location.search().spread
      if vm.isSpreadMenu
        spreadMenu = storeService.getMemoryItem 'menu'
        vm.isSpreadMenu = not not spreadMenu

      vm.breadcrumb = [
        {
          text: 'customized_menus'
          help: 'wechat_menu_info'
        }
      ]

      vm.links = [
        {
          text: 'wechat_menu_external_site_links',
          value: true
        }, {
          text: 'wechat_menu_site_links',
          value: false
        }
      ]

      angular.forEach vm.allTypes, (key) ->
        if key isnt vm.allTypes.spread
          vm.typeItems.push key
        return

      _init = ->
        channelId = $stateParams.id unless channelId?
        restService.get config.resources.menuaction, {channelId: channelId}, (actions) ->
          vm.extInfos = actions
          if isSubscriptionAccount rvm.currentChannel
            vm.extInfos = getSupportExts vm.extInfos
          _initData()
          return
        return

      isSubscriptionAccount = (currentChannel) ->
        return false if not currentChannel?
        if currentChannel.type isnt 'wechat'
          return false
        else
          return not currentChannel.isService

      # get supported extmodules for subscription account
      # which only support user center and helpdesk now
      getSupportExts = (extInfos) ->
        info = {}
        info['USER_CENTER'] = extInfos['USER_CENTER'] if extInfos['USER_CENTER']
        info['CUSTOMER_SERVICE'] = extInfos['CUSTOMER_SERVICE'] if extInfos['CUSTOMER_SERVICE']
        info

      _initData = ->
        restService.get config.resources.menus, {channelId: channelId}, (data) ->
          vm.menus = data.items.menus if data.items?.menus?
          vm.isPublish = data.items.status is 'PUBLISH'
          vm.types = []
          _initSelectedMenu()

          if data.keycodes
            angular.forEach data.keycodes, (keycode) ->
              vm.types.push vm.extInfos[keycode] if vm.extInfos[keycode]?

          if not vm.menus? or vm.menus.length < 1
            vm.isPublish = true
            vm.enablePublishButton = false
            return

          angular.forEach vm.menus, (menu) ->
            menu.ext = vm.extInfos[menu.keycode] if menu.keycode? and vm.extInfos[menu.keycode]?
            angular.forEach menu.subMenus, (submenu) ->
              submenu.ext = vm.extInfos[submenu.keycode] if submenu.keycode? and vm.extInfos[submenu.keycode]?
              return
            return
          return
        return

      # update selected menu data
      _initSelectedMenu = ->
        if vm.selectedMenu?.name
          # selected menu is used to update
          if vm.selectedMenu.id
            angular.forEach vm.menus, (menu) ->
              if vm.selectedMenu.id is menu.id
                # selected menu is main menu
                _updateSelectedMenu menu

              angular.forEach menu.subMenus, (submenu) ->
                if vm.selectedMenu.id is submenu.id
                  # selected menu is submenu
                  _updateSelectedMenu submenu
                return
              return
          else
            # selected menu is used to create
            if vm.selectedMenu.parentId
              # selected menu is submenu
              angular.forEach vm.menus, (menu) ->
                if vm.selectedMenu.parentId is menu.id
                  _updateSelectedMenu menu.subMenus[menu.subMenus.length - 1]
                return
            else
              # selected menu is main menu
              _updateSelectedMenu vm.menus[vm.menus.length - 1]
        return

      _updateSelectedMenu = (menu) ->
        ext = vm.selectedMenu.ext if vm.selectedMenu.ext
        vm.selectedMenu = angular.copy menu
        vm.selectedMenu.ext = ext if ext
        return

      # select a menu or submenu
      vm.activate = (menuItem, isTop = true) ->
        vm.trueMenu = angular.copy menuItem
        vm.selectedMenu = angular.copy menuItem
        vm.status = vm.allStatus.show
        vm.type = menuService.getType vm.selectedMenu
        vm.action = _initAction menuItem
        _resetFormTip(menuItem)
        menuService.toTop() if isTop
        if vm.selectedMenu.content and angular.isString(vm.selectedMenu.content)
          if (vm.selectedMenu.content.indexOf window.config.shortUrlDomain) >= 0
            vm.link = false
            vm.inLink =
              url: vm.selectedMenu.content
            condition =
              'url': encodeURIComponent vm.selectedMenu.content
            restService.get config.resources.searchTitle, condition, (data) ->
              if data
                vm.inLink.title = angular.copy data.title
          else
            vm.link = true
        return

      # this function is to remove the red border and red tip when change the menu/submenu
      _resetFormTip = (menuItem) ->
        $("div.highlight").removeClass("highlight") # when click another menu/submenu, delete the red tip
        $("input.form-control-error").removeClass("form-control-error") # the same, delete the red input border
        if menuItem.parentId?
          $("span.form-tip").addClass("normal").text($filter('translate')('wechat_submenu_name_tip')) # change the text in submenu's tip
        else
          $("span.form-tip").addClass("normal").text($filter('translate')('wechat_menu_name_tip')) # change the text in menu's tip

      _initAction = (menuItem) ->
        if menuItem.type is vm.allTypes.webhook or menuItem.type is vm.allTypes.spread
          return true
        if not menuItem.content
          if menuItem.keycode and vm.extInfos[menuItem.keycode]
            return true
          return false
        return true

      vm.add = (parentId) ->
        menuItem =
          name: ''
          subMenus: []
        if parentId
          menuItem.parentId = parentId

        if vm.isSpreadMenu
          vm.link = true
          params =
            content: spreadMenu.content
            contentName: spreadMenu.contentName
            type: 'SPREAD'
          menuItem = $.extend true, {}, menuItem, params
        vm.action = _initAction menuItem
        vm.status = vm.allStatus.edit
        vm.selectedMenu = menuItem
        _resetFormTip(menuItem)
        menuService.toTop()
        return

      # save the newly created menu or save the modified menu.
      vm.saveMenus = ->
        # to make sure the content isn't empty otherwise will notice a warning
        # edited by Woody Hu in 20150906 19:30
        # webhook's content can be null
        if not vm.selectedMenu?.type isnt 'WEBHOOK' and vm.selectedMenu?.content is ''
          notificationService.warning 'wechat_menu_content_missing', false
          return
        # warn user when the input characters more than wechat max length
        if vm.remainCharacter? and vm.remainCharacter < 0
          notificationService.warning 'wechat_menu_content_too_long', false
          return

        createTip = 'wechat_menu_create_success'
        updateTip = 'wechat_menu_update_success'

        if not _checkMenu vm.selectedMenu
          return

        if vm.selectedMenu.id
          # edit menu
          _mockMsgType()
          menuId = vm.selectedMenu.id
          angular.forEach vm.menus, (menu) ->
            if menu.id is menuId
              menu = angular.extend menu, vm.selectedMenu
              _saveMenus(updateTip)

            angular.forEach menu.subMenus, (submenu) ->
              if submenu.id is menuId
                submenu = angular.extend submenu, vm.selectedMenu
                _saveMenus(updateTip)
              return
            return
        else if vm.selectedMenu.parentId
          # create submenu
          parentId = vm.selectedMenu.parentId
          angular.forEach vm.menus, (menu) ->
            if menu.id is parentId
              if menu.subMenus.length is 0
                delete menu.type
                delete menu.msgType
                delete menu.content
                menu.keycode = menu.id # remove the keycode, otherwise the keycode of parentmenu may be the same as submenu's
              _mockId()
              _mockMsgType()
              menu.subMenus.push vm.selectedMenu
              _saveMenus(createTip)
        else
          _mockId()
          _mockMsgType()
          # create main menu
          vm.menus.push vm.selectedMenu
          _saveMenus(createTip)
        return

      # mock an id for currnet selectedMenu
      _mockId = ->
        vm.selectedMenu.id = 'mock' + new Date().valueOf() # add a mock id to created main menu so it can contain sub menu and can be edited

      # detect and mock an msgType for current selectedmenu to see the chagnes
      _mockMsgType = ->
        vm.selectedMenu.msgType = 'TEXT' if typeof vm.selectedMenu.content is 'string' and vm.selectedMenu.type is 'CLICK'
        vm.selectedMenu.msgType = 'NEWS' if typeof vm.selectedMenu.content is 'object' and vm.selectedMenu.type is 'CLICK'


      # return false if the menu doesn't have a name, or the name length is too long
      # return false if the action of menu is jump to a url and the url is invalid
      # otherwise return true
      _checkMenu = (menu) ->
        result = true
        if not menu.name
          result = false
        else if menu.parentId
          if menuService.checkSubmenuLength menu.name
            result = false
        else
          if menuService.checkMenuLength menu.name
            result = false

        result = false if result and menu.type is vm.allTypes.url and menuService.checkUrl menu.content

        result

      vm.editMenu = ->
        delete vm.inLink if vm.link is vm.links[0].value
        delete vm.outLink if vm.link is vm.links[1].value
        vm.status = vm.allStatus.edit
        return

      vm.cancelEdit = ->
        vm.activate vm.trueMenu
        vm.status = vm.allStatus.show
        return

      vm.delete = (index, menus, $event) ->
        $event.stopPropagation()
        vm.activate menus[index], false
        if menus.length > 1 or menus[index].parentId
          notificationService.confirm $event, {
            title: 'channel_wechat_menu_delete_confirm'
            params: [index, menus]
            submitCallback: _deleteHandler
          }
        else
          notificationService.warning 'wechat_menu_delete_fail', false
        return

      _deleteHandler = (index, menus) ->
        rvm.$apply ->
          menu = menus.splice(index, 1)[0]
          if menu.id is vm.selectedMenu.id
            vm.selectedMenu = null
          _saveMenus 'wechat_menu_delete_success'
        return

      _saveMenus = (msg) ->
        _updateExtInfo()
        notificationService.success msg, false
        vm.isPublish = false # menu have been changed, so set to false
        vm.enablePublishButton = true # enable button if any menu is created
        vm.status = vm.allStatus.show
        vm.trueMenu = angular.copy vm.selectedMenu

      # iterate through menus, and update the extInfo
      _updateExtInfo = ->
        vm.types = []
        remainExts = angular.copy vm.extInfos
        angular.forEach vm.menus, (menu) ->
            delete remainExts[menu.keycode] if menu.keycode? and remainExts[menu.keycode]
            angular.forEach menu.subMenus, (submenu) ->
              delete remainExts[submenu.keycode] if submenu.keycode? and remainExts[submenu.keycode]
        angular.forEach remainExts, (remain) ->
          vm.types.push remain

      # parse menu: spread type to url type
      _parseSpreadMenuToViewMenu = (menu) ->
        if menu.type is vm.allTypes.spread
          menu.type = vm.allTypes.url
          delete menu.contentName if menu.contentName

      _removeSaveSpreadMenuStoreage = ->
        vm.isSpreadMenu = false
        storeService.removeMemoryItem 'menu'

      # publish menus
      vm.publishMenus = ->
        for menu in vm.menus
          _parseSpreadMenuToViewMenu(menu)
          if menu.subMenus?.length is 0 and not menu.content and menu.type isnt vm.allTypes.ext and menu.type isnt vm.allTypes.webhook
            vm.activate(menu)
            notificationService.warning 'wechat_menu_content_missing', false
            return
          else if menu.subMenus?.length > 0
            for submenu in menu.subMenus
              _parseSpreadMenuToViewMenu(submenu)
              if not submenu.content and submenu.type isnt vm.allTypes.ext and submenu.type isnt vm.allTypes.webhook
                vm.activate(submenu)
                notificationService.warning 'wechat_menu_content_missing', false
                return
        params =
          channelId: channelId
          menu: angular.copy vm.menus
        angular.forEach params.menu, (menu) ->
          delete menu.id if menu.id.indexOf('mock') > -1
          delete menu.hitCount
          delete menu.ext
          angular.forEach menu.subMenus, (submenu) ->
            delete submenu.parentId if submenu.parentId.indexOf('mock') > -1
            delete submenu.id if submenu.id.indexOf('mock') > -1
            delete submenu.hitCount
            delete submenu.ext
            return
          return
        restService.post config.resources.menus, params, (data) -> # save the menus before publish
          _removeSaveSpreadMenuStoreage()
          params =
            channelId: channelId
          restService.post config.resources.publishmenu, params, (data) -> # after saving the menu, publish it
            notificationService.success 'wechat_menu_publish_success', false
            vm.isPublish = true
            return
        return

      # the onclick function of each item after click the "wechat_menu_set_action"
      # @param type   is the vm.type which is assigned in vm.activate function, the value of type must equals to what inside the vm.allTypes
      vm.selectType = (type) ->
        if type is vm.allTypes.ext
          modalInstance = $modal.open(
            templateUrl: 'extensions.html'
            controller: 'wm.ctrl.channel.wechat.menu.ext'
            size: 'lg'
            resolve:
              modalData: ->
                vm.types
          )
          modalInstance.result.then (selectedExt) ->
            if vm.extInfos[selectedExt]?
              vm.selectedMenu.ext = vm.extInfos[selectedExt]
              vm.selectedMenu.keycode = selectedExt
              vm.selectedMenu.type = type
            return
        else
          vm.selectedMenu.type = type
          if type is menuService.getType vm.selectedMenu
            vm.link = vm.links[0].value
            delete vm.inLink
            delete vm.outLink
        return

      # check menu length
      vm.checkMenuLength = menuService.checkMenuLength
      vm.checkSubmenuLength = menuService.checkSubmenuLength
      # check url
      vm.checkUrl = menuService.checkUrl

      ###
      # Sort Menu
      ###
      sortAnimateDuration = 200
      vm.sortedMenus = []

      # menu sort options
      vm.sortMenuOptions =
        group: 'menu'
        sort: true
        disabled: false
        animation: sortAnimateDuration
        handle: '.main-menu-sort-item'
        draggable: '.main-menu-sort-item'
        ghostClass: 'menu-place-holder'
        onUpdate: (items, item) ->
          vm.sortedMenus = items
          return

      # submenu sort options
      vm.sortSubmenuOptions =
        sort: true
        disabled: false
        animation: sortAnimateDuration
        handle: '.submenu-sort-item'
        draggable: '.submenu-sort-item'
        ghostClass: 'menu-place-holder'
        onUpdate: (items, item) ->
          angular.forEach vm.sortedMenus ,(menu) ->
            if menu.id is item.parentId
              menu.subMenus = items
            return
          return

      vm.initSortedMenus = ->
        vm.sortedMenus = angular.copy vm.menus
        return

      vm.saveSortedMenus = ->
        vm.menus = angular.copy vm.sortedMenus
        _saveMenus 'wechat_menu_order_success'
        return

      vm.cancelSortedMenus = ->
        vm.sortedMenus = []
        return

      # click statistics data
      vm.statistics = (menu, $event) ->
        $event.stopPropagation()
        vm.activate menu, false
        vm.count = menu.hitCount
        vm.shwoStatistics = true
        $target = $($event.currentTarget)
        $statistics = $('.statistics-confirm')
        $statistics.css
          'top': $target.offset().top - 16
          'right': $(document).width() - $target.offset().left - $target.width() / 2 - 58
        $mask = $('<div class="mask-confirm"></div>')
        $('body').append($mask)
        $mask.click(->
          $(this).remove()
          $scope.$apply(->
            vm.shwoStatistics = false
          )
          return
        )
        return

      vm.statisticsChat = ->
        vm.shwoStatistics = false
        $('.mask-confirm').remove()
        modalInstance = $modal.open(
          templateUrl: 'statistics.html'
          controller: 'wm.ctrl.channel.wechat.menu.statistics'
          size: 'lg'
          resolve:
            modalData: ->
              vm.selectedMenu
        )
        return

      vm.changeLink = (value, index) ->
        if vm.link is vm.links[0].value
          vm.outLink = vm.selectedMenu.content
          vm.selectedMenu.content = ''
        if value is vm.links[1].value
          vm.selectedMenu.content = if vm.inLink then vm.inLink.url else ''
        else if value is vm.links[0].value
          vm.selectedMenu.content = vm.outLink

      vm.showStationLink = ->
        $('.station-link-input').focus()
        modalInstance = $modal.open(
          templateUrl: '/build/modules/core/partials/selectPages.html'
          controller: 'wm.ctrl.core.selectPages'
          windowClass: 'station-link-dialog'
          resolve:
            modalData: ->
              'url': vm.selectedMenu.content
        )
        modalInstance.result.then (selectedContent) ->
          vm.inLink = angular.copy selectedContent
          vm.selectedMenu.content = vm.inLink.url
          $('.station-link-input').focus()
        return

      _init()
      vm
  ]
  .registerController 'wm.ctrl.channel.wechat.menu.statistics', [
    'restService'
    '$scope'
    '$stateParams'
    '$filter'
    '$modalInstance'
    'modalData'
    (restService, $scope, $stateParams, $filter, $modalInstance, modalData) ->
      vm = $scope
      channelId = null

      vm.selectDate = ->
        if vm.beginDate and vm.endDate
          _initData vm.beginDate, vm.endDate
        return

      _initData = (from, to) ->
        params =
          from: from
          to: to
          channelId: channelId
          menuId: modalData.id

        restService.get config.resources.menustatistics, params, (data) ->
          tip = $filter('translate')('wechat_menu_click_number')
          vm.chartData =
            categories: data.statDate,
            series: [{
               name: modalData.name
               data: data.count
            }]
            startDate: moment(parseInt(from)).format 'YYYY-MM-DD'
            endDate: moment(parseInt(to)).format 'YYYY-MM-DD'
            config:
              tooltip:
                formatter: (obj) ->
                  return obj[1] + '<br/>' + tip + ': ' + obj[2]
          return
        return

      _init = ->
        channelId = $stateParams.id unless channelId?
        vm.beginDate = moment().startOf('day').subtract(1, 'weeks').format 'x'
        vm.endDate = moment().startOf('day').subtract(1, 'days').format 'x'
        _initData vm.beginDate, vm.endDate
        return

      _init()

      vm.hideModal = ->
        $modalInstance.dismiss('cancel')
        return
      return
  ]
  .registerController 'wm.ctrl.channel.wechat.menu.ext', [
    'restService'
    '$scope'
    '$modalInstance'
    'modalData'
    (restService, $scope, $modalInstance, modalData) ->
      vm = $scope

      vm.extInfos = modalData

      vm.ok = ->
        if vm.selectedExt
          $modalInstance.close vm.selectedExt
        return

      vm.selectExt = (keycode) ->
        vm.selectedExt = keycode
        return

      vm.hideModal = ->
        $modalInstance.dismiss('cancel')
        return
      return
  ]
