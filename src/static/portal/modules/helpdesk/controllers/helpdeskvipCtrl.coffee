define [
  'wm/app'
  'wm/config'
  'core/coreModule'
], (app, config, mod) ->

  app.registerFilter 'itemFilter', ->
    (items, filterCond) ->
      if items and items.length
        if filterCond
          matchedItems = []
          angular.forEach items, (item) ->
            if item.badge.indexOf(filterCond) > -1 or item.email.indexOf(filterCond) > -1
              matchedItems.push item
          matchedItems
        else
          items


  app.registerController 'wm.ctrl.helpdesk.helpdeskvip', [
    'restService'
    'notificationService'
    '$modal'
    '$filter'
    (restService, notificationService, $modal, $filter) ->
      vm = this

      vm.order = 'desc'

      vm.breadcrumb = [
        'helpdesk_vip'
      ]

      _getTagList = ->
        restService.get config.resources.tags, {}, (data) ->
          if (angular.isArray data.items) and data.items.length
            angular.forEach data.items, (tagItem, tagIdx) ->
              tagItem.isSelected = tagIdx is 0
            if data.items.length
              vm.selectedTag = data.items[0]
              vm.list.deleteTitle = $filter('translate')('helpdesk_tag_delete_confirm', {'tagName': '<i class="confirm-gray-title">' + vm.selectedTag.name + '</i>'})
          vm.tags = data.items
          _getHelpdeskList()
          return

      _getHelpdeskList = ->
        vm.list?.emptyMessage = 'helpdesk_no_helpdesk_staff_assigned'
        if vm.selectedTag
          params =
            tagName: vm.selectedTag.name
            orderBy:
              clientCount: vm.order
          restService.get config.resources.helpdeskListByTag, params, (data) ->
            _transferToTable(data.items)
            vm.memberCount = data.memberCount
            return

      _transferToTable = (data) ->
        items = []
        for item in data
          listItem = {
            id: item.id
            email: item.email
            name: item.name
            badge: item.badge
            busy: {}
            operations: [{name: 'delete'}]
          }

          conversationCount = item.conversationCount
          maxClient = item.maxClient
          percent = conversationCount / maxClient

          if 0 <= percent <= 0.2
            icon = 'notbusy'
          if 0.2 < percent <= 0.8
            icon = 'alittlebusy'
          if percent > 0.8
            icon = 'busy'
          img = "/images/helpdesk/#{icon}.png"

          listItem.busy =
            icon: img
            text: conversationCount + '/' + maxClient

          items.push listItem
        vm.list.data = items

      _init = ->
        vm.list =
          columnDefs: [
            {
              field: 'email'
              label: 'helpdesk_account_account'
            }
            {
              field: 'name'
              label: 'helpdesk_account_nickname'
            }
            {
              field: 'badge'
              label: 'helpdesk_account_number'
            }
            {
              field: 'busy'
              label: 'helpdesk_account_busy'
              sortable: true
              desc: true
              sortHandler: ->
                vm.order = if vm.order is 'desc' then 'asc' else 'desc'
                _getHelpdeskList()
              type: 'iconText'
            }
            {
              field: 'operations'
              label: 'operations'
              type: 'operation'
            }
          ]
          data: []
          selectable: false

          deleteHandler: (idx) ->
            params =
              tagName: vm.selectedTag.name
              helpdeskIds: [vm.list.data[idx].id]
            restService.post config.resources.helpdeskRemoveTag, params, (data) ->
              _getHelpdeskList()


        _getTagList()

      _init()

      vm.selectTag = (selectedTag) ->
        angular.forEach vm.tags, (tagItem) ->
          tagItem.isSelected = false
        selectedTag.isSelected = true
        vm.selectedTag = selectedTag
        vm.list.deleteTitle = $filter('translate')('helpdesk_tag_delete_confirm', {'tagName': '<i class="confirm-gray-title">' + vm.selectedTag.name + '</i>'})
        _getHelpdeskList()

      vm.assignHelpdesk = (tagName) ->
        params =
          tagName: vm.selectedTag.name
        modalInstance = $modal.open(
          templateUrl: 'assignHelpdesk.html'
          controller: 'wm.ctrl.helpdesk.helpdeskvip.assignHelpdesk'
          windowClass: 'assign-helpdesk-dialog'
          resolve:
            modalData: ->
              params
        ).result.then( (data) ->
          _getHelpdeskList()
        )
      vm
  ]

  app.registerController 'wm.ctrl.helpdesk.helpdeskvip.assignHelpdesk', [
    '$scope'
    'restService'
    '$modalInstance'
    'modalData'
    'notificationService'
    ($scope, restService, $modalInstance, modalData, notificationService) ->
      vm = $scope

      _getIdxInArray = (selectedHelpdesk, helpdesks) ->
        selectedIdx = -1
        if angular.isArray helpdesks
          angular.forEach helpdesks, (helpdesk, idx) ->
            if helpdesk.id is selectedHelpdesk.id
              selectedIdx = idx
        selectedIdx

      _init = ->
        vm.selectedHelpdesks = []
        tagName = modalData.tagName
        if tagName
          params =
            tagName: tagName
          restService.get config.resources.helpdeskListExcludeTag, params, (data) ->
            vm.allHelpdesks = data

          restService.get config.resources.helpdeskListByTag, params, (data) ->
            vm.selectedHelpdesks = data.items

      _init()

      vm.selectHelpdesk = (helpdesk) ->
        idx = _getIdxInArray helpdesk, vm.allHelpdesks
        if idx isnt -1
          vm.selectedHelpdesks.push helpdesk
          vm.allHelpdesks.splice idx, 1

      vm.unSelectHelpdesk = (helpdesk) ->
        idx = _getIdxInArray helpdesk, vm.selectedHelpdesks
        if idx isnt -1
          vm.selectedHelpdesks.splice idx, 1
          vm.allHelpdesks.push helpdesk

      vm.submit = ->
        addTagHelpdeskIds = []
        removeTagHelpdeskIds = []
        angular.forEach vm.selectedHelpdesks, (helpdesk) ->
          addTagHelpdeskIds.push helpdesk.id
        angular.forEach vm.allHelpdesks, (helpdesk) ->
          removeTagHelpdeskIds.push helpdesk.id
        params =
          tagName: modalData.tagName
          addTagHelpdeskIds: addTagHelpdeskIds
          removeTagHelpdeskIds: removeTagHelpdeskIds
        restService.put config.resources.helpdeskAssignTag, params, (data) ->
          $modalInstance.close()
          notificationService.success 'helpdesk_assign_tag_success'

      vm.hideModal = ->
        $modalInstance.close()
  ]
