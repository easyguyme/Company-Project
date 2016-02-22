define [
  'wm/app'
  'wm/config'
  'core/directives/wmInnerDroppable'
  'core/directives/wmDroppable'
  'core/directives/wmDraggable'
  'wm/modules/microsite/controllers/page/componentConfigCtrl'
], (app, config) ->

  componentService = ( ->
    tabName = 'tab'
    _getComponent = (name, color, pageId, id) ->
      if name isnt tabName
        url = '/msite/widget'
        url += '/' + id if id
        url += '?t=' + name
        url: url
        color: color
        name: name
        pageId: pageId
      else
        jsonConfig:
          tabs: [
            {name: '', cpts: [], active: true}
            {name: '', cpts: [], active: false}
          ]
        color: color
        name: name
        pageId: pageId

    _getConfPath = (name) ->
      "/build/modules/microsite/partials/page/conf/#{name}.html?t=" + new Date().getTime()

    _updateCompontent = (compontent, options) ->
      angular.extend compontent, options

    _updateCompontentUrl = (component) ->
      if component
        url = '/msite/widget'
        url += '/' + component.id if component.id
        url += '?t=' + component.name
        url

    # refresh a iframe
    _refreshIframe = (id) ->
      iframe = $('#' + id)
      iframe.attr 'src', iframe.attr('src')
      return

    # refresh all iframes
    _refreshAllIframe = ->
      $(document).find('iframe').each( ->
        $(this).attr 'src', $(this).attr('src')
      )
      return

    _updateCompontentsColor = (components, color) ->
      if angular.isArray(components) and components.length
        for cpt in components
          if cpt and 'color' of cpt
            cpt.color = color

          if cpt.name is 'tab'
            subComponents = []
            tabs = cpt.jsonConfig.tabs or []
            if angular.isArray(tabs) and tabs.length
              for tab in tabs
                if angular.isArray(tab.cpts) and tab.cpts.length
                  subComponents = subComponents.concat tab.cpts

            for subCpt in subComponents
              if subCpt and 'color' of subCpt
                subCpt.color = color
      return

    get: _getComponent
    update: _updateCompontent
    getConfPath: _getConfPath
    refreshIframe: _refreshIframe
    refreshAllIframe: _refreshAllIframe
    updateUrl: _updateCompontentUrl
    updateCompontentsColor: _updateCompontentsColor
  )()

  isEmpty = (value) ->
    ret = true
    if angular.isArray(value)
      (value.length > 0) and (ret = false)
    else if angular.isObject(value)
      ret = $.isEmptyObject(value)
    else
      ret = !!!value
    ret

  # in order to highlight webpage, in fact is page edit controller
  app.registerController 'wm.ctrl.microsite.page.components', [
    '$stateParams'
    '$scope'
    '$sce'
    '$modal'
    '$document'
    '$timeout'
    '$location'
    'restService'
    'dragDropService'
    'notificationService'
    '$filter'
    'fixColorFilter'
    ($stateParams, $scope, $sce, $modal, $document, $timeout, $location, restService, dragDropService, notificationService, $filter, fixColorFilter) ->
      vm = this
      # 'map',
      vm.cptNames = [
        'nav', 'slide', 'pic', 'title', 'text',
        'articles', 'album', 'table', 'link',
        'tel', 'sms', 'map', 'html', 'contact', 'questionnaire', 'coupon'
      ]

      vm.layoutCptNames = ['delimiter', 'tab']

      vm.colors = [
        '#6ab3f7', '#00a3e7', '#4571ff', '#0f5f9e', '#5e7489', '#2eb6aa',
        '#6d5653', '#946da8', '#ae3c37', '#f31b18', '#fad247', '#83c30b'
      ]

      DEFAULTTABCOLOR = '#787878'

      vm.color = vm.colors[0]
      vm.showTitle = true
      vm.pageCpts = []
      vm.selectedComponent = {}
      vm.editedCptName = ''
      vm.curStep = 1 unless $stateParams.id

      # Init the default configuration for cover
      vm.initCoverConf = (type) ->
        vm.isCover = type is 'cover'
        if vm.isCover
          # Get cover component
          cover = vm.pageCpts[0]
          if cover
            cover.url = componentService.updateUrl cover
            vm.oldCoverName = cover.name
            vm.oldCoverId = cover.id
            vm.coverIndex = parseInt cover.name.charAt(cover.name.length - 1)
            vm.editCpt cover
            vm.isCover3 = cover.name is 'cover3'
            vm.color = vm.colors[0] if vm.isCover3

      # Get component list for editing page
      vm.getComponents = (page) ->
        if page.id
          restService.get config.resources.pageComponents, {pageId: page.id}, (components) ->
            # Render page components
            tmpCpts = []
            for item in components
              item.url = "/msite/widget/#{item.id}?t=" + item.name
              if item.name is 'tab' and item.jsonConfig.tabs
                for tab in item.jsonConfig.tabs
                  if tab.cpts
                    for cpt in tab.cpts
                      cpt.url = "/msite/widget/#{cpt.id}?t=" + cpt.name
              tmpCpts.push item
            vm.pageCpts = dragDropService.objects = tmpCpts
            vm.initCoverConf(page.type)

      # refresh tab color after change page color or load the tab widget
      refreshTabWidgetColor = (color) ->
        tabColor =
          'border':
            'active': color
            'unactive': $filter('fixColor')(color, 0.1)
          'text':
            'active': color
            'unactive': DEFAULTTABCOLOR
        tabColor

      # Wait for getting page type
      $scope.$on 'pageDataLoaded', (e, page) ->
        vm.page = page
        vm.color = vm.page.color or vm.colors[0]
        vm.tabColor = refreshTabWidgetColor vm.color
        vm.getComponents(page)
        return
      $scope.$emit 'cptPageLoaded'

      vm.checkErrors = ->
        # Scroll to show the errors
        count = 0
        timer = $timeout( ->
          $errors = if $('div.ng-invalid').length then $('div.ng-invalid') else $('input.ng-invalid')
          if $errors.length
            $confWrap = $('.config-wrap')
            $cardBody = $confWrap.parent()
            top = $errors.first().offset().top - $confWrap.offset().top - $cardBody.height() / 2
            (top < 0) and (top = 0)
            $body = $('body')
            $cardBody.scrollTop(top)
            $body.scrollTop($body.height() / 3)
          if ++count > 10 or $errors.length
            $timeout.cancel timer
            timer = null
        , 100)

      vm.pickColor = (color) ->
        vm.tabColor = refreshTabWidgetColor color
        # update the color in backend
        restService.put config.resources.pageColor + '/' + vm.page.id, {color: color}, (data) ->
          notificationService.success 'content_component_config_save_success', false
          componentService.updateCompontentsColor vm.pageCpts, color
          componentService.refreshAllIframe()
          return
        return

      vm.selectCover = (index) ->
        vm.coverIndex = index + 1
        coverName = 'cover' + vm.coverIndex
        vm.selectedComponent.name = coverName
        isOldCover = vm.oldCoverName is coverName
        vm.selectedComponent.id = if isOldCover then vm.oldCoverId else null
        vm.selectedComponent.url = componentService.updateUrl vm.selectedComponent
        vm.editCpt vm.selectedComponent
        vm.isCover3 = coverName is 'cover3'

      _checkCoverData = (type, data) ->
        error = 0
        if type is 'cover1'
          for item in data.slideInfo
            error++ if item.name.length is 0 or item.pic.length is 0
          for item in data.navInfo
            error++ if item.name.length is 0 or item.iconUrl.length is 0 or item.linkUrl.length is 0
        if type is 'cover3'
          for item in data.navs
            error++ if item.name.length is 0 or item.pic.length is 0 or item.linkUrl.length is 0
        return error

      vm.sendData = (data, callback) ->
        # Call customized callback function in componentConfigCtrl before saving component, skip saving if callback function return false
        if callback and angular.isFunction callback
          ret = callback()
          return if typeof ret is 'boolean' and not ret
        # add the config for component
        params = angular.copy vm.selectedComponent
        if params.name is 'cover1' and _checkCoverData(params.name, data) is 0
          delete data.navs
          delete vm.selectedComponent.jsonConfig.navs
        else if params.name is 'cover3' and _checkCoverData(params.name, data) is 0
          delete data.slideInfo
          delete data.navInfo
          delete vm.selectedComponent.jsonConfig.slideInfo
          delete vm.selectedComponent.jsonConfig.navInfo
        params.jsonConfig = data
        # Special for tab component, in case that the components in tab are passed to the backend
        if isEmpty vm.selectedComponent.jsonConfig
          vm.selectedComponent.jsonConfig = data
        else
          $.extend true, vm.selectedComponent.jsonConfig, data

        # update component in backend
        delete vm.selectedComponent.order
        delete params.order
        delete params.parentId
        delete params.pageId
        vm.selectedComponent.id = vm.oldCoverId if vm.selectedComponent.name.indexOf('cover') >= 0
        restService.put config.resources.pageComponent + '/' + vm.selectedComponent.id, params, (data) ->
          afterSaveComponent vm.selectedComponent.id, true
          vm.selectedComponent.url = componentService.updateUrl vm.selectedComponent
          vm.oldCoverId = vm.selectedComponent.id
          vm.oldCoverName = vm.selectedComponent.name
          # Remove all the form tips
          $('.form-tip').remove()
          return
        return

      vm.createComponent = (tabId, tabIndex) ->
        params = angular.copy vm.selectedComponent
        if angular.isNumber tabIndex
          params.tabId = tabId
          params.tabIndex = params.tabIndex
        # create component in backend
        restService.post config.resources.pageComponents, params, (data) ->
          afterSaveComponent data.id
          return

      afterSaveComponent = (id, isUpdate = false) ->
        successTip = 'content_component_save_success'
        if isUpdate
          # update component config
          successTip = 'content_component_config_save_success'
          componentService.refreshIframe id
        else
          componentService.update vm.selectedComponent, {id: id}
          vm.selectedComponent.url = componentService.updateUrl vm.selectedComponent
        notificationService.success successTip, false
        return

      vm.orderCpts = (from, to, tabId, tabIndex) ->
        # update the cpts order in backend
        component = dragDropService.getObject to, tabId, tabIndex
        param =
          newOrder: to
        if tabId
          param.tabId = tabId
        restService.put config.resources.pageComponentOrder + '/' + component.id, param ,(data) ->
          return
        return

      vm.addCpt = (name, index, tabId, tabIndex) ->
        vm.editedCptName = name
        vm.confPath = componentService.getConfPath name
        component = componentService.get name, vm.color, vm.page.id
        component.order = index

        if angular.isNumber tabIndex
          component.parentId = tabId
          component.tabIndex = tabIndex
        else
          component.parentId = vm.page.id

        vm.selectedComponent = component
        vm.createComponent tabId, tabIndex
        dragDropService.addObject component, index, tabId, tabIndex
        vm.pageCpts = dragDropService.objects
        return

      vm.editCpt = (component) ->
        vm.editedCptName = component.name
        vm.confPath = componentService.getConfPath component.name
        vm.selectedComponent = component
        $scope.$on "cptLoaded", ->
          if vm.selectedComponent.jsonConfig
            $scope.$broadcast 'refreshData', vm.selectedComponent.jsonConfig
            return
        return

      vm.deleteCptConfirm = ($event, index, component, tabId, tabIndex) ->
        params = [index, component, tabId, tabIndex]
        vm.selectedComponent = component
        component.deletable = true
        notificationService.confirm $event, {title: 'content_component_delete_confirm', submitCallback: deleteCpt, cancelCallback: cancelDeleteCpt, params: params}
        return

      cancelDeleteCpt = (index, component, tabId, tabIndex) ->
        $timeout ->
          delete component.deletable
          return
        , 0
        return

      deleteCpt = (index, component, tabId, tabIndex) ->
        vm.editedCptName = ''
        vm.confPath = ''
        dragDropService.removeObject index, tabId, tabIndex
        vm.pageCpts = dragDropService.objects
        vm.deleteCptId = undefined

        if component.id
          restService.del config.resources.pageComponent + '/' + component.id, (data) ->
            notificationService.success 'content_component_delete_success', false
            _fixTabHeight()
            return
        else
          notificationService.success 'content_component_delete_success', false
          _fixTabHeight()
        return

      _fixTabHeight = ->
        # fix the tab height
        $('.m-tab').each( ->
          $(this).parent().css 'height', 'auto'
          return
        )
        return

      vm.changeTab = (tabIndex, tabs) ->
        angular.forEach tabs, (tab) ->
          tab.active = false
          return
        tabs[tabIndex].active = true
        return

      vm.changeStep = (step) ->
        $scope.$parent.webpage.changeStep step
        return

      vm.finish = ->
        restService.put config.resources.pagePublish + '/' + vm.page.id, (data) ->
          notificationService.success 'content_page_update_finished', false
          $location.path 'microsite/webpage'
          return
        return

      vm.preview = ->
        modalInstance = $modal.open(
          templateUrl: 'pagePreview.html'
          controller: 'wm.ctrl.microsite.pagePreview'
          windowClass: 'page-preview-dialog'
          resolve:
            modalData: ->
              vm.page
        )
        return

      $timeout( ->
        $('.mobile-content').scroll( ->
          $questionnaire = $(this).find('[name=questionnaire]')
          if $questionnaire.length is 1
            questionnaireTop = if $questionnaire.position().top > 0 then 5 else -$questionnaire.position().top + 5
            $questionnaire.find('.cpt-delete').css('top', questionnaireTop)
          )
      , 1000)

      vm
  ]
  .registerController 'wm.ctrl.microsite.pagePreview', [
    '$scope'
    '$modalInstance'
    '$timeout'
    'modalData'
    ($scope, $modalInstance, $timeout, modalData) ->
      vm = $scope

      if modalData.type is 'cover'
        vm.iframeShow = false
        $timeout ->
          $('.mobile-bg.preview-mobile').append $('.mobile-wrap.real-mobile').clone()
          return
        , 200
      else
        vm.iframeShow = true
        vm.url = '/msite/page/' + modalData.id + '?s=1'

      vm.hideModal = ->
        $modalInstance.close()
      vm
  ]
