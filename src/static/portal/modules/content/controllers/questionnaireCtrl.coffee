define [
  'wm/app'
  'wm/config'
], (app, config) ->
  app.registerController 'wm.ctrl.content.questionnaire', [
    'restService'
    '$scope'
    'notificationService'
    '$location'
    (restService, $scope, notificationService, $location) ->
      vm = this

      vm.currentPage = $location.search().currentPage or 1
      vm.totalCount = 0
      vm.pageSize = $location.search().pageSize or 10

      vm.breadcrumb = [
        'questionnaire'
      ]

      vm.list =
        columnDefs: [
          field: 'name'
          label: 'content_questionnaire_name'
          type: 'link'
          cellClass: 'text-el'
        ,
          field: 'startTime'
          label: 'start_time'
          sortable: true
          desc: true
          type: 'date'
          headClass: 'width-175'
        ,
          field: 'endTime'
          label: 'end_time'
          sortable: true
          desc: true
          type: 'date'
          headClass: 'width-175'
        ,
          field: 'createdBy'
          label: 'create_by'
        ,
          field: 'createdAt'
          label: 'create_at'
          sortable: true
          desc: true
          type: 'date'
          headClass: 'width-175'
        ,
          field: 'isPublished'
          label: 'status'
          type: 'status'
          headClass: 'width-90'
        ]
        operations: [
          name: 'statistics'
        ,
          name: 'edit'
        ,
          name: 'delete'
        ]
        data: []
        selectable: false
        deleteTitle: 'content_delete_questionnaire'

        sortHandler: (colDef) ->
          key = colDef.field
          value = if colDef.desc then 'desc' else 'asc'
          vm.orderBy = '{"' + key + '":' + '"' + value + '"}'
          vm.currentPage = 1
          _getList()

        switchHandler: (idx) ->
          param =
            isPublished: vm.list.data[idx].isPublished isnt 'ENABLE'
          restService.put config.resources.questionnaire + '/' + vm.list.data[idx].id, param, (data) ->
            notificationService.success 'content_questionnaire_update_succsss'
          return

        editHandler: (idx) ->
          $location.url '/content/edit/questionnaire/' + vm.list.data[idx].id

        statisticsHandler: (idx) ->
          $location.url '/content/statistics/questionnaire/' + vm.list.data[idx].id

        deleteHandler: (idx) ->
          id = vm.list.data[idx]?.id
          restService.del config.resources.questionnaire + "/" + id, (data) ->
            _getList()
            notificationService.success 'content_questionnaire_delete_success'

      _getList = ->
        fields = ['id', 'name', 'startTime', 'endTime', 'creator', 'createdAt', 'isPublished']
        params =
          'page': vm.currentPage,
          'per-page': vm.pageSize,
          'fields': fields.join(',')
        params.orderBy = angular.copy vm.orderBy if vm.orderBy
        restService.get config.resources.questionnaires, params, (data) ->
          if data.items
            questionnaires = []
            angular.forEach data.items, (item) ->
              if item.name
                item.name =
                  text: item.name
                  link: '/content/view/questionnaire/' + item.id
              item.isPublished = if item.isPublished then 'ENABLE' else 'DISABLE'
              item.createdBy = item.creator?.name or '-'
              questionnaires.push item
            vm.list.data = angular.copy questionnaires
            vm.totalCount = data._meta.totalCount

      _init = ->
        _getList()

      vm.newQuestionnaire = ->
        $location.url '/content/edit/questionnaire'

      vm.changePageSize = (pageSize) ->
        vm.currentPage = 1
        vm.pageSize = pageSize
        _getList()

      vm.changePage = (currentPage) ->
        vm.currentPage = currentPage
        _getList()

      _init()
      vm
  ]
