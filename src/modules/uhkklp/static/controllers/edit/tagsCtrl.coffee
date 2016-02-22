define [
  'wm/app'
  'wm/config'
], (app, config) ->
  app.registerController 'wm.ctrl.uhkklp.edit.tags', [
    'restService'
    '$scope'
    '$modalInstance'
    'notificationService'
    '$timeout'
    'debounceService'
    '$http'
    '$filter'
    (restService, $scope, $modalInstance, notificationService, $timeout, debounceService, $http, $filter) ->

      $scope.isload = false       # mark lazyload.
      $scope.isEdit = false
      $scope.currentPage = 0
      $scope.list =
        data: []
      $scope.data = []

      $scope.reNameEdit = (index, oldName) ->
        $scope.list.data = angular.copy $scope.data
        $scope.isError = false
        $scope.editIndex = index
        $scope.isEdit = true
        $scope.oldName = oldName
        return

      $scope.tagTip = ''
      $scope.isError = false
      $scope.checkTag = (name) ->
        formTip = ''
        $scope.data = [] if not $scope.data?
        if name
          for tag in $scope.data
            if tag.name is name
              formTip = $filter('translate')('exist_tag')
              break
          if name.length > 5
            formTip = $filter('translate')('tag_character_tip')
        else
          formTip = $filter('translate')('required_tip')
        if formTip
          $scope.tagTip = formTip
          $scope.isError = true
        return formTip


      $scope.focus = ->
        $scope.isError = false

      $scope.reNameTag = (index, newName) ->
        if $scope.checkTag newName
          return
        $http
          url: '/api/uhkklp/cooking-type/rename'
          method: 'POST'
          headers:
            'Content-Type': 'application/json'
          data: {
            "newName": newName
            "id": $scope.list.data[index].id
          }
        .success (data) ->
          if data['code'] is 200
            notificationService.success 'rename_tag_success', false
            $scope.data = angular.copy $scope.list.data
            $scope.isEdit = false
          else
            notificationService.error 'rename_tag_fail', false
        .error ->
          notificationService.error 'rename_tag_fail', false

      $scope.cancelTag = (index) ->
        $scope.editIndex = index
        $scope.isEdit = false
        $scope.list.data = angular.copy $scope.data
        return

      deleteTagHandler = (index, name) ->
        $http
          url: '/api/uhkklp/cooking-type/delete'
          method: 'POST'
          headers:
            'Content-Type': 'application/json'
          data: {
            "id": $scope.list.data[index].id
          }
        .success (data) ->
          if data['code'] is 200
            notificationService.success 'recipe_list_delete_success_tip', false
            getAllTags()
            $scope.isEdit = false
          else
            notificationService.error 'recipe_list_delete_failed_tip', false
        .error ->
          notificationService.error 'recipe_list_delete_failed_tip', false
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
        getAllTags()

      getAllTags = ->
        $http
          url: '/api/uhkklp/cooking-type/list'
          method: 'GET'
          headers:
            'Content-Type': 'application/x-www-form-urlencoded'
        .success (data) ->
          $scope.list.data = []
          for $data in data['result']
            tag = {}
            if not ($data.category is '大類' or $data.category is '固定分類')
              tag.name = $data['name']
              tag.id = $data['id']
              $scope.list.data.push tag
          $scope.data = angular.copy $scope.list.data
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
