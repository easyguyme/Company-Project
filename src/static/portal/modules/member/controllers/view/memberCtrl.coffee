define [
  'wm/app'
  'wm/config'
], (app, config) ->
  app.registerController 'wm.ctrl.member.view.member', [
    'restService'
    '$stateParams'
    '$modal'
    '$sce'
    '$q'
    '$interval'
    'channelService'
    'utilService'
    '$scope'
    '$rootScope'
    '$filter'
    (restService, $stateParams, $modal, $sce, $q, $interval, channelService, utilService, $scope, $rootScope, $filter) ->
      vm = this
      rvm = $rootScope

      vm.isShowMemberOrderStats = $.inArray('store', rvm.enabledModules) isnt -1

      HELPDESK = 'helpdesk'

      origins =
        WECHAT: 'wechat'
        WEIBO: 'weibo'
        ALIPAY: 'alipay'
        PORTAL: 'portal'
        APP_ANDROID: 'app:android'
        APP_IOS: 'app:ios'
        APP_WEB: 'app:web'
        APP_WEBVIEW: 'app:webview'
        OTHERS: 'others'

      _init = ->
        vm.memberId = $stateParams.id if $stateParams.id
        vm.isShowCardPoster = false
        vm.isShowQrcode = false
        vm.isShowEditRemarksPane = false
        vm.defaultRemarks = ''
        vm.gender = 'male'
        vm.breadcrumb = [
          {
            text: 'customer_member_management'
            href: '/member/member'
          }
          'customer_member_detail'
        ]

        #fix bug when enter member detail page by modal, 'modal-open class will overflow:hidden'
        $('body').removeClass('modal-open')

        vm.ordersStatsConf = [
          title: 'member_cumulative_amount'
          icon: '/images/customer/accruingamounts.png'
          bgColor: ['#efa46c', 'rgba(240, 165, 110, 0.4)']
          fontColor: ['#dc8746', '#7b7b7b']
          width: ['0%', '0%']
          data: []
        ,
          title: 'member_purchase_last'
          icon: '/images/customer/times.png'
          bgColor: ['#8abbd8', 'rgba(140, 185, 215, 0.4)']
          fontColor: ['#508caf', '#7b7b7b']
          width: ['0%', '0%']
          data: []
        ,
          title: 'member_avg_per_deal'
          icon: '/images/customer/average_in_amount.png'
          bgColor: ['#aad096', 'rgba(170, 205, 150, 0.4)']
          fontColor: ['#6fa155', '#7b7b7b']
          width: ['0%', '0%']
          data: []
        ,
          title: 'member_highest_amount'
          icon: '/images/customer/highest.png'
          bgColor: ['#c2bdda', 'rgba(195, 190, 215, 0.4)']
          fontColor: ['#c2bdda', '#7b7b7b']
          width: ['0%', '0%']
          data: []
        ]

        # Get member
        _getMember()

        # Get orders stats
        _getOrderStats()

      # Get image size(width and height) according image url
      _getImageDimension = (url) ->
        deferred = $q.defer()

        # Record current time stamp
        startTime = new Date().getTime()

        # Assembling image url in order to clear cache
        imgUrl = "#{url}?#{startTime}"

        # Create image object
        img = new Image()
        img.src = imgUrl

        imgTimer = $interval ->
          if img.width > 0 or img.height > 0
            size =
              width: img.width
              height: img.height
            deferred.resolve size
            $interval.cancel imgTimer
        , 50

        deferred.promise

      # Format member info
      _formatMemberInfo = (data) ->
        vm.viewer = data if data?
        if vm.viewer.properties?
          angular.forEach vm.viewer.properties, (property) ->
            vm.viewer[property.name] = property.value
        if vm.viewer.socialAccount? and vm.viewer.socialAccount?.type?
          vm.viewer.socialAccount.typeName = vm.viewer.socialAccount?.type.toLowerCase()

          origin = vm.viewer.socialAccount.origin

          switch origin
            when origins.WEIBO
              vm.accountTypeName = 'weibo'
            when origins.WECHAT
              serviceAccounts = ['service_auth_account', 'service_account']
              typeName = vm.viewer.socialAccount?.type.toLowerCase()
              if jQuery.inArray(vm.viewer.socialAccount.typeName, serviceAccounts) > -1
                vm.accountTypeName = 'wechat_service'
              else
                vm.accountTypeName = 'wechat_subscription'
            when origins.ALIPAY
              vm.accountTypeName = 'alipay'
            when origins.PORTAL
              vm.accountTypeName = 'portal'
              vm.viewer.socialAccount.name = vm.accountTypeName or ''
            when origins.APP_ANDROID, origins.APP_IOS, origins.APP_WEB, origins.APP_WEBVIEW
              vm.accountTypeName = origin.replace ':', '_'
              vm.viewer.socialAccount.name = vm.accountTypeName or ''
            else
              vm.accountTypeName = 'others'
              vm.viewer.socialAccount.name = vm.accountTypeName or ''

        vm.gender = vm.viewer.gender or ''
        vm.gender = '' if vm.viewer.gender is 'unknown'
        vm.viewer.birthday = moment(Number vm.viewer.birthday).format "YYYY-MM-DD" if vm.viewer.birthday?
        vm.viewer.avatar = vm.viewer.avatar or "/images/management/image_hover_default_avatar.png"
        vm.fontColor = vm.viewer.card?.fontColor or "#ffffff"
        cardExpiredAtArr = vm.viewer.cardExpiredAt.split(" ")
        vm.viewer.cardExpiredAt = cardExpiredAtArr[0]
        vm.viewer.position = $.trim(vm.viewer.location.country + ' ' + vm.viewer.location.province + ' ' + vm.viewer.location.city)
        if vm.viewer.location.detail
          vm.viewer.position = vm.viewer.position + ' ' + vm.viewer.location.detail
        _getImageDimension(vm.viewer.qrcodeUrl).then (size) ->
          vm.qrcodeImageRight = -(size.width + 18)

        vm.defaultRemarks = vm.viewer.remarks
        vm.displayRemarks = vm.viewer.remarks

        # Get member properties
        _getMemberProperties()

      _createTab = (origin) ->
        enableMods = rvm.enabledModules
        tabs = []

        switch origin
          when 'wechat', 'weibo', 'alipay'
            tabs = [
                active: true
                name: 'member_wechat_interact'
                template: 'interaction.html'
              ,
                active: false
                name: 'member_point_hostory'
                template: 'point.html'
            ]
          else
            tabs = [
              active: true
              name: 'member_point_hostory'
              template: 'point.html'
            ]

        if $.inArray(HELPDESK, enableMods) isnt -1
          tabs.push({active: false, name: 'member_use_helpdesk', template: 'helpdesk.html'})

        vm.tabs = tabs

        return

      ###
      # Get Member info
      ###
      _getMember = ->
        restService.get config.resources.member + '/' + vm.memberId, (data) ->

          _formatMemberInfo(data)
          # create tabs according to channel
          _createTab(data.socialAccount.origin)

      ###
      # Get all properties
      ###
      _getMemberProperties = ->
        condition =
          "where": {"isVisible": true}
          "orderBy": {"order": "asc"}
          "unlimited": true
        # Get all the properties
        restService.get config.resources.memberProperties, condition, (data) ->
          vm.memberProperties = data.items if data?.items
          vm.properties = []
          if vm.memberProperties
            vm.extendedProperties = $filter('filter')(vm.memberProperties, {isDefault: false})
            if vm.extendedProperties
              for memberItem in vm.extendedProperties
                value = ''
                if vm.viewer.properties
                  for item in vm.viewer.properties
                    if memberItem.id is item.id
                      value = item.value
                      if memberItem.type is 'date'
                        value = moment(value).format('YYYY-MM-DD') if value
                      else if memberItem.type is 'checkbox'
                        value = value.join('、') if value and angular.isArray(value)
                      else if memberItem.type is 'textarea'
                        value = if value then $sce.trustAsHtml(utilService.replaceCharacter value) else '--'
                if memberItem.type is 'radio'
                  if $.isArray(memberItem.options) and memberItem.options.length > 0
                    value = value or memberItem.options[0]
                property =
                  name: memberItem.name
                  value: value or '--'
                  type: memberItem.type
                vm.properties.push property

      ###
      # Get orderStats info
      ###
      _getOrderStats = ->
        params =
          memberId: vm.memberId
        restService.get config.resources.memberOrderStats, params, (data) ->
          _formatOrderStatsInfo(data)

      # Format OrderStats info
      _formatOrderStatsInfo = (data) ->
        vm.orderStats = angular.copy data
        vm.ordersStatsConf[0].data = [data.consumptionAmount, data.consumptionAmountAvg]
        vm.ordersStatsConf[1].data = [data.recentConsumption, data.recentConsumptionAvg]
        vm.ordersStatsConf[2].data = [data.consumption, data.consumptionAvg]
        vm.ordersStatsConf[3].data = [data.memberMaxConsumption, data.maxConsumption]
        vm.ordersStatsConf[0].width = _operateStatsWidth(data.consumptionAmount, data.consumptionAmountAvg)
        vm.ordersStatsConf[1].width = _operateStatsWidth(data.recentConsumption, data.recentConsumptionAvg)
        vm.ordersStatsConf[2].width = _operateStatsWidth(data.consumption, data.consumptionAvg)
        vm.ordersStatsConf[3].width = _operateStatsWidth(data.memberMaxConsumption, data.maxConsumption)

      _operateStatsWidth = (param1, param2) ->
        width = ['0%', '0%']
        if param1 isnt 0 or param2 isnt 0
          param1Width = ''
          param2Width = ''

          if param1 >= param2
            param1Width = '100%'
            param2Width = Number(param2 * 100 / param1) + '%'
          else
            param1Width = Number(param1 * 100 / param2) + '%'
            param2Width = '100%'
          width = [param1Width, param2Width]
        return width

      # init member detail page
      _init()

      vm.showPopupPanel = (type) ->
        vm["isShow#{type}"] = true

      vm.hidePopupPanel = (type) ->
        vm["isShow#{type}"] = false

      vm.hideEditRemarksPane = ->
        vm.isShowEditRemarksPane = false
        vm.viewer.remarks = vm.defaultRemarks

      vm.showEditRemarksPane = ->
        vm.isShowEditRemarksPane = true

      vm.saveRemarks = ->
        condition =
          remarks: vm.viewer.remarks
        restService.put config.resources.member + '/' + vm.memberId, condition, (data) ->
          vm.defaultRemarks = vm.viewer.remarks
          vm.displayRemarks = vm.viewer.remarks
        vm.isShowEditRemarksPane = false

      vm.openProperties = ->
        modalInstance = $modal.open(
          templateUrl: 'properties.html'
          controller: 'wm.ctrl.member.view.properties'
          windowClass: 'properties-dialog'
          resolve:
            modalData: ->
              properties: vm.properties

        ).result.then( (data) ->
          return
        )

      vm.viewPurchase = ->
        modalInstance = $modal.open(
          templateUrl: 'purchase.html'
          controller: 'wm.ctrl.member.view.purchase as purchase'
          windowClass: 'member-purchase-dialog'
          resolve:
            modalData: ->
              memberId: vm.memberId
        ).result.then( (data) ->
          return
        )

      vm
  ]
  .registerController 'wm.ctrl.member.view.properties', [
    '$scope'
    '$modalInstance'
    'modalData'
    ($scope, $modalInstance, modalData) ->
      vm = $scope
      vm.properties = modalData.properties

      vm.hideModal = ->
        $modalInstance.close()

      vm
    ]

  .registerController 'wm.ctrl.member.profile.interaction', [
    'restService'
    '$stateParams'
    'channelService'
    '$modal'
    'notificationService'
    '$filter'
    '$location'
    (restService, $stateParams, channelService, $modal, notificationService, $filter, $location) ->
      vm = this

      vm.currentPage = $location.search().currentPage or 1
      vm.pageSize = $location.search().pageSize or 5
      vm.totalItems = 0
      vm.curTab = 0

      memberId = $stateParams.id

      ICON_BASE_URL = '/images/customer/'
      DATE_FORMAT = 'yyyy-MM-dd'
      MENU_TYPE = 0
      MESSAGE_TYPE = 1

      origins =
        WECHAT: 'wechat'
        WEIBO: 'weibo'
        ALIPAY: 'alipay'
        PORTAL: 'portal' # offline
        APP_ANDROID: 'app:android'
        APP_IOS: 'app:ios'
        APP_WEB: 'app:web' # mobile browser
        APP_WEBVIEW: 'app:webview' # mobile browser
        OTHERS: 'others' # others

       # Tabs with button style
      vm.tabs = [
        {
          active: true
          name: 'member_menu_interact'
          value: 0
        }
        {
          active: false
          name: 'member_message_keyword'
          value: 1
        }
      ]

      # Select items
      vm.channelItems = []

      # Menu Table
      vm.menuList =
        columnDefs: [
          {
            field: 'name'
            label: 'member_menu_content'
            type: 'link'
          }
          {
            field: 'menuType'
            label: 'member_menu_type'
            type: 'translate'
          }
          {
            field: 'channel'
            label: 'member_interact_channel'
            type: 'iconText'
          }
          {
            field: 'hitCount'
            label: 'member_click_number'
            sortable: true
            desc: true
          }
        ]
        data: []

        linkHandler: (idx) ->
          modalInstance = $modal.open(
            templateUrl: 'menu.html'
            controller: 'wm.ctrl.member.menu'
            windowClass: 'user-dialog'
            resolve:
              modalData: ->
                menu: vm.menuList.data[idx]
                openId: vm.openId
                channelId: vm.channelId
          ).result.then( (data) ->
            return
          )

        sortHandler: (colDef) ->
          key = colDef.field
          value = if colDef.desc then 'desc' else 'asc'
          vm.orderby = '{"' + key + '":' + '"' + value + '"}'
          vm.currentPage = 1
          _getList(vm.curTab)

      # Message Table
      vm.messageList =
        columnDefs: [
          {
            field: 'messageContent'
            label: 'member_message_content'
            type: 'html'
          }
          {
            field: 'channel'
            label: 'member_interact_channel'
            type: 'iconText'
          }
          {
            field: 'interactTime'
            label: 'member_interact_time'
            sortable: true
            desc: true
          }
        ]
        data: []

        sortHandler: (colDef) ->
          key = colDef.field
          value = if colDef.desc then 'desc' else 'asc'
          vm.orderby = value.toUpperCase()
          vm.currentPage = 1
          _getList(vm.curTab)

      vm.changeChannel = (item, idx) ->
        vm.channel = vm.channelItems[idx]
        vm.channelId = vm.channel.channelId
        vm.openId = vm.channel.openId
        _initPageInfo(vm.curTab)
        _renderPage(vm.curTab)

      vm.changeTab = (index) ->
        vm.curTab = index

        for tab in vm.tabs
          tab.active = false
        vm.tabs[index].active = true

        _initPageInfo(index)
        _renderPage(index)

      vm.changePage = (currentPage) ->
        vm.currentPage = currentPage
        _getList(vm.curTab)

      vm.changeSize = (pageSize) ->
        vm.pageSize = pageSize
        vm.currentPage = 1
        _getList(vm.curTab)

      vm.getMessageDetail = ->
        if vm.totalCount is 0
          notificationService.warning 'member_no_interact_messages', false
          return

        modalInstance = $modal.open(
          templateUrl: 'message.html'
          controller: 'wm.ctrl.member.message'
          resolve:
            modalData: ->
              openId: vm.openId
              channelId: vm.channelId
        ).result.then( (data) ->
          return
        )

      _getChannels = ->
        params =
          memberId: memberId
        restService.get config.resources.memberChannels, params, (data) ->
          for channel in data
            channel.channelName = {}
            status = if channel.status is 'enable' then '' else '_disabled'
            switch channel.origin
              when origins.WECHAT
                channel.type = channel.type.toLowerCase()
                icon = if channel.type.indexOf('service') isnt -1 then 'service' else 'subscription'
                icon = "wechat_#{icon}"
                icon = "wechat#{status}" if status
              when origins.WEIBO, origins.ALIPAY
                icon = "#{channel.origin}#{status}"

            channel.value = channel.channelId
            channel.channelName =
              text: channel.name
              icon: "#{ICON_BASE_URL}#{icon}.png"

            vm.channelItems.push channel

          vm.channel = vm.channelItems[0]
          vm.channelId = vm.channel.value
          vm.openId = vm.channel.openId

          _getMenuList()
          _getMenuOverview()

      _initPageInfo = (index) ->
        vm.currentPage = 1
        vm.pageSize = 5
        if index is MESSAGE_TYPE
          vm.orderby = 'DESC'
          vm.messageList.columnDefs[2].desc = true
        else
          vm.orderby = ''
          vm.menuList.columnDefs[3].desc = true


      _getOverviewText = (index) ->
        if index is 0
          vm.overview = [
            {
              text: 'member_menu_total_times'
            }
            {
              text: 'member_last_interact_time'
              notail: true
            }
          ]
        else
          vm.overview = [
            {
              text: 'member_send_message_total_times'
            }
            {
              text: 'member_trigger_keywords_times'
            }
            {
              text: 'member_last_interact_time'
              notail: true
            }
          ]

      _getParams = ->
        params =
          channelId: vm.channelId
          openId: vm.openId
          page: vm.currentPage
          'per-page': vm.pageSize
        params

      _formatNumber = (num, date) ->
        defaultText = '--'
        num = if num? and num isnt '' then num else defaultText
        if num isnt defaultText and date
          #firefox need to get timestamp with '2012/12/12 12:12:12' not '2012-12-12 12:12:12'
          num = $filter('date')(new Date(num.replace(/-/g, '/')).valueOf(), DATE_FORMAT)
        num

      _encodeHtml = (str) ->
        str.replace /[<>&"]/g, (c) ->
          {
            '<': '&lt;'
            '>': '&gt;'
            '&': '&amp;'
            '"': '&quot;'
          }[c]

      _getMenuOverview = ->
        params =
          channelId: vm.channelId
          openId: vm.openId

        restService.get config.resources.menuOverview, params, (data) ->

          vm.overview[0].value = _formatNumber data.hitCount
          vm.overview[1].value = _formatNumber data.lastHitTime, true

      _getMessageOverview = ->
        params =
          channelId: vm.channelId
          openId: vm.openId

        restService.get config.resources.messageOverview, params, (data) ->

          vm.overview[0].value = _formatNumber data.messageCount
          vm.overview[1].value = _formatNumber data.keyCount
          vm.overview[2].value = _formatNumber data.lastInteractTime, true

      _getMenuList = (channels) ->
        params = angular.copy _getParams()
        params.orderby = vm.orderby

        restService.get config.resources.menuList, params, (data) ->

          vm.totalCount = data._meta.totalCount

          items = []

          menuMap =
            mainMenu: 'member_main_menu'
            subMenu: 'member_sub_menu'

          for item in data.items
            item.name =
              text: item.content
              link: '#'
              explaination: 'member_menu_deleted' if item.isDeleted

            item.menuType = menuMap[item.type]
            item.channel = vm.channel.channelName

            items.push item

          vm.menuList.data = items

      _getMessageList = ->
        params = angular.copy _getParams()
        params.ordering = vm.orderby

        restService.get config.resources.messageList, params, (data) ->

          vm.totalCount = data._meta.totalCount

          items = []
          for item in data.items
            message = item.message.substr(0, 32) + if item.message.length > 32 then '...' else ''
            item.messageContent =
              tooltip: _encodeHtml item.message

            if item.msgType is 'IMAGE'
              item.messageContent.text = $filter('translate')('channel_unsupport_message_type')
              item.messageContent.tooltip = $filter('translate')('channel_unsupport_message_type')
            else
              message = _encodeHtml message
              item.messageContent.text = message
              if item.keycode
                replaceTxt = "<span class='member-hint-keyword'>#{item.keycode}</span>"
                item.messageContent.text = message.replace(new RegExp(item.keycode, 'g'), replaceTxt)

            item.channel = vm.channel.channelName

            items.push item

          vm.messageList.data = items

      _getList = (type) ->
        if type is MENU_TYPE
          _getMenuList()
        else
          _getMessageList()

      _renderPage = (index) ->
        _getOverviewText(index)
        if index is MENU_TYPE
          _getMenuOverview()
          _getMenuList()
        else
          _getMessageOverview()
          _getMessageList()

      _getChannels()
      _getOverviewText(MENU_TYPE)

      vm

  ]

  .registerController 'wm.ctrl.member.profile.point', [
    'restService'
    '$filter'
    '$q'
    'utilService'
    '$stateParams'
    '$location'
    (restService, $filter, $q, utilService, $stateParams, $location) ->
      vm = this
      origins =
        WECHAT: 'wechat'
        WEIBO: 'weibo'
        ALIPAY: 'alipay'
        PORTAL: 'portal'
        APP_ANDROID: 'app:android'
        APP_IOS: 'app:ios'
        APP_WEB: 'app:web'
        APP_WEBVIEW: 'app:webview'
        OTHERS: 'others'

      rules = ['birthday', 'perfect_information', 'first_card']

      _init = ->
        vm.currentPage = $location.search().currentPage or 1
        vm.totalItems = 0
        vm.pageSize = $location.search().pageSize or 5
        vm.memberId = $stateParams.id if $stateParams.id

        vm.list = {
          columnDefs: [
            {
              field: 'channel'
              label: 'nav_channel'
              type: 'scoreChannels'
              cellClass: 'score-channels-cell'
            }, {
              field: 'increment'
              label: 'customer_score_change'
            }, {
              field: 'createdAt'
              label: 'time'
              sortable: true
              desc: true
            }, {
              field: 'description'
              label: 'customer_rule_desc'
              type: 'description'
              cellClass: 'over-text'
            }
          ],
          data: []
          selectable: false
          sortHandler: (colDef) ->
            key = colDef.field
            value = if colDef.desc then 'desc' else 'asc'
            vm.orderBy = "{\"#{key}\":\"#{value}\"}"
            vm.currentPage = 1
            _getScores()
        }

        _getScores()
        _getMember()

      ###
      # Get channels
      ###
      _getChannels = ->
        deferred = $q.defer()
        if not vm.channels
          restService.get config.resources.channelsAll, (data) ->
            if data
              vm.channels = angular.copy utilService.formatChannels data
              deferred.resolve vm.channels
        else
          deferred.resolve vm.channels

        deferred.promise

      ###
      # Get Member info
      ###
      _getMember = ->
        restService.get config.resources.member + '/' + vm.memberId, (data) ->
          if data
            vm.score = data.score
            vm.totalScoreAfterZeroed = data.totalScoreAfterZeroed

      ###
      # Get sent score list
      ###
      _getScores = ->
        condition =
          'memberId': vm.memberId
          'per-page': vm.pageSize
          'page': vm.currentPage
        condition.orderBy = vm.orderBy if vm.orderBy
        restService.get config.resources.scores, condition, (data) ->
          if data?.items
            _getChannels().then ->
              scores = _formatScores(data.items)

              vm.list.data = angular.copy scores
            vm.totalItems = data._meta.totalCount
            vm.pageSize = data._meta.perPage
            vm.pageCount = data._meta.pageCount

      _formatScores = (items) ->
        scores = []
        angular.forEach items, (item) ->
          score =
            descriptions: ''
            increment: if item.increment > 0 then ('+' + item.increment) else (item.increment + '')
            createdAt: item.createdAt or '-'
            channel: {}

          if item.channel

            if item.brief
              score.description = '【' + $filter('translate')("customer_score_#{item.brief}") + '】'

            if item.description
              if item.brief is 'rule_assignee' and $.inArray(item.description, rules) > -1
                item.description = "customer_score_#{item.description}"
                score.description += $filter('translate')(item.description)
              else
                score.description += item.description

            score.description = score.description or '-'

            score.channel = {}

            if item.channel.id
              key = 'channelId'
              value = item.channel.id
            else
              key = 'id'
              value = item.channel.origin

            channel = utilService.getArrayElem vm.channels, value, key

            if channel
              if $.inArray(channel.origin, [origins.WECHAT, origins.WEIBO, origins.ALIPAY]) isnt -1
                channel.icon = channel.icon.replace '_disabled', ''
              score.channel.icon = "/images/customer/#{channel.icon}.png" if channel.icon
              score.channel.text = $filter('translate')(channel.name) if channel.name
              score.channel.suffix = "(#{item.user.name})" if item.user?.name
            else
              score.channel.text = '-'

          else
            # Fix klp bug #3346, old data of score history, when the record's assigner is "exchange goods" or "redee promotion code"
            # the description field contains channel info
            if item.assigner is 'exchange_goods' or item.assigner is 'exchange_promotion_code'
              item.description = item.description.replace(/:/g, '_') if item.description
              score.channel.text = item.description or '-'
              channelOrigin = item.description.replace(/_/g, ':') if item.description
              if channelOrigin and $.inArray(channelOrigin, [origins.PORTAL, origins.APP_ANDROID, origins.APP_IOS, origins.APP_WEB, origins.APP_WEBVIEW, origins.OTHERS]) isnt -1
                score.channel.icon = "/images/customer/#{item.description}.png"
            else
              score.channel.text = 'portal'

            if item.assigner is 'rule_assignee' or item.assigner is 'auto_zeroed'
              score.description = '【' + $filter('translate')("customer_score_#{item.brief}") + '】' if item.brief
            else
              score.description = "【#{$filter('translate')(item.brief)}】" if item.brief

            if item.assigner isnt 'exchange_goods' and item.assigner isnt 'exchange_promotion_code' and item.description
              score.description += $filter('translate')(item.description)

            score.description = score.description or '-'

          scores.push angular.copy score
        scores

      vm.changePage = (currentPage) ->
        vm.currentPage = currentPage
        _getScores()

      vm.changeSize = (pageSize) ->
        vm.pageSize = pageSize
        vm.currentPage = 1
        _getScores()

      _init()
      vm
  ]

  .registerController 'wm.ctrl.member.profile.helpdesk', [
    'restService'
    '$stateParams'
    '$location'
    (restService, $stateParams, $location) ->
      vm = this
      origins =
        WECHAT: 'wechat'
        WEIBO: 'weibo'
        WEBSITE: 'website'
        ALIPAY: 'alipay'
        APP: 'app'

      _init = ->
        vm.currentPage = $location.search().currentPage or 1
        vm.totalItems = 0
        vm.pageSize = $location.search().pageSize or 5
        vm.memberId = $stateParams.id if $stateParams.id

        vm.list = {
          columnDefs: [
             {
              field: 'lastChatTime'
              label: 'helpdesk_session_time'
              cellClass: 'text-el'
              sortable: true
              desc: true
            }, {
              field: 'helpdesk'
              label: 'helpdesk_reception_helpdesk'
              cellClass: 'text-el'
            }, {
              field: 'channel'
              label: 'helpdesk_access_account'
              type: 'iconText'
              isHideText: true
              cellClass: 'session-channel-cell member-helpdesk-icon'
            }
          ],
          data: []
          selectable: false
          sortHandler: (colDef) ->
            key = colDef.field
            value = if colDef.desc then 'desc' else 'asc'
            vm.orderBy = "{\"#{key}\":\"#{value}\"}"
            vm.currentPage = 1
            _getConversations()
        }

        _getConversations()

      _getConversations = ->
        condition =
          'memberId': vm.memberId
          'per-page': vm.pageSize
          'page': vm.currentPage
        condition.orderBy = vm.orderBy if vm.orderBy
        restService.get config.resources.conversations, condition, (data) ->
          if data
            vm.count = data._meta.totalCount
            vm.lastChatDate = data.lastChatDate or '--'
            vm.sessionDatas = []

            for item in data.items
              item.desk.email = item.desk.email or '-'
              session =
                'helpdesk': item.desk.email
                'lastChatTime': item.lastChatTime

              switch item.client.source
                when origins.WECHAT
                  serviceAccounts = ['service_auth_account', 'service_account']
                  typeName = item.client.wechatAccountInfo?.type.toLowerCase() or ''
                  if $.inArray(typeName, serviceAccounts) > -1
                    icon = 'wechat_service'
                    name = 'follower_wechat_service_number'
                  else
                    icon = 'wechat_subscription'
                    name = 'follower_wechat_subscription_number'
                when origins.WEIBO, origins.WEBSITE, origins.APP, origins.ALIPAY
                  name = icon = item.client.source

              session.channel =
                icon: "/images/customer/#{icon}.png"
                text: name
              vm.sessionDatas.push session
            vm.list.data = vm.sessionDatas

            vm.totalItems = data._meta.totalCount
            vm.pageSize = data._meta.perPage
            vm.pageCount = data._meta.pageCount

      vm.changePage = (currentPage) ->
        vm.currentPage = currentPage
        _getConversations()

      vm.changeSize = (pageSize) ->
        vm.pageSize = pageSize
        vm.currentPage = 1
        _getConversations()

      _init()
      vm
  ]

  .registerController 'wm.ctrl.member.view.purchase', [
    '$scope'
    '$modalInstance'
    'modalData'
    'restService'
    '$filter'
    'utilService'
    '$location'
    ($scope, $modalInstance, modalData, restService, $filter, utilService, $location) ->
      vm = $scope
      vm.currentPage = $location.search().currentPage or 1
      vm.pageSize = $location.search().pageSize or 10
      vm.totalCount = 0

      vm.list =
        columnDefs: [
          field: 'goodsName'
          label: 'product_promotion_goods_name'
          cellClass: 'text-el'
          headClass: 'goodsname-head'
        ,
          field: 'createdAt'
          label: 'purchase_on'
          sortable: true
          cellClass: 'text-el'
          headClass: 'create-head'
        ,
          field: 'storeName'
          label: 'purchase_form'
          cellClass: 'text-el'
          headClass: 'goodsname-head'
        ,
          field: 'staffName'
          label: 'store_order_service_staff'
          cellClass: 'text-el'
          headClass: 'goodsname-head'
        ,
          field: 'goodsNumber'
          label: 'quantity'
        ,
          field: 'totalPrice'
          label: 'purchase_amount'
          cellClass: 'text-el'
        ,
          field: 'payWay'
          label: 'payment_method'
        ,
          field: 'remark'
          label: 'product_coupon_name'
          cellClass: 'text-el'
          headClass: 'goodsname-head'
        ]
        data: []
        nodata: 'no_data'
        hasLoading: true
        sortHandler: (colDef) ->
          key = colDef.field
          value = if colDef.desc then 'asc' else 'desc'
          vm.orderBy = '{"' + key + '":' + '"' + value + '"}'
          vm.currentPage = 1
          _getList()

      _getList = ->
        params =
          expand: 'store'
          memberId: modalData.memberId
          'page': vm.currentPage
          'per-page': vm.pageSize
        params.orderBy = angular.copy vm.orderBy if vm.orderBy
        params.beginCreatedAt = angular.copy vm.startTime if vm.startTime
        if vm.endTime
          params.endCreatedAt = vm.endTime + 24 * 3600 * 1000 - 1
        restService.get config.resources.memberOrders, params, (data) ->
          if data.items
            orders = []
            angular.forEach data.items, (item) ->
              item.storeName =
                text: utilService.formateString 5, item.store?.name or '-'
                tooltip: item.store?.name or '-'

              item.staffName =
                text: utilService.formateString 5, item.staff?.name or '-'
                tooltip: item.staff?.name or '-'

              if item.storeGoods.count > 0
                item.goodsName = item.storeGoods.name
                item.goodsNumber = item.storeGoods.count

              item.remark = item.remark or '-'
              item.createdAt = item.createdAt.substr(0, 10)
              item.payWay = $filter('translate')('member_cash') if item.payWay is 'manual'
              item.payWay = $filter('translate')('alipay') if item.payWay is 'alipay'
              orders.push item
            vm.list.data = angular.copy orders

            if data._meta
              vm.currentPage = data._meta.currentPage
              vm.totalCount = data._meta.totalCount
              vm.pageSize = data._meta.perPage
              vm.pageCount = data._meta.pageCount

            vm.list.hasLoading = false
        , ->
          vm.list.hasLoading = false

      vm.clear = ->
        vm.startTime = null
        vm.endTime = null
        vm.currentPage = 1
        _getList()

      vm.selectDate = ->
        _getList()

      vm.changePage = (currentPage) ->
        vm.currentPage = currentPage
        _getList()

      vm.changeSize = (pageSize) ->
        vm.pageSize = pageSize
        vm.currentPage = 1
        _getList()

      vm.hideModal = ->
        $modalInstance.close()

      _getList()

      vm
  ]

  .registerController 'wm.ctrl.member.menu', [
    'modalData'
    'restService'
    '$modalInstance'
    '$scope'
    'debounceService'
    '$timeout'
    (modalData, restService, $modalInstance, $scope, debounceService, $timeout) ->
      vm = $scope

      menu = modalData.menu
      openId = modalData.openId
      channelId = modalData.channelId

      vm.content = menu.content
      vm.isDeleted = menu.isDeleted
      vm.pageSize = 5
      vm.orderby = '{"refDate":"desc"}'

      vm.list =
        columnDefs: [
          {
            field: 'refDate'
            label: 'member_click_time'
            type: 'date'
            format: 'yyyy-MM-dd'
            sortable: true
            desc: true
          }, {
            field: 'hitCount'
            label: 'member_click_number'
            sortable: true
          }
        ]
        data: []

        sortHandler: (colDef) ->
          key = colDef.field
          value = if colDef.desc then 'desc' else 'asc'
          vm.orderby = '{"' + key + '":' + '"' + value + '"}'
          vm.currentPage = 1
          vm.list.data = []
          _getMenuDetail()

      vm.hideModal = ->
        $modalInstance.close()

      $timeout( ->
        tbodyWrapper = $('.tbody-wrapper')[0]
        $('.tbody-wrapper').scroll debounceService.callback( ->
          if tbodyWrapper.scrollHeight - tbodyWrapper.clientHeight - tbodyWrapper.scrollTop < 20
            vm.currentPage++
            if vm.currentPage <= vm.pageCount
              _getMenuDetail()
        )
      , 1000)

      _getMenuDetail = ->
        params =
          menuId: menu.id
          openId: openId
          channelId: channelId
          orderby: vm.orderby
          'per-page': vm.pageSize
          page: vm.currentPage

        restService.get config.resources.menuStat, params, (data) ->
          vm.pageCount = data._meta.pageCount
          vm.list.data = vm.list.data.concat data.items

      _getMenuDetail()

      vm

  ]

  .registerController 'wm.ctrl.member.message', [
    'modalData'
    'restService'
    '$modalInstance'
    '$scope'
    '$sce'
    '$rootScope'
    (modalData, restService, $modalInstance, $scope, $sce, $rootScope) ->
      vm = $scope
      rvm = $rootScope

      vm.currentPage = 1
      vm.pageSize = 20
      vm.showPagination = true

      messages = []
      openId = modalData.openId
      channelId = modalData.channelId

      vm.hideModal = ->
        $modalInstance.close()

      vm.loadMore = ->
        vm.currentPage++
        _getMessageDetail()

      _encodeHtml = (str) ->
        str.replace /[<>&"]/g, (c) ->
          {
            '<': '&lt;'
            '>': '&gt;'
            '&': '&amp;'
            '"': '&quot;'
          }[c]

      _getChannelImg = (channelId) ->
        for channel in rvm.channels
          if channel.id is channelId
            return channel.avatar
        return null

      _getMessageDetail = ->
        params =
          openId: openId
          channelId: channelId
          page: vm.currentPage
          'per-page': vm.pageSize

        restService.get config.resources.messageDetail, params, (data) ->

          vm.totalCount = data._meta.totalCount
          vm.pageCount = data._meta.pageCount

          if vm.currentPage is vm.pageCount
            vm.showPagination = false

          for msg in data.items
            if msg.direction is 'RECEIVE'
              msg.headerImgUrl = msg.sender.headerImgUrl or '/images/management/image_hover_default_avatar.png'
            else
              msg.headerImgUrl = _getChannelImg(msg.accountId)

            if msg.message.msgType is 'TEXT'
              msg.message.content = _encodeHtml msg.message.content

              if msg.keycode
                reg = new RegExp msg.keycode, 'g'
                msg.message.content = msg.message.content.replace reg, '<span class="fs14">' + msg.keycode + '</span>'
                msg.message.content = $sce.trustAsHtml msg.message.content

            else
              msg.message.content = 'channel_unsupport_message_type'

            messages.push msg

          vm.historyMessages = messages

      _getMessageDetail()

      vm

  ]

