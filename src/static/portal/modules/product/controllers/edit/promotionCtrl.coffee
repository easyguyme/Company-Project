define [
  'wm/app'
  'wm/config'
], (app, config) ->
  app.registerController 'wm.ctrl.product.edit.promotion', [
    'restService'
    '$stateParams'
    '$modal'
    'notificationService'
    '$scope'
    '$location'
    '$timeout'
    'validateService'
    '$filter'
    'utilService'
    'channelService'
    (restService, $stateParams, $modal, notificationService, $scope, $location, $timeout, validateService, $filter, utilService, channelService) ->
      vm = this

      _init = ->
        vm.userTags = []
        vm.isSHowChosenGoodsTip = false
        vm.isCreating = not $stateParams.id

        vm.storeTitle = if $stateParams.id then 'product_promotion_edit_activity' else 'product_promotion_create_activity'
        vm.breadcrumb = [
          icon: 'promotion'
          text: 'product_promotion_active'
          href: '/product/promotion?active=0'
        ,
          vm.storeTitle
        ]

        vm.enable = false

        vm.giftTypes = [
          {
            name: 'member_score'
            value: 'score'
          }
          {
            name: 'product_promotion_lotto'
            value: 'lottery'
          }
        ]

        vm.sendLottoTypes = [
          {
            name: 'product_promotion_lotto_send_by_scale'
            value: 'scale'
          }
          {
            name: 'product_promotion_lotto_send_by_number'
            value: 'number'
          }
        ]

        vm.sendLottoType = vm.sendLottoTypes[0].value

        vm.sendScoreTypes = [
          {
            id: 'giftRewardScore'
            name: 'product_promotion_basic_score'
            value: 'score'
            unit: 'product_promotion_basic_score_unit'
            holder: "product_promotion_input_score_number"
          }
        ]

        vm.sendScoreType = vm.sendScoreTypes[0].value

        vm.sendLottoTypesGiftInfo =
          scale:
            numberTitle: 'product_promotion_winning_odds'
            numberUnit: '%'
          number:
            numberTitle: 'product_promotion_gift_number'
            numberUnit: 'product_promotion_gift_unit'

        vm.lottoPrizes = [
          {
            name: ''
            number: ''
          }
        ]

        vm.scoreSituations = [
          {
            name: 'product_promotion_activity_first_involved'
            value: 'first'
          }
          {
            name: 'product_promotion_activity_ever_involved'
            value: 'campaigns'
          }
        ]

        vm.chosenGoods = []

        checkTypes = ['tags', 'channels', 'goods']

        angular.forEach checkTypes, (item) ->
          vm[item] = []
          vm[item + 'Templet'] = []
          vm[item + 'All'] = false

        vm.campaign =
          gift:
            type: vm.giftTypes[0].value
            config:
              method: vm.sendScoreTypes[0].value
              number: null
        vm.products = 'unlimited'

        _getMemberTags()
        _getChannels()
        _getTags()
        _getGoods()

        _getCampaign() if $stateParams.id

      vm.changeSendScoreTypes = (selected) ->
        angular.forEach vm.sendScoreTypes, (type) ->
          type.number = null

        $('#' + selected.id).focus()
        return

      _getTags = ->
        restService.get config.resources.tags, (data) ->
          tagContainer = []
          for item in data.items
            tagContainer.push item.name
          vm.autoCompleteItems = tagContainer

      vm.changeGiftTypes = ->
        vm.changeSendScoreTypes(vm.sendScoreTypes[0])
        vm.changeSendLottoTypes()
        vm.sendScoreType = vm.sendScoreTypes[0].value
        vm.sendLottoType = vm.sendLottoTypes[0].value

      vm.checkItem = (type, item, fieldName) ->
        checkAllName = type + 'All'
        templetName = type + 'Templet'

        fieldName = fieldName or 'name'

        field = item[fieldName]

        index = utilService.getArrayElemIndex(vm[type], field)

        if item.check
          vm[type].push field if index is -1
        else
          vm[type].splice index, 1 if index isnt -1

        vm[checkAllName] = vm[type].length is vm[templetName].length

      vm.checkAllItem = (type, fieldName) ->
        checkAllName = type + 'All'
        templetName = type + 'Templet'
        vm[type] = []

        fieldName = fieldName or 'name'

        checkAllFlag = vm[checkAllName]

        if checkAllFlag
          templets = []
          angular.forEach vm[templetName], (item) ->
            item.check = checkAllFlag
            templets.push item[fieldName]

          vm[type] = angular.copy templets
        else
          angular.forEach vm[templetName], (item) ->
            item.check = checkAllFlag

      vm.changeSendLottoTypes = ->
        vm.lottoPrizes = [
          {
            name: ''
            number: ''
          }
        ]

        $('#prizeName0').focus()
        return

      vm.addLottoPrizes = ->
        vm.lottoPrizes.push {name: '', number: ''}

      vm.removeLottoPrizes = (index, $event) ->

        if vm.lottoPrizes.length isnt 1
          notificationService.confirm $event, {
            title: 'product_promotion_gift_delete_confirm'
            submitCallback: _removeLottoPrizesHandler
            params: [index]
          }

      _removeLottoPrizesHandler = (index) ->
        $scope.$apply( ->
          vm.lottoPrizes.splice index, 1
        )

      _getCampaign = ->
        restService.get config.resources.campaign + '/' + $stateParams.id, (data) ->
          if data
            vm.campaign = angular.copy data

            vm.campaign.startTime = moment(vm.campaign.startTime).valueOf()
            vm.campaign.endTime = moment(vm.campaign.endTime).valueOf()

            vm.isDisabledStartPicker = vm.campaign.startTime < moment().valueOf()
            vm.isDisabledEndPicker = vm.campaign.endTime < moment().valueOf()

            vm.isAutoAddTags = vm.campaign.isAddTags

            if vm.campaign.userTags and vm.campaign.userTags.length isnt 0
              vm.userTags = angular.copy vm.campaign.userTags

            if not vm.isDisabledStartPicker
              vm.startPickerConfig =
                minDate: moment()

            if not vm.isDisabledEndPicker
              vm.endPickerConfig =
                minDate: moment()

            vm.campaign.gift = angular.copy vm.campaign.promotion.gift if vm.campaign.promotion?.gift?
            vm.chosenGoods = angular.copy vm.campaign.promotion.data if vm.campaign.promotion?.data?

            vm.chosenGoodsName = _concatArrayFieldValue vm.chosenGoods, 'name'

            vm.products = angular.copy vm.campaign.promotion.products if vm.campaign.promotion?.products?
            vm.channels = angular.copy vm.campaign.promotion.channels if vm.campaign.promotion?.channels?
            vm.tags = angular.copy vm.campaign.promotion.tags if vm.campaign.promotion?.tags?

            if angular.isArray vm.products
              vm.goods = angular.copy vm.products

              _getGoods( ->
                angular.forEach vm.goodsTemplet, (item) ->
                  if utilService.getArrayElemIndex(vm.goods, item, 'id') isnt -1
                    item.check = true

                if vm.goods.length is vm.goodsTemplet.length
                  vm.goodsAll = true
              )

              vm.products = 'campaigns'

            _getMemberTags( ->
              angular.forEach vm.tagsTemplet, (tag) ->
                if utilService.getArrayElemIndex(vm.tags, tag, 'name') isnt -1
                  tag.check = true

              if vm.tags.length is vm.tagsTemplet.length
                vm.tagsAll = true
            )

            _getChannels( ->
              angular.forEach vm.channelsTemplet, (channel) ->
                if utilService.getArrayElemIndex(vm.channels, channel, 'id') isnt -1
                  channel.check = true

              if vm.channels.length is vm.channelsTemplet.length
                vm.channelsAll = true
            )

            switch vm.campaign.gift.type
              when 'score'
                vm.sendScoreType = vm.campaign.gift.config.method

                angular.forEach vm.sendScoreTypes, (type) ->
                  if type.value is vm.sendScoreType
                    type.number = vm.campaign.gift.config.number

              when 'lottery'
                vm.sendLottoType = vm.campaign.gift.config.method
                vm.lottoPrizes = angular.copy vm.campaign.gift.config.prize if vm.campaign.gift?.config?.prize?

      _getMemberTags = (callback) ->
        restService.get config.resources.tags, (data) ->
          if data
            vm.tagsTemplet = angular.copy data.items
            callback() if callback

      _getChannels = (callback) ->
        channelService.getChannels().then((channels) ->
          if channels
            vm.channelsTemplet = utilService.formatChannels angular.copy(channels)
            callback() if callback
        )

      _getGoods = (callback) ->
        restService.get config.resources.campaignProduct, (data) ->
          if data
            vm.goodsTemplet = angular.copy data.data
            callback() if callback

      _concatArrayFieldValue = (goods, field, separator) ->
        str = ''
        field = field or 'name'
        separator = separator or '、'
        length = goods.length
        angular.forEach goods, (good, index) ->
          name = good[field] if good.hasOwnProperty(field)
          if index is length - 1
            str += name
          else
            str += name + separator
        return str

      vm.associatedGoods = ->
        modalInstance = $modal.open(
          templateUrl: 'associatedGoods.html'
          controller: 'wm.ctrl.product.edit.associatedGoods'
          windowClass: 'associated-goods-dialog'
          resolve:
            modalData: -> vm.chosenGoods
        ).result.then( (data) ->
          if data
            if data.length > 0
              vm.isSHowChosenGoodsTip = false
            vm.chosenGoods = angular.copy data
            vm.chosenGoodsName = _concatArrayFieldValue vm.chosenGoods, 'name'
        )

      vm.submit = ->
        cannotSubmit = false
        cannotSubmit = true if vm.checkName() isnt ''

        if vm.campaign.participantCount and vm.checkPositiveInt('participantCount', vm.campaign.participantCount) isnt ''
          cannotSubmit = true

        if vm.campaign.limitTimes and vm.checkPositiveInt('limitTimes', vm.campaign.limitTimes) isnt ''
          cannotSubmit = true

        if not vm.campaign.startTime or not vm.campaign.endTime or vm.campaign.startTime > vm.campaign.endTime
          cannotSubmit = true

        if vm.checkRedeemExperience() isnt ''
          cannotSubmit = true

        productIds = []

        if vm.chosenGoods.length is 0
          vm.isSHowChosenGoodsTip = true
          cannotSubmit = true
        else
          angular.forEach vm.chosenGoods, (item) ->
            productIds.push item.id

        gift =
          type: vm.campaign.gift.type
          config: null

        switch vm.campaign.gift.type
          when 'score'
            score = {}

            score.method = vm.sendScoreType = vm.sendScoreTypes[0].value

            if score.method is 'score'
              cannotSubmit = true if vm.checkPositiveInt('giftRewardScore', vm.sendScoreTypes[0].number) isnt ''
              score.number = parseInt vm.sendScoreTypes[0].number if not cannotSubmit
            else
              cannotSubmit = true if vm.checkPositiveInt('giftRewardTimes', vm.sendScoreTypes[1].number) isnt ''
              score.number = parseFloat vm.sendScoreTypes[1].number if not cannotSubmit

            gift.config = score

          when 'lottery'
            lottery =
              method: vm.sendLottoType
              prize: []

            angular.forEach vm.lottoPrizes, (prize, index) ->
              cannotSubmit = true if vm.checkPrizeName('prizeName' + index, prize.name) isnt ''

              if lottery.method is 'number'
                cannotSubmit = true if vm.checkPositiveInt('prizeNumber' + index, prize.number) isnt ''
                prize.number = parseInt prize.number if not cannotSubmit
              else
                cannotSubmit = true if vm.checkPrizeNumber('prizeNumber' + index, prize.number) isnt ''
                prize.number = parseFloat prize.number if not cannotSubmit
              lottery.prize.push prize

            gift.config = lottery

        if cannotSubmit
          return

        participantCount = if vm.campaign.participantCount then Number vm.campaign.participantCount else null
        limitTimes = if vm.campaign.limitTimes then Number vm.campaign.limitTimes else null

        campaign =
          name: vm.campaign.name
          startTime: vm.campaign.startTime
          endTime: vm.campaign.endTime
          participantCount: participantCount
          limitTimes: limitTimes
          productIds: productIds
          gift: gift
          isActivated: vm.campaign.isActivated
          tags: vm.tags
          userTags: if vm.isAutoAddTags then vm.userTags else []
          isAddTags: vm.isAutoAddTags
          channels: angular.copy vm.channels
          products: angular.copy vm.products

        campaign.channels.push('all') if vm.channelsAll

        campaign.products =  angular.copy vm.goods if campaign.products is 'campaigns'

        url = config.resources.campaigns
        method = 'post'

        if $stateParams.id
          method = 'put'
          url = config.resources.campaign + '/' + $stateParams.id

        restService[method] url, campaign, (data) ->
          if method is 'post'
            notificationService.success 'product_promotion_activity_create_success'
          else
            notificationService.success 'product_promotion_activity_update_success'
          $timeout (->
            $location.url '/product/promotion'
          ), 500
          return

      $scope.$watch 'promotion.products', ->
        vm.isRedeemExperienceTip = false

      vm.checkRedeemExperience = ->
        tip = ''
        if vm.products is 'campaigns' and (not vm.goods or not vm.goods.length)
          tip = 'product_not_exchange_experience'
          vm.isRedeemExperienceTip = true
        tip

      vm.checkName = ->
        tip = ''
        if not vm.campaign.name or vm.campaign.name.length < 4 or vm.campaign.name.length > 30
          tip = 'product_promotion_name_tip'
        tip

      vm.checkPrizeName = (id, value) ->
        tip = ''
        if not value or value.length < 4 or value.length > 30
          tip = 'character_length_tip'
          validateService.highlight($('#' + id), $filter('translate')('character_length_tip', {'name': 'Prize name', 'minNumber': 4, 'maxNumber': 30}))
        tip

      vm.checkPositiveInt = (id, number) ->
        tip = ''
        reg = /^[1-9][0-9]*$/
        if number and not reg.test number # if the number is '' then do not check
          tip = 'product_promotion_activity_member_number_tip'
          validateService.highlight($('#' + id), $filter('translate')('product_promotion_activity_member_number_tip'))
        tip

      vm.checkPrizeNumber = (id, number) ->
        tip = ''
        if isNaN number
          tip = 'product_promotion_winning_odds_tip'
          validateService.highlight($('#' + id), $filter('translate')('product_promotion_winning_odds_tip'))
        else
          if typeof number is 'string'
            number = parseFloat number

          if number > 100 or number < 0
            tip = 'product_promotion_winning_odds_tip'
            validateService.highlight($('#' + id), $filter('translate')('product_promotion_winning_odds_tip'))
        tip

      vm.cancel = ->
        $location.url '/product/promotion'

      _init()
      vm
  ]

  app.registerController 'wm.ctrl.product.edit.associatedGoods', [
    '$scope'
    '$modalInstance'
    'restService'
    'notificationService'
    'modalData'
    'utilService'
    '$location'
    ($scope, $modalInstance, restService, notificationService, modalData, utilService, $location) ->
      vm = $scope

      _init = ->

        vm.noData = 'no_data'
        vm.chosenGoods = []

        vm.chosenGoods = angular.copy modalData if modalData

        vm.currentPage = $location.search().currentPage or 1
        vm.pageSize = $location.search().pageSize or 10
        vm.totalItems = 0

        vm.list =
          isCheckBox: true
          columnDefs: [
            {
              field: 'sku'
              label: 'product_promotion_goods_sku'
            }, {
              field: 'name'
              label: 'product_promotion_goods_name'
              type: 'mark'
              markText: 'product_promotion_goods_has_associated_mark'
              markTip: 'product_promotion_goods_has_associated'
              cellClass: 'table-mark-cell'
            },{
              field: 'codeNum'
              label: 'product_promotion_code_number'
              type: 'number'
            }
          ],
          data: []
          checkHandler: (idx, checked) ->
            if idx?
              index = utilService.getArrayElemIndex(vm.chosenGoods, vm.list.data[idx], 'id')
              if checked
                vm.chosenGoods.push vm.list.data[idx] if index is -1
              else
                vm.chosenGoods.splice index, 1 if index isnt -1
            return

        _getGoodsList( ->
          _backCheckSituation()
        )

      _getGoodsList = (callback) ->
        params =
          'page': vm.currentPage
          'per-page': vm.pageSize
          'assigned': true

        params.searchKey = vm.searchKey if vm.searchKey

        restService.get config.resources.products, params, (data) ->
          if data
            vm.totalItems = data._meta.totalCount
            vm.pageSize = data._meta.perPage
            vm.pageCount = data._meta.pageCount

            vm.list.data = angular.copy data.items

            angular.forEach vm.list.data, (goods, index) ->
              goods.enabled = true

            callback() if callback

      _clearCheckSituation = ->
        angular.forEach vm.list.data, (goods, index) ->
          goods.checked = false

        vm.chosenGoods = []


      _backCheckSituation = ->
        angular.forEach vm.list.data, (goods, index) ->
          if utilService.getArrayElemIndex(vm.chosenGoods, goods, 'id') isnt -1
            goods.checked = true

      vm.search = ->
        _getGoodsList( ->
          _backCheckSituation()
        )
        vm.noData = 'search_no_data'

      vm.removeGoods = (index) ->
        containIndex = utilService.getArrayElemIndex(vm.list.data, vm.chosenGoods[index], 'id')
        vm.list.data[containIndex].checked = false if containIndex isnt -1 and vm.list.data[containIndex].checked
        vm.chosenGoods.splice index, 1

      vm.changePage = (currentPage) ->
        vm.currentPage = currentPage
        _getGoodsList( ->
          _backCheckSituation()
        )

      vm.submit = ->
        $modalInstance.close(vm.chosenGoods)

      vm.hideModal = ->
        $modalInstance.close()

      _init()

      vm
  ]
