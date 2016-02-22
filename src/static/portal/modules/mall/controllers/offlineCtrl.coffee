define [
  'wm/app'
  'wm/config'
  'wm/modules/mall/controllers/sendMessageModalCtrl'
], (app, config) ->
  app.registerController 'wm.ctrl.mall.offline', [
    'restService'
    'notificationService'
    '$location'
    '$modal'
    '$filter'
    'validateService'
    '$scope'
    '$rootScope'
    'localStorageService'
    '$q'
    'searchFilterService'
    '$timeout'
    'utilService'
    (restService, notificationService, $location, $modal, $filter, validateService, $scope, $rootScope, localStorageService, $q, searchFilterService, $timeout, utilService) ->
      vm = this
      vm.showCodePage = false
      vm.showGoodsPage = false
      vm.SERACH_CACHE = 'offlineSearchCache'

      REDEMPTION = 'redemption_template'
      PROMOCODE = 'promotioncode_template'
      MAX_PROMOCODE = 100
      STATUS_OFF = 'off'

      ## offline exchange
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
        'per-page': 10
        page: 1

      vm.breadcrumb = [
        'offline_exchange'
      ]

      vm.timeOptions = [
        {
          text: 'channel_wechat_mass_unlimited'
          value: 0
        }
        {
          text: 'within_month'
          value: moment(moment().subtract(29, 'days').format('YYYY-MM-DD 00:00:00')).valueOf()
        }
        {
          text: 'within_week'
          value: moment(moment().subtract(6, 'days').format('YYYY-MM-DD 00:00:00')).valueOf()
        }
        {
          text: 'within_three_days'
          value: moment(moment().subtract(2, 'days').format('YYYY-MM-DD 00:00:00')).valueOf()
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

      vm.memberList = {
        columnDefs: [
          {
            field: 'number'
            label: 'customer_members_number'
            type: 'link'
          }, {
            field: 'name'
            label: 'customer_members_name'
          },{
            field: 'tel'
            label: 'telephone_number'
          }, {
            field: 'score'
            label: 'customer_members_score'
          }
        ],
        data: []
        selectable: false
        operations: [
          {
            name: 'edit'
          }
          {
            name: 'qrcode'
            title: 'product_exchange_code'
          }
          {
            name: 'goods'
            title: 'product_exchange_item'
          }
        ]

        editHandler: (idx) ->
          $location.url "/member/edit/member/" + vm.memberList.data[idx].id

        qrcodeHandler: (idx) ->
          $location.search('qrcode', 't')
          vm.memberId = vm.memberList.data[idx].id
          vm.showCodePage = true
          _clearCodePage()

        goodsHandler: (idx) ->
          $location.search('goods', 't')
          vm.memberId = vm.memberList.data[idx].id
          vm.showGoodsPage = true
          _clearGoodsPage()
      }

      utilService.webhooksObj().then (maps) ->
        vm.webhookObj = maps

      _initOfflinePage = ->
        vm.isShowDefault = true
        vm.hasMember = false
        vm.totalCount = 0
        vm.currentPage = $location.search().currentPage or 1
        vm.pageSize = $location.search().pageSize or 10

        vm.params.startTime = null
        vm.params.endTime = null
        vm.params.gender = vm.genderOptions[0].value

        vm.tagAll = false
        vm.cardAll = false
        vm.cardStateAll = false

        vm.conditions = angular.copy vm.params

        restService.get config.resources.tags, (data) ->
          vm.tags = data.items
          return

        restService.get config.resources.cards, (data) ->
          vm.cards = data.items
          return

      _formatMember = (members) ->
        items = []
        angular.forEach members, (member) ->
          member.tags = [] if not member.tags
          member.propertyName = ''
          angular.forEach member.properties, (property) ->
            member.propertyName = property.value if property.name is 'name'
            member.propertyTel = property.value if property.name is 'tel'
            return

          memberTrueName = '--'
          if member.propertyName
            memberTrueName =
              text: utilService.formateString 8, member.propertyName
              tooltip: member.propertyName
          items.push
            id: member.id
            number: {
              text: member.cardNumber
              link: '/member/view/member/' + member.id
            }
            name: memberTrueName
            tel: member.propertyTel or '--'
            score: member.score or 0
        items

      _getMembers = (type) ->
        vm.showCenterAdd = false
        if type is 'back'
          backedParams = searchFilterService.getFilter vm.SERACH_CACHE
          vm.params = angular.copy backedParams.params
          vm.location = angular.copy backedParams.location
          vm.genderText = angular.copy backedParams.genderText
        vm.conditions = angular.copy vm.params
        vm.conditions.location = angular.copy vm.location
        vm.conditions.genderText = angular.copy vm.genderText

        # Backup condition
        backParams =
          params: vm.params
          location: vm.location
          genderText: vm.genderText
        searchFilterService.setFilter vm.SERACH_CACHE, backParams

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
        params['fields'] = 'id, cardNumber, properties, card, score, socialAccount, createdAt, tags'
        params['searchKey'] = params['searchKey'].replace(/：/g, ':') if params['searchKey']
        restService.get config.resources.members, params, (data) ->
          vm.totalCount = data._meta.totalCount
          vm.pageCount = data._meta.pageCount
          vm.currentPage = data._meta.currentPage
          vm.params['page'] = data._meta.currentPage
          vm.params['per-page'] = data._meta.perPage

          if data.items.length > 0
            items = _formatMember(data.items)
            vm.memberList.data = items

            vm.isShowDefault = false
            vm.hasMember = true
          else
            vm.isShowDefault = true
            vm.hasMember = false
            vm.showCenterAdd = true if type is 'centerAdd'

        return

      _selectAllCheck = (name, key) ->
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

      _selectItem = (name, item, key) ->
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

      # Click all tags checkbox
      vm.selectAllTag = ->
        _selectAllCheck 'tag'
        return

      # Click all cards checkbox
      vm.selectAllCard = ->
        _selectAllCheck 'card'
        return

      # Click all accounts checkbox
      vm.selectAllState = ->
        _selectAllCheck 'cardState', 'value'


      vm.selectTag = (tag) ->
        _selectItem 'tag', tag

      vm.selectCard = (card) ->
        _selectItem 'card', card

      vm.selectState = (status) ->
        _selectItem 'cardState', status, 'value'

      vm.search = ->
        _getMembers()

      vm.clear = ->
        vm.params =
          accounts: []
          tags: []
          cards: []
          cardStates: []
          startTime: null
          endTime: null
          gender: vm.genderOptions[0].value
          country: ''
          province: ''
          city: ''
          'per-page': vm.pageSize
          page: vm.currentPage

        vm.tagAll = false
        vm.cardAll = false
        vm.cardStateAll = false

        angular.forEach vm.tags, (tag) ->
          tag.check = false

        angular.forEach vm.cards, (card) ->
          card.check = false

        angular.forEach vm.cardStates, (status) ->
          status.check = false

        vm.location = {}
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

      vm.showConditions = ->
        vm.followers = not vm.followers
        vm.isShow = not vm.isShow
        searchKey = vm.params['searchKey']

        if vm.conditions
          vm.location =  angular.copy vm.conditions.location
          vm.params = angular.copy vm.conditions
          vm.params['searchKey'] = searchKey
          vm.genderText = angular.copy vm.conditions.genderText
          vm.startTime = angular.copy vm.conditions.startTime
          vm.endTime = angular.copy vm.conditions.endTime

          angular.forEach vm.accounts, (label) ->
            vm.accountAll = false
            label.check = false
          if vm.conditions.accounts
            if vm.conditions.accounts.length is vm.accounts.length
              vm.accountAll = true
            for value in vm.conditions.accounts
              vm.accounts.forEach (label) ->
                  if label.id is value
                    label.check = true

          angular.forEach vm.tags, (lable) ->
            lable.check = false
            vm.tagAll = false
          if vm.conditions.tags
            if vm.conditions.tags.length is vm.tags.length
              vm.tagAll = true
            for value in vm.conditions.tags
              vm.tags.forEach (lable) ->
                if lable.name is value
                  lable.check = true

          angular.forEach vm.cards, (lable) ->
            lable.check = false
            vm.cardAll = false
          if vm.conditions.cards
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

          if vm.conditions.cardStates
            if vm.conditions.cardStates.length is vm.cardStates.length
              vm.cardStateAll = true
            for value in vm.conditions.cardStates
              vm.cardStates.forEach (lable) ->
                if lable.value is value
                  vm.cardStatus.push lable.name
                  lable.check = true

          return

      vm.isSelectedAccount = (id) ->
        return $.inArray(id, vm.conditions.accounts) isnt -1

      vm.changeGender = (gender, idx) ->
        vm.params.gender = gender
        vm.genderText = vm.genderOptions[idx].text
        return

      vm.checkTel = (tel) ->
        validateService.checkTelNum tel

      vm.addMember = ->
        modalInstance = $modal.open(
          templateUrl: 'addMember.html'
          controller: 'wm.ctrl.mall.addMember as member'
          windowClass: 'add-member-dialog'
        ).result.then( (data) ->
          localStorageService.removeItem 'memberMsg'
          if data
            members = _formatMember([data])
            vm.memberList.data = members
            vm.totalCount = members.length
            vm.hasMember = true
            vm.isShowDefault = false
        , (data) ->
          item = angular.element('.add-member-dialog').scope()
          data =
            memberProperties: item.memberProperties
            checkedTags: item.checkedTags
          localStorageService.setItem 'memberMsg', data
        )

      vm.changeSize = (pageSize) ->
        vm.params['per-page'] = pageSize
        vm.params['page'] = 1
        _getMembers()

      vm.changePage = (currentPage) ->
        vm.params['page'] = currentPage
        _getMembers()

      vm.searchMember =  ->
        _getMembers('centerAdd')

      $('#memberInfo').on('focus', (e) ->
        validateService.restore($('#memberInfo'), $filter('translate')('product_offline_redeemer_info_tip'))
      )

      _initOfflinePage()


      ##promo code exchange
      _initCodePage = ->
        vm.breadcrumb2 = [
          {
            icon: 'offline'
            text: 'offline_exchange'
            href: '#'
          }
          'product_exchange_code_title'
        ]
        vm.promoCodeTotalScore = 0
        vm.isShowExpired = false

        vm.promoCodes =
          columnDefs: [
            field: 'code'
            label: 'product_codechange_promo_code'
          ,
            field: 'status'
            label: 'product_promo_code_status'
            type: 'tooltip'
          ,
            field: 'score'
            label: 'product_codechange_points_redeemable'
          ]
          data: []
          operations: [
            {
              name: 'delete'
            }
          ]
          nodata: 'product_codechange_no_promo_codes'
          hasLoading: false
          deleteHandler: (index) ->
            $scope.$apply ( ->
              vm.promoCodeTotalScore -= vm.promoCodes.data[index].score
              code = vm.promoCodes.data.splice(index, 1)

              params =
                code: code[0].code
                memberId: vm.memberId

              _clearExchangeRecord params
            )

        _getMember()

      _getMember = ->
        # Get member
        restService.get config.resources.member + '/' + vm.memberId, (data) ->
          vm.member = data if data?
          if vm.member.properties?
            angular.forEach vm.member.properties, (property) ->
              vm.member[property.name] = property.value or '-'

      _openCodeScores = (params) ->
        modalInstance = $modal.open(
          templateUrl: 'codeScore.html'
          controller: 'wm.ctrl.product.codeScore'
          windowClass: 'user-dialog'
          resolve:
            modalData: ->
              member: vm.member
              totalScore: vm.promoCodeTotalScore
              total: params.code.length
              params: params
          ).result.then( (data) ->
            if data
              vm.displayOfflinePage()
              _getMembers()
              vm.member.score += vm.promoCodeTotalScore
              vm.promoCodes.data = []
              vm.promoCodeTotalScore = 0
        )

      _existCodes = (promoCode) ->
        flag = false
        angular.forEach vm.promoCodes.data, (item) ->
          if angular.isNumber(item.code)
            item.code = "#{item.code}"
          if promoCode.toLowerCase() is item.code.toLowerCase()
            flag = true
        return flag

      _isShowExpired = ->
        vm.isShowExpired = false
        if vm.promoCodes.data.length > 0
          angular.forEach vm.promoCodes.data, (promoCode) ->
            if promoCode.status.text isnt 'valid'
              vm.isShowExpired = true

      _clearCodePage = ->
        vm.promoCodes.data = [] if vm.promoCodes?.data
        vm.promoCodeTotalScore = 0
        vm.promoCodeIds = ''
        vm.exchangeTime = null

      _clearExchangeRecord = (params) ->
        deferred = $q.defer()
        restService.get config.resources.clearExchangeRecord, params, ->
          deferred.resolve()
        deferred.promise

      _unique = (array) ->
        result = []
        if angular.isArray(array) and array.length > 0
          array.sort()
          result.push array[0]
          for item in array
            if item isnt result[result.length - 1]
              result.push item
        result

      _formatPromoCodes = (promoCodes) ->
        if angular.isArray(promoCodes) and promoCodes.length is 0
          validateService.highlight $('#exchange-tip'), $filter('translate')('product_codechange_check_promocode_tip')
        else
          angular.forEach promoCodes, (item) ->
            promoCode =
              code: item.code
              score: item.score
              status:
                text: item.status
                tooltip: item.description
              isValid: item.status is 'valid'

            promoCode.rowClass = 'promocodes-invalid-code' if item.status isnt 'valid'

            # if the code was redeemed, then get redeemer name
            if item and item.status is 'redeemed' and item.memberId?
              memberName = item.memberName or '--'
              if item.memberId
                viewMemberUrl = '/member/view/member/' + item.memberId
                promoCode.status.suffix = "(<a href='#{viewMemberUrl}'>#{memberName}</a>)"
              else
                promoCode.status.suffix = "(#{memberName})"
              vm.promoCodes.data.push promoCode
            else
              vm.promoCodes.data.push promoCode
            vm.promoCodeTotalScore += item.score if item and item.status is 'valid'

      _addPromoCodes = ->
        if vm.promoCodeIds? and vm.promoCodeIds isnt ''

          promoCodeIds = angular.copy vm.promoCodeIds
          promoCodeIds = promoCodeIds.replace(/\s*(,|，)\s*/g, ',') #remove all space and transfer chinese comma to english comma
          promoCodesList = promoCodeIds.split(',')
          promoCodesList = promoCodesList.filter (item) ->
            return item

          # A max of promo codes allowed is 100
          if (promoCodesList.length + vm.promoCodes.data.length) > MAX_PROMOCODE
            notificationService.error 'product_code_exceed_tip', false
            return

          promoCodesList = _unique promoCodesList

          repeatPromoCodesList = promoCodesList.filter (item) ->
            return _existCodes item

          if repeatPromoCodesList.length
            notificationService.error 'product_promo_codes_exist_msg'
            return

          vm.promoCodes.hasLoading = true
          params =
            code: promoCodesList.join(',')
            memberId: vm.memberId
            exchangeTime: vm.exchangeTime or moment().valueOf()
          restService.noLoading().get config.resources.checkPromoCode, params, (data) ->
            if data and data.data
              _formatPromoCodes data.data
              delete vm.promoCodeIds

            vm.promoCodes.hasLoading = false
          , (data) ->
            vm.promoCodes.hasLoading = false
        else
          validateService.highlight $('#exchange-tip'), $filter('translate')('required_field_tip')
        return

      vm.addPromoCodes = ->
        if angular.isArray(vm.promoCodes.data) and not vm.promoCodes.data.length
          params =
            memberId: vm.memberId
          _clearExchangeRecord(params).then ->
            _addPromoCodes()
        else
          _addPromoCodes()

      vm.checkAllPromoCodes = ->
        conditions =
          memberId: vm.memberId

        _clearExchangeRecord(conditions).then ->
          promoCodeIds = []
          angular.forEach vm.promoCodes.data, (promoCode) ->
            promoCodeIds.push promoCode.code

          if promoCodeIds.length > 0
            params =
              code: promoCodeIds.join ','
              memberId: vm.memberId
              exchangeTime: vm.exchangeTime
            restService.get config.resources.checkPromoCode, params, (data) ->
              vm.promoCodes.data = []
              vm.promoCodeTotalScore = 0
              _formatPromoCodes data.data if data and data.data

      vm.redeemPromoCodes = ->
        codes = []
        if vm.promoCodes?.data and angular.isArray vm.promoCodes.data
          vm.promoCodes.data.forEach (item) ->
            if item.status.text is 'valid'
              codes.push item.code

        params =
          code: codes
          memberId: vm.memberId
          exchangeTime: vm.exchangeTime or moment().valueOf()
          useWebhook: vm.webhookObj[PROMOCODE]

        _openCodeScores(params)

      vm.restoreCodesTip = ->
        validateService.restore $('#exchange-tip'), $filter('translate')('product_input_code_tip')
        return

      vm.isCheckCode = (event) ->
        keyCode = event.keyCode or event.which
        if keyCode isnt 13
          vm.restoreCodesTip()
        return

      ## goods exchange
      _getMemberInfo = ->
        if vm.memberId
          restService.get config.resources.member + '/' + vm.memberId, (data) ->
            vm.member = data
            _formatMemberInfo()

      _getMemberAddress = ->
        restService.get config.resources.shippingAddress + '/' + vm.memberId, (data) ->
          vm.address = data.address or ''
          vm.postcode = data.postcode or ''

      _formatMemberInfo = ->
        if vm.member.properties?
          angular.forEach vm.member.properties, (property) ->
            vm.member[property.name] = property.value or '-'

      _checkInt = (value) ->
        re = /^[1-9][0-9]*$/
        re.test(value)

      _checkIntAndZero = (value) ->
        re = /^[0-9]*$/
        re.test(value)

      _checkInput = (elem, value) ->
        if not _checkInt(value)
          validateService.showError(elem, $filter('translate')('product_check_int_error_tip'))
          return false
        else
          index = elem.data('index')
          item = vm.items[index]
          if item.total isnt '' and item.total < Number(value)
            validateService.showError(elem, $filter('translate')('product_check_amount_error_tip'))
            return false
        return true

      _caculateScores = ->
        scores = 0
        for item in vm.items
          scores += item.score * item.quantity if _checkInt(item.quantity)
        scores

      _clearGoodsPage = ->
        vm.items = []
        vm.member = {}
        vm.totalScore = ''
        vm.realTotalScore = ''
        vm.address = ''
        vm.postcode = ''

      _openMessagesPopup = ->
        modalInstance = $modal.open(
          templateUrl: '/build/modules/mall/partials/sendMessageModal.html'
          controller: 'wm.ctrl.mall.sendMessage'
          windowClass: 'assign-helpdesk-dialog'
          resolve:
            modalData: ->
              redemptionItems: vm.items
              usedScore: vm.realTotalScore
              member: vm.member
              type: 'redemption'
              language: $rootScope.user.language
              address: vm.address
              postcode: vm.postcode

          ).result.then( (data) ->

        )

      _initGoodsPage = ->
        vm.items = []
        vm.breadcrumb2 = [
          {
            icon: 'offline'
            text: 'offline_exchange'
            href: '#'
          }
          'product_exchange_item_title'
        ]
        modes = [
            name: 'product_goods_courier_service'
            value: 'express'
          ,
            name: 'product_goods_local_pickup'
            value: 'self'
        ]

        vm.receiveModes = angular.copy modes.slice(0, 1)
        vm.receiveMode = vm.receiveModes[0].value

        _getMemberInfo()
        _getMemberAddress()
        _getReceiveAddresss().then (items) ->
          vm.receiveAddresss = []
          for item in items
            vm.receiveAddresss.push
              text: item.address
              value: item.id
              address: $filter('translate')(item.location.province) + $filter('translate')(item.location.city) + $filter('translate')(item.location.district) + item.location.detail
          vm.receiveModes = angular.copy modes
          vm.receiveMode = vm.receiveModes[0].value
        , ->
          vm.receiveModes = angular.copy modes.slice(0, 1)
          vm.receiveMode = vm.receiveModes[0].value

      _clearLocation = ->
        $location.search('goods', null)
        $location.search('qrcode', null)

      _getReceiveAddresss = ->
        deferred = $q.defer()
        param =
          unlimited: true
        restService.get config.resources.receiveAddresss, param, (data) ->
          if data.items and data.items.length
            deferred.resolve(data.items)
          else
            deferred.reject()
        deferred.promise

      _restoreReceiveModes = (items) ->
        if vm.receiveAddresss and vm.receiveAddresss.length
          hasSelfReceiveMode = false

          if items.length
            for goods in items
              rmodes = goods.receiveModes
              if rmodes and (rmodes is 'self' or (angular.isArray(rmodes) and rmodes.length is 1 and rmodes.indexOf('self') > -1))
                hasSelfReceiveMode = true
                break

          if hasSelfReceiveMode
            vm.receiveModes[0].disabled = 'true'
            if vm.receiveMode isnt 'self'
              vm.pickedAddressId = vm.receiveAddresss[0].value
              vm.receiveMode = 'self'
          else if vm.receiveModes[0].disabled is 'true'
            delete vm.receiveModes[0].disabled

      vm.changeReceiveAddress = (value) ->
        pickedItem = utilService.getArrayElem vm.receiveAddresss, value, 'value'
        if pickedItem
          vm.address = pickedItem.text
          vm.pickedAddress = pickedItem.address

      vm.displayOfflinePage = ->
        vm.showGoodsPage = false
        vm.showCodePage = false
        _clearLocation()

      $scope.$watch 'offline.receiveMode', (value) ->
        vm.address = ''
        vm.postcode = ''
        if value is 'self'
          if vm.receiveAddresss.length
            vm.address = vm.receiveAddresss[0].text
            vm.pickedAddressId = vm.receiveAddresss[0].value
            vm.pickedAddress = vm.receiveAddresss[0].address

      vm.selectGoods = ->
        modalInstance = $modal.open(
          templateUrl: 'selectGoods.html'
          controller: 'wm.ctrl.mall.selectGoods'
          windowClass: 'choose-pictures-dialog'
          resolve:
            modalData: ->
              selectedItems: angular.copy vm.items

        ).result.then( (data) ->
          if data
            vm.items = data
            vm.totalScore = vm.realTotalScore = _caculateScores()
            _restoreReceiveModes(vm.items)
        )

      vm.deleteItem = (index) ->
        temp = vm.items.splice index, 1
        if vm.items.length is 0
          vm.realTotalScore = vm.totalScore = ''
        else if vm.totalScore and temp[0].quantity
          vm.realTotalScore = vm.totalScore -= temp[0].score * Number(temp[0].quantity)

        _restoreReceiveModes(vm.items)

      vm.submit = ->
        canSubmit = true

        inputList = $('.exchange-goods-amount-input')
        for input in inputList
          if not _checkInput($(input), $(input)[0].value)
            canSubmit = false

        if not _checkIntAndZero(vm.realTotalScore)
          validateService.showError($('#totalScore'), $filter('translate')('product_check_int_error_tip'))
          canSubmit = false

        if vm.member.score < vm.realTotalScore
          notificationService.error 'product_member_score_not_enough', false
          canSubmit = false

        if canSubmit
          goods = []
          for item in vm.items
            goods.push {id: item.id, count: Number(item.quantity)}

          params =
            memberId: vm.member.id
            goods: angular.copy goods
            usedScore: Number(vm.realTotalScore)
            address: vm.address
            postcode: vm.postcode
            useWebhook: vm.webhookObj[REDEMPTION]
            receiveMode: vm.receiveMode

          restService.post config.resources.redeemItems, params, (data) ->
            if not vm.webhookObj[REDEMPTION]
              _openMessagesPopup()
            else
              notificationService.success 'product_goods_redeem_successfully', false
            vm.showGoodsPage = false
            _getMembers()

      $('.product-detail-wrapper').on('focusout', '.exchange-goods-amount-input', (e) ->
        value = $(e.target)[0].value
        if _checkInput($(e.target), value)
          $scope.$apply ->
            vm.realTotalScore = vm.totalScore = _caculateScores()
            validateService.restore($('#totalScore'), '')
      )

      $('#totalScore').on('focusout', (e) ->
        if not _checkIntAndZero(vm.realTotalScore)
          validateService.showError($('#totalScore'), $filter('translate')('product_check_int_error_tip'))
      )

      $scope.$watch('offline.showCodePage', (newVal, oldVal) ->
        _initCodePage() if newVal
      )

      $scope.$watch('offline.showGoodsPage', (newVal, oldVal) ->
        _initGoodsPage() if newVal
      )

      watchURL = ->
        $scope.location = $location
        $scope.$watch 'location.url()', (newPath, oldPath) ->
          #Judge whether the address back
          if newPath < oldPath
            vm.displayOfflinePage()
          else if newPath > oldPath
            if newPath.indexOf('qrcode') isnt -1
              vm.showCodePage = true
            if newPath.indexOf('goods') isnt -1
              vm.showGoodsPage = true
            vm.followers = false
            vm.isShow = false

      _init = ->
        if $location.search().search is 't' and searchFilterService.getFilter vm.SERACH_CACHE
          vm.isShowDefault = false
          _getMembers('back')
          $timeout ->
            vm.showConditions()
            vm.followers = false
            vm.isShow = false
          , 1000
        watchURL()

      _init()
      vm
  ]

  app.registerController 'wm.ctrl.mall.addMember', [
    '$scope'
    '$modalInstance'
    'restService'
    'notificationService'
    '$filter'
    'validateService'
    '$timeout'
    'localStorageService'
    'utilService'
    ($scope, $modalInstance, restService, notificationService, $filter, validateService, $timeout, localStorageService, utilService) ->
      vm = $scope

      backupData = localStorageService.getItem 'memberMsg'

      _init = ->
        vm.member = {}
        vm.checkedTags = backupData?.checkedTags or []
        vm.tagsStore = []
        vm.isShowDropdown = false

        _getProperties()
        _getTags()

      _getProperties = ->
        # Get all the properties
        condition =
          "where": {"isVisible": true}
          "orderBy": {"order": "asc"}
          "unlimited": true
        restService.get config.resources.memberProperties, condition, (data) ->
          vm.memberProperties = data.items if data?.items
          angular.forEach vm.memberProperties, (item) ->
            urlStr = item.type
            if item.type is 'checkbox'
              options = new Array()
              item.options.forEach (item) ->
                item =
                  name: item
                  check: false
                options.push angular.copy item
              item.options = options
            else if item.type is 'radio'
              item.hasTooltip = true
              item.value = item.defaultValue or item.options[0]
            else if item.type is 'input'
              if item.name is 'name' or item.name is 'tel'
                urlStr = item.name
            item.url = "/build/modules/core/partials/properties/" + urlStr + ".html"

            dataItem = angular.copy utilService.getArrayElem(backupData.memberProperties, item, 'id') if backupData?.memberProperties
            if dataItem and dataItem.type is item.type
              switch dataItem.type
                when 'checkbox'
                  checkedOptions = dataItem.options.filter (item) ->
                    item.check
                  angular.forEach checkedOptions, (value) ->
                    option = utilService.getArrayElem(item.options, value, 'name')
                    option.check = true if option
                when 'radio'
                  if dataItem.value and $.inArray(dataItem.value, item.options) isnt -1
                    item.value = dataItem.value
                  else
                    item.value = item.defaultValue or item.options[0]
                else
                  item.value = dataItem.value if dataItem.value

      _getTags = ->
        restService.get config.resources.tags, (data) ->
          if data
            angular.forEach data.items, (item) ->
              vm.tagsStore.push item.name

      _checkTag = (item) ->
        item = item.trim()
        if $.inArray(item, vm.checkedTags) is -1
          vm.checkedTags.push item
        delete vm.tagValue
        vm.hideAutoDropdown()
        _focusTagsValue()
        return

      _focusTagsValue = ->
        $('#tagsValue').focus()
        return

      _clearSelectedDropItem = ->
        $('.autodropdown-item').removeClass 'selected'
        return

      _scrollTopDropdown = ->
        $('.autodropdown-items').scrollTop 0
        return

      _createMember = (callback) ->
        if vm.tagValue
          vm.checkedTags.push vm.tagValue.trim()
          delete vm.tagValue
        vm.member.tags = angular.copy vm.checkedTags
        vm.member.properties = []

        angular.forEach vm.memberProperties, (item) ->
          property =
            id: item.id
            name: item.name
          property.value = item.value if item.value?
          if item.type is 'checkbox'
            property.value = []
            item.options.forEach (option) ->
              property.value.push option.name if option.check
          if property.value? and property.value isnt ""
            if property.value instanceof Array
              if property.value.length isnt 0
                vm.member.properties.push property
            else
              vm.member.properties.push property
        for option in vm.member.properties
          vm.tel = option.value if option.name is 'tel'

        # Create member or Update member
        condition =
          properties: vm.member.properties
          score: 0

        condition.tags = vm.member.tags if vm.member.tags.length > 0

        restService.post config.resources.members, condition, (data) ->
          vm.member = angular.copy data
          callback() if callback
          localStorageService.removeItem 'memberMsg'
          return

      vm.hideAutoDropdown = ->
        vm.isShowDropdown = false
        _clearSelectedDropItem()

      vm.showAutoDropdown = ->
        vm.isShowDropdown = not vm.isShowDropdown
        _focusTagsValue()

      vm.checkTag = (item) ->
        _checkTag item

      vm.removeTag  = (index, event) ->
        vm.checkedTags.splice index, 1
        event.stopPropagation()
        _focusTagsValue()

      vm.hoverDropItem = (index) ->
        _clearSelectedDropItem()
        $($('.autodropdown-item')[index]).addClass 'selected' if index?
        return

      vm.$watch 'isShowDropdown', (newVal) ->
        if newVal
          $timeout ->
            _scrollTopDropdown()
          , 200

      vm.operateTag = (event) ->
        if not vm.isShowDropdown
          _clearSelectedDropItem()
          vm.isShowDropdown = true
        keyCode = event.keyCode or event.which
        selectedIndex = -1
        $dropItems = $('.autodropdown-item')
        if $dropItems.length isnt 0
          for item, index in $dropItems
            $item = $ item
            if $item.hasClass 'selected'
              selectedIndex = index
        if keyCode is 13
          if selectedIndex is -1
            item = vm.tagValue
            _checkTag item.trim() if item
          else
            _checkTag $($dropItems[selectedIndex]).text()
            selectedIndex = -1
          return false
        else if keyCode is 38 # key up
          if $dropItems.length isnt 0
            _clearSelectedDropItem()
            if selectedIndex is -1
              selectedIndex = $dropItems.length - 1
            else
              selectedIndex--
            $($dropItems[selectedIndex]).addClass 'selected'
            return false
        else if keyCode is 40 # key down
          if $dropItems.length isnt 0
            _clearSelectedDropItem()
            selectedIndex++
            $($dropItems[selectedIndex]).addClass 'selected' if selectedIndex < $dropItems.length
            return false
        return true

      vm.submit = ->
        if not vm.checkProperties()
          return

        _createMember ->
          if angular.isArray(vm.checkedTags) and vm.checkedTags.length isnt 0
            params =
              tags: vm.checkedTags
            restService.post config.resources.tags, params, (data) ->
              notificationService.success 'customer_member_create_success', false
              $modalInstance.close(vm.member)
          else
            notificationService.success 'customer_member_create_success', false
            $modalInstance.close(vm.member)

      vm.checkTelNum = (tel) ->
        validateService.checkTelNum tel

      vm.checkProperties = ->
        result = true
        i = -1
        angular.forEach vm.memberProperties, (item) ->
          if item.type is "checkbox" and item.isRequired
            i++
            $($(".checkbox-form-tip")[i]).text('')
            flag = true
            angular.forEach item.options, (option) ->
              if option.check
                flag = false
            if flag
              $($(".checkbox-form-tip")[i]).text($filter('translate')('required_field_tip'))
              result = false
          else if item.name is "tel" and item.isRequired and item.value
            tip = vm.checkTelNum(item.value)
            if tip isnt ''
              result = false
              validateService.highlight($("##{item.id}"), $filter('translate')(tip))
          else if item.name is "name" and item.isRequired and item.value
            tip = vm.checkName(item.value)
            if tip isnt ''
              result = false
              validateService.highlight($("##{item.id}"), $filter('translate')(tip))

        return result

      vm.checkName = ->
        nameTip = ''
        angular.forEach vm.memberProperties, (item) ->
          if item.name is "name"
            if item.value and (item.value.length < 2 or item.value.length > 30)
              nameTip = 'customer_member_name_tip'
        nameTip

      vm.hideModal = ->
        $modalInstance.close()

      _init()

      vm
  ]

  app.registerController 'wm.ctrl.mall.selectGoods', [
    '$scope'
    '$modalInstance'
    'restService'
    'modalData'
    'utilService'
    ($scope, $modalInstance, restService, modalData, utilService) ->
      vm = $scope
      STATUS_ON = 'on'

      # fix bug about click checkbox will trigger twice clicks
      flag = false

      vm.params =
        page: 1
        isAll: true
        orderBy: '{"order": "asc"}'
        category: ''
        searchKey: ''

      vm.index = -1
      vm.selectedItems = modalData.selectedItems or []

      _getAllCategories = ->
        restService.get config.resources.categories, (data) ->
          vm.categories = data.items

      _getList = ->
        vm.params['notSoldOut'] = 1
        if vm.goodsStatus
          vm.params.status = ''
        else
          vm.params.status = STATUS_ON

        restService.get config.resources.goodsList, vm.params, (data) ->
          items = []
          for item in data.items
            item['pictures'] = ['/images/content/default.png'] if item.pictures.length is 0
            position = utilService.getArrayElemIndex(vm.selectedItems, item.sku, 'sku')
            item.checked = true if position > -1
            item.quantity = 1
            items.push item
          vm.items = items

      _init = ->
        _getAllCategories()
        _getList()

      _init()

      vm.getItems = (id, index) ->
        # click same category needn't send request
        if vm.index isnt index
          vm.params.category = id
          vm.index = index
          _getList()

      vm.search = ->
        _getList()

      # fix bug about checkbox will trigger twice clicks, so will send two requests
      vm.checkStatus = (status) ->
        vm.goodsStatus = status
        _getList() if flag
        flag = not flag

      vm.choose = (index) ->
        isChecked = vm.items[index].checked
        vm.items[index].checked = not isChecked

        if not isChecked
          vm.selectedItems.push vm.items[index]
        else
          position = utilService.getArrayElemIndex(vm.selectedItems, vm.items[index].sku, 'sku')
          vm.selectedItems.splice position, 1 if position > -1

      vm.hideModal = ->
        $modalInstance.close()

      vm.submit = ->
        if vm.selectedItems.length > 0
          $modalInstance.close(angular.copy(vm.selectedItems))

      vm

  ]

  app.registerController 'wm.ctrl.product.codeScore', [
    '$scope'
    '$modalInstance'
    'restService'
    'modalData'
    '$modal'
    '$rootScope'
    'notificationService'
    ($scope, $modalInstance, restService, modalData, $modal, $rootScope, notificationService) ->
      vm = $scope

      vm.member = modalData.member
      vm.totalScore = modalData.totalScore
      params = modalData.params

      if vm.member.properties?
        angular.forEach vm.member.properties, (property) ->
          vm.member[property.name] = property.value

      _openCodePopup = ->
        modalInstance = $modal.open(
          templateUrl: '/build/modules/mall/partials/sendMessageModal.html'
          controller: 'wm.ctrl.mall.sendMessage'
          windowClass: 'assign-helpdesk-dialog'
          resolve:
            modalData: ->
              total: modalData.total
              totalScore: modalData.totalScore
              member: vm.member
              type: 'promocode'
              language: $rootScope.user.language
              codes: params.code

          ).result.then( (data) ->

        )

      vm.hideModal = ->
        $modalInstance.close()

      vm.submit = ->
        vm.disableBtn = true
        restService.post config.resources.exchangeCodes, params, (data) ->
          vm.disableBtn = false
          if data
            $modalInstance.close('ok')
            if not params.useWebhook
              _openCodePopup()
            else
              notificationService.success 'product_code_redeem_successfully', false
        , (data) ->
          vm.disableBtn = false
  ]
