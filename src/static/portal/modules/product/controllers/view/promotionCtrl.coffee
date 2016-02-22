define [
  'wm/app'
  'wm/config'
], (app, config) ->
  app.registerController 'wm.ctrl.product.view.promotion', [
    'restService'
    '$stateParams'
    '$translate'
    '$location'
    '$filter'
    'utilService'
    'channelService'
    (restService, $stateParams, $translate, $location, $filter, utilService, channelService) ->
      vm = this

      origins =
        WECHAT: 'weixin'
        WEIBO: 'weibo'
        ALIPAY: 'alipay'

      _init = ->
        vm.breadcrumb = [
          icon: 'promotion'
          text: 'product_promotion_active'
          href: '/product/promotion?active=0'
        ,
          'product_activity_details'
        ]

        vm.channels = []
        vm.tags = []

        _getCampaign()

      _getChannels = ->
        if vm.channels? and vm.channels.length isnt 0
          _concatChannelsName()
        else
          restService.get config.resources.channels, (data) ->
            for key, value of data
              vm.channels = vm.channels.concat(angular.copy(value)) if value and angular.isArray value
          channelService.getChannels().then (channels) ->
            if channels
              vm.channels = utilService.formatChannels angular.copy(channels)
              _concatChannelsName()

      _getTags = ->
        if vm.tags? and vm.tags.length isnt 0
          _concatTagsName()
        else
          restService.get config.resources.tags, (data) ->
            vm.tags = data.items if data

            _concatTagsName()

      _concatTagsName = ->
        if vm.campaign?.promotion?.tags?
          tags = vm.tags.filter (tag, index) ->
            return utilService.getArrayElemIndex(vm.campaign.promotion.tags, tag, 'name') isnt -1
          vm.tagsText = _concatArrayFieldValue(tags) or '-'

      _concatChannelsName = ->
        if vm.campaign?.promotion?.channels?
          vm.checkedChannels = vm.channels.filter (account, index) ->
            isChecked = utilService.getArrayElemIndex(vm.campaign.promotion.channels, account, 'id') isnt -1

            if isChecked
              if account.type
                account.online = true

                account.entranceUrl = "#{$location.$$protocol}://#{$location.$$host}/api/mobile/campaign?channelId=#{account.id}&campaignId=#{$stateParams.id}&action=promotion"
                account.recordUrl = "#{$location.$$protocol}://#{$location.$$host}/api/mobile/campaign?channelId=#{account.id}&campaignId=#{$stateParams.id}&action=history"

            isChecked

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

      _getCampaign = ->
        restService.get config.resources.campaign + '/' + $stateParams.id, (data) ->
          if data
            vm.campaign = angular.copy data

            vm.isActivated = if vm.campaign.isActivated then 'customer_card_enable' else 'customer_card_disable'
            vm.chosenGoodsName = _concatArrayFieldValue(vm.campaign.promotion.data, 'name') or '-'
            vm.campaign.limitTimes = vm.campaign.limitTimes or ''
            vm.campaign.participantCount = vm.campaign.participantCount or ''

            if vm.campaign.promotion.gift?.type is 'score'
              vm.scoreType = "product_promotion_basic_#{vm.campaign.promotion.gift.config.method}" if vm.campaign.promotion.gift.config.method?

            vm.productsText = ''
            productIds = vm.campaign.promotion.products if vm.campaign.promotion?.products
            if angular.isArray productIds
              vm.productsText = $filter('translate')('product_promotion_ever_redeemed')

              if productIds.length > 0
                params =
                  id: productIds.join(',')
                restService.get config.resources.productNames, params, (data) ->
                  if data
                    names = data.map (item) ->
                      return "\"#{item}\""
                    vm.productsText += ' ' + names.join(', ')
              else
                vm.productsText += ' ' + $filter('translate')('content_no_creator')
            else if productIds is 'unlimited'
              vm.productsText = 'channel_wechat_mass_unlimited'
            else if productIds is 'first'
              vm.productsText = 'product_promotion_activity_first_involved'

            _getChannels()
            _getTags()

      _init()

      vm
  ]
