define [
  'wm/app'
  'wm/config'
], (app, config) ->
  app.registerController 'wm.ctrl.channel.broadcast', [
    'restService'
    '$stateParams'
    'notificationService'
    '$location'
    (restService, $stateParams, notificationService, $location) ->
      vm = this

      vm.channelId = $stateParams.id
      vm.hideNodata = true
      createTime = 'createTime'
      finishTime = 'finishTime'
      submitTime = 'submitTime'
      desc = 'desc'
      asc = 'asc'

      vm.breadcrumb = [
        'broadcast'
      ]

      vm.tabs = [
        {
          "name": "channel_wechat_mass_sended"
          "value": 0
        }
        {
          "name": "channel_wechat_mass_sending"
          "value": 1
        }
      ]
      tabVal = $location.search().active
      vm.curTab = if tabVal then vm.tabs[tabVal] else vm.tabs[0]
      statuses = ['FINISHED', 'SCHEDULED']

      vm.list = []

      _initPagination = ->
        vm.pageSize = $location.search().pageSize or 10
        vm.currentPage = $location.search().currentPage or 1
        vm.orderby = if vm.curTab.value is 0 then submitTime else createTime
        vm.order = desc

      _initPagination()

      #send data to get list

      _displayList = ->
        vm.index = -1
        vm.showDetail = false

        listParams =
          channelId: vm.channelId
          status: statuses[vm.curTab.value]
          'per-page': vm.pageSize
          page: vm.currentPage
          orderby: '{"' + vm.orderby + '":' + '"' + vm.order + '"}'

        restService.get config.resources.massmessages, listParams, (data) ->
          vm.list = data.items
          vm.totalItems = data._meta.totalCount
          return
        return

      _displayList()

      vm.changeSize = (pageSize) ->
        vm.pageSize = pageSize
        vm.currentPage = 1
        _displayList()

      vm.changePage = (currentPage) ->
        vm.currentPage = currentPage
        _displayList()

      vm.changTab = ->
        vm.showDetail = false
        vm.currentPage = 1
        vm.order = 'desc'
        vm.orderby = if vm.curTab.value is 0 then submitTime else createTime
        _displayList()

      vm.orderByCreateTime = ->
        vm.order = if vm.order is desc then asc else desc
        _displayList()

      vm.closeDialog = ->
        vm.showDetail = false
        vm.index = -1

      ##show detail
      vm.getDetail = (index) ->
        vm.showDetail = true
        vm.index = index
        vm.detail = vm.list[index]
        query = vm.detail.userQuery
        if (not query.tags or query.tags.length is 0) and not query.country and not query.province and not query.city and not query.gender and (not query.userIds or query.userIds.length is 0)
          vm.detail.allFans = '全部粉丝'

        if query.userIds?
          len = query.userIds.length
          if len > 0
            vm.detail.sendCount = len

        if query.tags and query.tags.length > 0
          vm.detail.tags = query.tags.join('、')
        if query.country or query.province or query.city
          address = []
          address.push(query.country) if query.country
          address.push(query.province) if query.province
          address.push(query.city) if query.city
          vm.detail.address = address.join('， ')
         vm.detail.gender = query.gender if query.gender

      vm.deleteMsg = (id, $event) ->
        notificationService.confirm $event,{
          title: "channel_wechat_broadcast_delete"
          submitCallback: _deleMsgHandler
          params: [id]
        }

      _deleMsgHandler = (id) ->
        data =
          channelId: $stateParams.id
        restService.del config.resources.massmessage + '/' + id, data, (data) ->
          _displayList()
        return

      vm.createMsg = ->
        $location.url '/channel/edit/broadcast/' + vm.channelId

      vm.editMsg = (id) ->
        $location.url '/channel/edit/broadcast/' + vm.channelId + '?msg=' + id

      return
  ]
