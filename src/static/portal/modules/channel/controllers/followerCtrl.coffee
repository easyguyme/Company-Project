define [
  'wm/app'
  'wm/config'
  'moment'
], (app, config) ->
  app.registerController 'wm.ctrl.channel.follower', ['restService'
  '$modal'
  '$scope'
  '$timeout'
  'notificationService'
  '$filter'
  'validateService'
  '$location'
  '$q'
  'utilService'
  '$stateParams'
  (restService, $modal, $scope, $timeout, notificationService, $filter, validateService, $location, $q, utilService, $stateParams) ->
      vm = this

      vm.channelId = $stateParams.id

      # Define the page status
      vm.isListPage = true
      vm.orderBy = 'subscribeTime'
      vm.ordering = 'DESC'

      vm.checkedFollowersStore = []

      # broadcast message
      vm.isCollapsed = false

      vm.breadcrumb = [
        'customer_follower_fans_management',
      ]

      vm.channels = [
        {
          name: 'channel_subscribe'
          value: true
        }
        {
          name: 'channel_unsubscribe'
          value: false
        }
      ]

      vm.infos = [
        {
          name: 'channel_fetch_oauth'
          value: 'oauth'
        }
        {
          name: 'channel_fetch_property'
          value: 'property'
        }
      ]

      vm.genderOptions = [
        {
          text: 'channel_wechat_mass_unlimited'
          value: 0
        }
        {
          text: 'channel_wechat_mass_male'
          value: 'MALE'
        }
        {
          text: 'channel_wechat_mass_female'
          value: 'FEMALE'
        }
        {
          text: 'unknown'
          value: 'UNKNOWN'
        }
      ]

      _getAllTags = ->
        deferred = $q.defer()
        restService.get config.resources.tags, (data) ->
          if data.items
            deferred.resolve data.items
        deferred.promise

      # Table definitions
      vm.list = {
        columnDefs: [
          {
            field: 'followerId'
            label: 'follower_number'
            type: 'link'
          }, {
            field: 'nickname'
            label: 'nickname'
            cellClass: 'text-el'
          }, {
            field: 'subscribeTime'
            label: 'follow_time'
            sortable: true
            desc: true
            sortHandler: ->
              vm.orderBy = 'subscribeTime'
              vm.ordering = if vm.ordering is 'DESC' then 'ASC' else 'DESC'
              getFollowers()
            type: 'date'
          }, {
            field: 'sex'
            label: 'gender'
            type: 'translate'
          }, {
            field: 'location'
            label: 'customer_follower_location'
          }
        ],
        data: []
        operations: [{
            title: 'customer_select_add_tag'
            name: 'tag'
          }
        ],
        selectable: true
        selectHandler: (checked, idx) ->
          if idx?
            follower = vm.list.data[idx]
            if checked
              vm.checkedFollowersStore.push follower.id if $.inArray(follower.id, vm.checkedFollowersStore) is -1
            else
              position = $.inArray(follower.id, vm.checkedFollowersStore)
              vm.checkedFollowersStore.splice position, 1 if position > -1
          else
            _rememberCheck()
          return

        tagHandler: (idx, $event) ->
          vm.bindTagFollowerStore = [vm.list.data[idx].id]
          vm.isShowEditTagDropdown = true
          vm.targetBoundTags = vm.list.data[idx].tags
          vm.tagStyle =
            top: $($event.target).offset().top - 20 - $('.portal-message').height()

        linkHandler: (idx) ->
          modalInstance = $modal.open(
            templateUrl: 'followerDetail.html'
            controller: 'wm.ctrl.channel.follower.detail as follower'
            windowClass: 'user-dialog'
            resolve:
              modalData: ->
                follower: vm.list.data[idx]
          )
      }

      _setCondition = (name) ->
        items = "#{name}s"
        selectItems = []
        if vm[items]
          selectItems = vm[items].filter (item) ->
            item.checked
        selectItems

      _formateCheckParams = (name, condition) ->
        items = []
        if $.isArray condition[name]
          for item in condition[name]
            items.push item.value
        items.join ','

      _getSearchFollowersCondition = ->
        condition = {}

        # current channel
        condition.channelId = vm.channelId

        # get filter checkbox
        condition.tags = angular.copy _setCondition('tag')
        condition.channels = angular.copy _setCondition('channel')
        #condition.infos = angular.copy _setCondition('info')

        # subscribe time
        condition.subscribeTimeFrom = vm.startTime if vm.startTime
        condition.subscribeTimeTo = vm.endTime if vm.endTime

        # gender
        condition.gender = vm.gender if vm.gender

        # location
        if vm.location
          location = vm.location
          condition.country = location.country if location.country and location.country isnt '不限'
          condition.province = location.province if location.province
          condition.city = location.city if location.city

        # cache select conditions
        vm.params = angular.copy condition

        # format data to call api
        condition.tags = _formateCheckParams('tags', condition)
        condition.subscribed = if condition.channels.length is 1 then condition.channels[0].value else null
        delete condition.channels
        #condition.infos = _formateCheckParams('infos', condition)

        condition

      vm.getTextByValue = (options, value) ->
        if options
          for option in options
            if option.value is value
              text = option.text
              break
          text

      getFollowers = ->
        UNKNOWN = 'UNKNOWN'

        vm.list.hasLoading = true

        condition = angular.copy _getSearchFollowersCondition()
        condition.subscribed
        condition['per-page'] = vm.pageSize
        condition['page'] = vm.currentPage
        condition.ordering = vm.ordering if vm.ordering
        condition.orderBy = vm.orderBy if vm.orderBy
        condition.nickname = vm.searchFollower

        restService.get config.resources.followers, condition, (data) ->
          if data
            items = data.items
            vm.list.hasLoading = false
            vm.list.checkAll = false
            if data._meta
              vm.totalItems = data._meta.totalCount

            angular.forEach items, (item) ->

              item.followerId =
                text: item.id
                link: '#'

              item.sex = item.gender.toLowerCase() or '-'
              item.nickname = item.nickname or '-'
              item.subscribeTime = item.subscribeTime or '-'

              location = ''
              locationFields = ['country', 'province', 'city']

              for field in locationFields
                if item[field] and item[field] isnt UNKNOWN
                  location += $filter('translate')(item[field]) + ' '

              item.location = location or '-'

              item.checked = false
              item.checked = true if $.inArray(item.id, vm.checkedFollowersStore) isnt -1

            vm.list.data = items

            vm.list.checkAll = vm.list.data.filter((item) ->
              return item.checked
            ).length is vm.list.data.length and vm.list.data.length

            vm.list.hasLoading = false
        , (errordata) ->
          vm.list.hasLoading = false
        return

      _rememberCheck = ->
        for row in vm.list.data
          position = $.inArray(row.id, vm.checkedFollowersStore)
          if row.checked and position is -1
            vm.checkedFollowersStore.push row.id
          if not row.checked and position isnt -1
            vm.checkedFollowersStore.splice position, 1

      vm.updateTags = ->
        tags = []
        currentTagsName = []
        _getAllTags().then (items) ->
          # remove selected tags which deleted a moment ago
          checkedTags = []

          for item in items
            checked = false
            if vm.params and angular.isArray(vm.params.tags)
              for picked in vm.params.tags
                if picked.name is item.name
                  checked = true
                  break
            tag =
              name: item.name
              value: item.name
              checked: checked
            tags.push tag
            checkedTags.push(tag) if checked

          vm.params.tags = angular.copy(checkedTags) if vm.params

          vm.tagAll = vm.params and tags.length and tags.length is vm.params.tags.length
          vm.tags = angular.copy tags
        return

      _init = ->
        # is display add tag input text on tags panel
        vm.tagAdding = false
        vm.isShowBatchBindTagDropdown = false
        vm.isShowEditTagDropdown = false
        vm.isShow = false
        vm.currentPage = $location.search().currentPage or 1
        vm.pageSize = $location.search().pageSize or 10
        vm.totalItems = 0
        vm.startTime = null
        vm.endTime = null
        vm.gender = vm.genderOptions[0].value

        # Get all the availablle tags
        _getAllTags().then (tags) ->
          if tags
            for tag in tags
              tag.checked = false
              tag.value = tag.name
            vm.tags = angular.copy tags

        getFollowers()

      _init()

      vm.showBatchBindTag = ->
        vm.isShowBatchBindTagDropdown = true

      vm.hideTagModal = (type, bindWay, boundTags) ->
        vm.isShowEditTagDropdown = false
        vm.isShowBatchBindTagDropdown = false

        if type and type is 'bind'
          if bindWay and bindWay is 'batch'
            angular.forEach vm.list.data, (item) ->
              item.checked = false if item.checked
              item.tags = item.tags or []
              if $.inArray(item.id, vm.checkedFollowersStore) isnt -1 and boundTags
                for tag in boundTags
                  if $.inArray(tag, item.tags) is -1
                    item.tags.push tag

            vm.checkedFollowersStore = []
            vm.list.checkAll = false
          else if bindWay and bindWay is 'single'
            angular.forEach vm.list.data, (item) ->
              afterTags = []
              if $.inArray(item.id, vm.bindTagFollowerStore) isnt -1 and boundTags
                afterTags = angular.copy vm.targetBoundTags.filter (tag) ->
                  utilService.getArrayElemIndex(vm.tags, tag, 'name') is -1
                afterTags = afterTags.concat boundTags
                item.tags = angular.copy afterTags


      vm.changePage = (currentPage) ->
        vm.currentPage = currentPage
        # Get all followers without filter
        getFollowers()

      vm.changeSize = (pageSize) ->
        vm.pageSize = pageSize
        vm.currentPage = 1
        # Get all followers without filter
        getFollowers()

      # name like 'channel, info'
      vm.selectAll = (name, value) ->
        # select all model
        allModel = "#{name}All"
        # checkbox repeate
        items = "#{name}s"
        if value?
          vm[allModel] = value
          vm[items] = vm[items].map (item) ->
            item.checked = value
            item

      vm.selectItem = (name, value) ->
        # select all model
        allModel = "#{name}All"
        # checkbox repeate
        items = "#{name}s"
        if value?
          vm[allModel] = vm[items].filter((item) ->
            return item.checked
          ).length is vm[items].length

      _isSelect = (value, objs) ->
        for obj in objs
          if obj.value is value
            return true
        return false

      _fillCheckbox = (name) ->
        # select all model
        allModel = "#{name}All"
        # checkbox repeat
        items = "#{name}s"

        # uncheck all checkbox
        vm[allModel] = false
        for item in vm[items]
          item.checked = false

        # check by condition
        if vm.params[items].length is vm[items].length
          vm[allModel] = true

        if $.isArray(vm.params[items]) and vm.params[items].length > 0
          for item in vm[items]
            if _isSelect(item.value, vm.params[items])
              item.checked = true
        return

      vm.showConditions = ->
        vm.isShow = if vm.isShow is false then true else false

        if vm.isShow
          # fill checkbox
          _fillCheckbox('tag')
          _fillCheckbox('channel')
          #_fillCheckbox('info')

          vm.startTime = vm.params.subscribeTimeFrom if vm.params.subscribeTimeFrom
          vm.endTime = vm.params.subscribeTimeTo if vm.params.subscribeTimeTo
          vm.gender = vm.params.gender if vm.params.gender?
          location =
            country: vm.params.country
            province: vm.params.province
            city: vm.params.city
          vm.location = angular.copy location

      vm.clearCheckbox = (name, isgetList) ->
        # select all model
        allModel = "#{name}All"
        # checkbox repeat
        items = "#{name}s"
        for item in vm[items]
          item.checked = false
        vm[allModel] = false

        if isgetList
          getFollowers()

      vm.clearTime = ->
        vm.startTime = null
        vm.endTime = null
        getFollowers()

      vm.clearGender = ->
        vm.gender = vm.genderOptions[0].value
        getFollowers()

      vm.clearLocation = ->
        vm.location = {}
        getFollowers()

      vm.clearQueryCondition = ->
        vm.clearCheckbox('tag')
        vm.clearCheckbox('channel')
        vm.clearCheckbox('info')
        vm.searchFollower = ''
        vm.gender = vm.genderOptions[0].value
        vm.startTime = null
        vm.endTime = null
        vm.location = {}

      vm.search = ->
        vm.checkedFollowersStore = []
        vm.currentPage = 1
        getFollowers()
        vm.list.emptyMessage = 'search_no_data'

      vm.backToList = ->
        vm.isListPage = true

      # Send Broadcast Message
      vm.remainCharacter = 0

      _addErrorTip = ->
        $('.wechat-message-wrap .message-input').addClass 'form-control-error'
        $('.text-tip').addClass 'hide'
        $('.message-error-tip').removeClass 'hide'
        return

      _removeErrorTip = ->
        $('.wechat-message-wrap .message-input').removeClass 'form-control-error'
        $('.text-tip').removeClass 'hide'
        $('.message-error-tip').addClass 'hide'
        return

      _checkDate = ->
        if vm.sendType is 'timing' and vm.scheduleTime and vm.scheduleTime <= moment().valueOf()
          validateService.showError $('#schedule-picker'), $filter('translate')('channel_wechat_mass_time_safe')
          return false
        return true

      _checkFields = ->
        _removeErrorTip()
        if not vm.message
          _addErrorTip()
          return false
        if vm.remainCharacter? and vm.remainCharacter < 0
          notificationService.warning 'channel_broadcast_message_too_long', false
          return false
        if not _checkDate()
          return false
        return true

      _formatDateTime = (timestamp) ->
        time = moment(timestamp).format('YYYY-MM-DD HH:mm:ss')

        today = moment().format('YYYY-MM-DD')
        tomorrow = moment().add(1, 'day').format('YYYY-MM-DD')
        date = time.substring(0,10)
        dateTime = time.substring(11,19)

        switch date
          when today then time = $filter('translate')("channel_today") + dateTime
          when tomorrow then time = $filter('translate')("channel_tomorrow") + dateTime
          else time = time
        time

      _sendMessage = ->
        if vm.sendType is 'immediate'
          vm.scheduleTime = ''

        vm.msgType = 'TEXT' if typeof vm.message is 'string'
        vm.msgType = 'NEWS' if typeof vm.message is 'object'

        data =
          channelId: vm.channelId
          msgType: vm.msgType
          content: vm.message
          scheduleTime: vm.scheduleTime
          userQuery:
            userIds: vm.checkedFollowersStore
          mixed: vm.mixed

        restService.post config.resources.massmessages, data, (data) ->
          if vm.scheduleTime
            dateText = _formatDateTime(vm.scheduleTime)
            notificationText = $filter('translate')('channel_schedule_broadcast_success_tip') +
              '<i>' + dateText + '</i>'
            notificationService.success notificationText, true
          else
            notificationService.success 'channel_send_now_successfully', false

          vm.isCollapsed = false
          vm.checkedFollowersStore = []

          getFollowers()

      vm.closeMessage = ->
        vm.isCollapsed = false

      vm.showMessageModal = ->
        vm.isCollapsed = not vm.isCollapsed
        if vm.isCollapsed
          vm.sendCount = vm.checkedFollowersStore.length
          vm.sendType = 'immediate'
          vm.scheduleTime = null
          vm.message = null
          vm.mixed = true

      vm.sendMessage = ->
        if _checkFields()
         _sendMessage()

      vm
  ]

  app.registerController 'wm.ctrl.channel.follower.detail', [
    '$modalInstance'
    'restService'
    'modalData'
    ($modalInstance, restService, modalData) ->
      vm = this

      vm.defaultInfo = []
      vm.extendInfo = []

      BASE_URL = '/images/customer/'

      vm.follower = modalData.follower
      openId = vm.follower.originId

      vm.gender = "#{BASE_URL}#{vm.follower.sex}.png" if vm.follower.gender and vm.follower.gender.toLowerCase() isnt 'unknown'

      vm.baseInfo = [
        name: 'follower_number'
        value: vm.follower.id
      ,
        name: 'follow_time'
        value: if vm.follower.subscribeTime and vm.follower.subscribeTime isnt '-' then moment(vm.follower.subscribeTime).format('YYYY-MM-DD HH:mm:ss') else '-'
      ,
        name: 'customer_follower_location'
        value: vm.follower.location
      ,
        name: 'management_language'
        value: vm.follower.language?.replace('-', '_').toLowerCase() or '-'
      ]

      vm.hideModal = ->
        $modalInstance.close()

      # get all cuatomer properties
      _getProperties = ->
        condition =
          "where": {"isVisible": true}
          "orderBy": {"order": "asc"}
          "unlimited": true
        # Get all the properties
        restService.get config.resources.memberProperties, condition, (data) ->
          if data.items
            for item in data.items
              propertyVal = _getValueById(item.id, vm.followerProperty)
              item.value = '-'
              if propertyVal
                switch item.type
                  when 'checkbox'
                    item.value = propertyVal.value.join '、' if propertyVal.value.length > 0
                  when 'date'
                    item.value = moment(propertyVal.value).format('YYYY-MM-DD') if propertyVal.value
                  else
                    item.value = propertyVal.value if propertyVal.value

                if item.name is 'gender'
                  vm.gender = "#{BASE_URL}#{item.value}.png" if item.value and item.value isnt 'unknown'

            vm.detail = data.items


      _getValueById = (propertyId, followerProperties) ->
        for item in followerProperties
          if item.id is propertyId
            return item
        return null

      # get follower property
      _getFollowerProperty = ->
        restService.get config.resources.followerProperty, {openId: openId}, (data) ->
          vm.followerProperty = data
          _getProperties()

      _getFollowerProperty()

      vm
  ]
