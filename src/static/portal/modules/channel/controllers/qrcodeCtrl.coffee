define [
  'wm/app'
  'wm/config'
  'core/directives/wmCharts'
], (app, config) ->
  app.registerController 'wm.ctrl.channel.qrcode', [
    'restService'
    '$stateParams'
    '$scope'
    'notificationService'
    'downloadService'
    '$location'
    '$filter'
    (restService, $stateParams, $scope, notificationService, downloadService, $location, $filter) ->
      vm = this

      channelId = $stateParams.id

      desc = 'desc'
      asc = 'asc'
      createTime = 'createTime'
      scanCount = 'scanCount'
      subscribeCount = 'subscribeCount'

      vm.items = []
      vm.qrcodeList = []
      vm.orderby = createTime
      vm.order = desc
      vm.hideNodata = true

      vm.isShowQrcodeDropdown = false

      _initPagination = ->
        vm.pageSize = $location.search().pageSize or 10
        vm.currentPage = $location.search().currentPage or 1

      _initPagination()

      vm.changeSize = (pageSize) ->
        vm.pageSize = pageSize
        vm.currentPage = 1
        _displayList()

      vm.changePage = (currentPage) ->
        vm.currentPage = currentPage
        _displayList()

      vm.breadcrumb = [
        text: 'promotion_qrcode'
      ]

      vm.list =
      {
        columnDefs: [
          {
            field: 'name'
            label: 'channel_wechat_qrcode_name'
          }
          {
            field: 'reply'
            label: 'channel_reply_message'
            type: 'label'
          }
          {
            field: 'followerTag'
            label: 'chnnel_wechat_follower_tags'
          }
          {
            field: 'scanCount'
            label: 'channel_wechat_qrcode_scan'
            sortable: true
            desc: true
            sortHandler: (colDef) ->
              _sortList colDef
          }
          {
            field: 'subscribeCount'
            label: 'channel_wechat_qrcode_subscribe'
            sortable: true
            desc: true
            sortHandler: (colDef) ->
              _sortList colDef
          }
        ]
        data: vm.items
        selectable: false
        operations: [
          {
            name: 'statistics'
            title: 'view_statistics'
          }
          {
            name: 'edit'
          }
          {
            name: 'qrcode'
          }
          {
            name: 'delete'
          }
        ]

        editHandler: (idx) ->
          $location.url '/channel/edit/qrcode/' + channelId + '?id=' + vm.qrcodeList[idx].id

        deleteHandler: (idx) ->
          data =
            channelId: channelId
          restService.del config.resources.qrcode + '/' + vm.qrcodeList[idx].id, data, (data) ->
            _displayList()

        statisticsHandler: (idx) ->
          $location.url '/channel/statistics/qrcode/' + channelId + '?id=' + vm.qrcodeList[idx].id

        qrcodeHandler: (idx, $event) ->
            vm.isShowQrcodeDropdown = not vm.isShowQrcodeDropdown
            if vm.isShowQrcodeDropdown
              qrcode = vm.qrcodeList[idx]
              vm.qrcodePaneTop = $($event.target).offset().top - 20 - $('.portal-message').height()
              if qrcode
                vm.downQrcodeName = qrcode.name
                vm.qrcodeUrl = qrcode.imageUrl
            return

      }

      _sortList = (colDef) ->
        vm.orderby = colDef.field
        vm.order = if colDef.desc then desc else asc
        _displayList()

      _displayList = ->
        vm.param =
          channelId: channelId
          'per-page': vm.pageSize
          page: vm.currentPage
          orderby: '{"' + vm.orderby + '":' + '"' + vm.order + '"}'

        restService.get config.resources.qrcodes, vm.param, (data) ->
          vm.qrcodeList = data.items
          if data._meta
            vm.totalItems = data._meta.totalCount
          _transferToTable(vm.qrcodeList)

          return

      _transferToTable = (data) ->
        vm.items = []

        if data
          for qrcode in data
            item = {
              reply: {}
            }
            item.followerTag = ''
            #remove random string with store qrcode name
            item.name = qrcode.name

            if qrcode.name.indexOf('subscribe_qrcode') is 0
              item.name = qrcode.name.replace(/^subscribe_qrcode_\w{24,32}/, $filter('translate')('subscribe_qrcode'))

            if qrcode.content
              item.reply.type = qrcode.msgType
              item.reply.content = if qrcode.msgType is 'TEXT' then qrcode.content else qrcode.content.articles[0].title
            else
              item.reply.content = '-'
            item.subscribeCount = qrcode.subscribeCount
            item.scanCount = qrcode.scanCount
            item.imageUrl = qrcode.imageUrl

            if qrcode.autoTags and qrcode.autoTags.length isnt 0
              for tag, index in qrcode.autoTags
                item.followerTag += qrcode.autoTags
                if index isnt qrcode.autoTags.length - 1
                  item.followerTag = ' '
            else
                item.followerTag = '-'
            vm.items.push item

          vm.list.data = vm.items
          return

      _displayList()

      vm.addQrcode = ->
        $location.url '/channel/edit/qrcode/' + channelId
        return

      return
  ]
