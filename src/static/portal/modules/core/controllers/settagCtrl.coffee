define [
  'core/coreModule'
  'wm/config'
], (mod, config) ->
  mod.controller 'wm.ctrl.core.settag', [
    'restService'
    '$scope'
    '$modalInstance'
    'notificationService'
    '$timeout'
    'debounceService'
    'modalData'
    (restService, $scope, $modalInstance, notificationService, $timeout, debounceService, modalData) ->

      $scope.isload = false       # mark lazyload.
      $scope.isEdit = false
      $scope.currentPage = 0
      $scope.pageSize = 20
      $scope.pageCount = 1
      $scope.list =
        data: []
      $scope.data = []

      $scope.reNameEdit = (index, oldName) ->
        $scope.editIndex = index
        $scope.isEdit = true
        $scope.oldName = oldName
        return

      $scope.checkTag = (name) ->
        formTip = ''
        tagList = if modalData then angular.copy modalData else []
        if name
          if name.length > 5
            formTip = 'core_length'
            angular.forEach tagList, (tag) ->
              if tag.name is name
                formTip = 'core_required_exist'
        else
          formTip = 'core_required_field_tip'
        formTip

      $scope.reNameTag = (index, newName) ->
        if not $scope.checkTag newName
          params =
            newName: newName
            name: $scope.oldName
          restService.put config.resources.tagReName, params, (data) ->
            if data.message is "OK"
              notificationService.success 'core_rename_tag_success'
            $scope.isEdit = false

      $scope.cancelTag = (index) ->
        $scope.editIndex = index
        $scope.isEdit = false
        $scope.list.data = angular.copy $scope.data
        return

      deleteTagHandler = (index, name) ->
        restService.del config.resources.tagDelete, {
          name: name
        }, ->
          $scope.list.data.splice index, 1
          return
        return

      $scope.deleteTag = (name, index, $event) ->
        notificationService.confirm $event, {
          "params": [index, name]
          "submitCallback": deleteTagHandler
        }
        return

      $scope.closeDialog = ->
        $modalInstance.close()
        return

      _init = ->
        statisticalData =
          name: ''
          followerNum: 0
          memberNum: 0
        getAllTags()

      getAllTags = ->
        list =
          data: []
        if $scope.currentPage < $scope.pageCount
          params =
            'per-page': $scope.pageSize
            'page': parseInt($scope.currentPage) + 1
          restService.get config.resources.allTags, params, (data) ->
            if data
              angular.forEach data.items, (item) ->
                statisticalData =
                  name: item.name
                  followerNum: item.followerCount
                  memberNum: item.memberCount
                list.data.push statisticalData
              $scope.isOpenChannle = data.data
              $scope.currentPage = data._meta.currentPage
              $scope.pageCount = data._meta.pageCount
              $scope.list.data = $scope.list.data.concat(list.data)
              $scope.data = angular.copy $scope.list.data
              $scope.$broadcast "reform-waterfall"
              return

      $timeout( ->
        $('.label-content').scroll debounceService.callback( ->
          if $(".label-content")[0].scrollHeight - $(".label-content")[0].clientHeight - $(".label-content")[0].scrollTop < 20
            $scope.isload = true
            getAllTags()
            $scope.isload = false
        )
      , 1000)

      _init()
      return
  ]
