define [
  'wm/app'
  'wm/config'
], (app, config) ->
  app.registerController 'wm.ctrl.uhkklp.news', [
    'restService'
    '$location'
    'notificationService'
    (restService, $location, notificationService) ->
      vm = this

      Date.prototype.format = (fmt) ->
        o =
          "M+": this.getMonth() + 1
          "d+": this.getDate()
          "h+": this.getHours()
          "m+": this.getMinutes()
          "s+": this.getSeconds()
          "q+": Math.floor (this.getMonth() + 3) / 3
          "S": this.getMilliseconds()
        if /(y+)/.test fmt
          fmt = fmt.replace RegExp.$1, (this.getFullYear() + "").substr 4 - RegExp.$1.length
        (
          fmt = fmt.replace RegExp.$1,  if RegExp.$1.length is 1 then v else ("00" + v).substr ("" + v).length
        )for k, v of o when (new RegExp "(" + k + ")").test fmt
        return fmt

      EDIT_URL = '/uhkklp/edit/news'

      #tabs
      vm.tabs = [
        {
          name: 'news_list_tab_latest'
          value: 0
        }
        {
          name: 'news_list_tab_post'
          value: 1
        }
        {
          name: 'news_list_tab_preference'
          value: 4
        }
        {
          name: 'news_list_tab_audio'
          value: 3
        }
      ]
      tabVal = $location.search().active
      if tabVal == 4
        tabVal = 2
      vm.curTab = if tabVal then vm.tabs[tabVal] else vm.tabs[0]

      #pagination
      #vm.pageSize = 3
      #vm.currentPage = 1

      #table
      vm.list =
        columnDefs: [
          {
            field: 'news_id'
            label: 'news_list_news_id'
          }
          {
            field: 'title'
            label: 'news_list_news_title'
          }
          {
            field: 'begin'
            label: 'news_list_news_begin'
          }
          #{
          # field: 'isTop'
          #  label: 'news_list_news_is_top'
          #}
        ]
        operations: [
          {
            text: 'recipe_list_operations_edit'
            title: 'edit'
            name: 'edit'
          }
          {
            text: 'recipe_list_operations_delete'
            title: 'delete'
            name: 'delete'
          }
        ]
        data: []
        deleteHandler: (item) ->
          restService.del config.resources.newsDelete + '/' + vm.list.data[item].news_id, (data) ->
            if data.code is 200
              notificationService.success '刪除成功！', false
              _getList()
            else
              notificationService.error '删除失败！', false
            return
          return

        editHandler: (item) ->
          $location.url EDIT_URL + '/' + vm.list.data[item].news_id
          return

      #local data
      alllist = []

      vm.changeTab = ->
        vm.list.data = _filterByTab()

      _getList = ->
        params =
          device_id: 0

        restService.get config.resources.newsList, params, (data) ->
          alllist = _formatListData(data.data)
          vm.list.data = _filterByTab()
          #vm.totalCount = vm.list.data.length
          return
        return

      _getList()

      _formatListData = (data) ->
        list = []
        for item, i in data.top
          item = data.top[i]
          item.isTop = '是'
          date = new Date item.begin
          item.begin = date.format 'yyyy-MM-dd hh:mm'
          list.push item

        for item, j in data.list
          item = data.list[j]
          item.isTop = '否'
          date = new Date item.begin
          item.begin = date.format 'yyyy-MM-dd hh:mm'
          list.push item

        return list

      _filterByTab = ->
        list = []
        for item, i in alllist
          item = alllist[i]
          if item.icon == vm.curTab.value
            list.push item
          else
            continue

        return list

      vm
  ]
