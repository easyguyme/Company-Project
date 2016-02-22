define [
  'wm/app'
  'wm/config'
], (app, config) ->
  app.registerController 'wm.ctrl.uhkklp.activity', [
    'restService'
    '$location'
    'notificationService'
    (restService, $location, notificationService) ->
      vm = this

      EDIT_URL = '/uhkklp/edit/activity'
      LIST_URL = '/uhkklp/activity'
      EXPORT_URL = '/uhkklp/download/activity'

      #tabs
      vm.tabs = [
        {
          name: 'activity_list_tab_active_list'
          value: 0
        }
        {
          name: 'activity_list_tab_inactive_list'
          value: 1
        }
      ]
      tabVal = $location.search().active
      vm.curTab = if tabVal then vm.tabs[tabVal] else vm.tabs[0]

      #pagination
      vm.pageSize = 10
      vm.currentPage = 1

      #table
      vm.list =
        columnDefs: [
          {
            field: '_id'
            label: 'activity_list_activity_id'
          }
          {
            field: 'name'
            label: 'activity_list_activity_name'
          }
          {
            field: 'duration'
            label: 'activity_list_activity_duration'
          }
          {
            field: 'updatedAt'
            label: 'activity_list_activity_update_date'
          }
          {
            field: 'isActive'
            label: 'activity_list_activity_status'
            type: 'status'
          }
        ]
        operations: [
            {
              text: 'activity_list_operations_edit'
              name: 'edit'
              title: 'edit'
            }
            {
              text: 'activity_list_operations_export'
              name: 'export'
              title: 'activity_list_operations_export'
            }
            {
              text: 'activity_list_operations_delete'
              name: 'delete'
              title: 'delete'
            }
          ]
        data: []
        # hasLoading: true

        editHandler: (item) ->
          $location.url EDIT_URL + '/' + vm.list.data[item]._id

        exportHandler: (item) ->
          $location.url EXPORT_URL + '/' + vm.list.data[item]._id

        switchHandler: (item) ->
          restService.get config.resources.changeStatus + '/' + vm.list.data[item]._id, (data) ->
            if data
              if data.code is 1000
                vm.list.data[item].isActive = 'DISABLE'
                notificationService.warning '已存在上架拉霸，您不能再上架拉霸！',true
              if data.code is 200
                _getList()
                if vm.curTab.value is 1
                  notificationService.success '上架拉霸成功！',true
                else
                  notificationService.success '下架拉霸成功！',true
            return
          return

        deleteHandler: (item) ->
          restService.del config.resources.deleteActivity + '/' + vm.list.data[item]._id, (data) ->
            if data
              if data.code is 200
                _getList()
                notificationService.success '刪除拉霸成功！',true
            return
          return

      _getList = ->
        params =
          pageSize: vm.pageSize
          currentPage: vm.currentPage
          status: if vm.curTab.value is 0 then 'Y' else 'N'

        restService.get config.resources.activityList, params, (data) ->
          vm.list.data = _formatListData(data.list)
          vm.totalCount = data.count
          return
        return

      _getList()

      _formatListData = (data) ->
        i = 0
        while i < data.length
          bar = data[i]
          bar.duration = bar.startDate + ' ~ ' + bar.endDate
          bar._id = bar._id.$id
          bar.isActive = if bar.status is 'Y' then 'ENABLE' else 'DISABLE'
          i++
        data

      vm.createHandler = ->
        restService.get config.resources.canOnshelveActivity, (data) ->
          if data.code is 1
            notificationService.warning '已存在上架拉霸，您不能新增！',true
          else
            $location.url EDIT_URL
          return
        return

      vm.changeTab = ->
        vm.currentPage = 1
        _getList()

      vm.changePage = (currentPage) ->
        vm.currentPage = currentPage
        _getList()

      vm.changeSize = (pageSize) ->
        vm.pageSize = pageSize
        vm.currentPage = 1
        _getList()

      vm

  ]
