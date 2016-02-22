define [
  'wm/app'
  'wm/config'
], (app, config) ->
  app.registerController 'wm.ctrl.mall.view.goods', [
    'restService'
    '$stateParams'
    '$sce'
    'channelService'
    '$location'
    'utilService'
    '$q'
    (restService, $stateParams, $sce, channelService, $location, utilService, $q) ->
      vm = this

      listPageUrl = '/mall/goods'

      _init = ->
        vm.goods = {}
        vm.selfAddress = []

        vm.breadcrumb = [
          {
            text: 'shelf_management'
            href: listPageUrl
          }
          'product_description'
        ]

        vm.goodsMissing = false

        _getGoods()

      _getAddress = (ids) ->
        defered = $q.defer()
        idstr = ''
        for id in ids
          idstr += '"' + id + '"' + ','
        idstr = idstr.slice(0, -1)
        params =
          unlimited: true
          where: '{"_id":{"in":[' + idstr + ']}}'
        restService.get config.resources.receiveAddresss, params, (data) ->
          for item in data.items
            location = item.location
            item.position = "#{location.province}#{location.city}#{location.district}#{location.detail}"
          defered.resolve data.items
        defered.promise

      _getChannels = ->
        channelService.getChannels().then (channels) ->
          if channels
            vm.channels = utilService.formatChannels(angular.copy(channels), false)

            angular.forEach vm.channels, (account) ->
              account.oauthUrl = "#{$location.$$protocol}://#{$location.$$host}/api/mobile/mall?channelId=#{account.id}&goodsId=#{vm.goods.id}&productId=#{vm.product.id}"

      _getProduct = (id) ->
        restService.get config.resources.product + '/' + id, (data) ->
          if data
            product = data
            product.intro = $sce.trustAsHtml product.intro
            if not $.isArray product.category
              for property in product.category.properties
                property.value = property.value.join('， ') if angular.isArray(property.value)

            vm.product = angular.copy product
            _getChannels()

      _getGoods = ->
        restService.get config.resources.goods + '/' + $stateParams.id, (data) ->
          if data
            vm.goods = angular.copy data
            vm.goods.shelves = if vm.goods.status is 'on' then 'product_onshelves' else 'product_offshelves'
            vm.goods.total = vm.goods.total + ''
            vm.goods.labelColor = if vm.goods.onSaleTime isnt '' then 'green' else 'gray'

            if vm.goods.receiveModes and vm.goods.receiveModes.length > 0
              if $.inArray('self', vm.goods.receiveModes) isnt -1
                _getAddress(vm.goods.addresses).then (data) ->
                  vm.selfAddress = data
                  vm.showSelf = true
              if $.inArray('express', vm.goods.receiveModes) isnt -1
                vm.showExpress = true

            if vm.goods.status is 'on'
              vm.goods.shelves = 'store_on_shelves'
            else if vm.goods.status is 'off' and vm.goods.onSaleTime isnt ''
              vm.goods.shelves = 'store_scheduled_shelves'
            else
              vm.goods.shelves = 'store_off_shelves'
            _getProduct(vm.goods.productId)
            return
        , (res) ->
          vm.goodsMissing = true

      _init()

      vm
  ]
