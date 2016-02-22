define [
  'wm/app'
  'wm/config'
], (app, config) ->
  app.registerController 'wm.ctrl.member.member', [
    'restService'
    '$modal'
    '$scope'
    '$location'
    '$timeout'
    'exportService'
    '$filter'
    'notificationService'
    'utilService'
    'validateService'
    '$interval'
    (restService, $modal, $scope,$location, $timeout, exportService, $filter, notificationService, utilService, validateService, $interval) ->
      vm = this
      vm.isShow = false
      vm.isHide = true
      vm.showCondition = false
      vm.totalCount = 0
      vm.enableExport = true
      vm.bindTags = []
      vm.checkedMembersStore = []
      vm.mergeMembers = []
      vm.updateStatus = {
        isDisabled: true
        id: ''
      }

      vm.breadcrumb = [
        'member_management'
      ]

      vm.params =
        accounts: []
        tags: []
        cards: []
        cardStates: []
        startTime: null
        endTime: null
        gender: ''
        country: ''
        province: ''
        city: ''
        searchKey: ''
        'per-page': $location.search().pageSize or 10
        page: $location.search().currentPage or 1

      vm.changeSize = (pageSize) ->
        vm.pageSize = pageSize
        vm.params['per-page'] = pageSize
        _getMembers()
        return

      vm.changePage = (currentPage) ->
        vm.currentPage = currentPage
        vm.params.page = currentPage
        _getMembers()
        return

      vm.isShowBatchBindTagDropdown = false
      # selected updating follower index
      vm.openedEditTagDropdownIndex = -1

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

      vm.cardStates = [
        {
          name: 'product_card_one_day_expired'
          value: 1
        }
        {
          name: 'product_card_seven_day_expired'
          value: 2
        }
        {
          name: 'product_card_expired'
          value: 3
        }
      ]

      vm.pointItems = [
        {
          value: 'increase',
          text: 'customer_increase_point'
        }
        {
          value: 'reduce',
          text: 'customer_reduce_point'
        }
      ]
      vm.pointType = vm.pointItems[0].value

      _init = ->
        vm.currentPage = $location.search().currentPage or 1
        vm.pageSize = $location.search().pageSize or 10
        vm.tagAll = false
        vm.cardAll = false
        vm.cardStateAll = false
        vm.manualCards = []
        vm.params.startTime = null
        vm.params.endTime = null
        vm.params.gender = vm.genderOptions[0].value

        restService.get config.resources.tags, (data) ->
          vm.tags = data.items
          return

        cardParam =
          unlimited: true
        restService.get config.resources.cards, cardParam, (data) ->
          vm.cards = data.items
          if data.items
            for card in data.items
              if not card.isAutoUpgrade
                vm.manualCards.push {text: card.name, value: card.id}
            vm.sendCardId = vm.manualCards[0].value
            return

        # Table definitions
        vm.list = {
          columnDefs: [
            {
              field: 'cardId'
              label: 'customer_members_number'
              type: 'link'
            }, {
              field: 'name'
              label: 'customer_members_name'
            }, {
              field: 'card'
              label: 'customer_members_card'
            }, {
              field: 'score'
              label: 'member_current_score'
              sortable: true
              desc: true
              headClass: 'member-score'
            }, {
              field: 'scoreOfYear'
              label: 'member_score_of_year'
              headClass: 'member-score'
            }, {
              field: 'socialAccount'
              label: 'customer_follower_social_account'
              type: 'icon'
              cellClass: 'member-account-tbody'
              headClass: 'member-account-thead'
            }, {
              field: 'createdAt'
              label: 'customer_members_register_time'
              sortable: true
              desc: true
              type: 'date'
            }, {
              field: 'isDisabled'
              label: 'member_status'
              type: 'status'
            }
          ],
          data: []
          operations: [
            {
              name: 'edit'
            }, {
              title: 'customer_select_add_tag'
              name: 'tag'
            }
          ],
          selectable: true
          hasLoading: true

          sortHandler: (colDef) ->
            key = colDef.field
            value = if colDef.desc then 'desc' else 'asc'
            vm.orderBy = '{"' + key + '":' + '"' + value + '"}'
            vm.params.page = 1
            _getMembers()

          editHandler: (idx) ->
            $location.path '/member/edit/member/' + vm.list.data[idx]?.id
            return

          selectHandler: (checked, idx) ->
            if idx?
              member = vm.list.data[idx]
              position = $.inArray(member.id, vm.checkedMembersStore)
              if checked
                if position is -1
                  vm.checkedMembersStore.push member.id
                  vm.mergeMembers.push member
              else
                if position > -1
                  vm.checkedMembersStore.splice position, 1
                  vm.mergeMembers.splice position, 1
            else
              _rememberCheck()
            return

          tagHandler: (idx, $event) ->
            vm.bindTagMemberStore = [vm.list.data[idx].id]
            vm.isShowEditTagDropdown = true
            vm.targetBoundTags = vm.list.data[idx].tags
            vm.tagStyle =
              top: $($event.target).offset().top - 20

          # update status
          switchHandler: (idx) ->
            item = vm.list.data[idx]
            item.isDisabled = if item.isDisabled is 'DISABLE' then false else true
            vm.updateStatus.isDisabled = item.isDisabled
            vm.updateStatus.id = item.id
            _updateStatus()
        }

        # Only get query parameters for member expired state
        cardState = parseInt($location.search()['cardState'])
        if not isNaN(cardState) and cardState > 0
          for state in vm.cardStates
            state.check = false
          vm.cardStates[cardState - 1].check = true
          vm.showCondition = true
          vm.params.cardStates = [cardState]
        vm.search()
        return

      _rememberCheck = ->
        for row in vm.list.data
          _cacheCheck(row)

      _cacheCheck = (row) ->
        position = $.inArray(row.id, vm.checkedMembersStore)
        if row.checked and position is -1
          vm.checkedMembersStore.push row.id
          vm.mergeMembers.push row
        else if not row.checked and position isnt -1
          vm.checkedMembersStore.splice position, 1
          vm.mergeMembers.splice position, 1

      # Show the customer filter
      vm.showConditions = ->
        vm.showCondition = not vm.showCondition
        vm.isShow = not vm.isShow
        vm.isHide = not vm.isHide

        vm.location =  angular.copy vm.conditions.location
        vm.params = angular.copy vm.conditions
        vm.genderText = angular.copy vm.conditions.genderText

        angular.forEach vm.accounts, (label) ->
          vm.accountAll = false
          label.check = false

        angular.forEach vm.tags, (lable) ->
          lable.check = false
          vm.tagAll = false
        if vm.tags
          if vm.conditions.tags.length is vm.tags.length
            vm.tagAll = true
          for value in vm.conditions.tags
            vm.tags.forEach (lable) ->
              if lable.name is value
                lable.check = true

        angular.forEach vm.cards, (lable) ->
          lable.check = false
          vm.cardAll = false
        if vm.conditions.cards.length is vm.cards.length
          vm.cardAll = true
        for value in vm.conditions.cards
          vm.cards.forEach (lable) ->
            if lable.name is value
              lable.check = true

        vm.cardStatus = []
        angular.forEach vm.cardStates, (lable) ->
          lable.check = false
          vm.cardStateAll = false
        if vm.conditions.cardStates.length is vm.cardStates.length
          vm.cardStateAll = true
        for value in vm.conditions.cardStates
          vm.cardStates.forEach (lable) ->
            if lable.value is value
              vm.cardStatus.push lable.name
              lable.check = true

        return

      # Click all tags checkbox
      vm.selectAllTag = ->
        selectAllCheck 'tag'
        return

      # Click all cards checkbox
      vm.selectAllCard = ->
        selectAllCheck 'card'
        return

      vm.selectAllState = ->
        selectAllCheck 'cardState', 'value'

      vm.selectTag = (tag) ->
        selectItem 'tag', tag
        return

      vm.selectCard = (card) ->
        selectItem 'card', card
        return

      vm.selectState = (status) ->
        selectItem 'cardState', status, 'value'

      selectAllCheck = (name, key) ->
        key = 'name' if not key
        itemsName = name + 's'
        if vm[name + 'All']
          items = []
          angular.forEach vm[itemsName], (item) ->
            items.push item[key]
            item.check = true
          vm.params[itemsName] = items
        else
          angular.forEach vm[itemsName], (item) ->
            item.check = false
          vm.params[itemsName] = []
        return

      selectItem = (name, item, key) ->
        key = 'name' if not key
        itemsName = name + 's'
        allName = name + 'All'
        index = $.inArray(item[key], vm.params[itemsName])
        if item.check
          if index is -1
            vm.params[itemsName].push item[key]
        else
          if index isnt -1
            vm.params[itemsName].splice(index, 1)

        if vm.params[itemsName].length is vm[itemsName].length
          vm[allName] = true
        else
          vm[allName] = false
        return

      vm.isSelectedAccount = (id) ->
        return $.inArray(id, vm.conditions.accounts) isnt -1

      vm.changeGender = (gender, idx) ->
        vm.params.gender = gender
        vm.genderText = vm.genderOptions[idx].text
        return

      vm.clearAccounts = ->
        vm.params.accounts = []
        _getMembers()

      vm.clearTags = ->
        vm.params.tags = []
        vm.tagAll = false
        _getMembers()

      vm.clearCards = ->
        vm.params.cards = []
        vm.cardAll = false
        _getMembers()

      vm.clearCardStates = ->
        vm.params.cardStates = []
        vm.cardStateAll = false
        _getMembers()

      vm.clearCreatedAt = ->
        vm.params.startTime = null
        vm.params.endTime = null
        _getMembers()

      vm.clearGender = ->
        vm.params.gender = ''
        _getMembers()

      vm.clearLocation = ->
        vm.location = {}
        vm.params.location = {}
        _getMembers()

      # Clear params
      vm.clear = ->
        vm.params =
          accounts: []
          tags: []
          cards: []
          startTime: null
          endTime: null
          gender: vm.genderOptions[0].value
          country: ''
          province: ''
          city: ''
          searchKey: ''
          'per-page': vm.pageSize
          page: vm.currentPage
        vm.tagAll = false
        vm.cardAll = false
        #vm.accountAll = false
        vm.cardStateAll = false
        angular.forEach vm.tags, (tag) ->
          tag.check = false
          return
        angular.forEach vm.cards, (card) ->
          card.check = false
          return
        angular.forEach vm.cardStates, (state) ->
          state.check = false
          return
        angular.forEach vm.accounts, (account) ->
          account.check = false
          return
        vm.location = {}
        return

      _assembleParams = ->
        vm.conditions = angular.copy vm.params
        vm.conditions.location = angular.copy vm.location
        vm.conditions.genderText = angular.copy vm.genderText

        if vm.location
          vm.params = angular.extend vm.params, vm.location
        params = angular.copy vm.params
        params.cards = []
        angular.forEach vm.params.cards, (name) ->
          angular.forEach vm.cards, (card) ->
            if card.name is name
              params.cards.push card.id
            return
          return

        angular.forEach params, (param, index) ->
          if angular.isArray param
            params[index] = param.join ','
          return

        params['orderBy'] =
          'createdAt': 'desc'

        if vm.orderBy
          params['orderBy'] = vm.orderBy

        return params

      _getMembers = ->
        params = _assembleParams()

        vm.list.checkAll = false
        params['fields'] = 'id,cardNumber,properties,card,score,socialAccount,createdAt,tags,isDisabled,totalScoreAfterZeroed'
        restService.get config.resources.members, params, (data) ->
          items = []
          data.items = if data.items then data.items else []
          angular.forEach data.items, (member) ->
            member.tags = [] if not member.tags
            member.propertyName = ''
            angular.forEach member.properties, (property) ->
              member.propertyName = property.value if property.name is 'name'
              return

            member.totalScoreAfterZeroed = member.totalScoreAfterZeroed or 0
            costScoreOfThisYear = member.totalScoreAfterZeroed - member.score

            memberName = '--'
            if member.propertyName
              memberName =
                text: utilService.formateString 8, member.propertyName
                tooltip: member.propertyName
            items.push
              id: member.id
              cardId:
                text: member.cardNumber?.toString()
                link: '/member/view/member/' + member.id
              name: memberName
              card: member.card?.name or '--'
              score: member.score or 0
              scoreOfYear: member.totalScoreAfterZeroed + '/' + costScoreOfThisYear
              createdAt: member.createdAt
              isDisabled: if member.isDisabled then 'DISABLE' else 'ENABLE'
              tags: member.tags
              socialAccount: utilService.formatChannel member.socialAccount
            return

          if data._meta
            vm.totalCount = data._meta.totalCount
            vm.pageCount = data._meta.pageCount
            vm.params['per-page'] = data._meta.perPage
            vm.params.page = data._meta.currentPage

          vm.list.data = items
          #loading
          vm.list.hasLoading = false
          angular.forEach vm.list.data, (item) ->
            item.checked = false
            item.checked = true if $.inArray(item.id, vm.checkedMembersStore) isnt -1
          vm.list.checkAll = vm.list.data.filter((item) ->
              return item.checked
            ).length is vm.list.data.length and vm.list.data.length

        , ->
          vm.list.hasLoading = false

        return

      _updateStatus = ->
        restService.put config.resources.updateMemberStatus, vm.updateStatus, (data) ->
          _clearCheck()
          _getMembers()

      # get all tags after add tags
      vm.updateTags = ->
        tags = []
        currentTagsName = []
        restService.get config.resources.tags, (data) ->
          if data.items
            angular.forEach data.items, (item) ->
              checked = vm.params and $.inArray(item.name, vm.params.tags) isnt -1
              tag =
                name: item.name
                check: checked
              tags.push tag
              currentTagsName.push item.name

            # remove selected tags which deleted a moment ago
            checkedTags = []
            for name in currentTagsName
              if vm.conditions and $.inArray(name, vm.conditions.tags) isnt -1
                checkedTags.push name

            vm.conditions.tags = angular.copy(checkedTags) if vm.conditions

            vm.tagAll = vm.conditions and tags.length and tags.length is vm.conditions.tags.length
            vm.tags = angular.copy tags
            return

      # Search the members with params
      vm.search = ->
        _clearCheck()
        _getMembers()
        vm.list.emptyMessage = 'search_no_data'

      _clearCheck = ->
        vm.mergeMembers = []
        vm.checkedMembersStore = []

      vm.showBatchBindTag = ->
        vm.isShowBatchBindTagDropdown = true

      vm.hideTagModal = (type, bindWay, boundTags) ->
        vm.isShowBatchBindTagDropdown = false
        vm.isShowEditTagDropdown = false
        vm.isShowScoreModal = false
        vm.isShowCardModal = false

        if type and type is 'bind'
          if bindWay and bindWay is 'batch'
            angular.forEach vm.list.data, (item) ->
              item.checked = false if item.checked
              item.tags = item.tags or []
              if $.inArray(item.id, vm.checkedMembersStore) isnt -1 and boundTags
                for tag in boundTags
                  if $.inArray(tag, item.tags) is -1
                    item.tags.push tag

            _clearCheck()
            vm.list.checkAll = false
          else if bindWay and bindWay is 'single'
            angular.forEach vm.list.data, (item) ->
              if $.inArray(item.id, vm.bindTagMemberStore) isnt -1 and boundTags
                item.tags = angular.copy boundTags

      _getStatus = (data) ->
        if data.lack is '-1'
          notificationService.success 'member_import_fail', false
        else
          condition =
            totalCount: data.totalCount
          notificationService.success 'member_import_success', false, condition

      _checkJob = (info) ->
        timer = $interval( ->
          if $location.absUrl().indexOf('/member/member') isnt -1
            param =
              token: info.token
              filename: info.filename
            restService.noLoading().get config.resources.importStatue, param, (data) ->
              if data.status is 2 and data.lack is '1' #run
                condition =
                  totalCount: data.totalCount
                notificationService.success 'member_import_success', false, condition
                vm.list.hasLoading = false
                $interval.cancel timer
                _getMembers()

              else if data.status is 3 #fail
                _getStatus(data)

                vm.list.hasLoading = false
                $interval.cancel timer
                _getMembers()
              else if data.status is 4 #finish
                _getStatus(data)

                vm.list.hasLoading = false
                $interval.cancel timer
                _getMembers()
            , ->
              vm.list.hasLoading = false
          else
            $interval.cancel timer
        , 2000)


      vm.import = ->
        modalInstance = $modal.open(
          templateUrl: 'importMember.html'
          controller: 'wm.ctrl.member.importMember'
          windowClass: 'user-dialog'
          resolve:
            modalData: ->
        ).result.then( (data) ->
          if data
            vm.list.hasLoading = true
            _checkJob(data)
        ,(data) ->
          item = angular.element('.user-dialog').scope()
          if item.filename
            param =
              filename: item.filename
            restService.get config.resources.delUploadMemberFile, param, (data) ->
        )

      vm.export = ->
        if not vm.enableExport
          return

        params = {}
        if vm.checkedMembersStore and vm.checkedMembersStore.length isnt 0
          params['memberId'] = vm.checkedMembersStore.join ','
          allParams = _assembleParams()
          params['orderBy'] = allParams.orderBy
        else
          params = _assembleParams()

        exportService.export 'member', config.resources.exportMember, params, false
        vm.enableExport = false

      $scope.$on 'exportDataPrepared', (event, type) ->
        if type is 'member'
          vm.enableExport = true

      $scope.$watch ->
        $location.search()
      , (newValue) ->
        _init() if not $.isEmptyObject(newValue)

      _init()

      vm.showMergeModal = ->
        modalInstance = $modal.open(
          templateUrl: 'mergeMember.html'
          controller: 'wm.ctrl.member.mergeMember'
          windowClass: 'merge-member-dialog'
          resolve:
            modalData: ->
              angular.copy vm.mergeMembers

        ).result.then( (data) ->
          if data
            params =
              id: data.memberId
              viewTip: $filter('translate')('member_view_member')
            notificationService.success 'member_merge_success_tip', false, params
            vm.checkedMembersStore = []
            vm.mergeMembers = []
            vm.checkAll = false
            _getMembers()
        )

      vm.showScoreModal = ->
        vm.isShowScoreModal = not vm.isShowScoreModal
        _clearPointValues()

      vm.checkPoint = (score) ->
        score = score.trim()
        intRex =  /^[0-9]*[1-9][0-9]*$/
        error = ''
        if not intRex.test score
          error = 'customert_point_input_string'
        error

      _clearPointValues = ->
        vm.pointType = vm.pointItems[0].value
        vm.point = ''
        vm.description = ''
        if $('#points').length > 0
          validateService.restore($('#points'), $filter('translate')('customert_point_input_string'))
        return

      _clearCardValues = ->
        vm.sendCardId = vm.manualCards[0].value
        vm.expiredAt = null

      _getCardNumber = ->
        numbers = []
        for member in vm.mergeMembers
          numbers.push member.cardId.text
        numbers

      vm.issuePoint = ->
        if not vm.checkPoint(vm.point)
          numbers = _getCardNumber()
          vm.scores = if vm.pointType is vm.pointItems[0].value then parseInt vm.point else parseInt '-' + vm.point
          scoreChangeMessage = if vm.scores > 0 then 'customer_score_give_success' else 'customer_score_deduct_success'

          params =
            score: vm.scores
            filterType: 'number'
            numbers: numbers
            description: vm.description

          restService.post config.resources.giveScore, params, (data) ->
            notificationService.success scoreChangeMessage, false
            vm.isShowScoreModal = false
            _clearCheck()
            _getMembers()

      vm.hidePointModal = ->
        vm.isShowScoreModal = false

      vm.showCardModal = ->
        vm.isShowCardModal = not vm.isShowCardModal
        _clearCardValues()

      vm.sendCard = ->
        numbers = _getCardNumber()
        params =
          cardId: vm.sendCardId
          cardExpiredAt: vm.expiredAt
          cardNumbers: numbers

        restService.post config.resources.provideCard, params, (data) ->
          notificationService.success 'customer_card_extend_success'
          vm.isShowCardModal = false
          _clearCheck()
          _getMembers()

      return
  ]

  .registerController 'wm.ctrl.member.mergeMember', [
    'modalData'
    'restService'
    'notificationService'
    '$modalInstance'
    '$scope'
    (modalData, restService, notificationService, $modalInstance, $scope) ->
      vm = $scope

      vm.memberList = {
        columnDefs: [
          {
            field: 'cardId'
            label: 'customer_members_number'
            type: 'link'
          }, {
            field: 'name'
            label: 'customer_members_name'
          }, {
            field: 'card'
            label: 'customer_members_card'
          }, {
            field: 'score'
            label: 'customer_members_score'
            sortable: true
            headClass: 'member-score'
          }, {
            field: 'socialAccount'
            label: 'customer_follower_social_account'
            type: 'icon'
            cellClass: 'member-account-tbody'
            headClass: 'member-account-thead'
          }, {
            field: 'createdAt'
            label: 'customer_members_register_time'
            sortable: true
            type: 'date'
          }, {
            field: 'operations'
            label: 'operations'
            type: 'mainRecord'
            headClass: 'member-merge-operation'
          }
        ],
        data: [],
        selectable: false,
        recordHandler: (idx) ->
          vm.mainRecord = modalData[idx]
          _transferOpt(idx)
      }

      _transferOpt = (idx) ->
        for item, index in modalData
          if index is idx
            item.operations =
              isMainRecord: true
          else
            item.operations =
              name: 'record'
              isMainRecord: false

      _transferOpt(0)

      vm.mainRecord = modalData[0]
      vm.memberList.data = modalData

      vm.hideModal = ->
        $modalInstance.close()

      vm.merge = ->
        params = {
          main: ''
          others: []
        }

        for item in modalData
          if item.operations.isMainRecord
            params.main = item.id
          else
            params.others.push item.id

        restService.put config.resources.mergeMembers, params, (data) ->
          $modalInstance.close(
            {
              memberId: vm.mainRecord.id
            }
          )

      vm

  ]

  .registerController 'wm.ctrl.member.importMember', [
    'modalData'
    'restService'
    'notificationService'
    '$modalInstance'
    '$scope'
    '$upload'
    '$interval'
    '$location'
    'messageService'
    '$q'
    (modalData, restService, notificationService, $modalInstance, $scope, $upload, $interval, $location, messageService, $q) ->
      vm = $scope

      delayTime = 5000
      vm.status = false

      vm.fileTypes = ['application/vnd.ms-excel', 'application/octet-stream', 'text/csv',
      'application/wps-office.xls', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'application/wps-office.xlsx']

      _showLoading = ->
        document.getElementById('upload-loading').style.display = 'block'

      _hideLoading = ->
        document.getElementById('upload-loading').style.display = 'none'

      _deleteCache = ->
        param =
          filename: vm.filename
        restService.get config.resources.delUploadMemberFile, param, (data) ->

      _getStatus = (data) ->
        if data.wrong is '-1'
          vm.status = false
          condition =
            row: data.rows
            column: data.cols
            property: data.property
          notificationService.error 'member_import_required', false, condition
        else if data.wrong is '-2'
          vm.status = false
          condition =
            row: data.rows
            column: data.cols
            property: data.property
          notificationService.error 'member_import_unique', false, condition
        else if data.wrong is '-3'
          vm.status = false
          condition =
            row: data.rows
            column: data.cols
            property: data.property
          notificationService.error 'member_import_format_error', false, condition
        else if data.wrong is '-4'
          vm.status = false
          notificationService.error 'member_no_data', false
        else if data.wrong is '-6'
          vm.status = false
          condition =
            missProperty: data.miss.join(',')
          notificationService.error 'member_no_exist', false, condition
        else if data.wrong is '-7'
          vm.status = false
          condition =
            row: data.rows
            column: data.cols
            property: data.property
          notificationService.error 'member_property_illegal', false, condition
        else if data.wrong is '-8'
          vm.status = false
          condition =
            repeatTitle: data.repeat.join(',')
          notificationService.error 'member_repeat_title', false, condition
        else if not data.ignore and data.right > 0 and data.wrong is '0'
          vm.status = true
          notificationService.info 'product_detail_upload_success', false
        else if data.ignore and data.right > 0 and data.wrong is '0'
          # array to string
          vm.status = true
          condition =
            ignoreProperty: data.ignore.join(',')
          notificationService.info 'product_detail_upload_success_ignore', false, condition
        else
          vm.status = false
          notificationService.error 'product_upload_fail', false

      _checkJob = ->
        timer = $interval( ->
          if $location.absUrl().indexOf('/member/member') isnt -1
            param =
              token: vm.token
              filename: vm.filename

            restService.noLoading().get config.resources.checkMemberStatus, param, (data) ->
              if data.status is 3 #fail
                _getStatus(data)

                vm.uploading = false
                _hideLoading()
                _deleteCache()
                $interval.cancel timer
              else if data.status is 4 #finish
                _getStatus(data)
                vm.uploading = false
                _hideLoading()
                $interval.cancel timer
          else
            $interval.cancel timer
        , delayTime)

      vm.hideModal = ->
        $modalInstance.close()

      vm.upload = (files) ->
        vm.file = ''
        phase = if $scope.$root then $scope.$root.$$phase else ''
        if phase isnt '$digest' and phase isnt '$apply'
          $scope.$digest()
        for file in files
          if $.inArray(file.type, vm.fileTypes) is -1
            notificationService.error 'member_file_type_error', false
          else
            if(file.size >= Math.pow(10, 6) * 3)
              delayTime = Math.pow(10, 4) * 3
            else if(file.size > Math.pow(10, 6))
              delayTime = Math.pow(10, 4) * 1.5

            vm.file = file.name.substring(0, file.name.lastIndexOf('.'))
            vm.uploading = true
            _showLoading()
            tmoffset = new Date().getTimezoneOffset() / 60
            $upload.upload({
              url: config.resources.uploadMember + '?tmoffset=' + tmoffset
              headers:
                'Content-Type': 'multipart/form-data'
              file: file
              method: 'POST'
            }).progress((evt) ->

            ).success((data, status, headers, config) ->
              notificationService.info 'product_uploading_tip', false
              vm.token = data.data.token
              vm.filename = data.data.filename
              _checkJob()
            ).error ->
              vm.uploading = false
              _hideLoading()
              notificationService.error 'member_have_error', false

      _import = ->
        restService.post config.resources.importMember, params, (data) ->
          data =
            ok: data.message

      vm.import = ->
        params =
          filename: vm.filename

        defered = $q.defer()
        restService.post config.resources.importMember, params, (data) ->
          data =
            token: data.data
            filename: vm.filename
          defered.resolve $modalInstance.close(data)
        defered.promise
      vm
    ]
