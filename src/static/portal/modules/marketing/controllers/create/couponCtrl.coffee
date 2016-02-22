define [
  'wm/app'
  'wm/config'
], (app, config) ->
  app.registerController 'wm.ctrl.marketing.create.coupon', [
    'restService'
    'notificationService'
    '$location'
    '$modal'
    'validateService'
    '$filter'
    '$scope'
    '$timeout'
    (restService, notificationService, $location, $modal, validateService, $filter, $scope, $timeout) ->
      vm = this
      vm.isShowCoupons = true
      vm.storeType = 'specify'
      vm.expiryType = 'absolute'
      vm.createEnable = true

      vm.breadcrumb = [
        text: 'product_coupon'
        href: '/marketing/coupon'
      ,
        'product_new_coupon'
      ]

      vm.coupons = [
        name: 'product_coupon_discount'
        description: 'product_coupon_discount_desc'
        itemTitle: 'product_coupon_discount_title'
        picTitle: 'product_coupon_discount_image'
        itemDetail: 'product_coupon_discount_detail'
        type: 'discount'
      ,
        name: 'product_coupon_cash'
        description: 'product_coupon_cash_desc'
        itemTitle: 'product_coupon_cash_title'
        picTitle: 'product_coupon_cash_image'
        itemDetail: 'product_coupon_cash_detail'
        type: 'cash'
      ,
        name: 'product_coupon_gift'
        description: 'product_coupon_gift_desc'
        itemTitle: 'product_coupon_gift_title'
        picTitle: 'product_coupon_gift_image'
        itemDetail: 'product_coupon_gift_detail'
        type: 'gift'
      ,
        name: 'product_coupon_coupon'
        description: 'product_coupon_coupon_desc'
        itemTitle: 'product_coupon_coupon_title'
        picTitle: 'product_coupon_coupon_image'
        itemDetail: 'product_coupon_coupon_detail'
        type: 'coupon'
      ]

      vm.infos = [
        title: 'product_coupon_information'
        active: true
      ,
        title: 'product_coupon_setting'
        active: false
      ,
        title: ''
        active: false
      ,
        title: 'product_coupon_store'
        active: false
      ]

      daysI18n = $filter('translate')('management_unit_day')

      vm.effectTimes = [
        value: 0
        text: 'current_day'
      ,
        value: 1
        text: 'one_day'
      ]

      vm.activeTimes = [
        value: 1
        text: 'one_day'
      ]

      for i in [2..90]
        vm.effectTimes.push {value: i, text: "#{i}#{daysI18n}"}
        vm.activeTimes.push {value: i, text: "#{i}#{daysI18n}"}

      vm.effectTime = vm.effectTimes[0].value
      vm.activeTime = vm.activeTimes[29].value

      vm.storeList =
        columnDefs: [
          field: 'name'
          label: 'management_store_name'
          cellClass: 'text-el'
        ,
          field: 'branchName'
          label: 'management_store_branch_name'
          cellClass: 'text-el'
        ,
          field: 'address'
          label: 'address'
          cellClass: 'text-el'
        ,
          field: 'phone'
          label: 'content_component_tel'
          cellClass: 'text-el'
        ]
        data: []

      vm.chooseCoupon = (idx) ->
        vm.isShowCoupons = false
        vm.currentCoupon = vm.coupons[idx]
        vm.couponType = vm.currentCoupon.type
        vm.infos[2].title = vm.currentCoupon.itemDetail

      vm.select = (idx) ->
        angular.forEach vm.infos, (item) ->
          item.active = false
        vm.infos[idx].active = true
        _setTrangle idx
        $scope.$broadcast 'clearValidityError'

      vm.selectStore = ->
        if vm.storeType is 'specify' and vm.infos[3].active
          modalInstance = $modal.open(
            templateUrl: 'selectStore.html'
            controller: 'wm.ctrl.marketing.create.coupon.selectStore'
            windowClass: 'product-select-store-dialog'
            resolve:
              modalData: ->
                stores: vm.storeList.data
          ).result.then( (data) ->
            if data.callback
              vm.storeList.data = angular.copy data.stores
          )

      vm.save = ->
        result = _checkParams()
        if not isNaN(result)
          vm.select result
          $timeout ->
            $('#createCoupon')[0].checkValidity()
          , 300
        else
          _createCoupon()

      _checkParams = ->
        if not vm.title or not vm.picUrl
          return 0
        if vm.expiryType is 'absolute' and (not vm.startDate or not vm.endDate)
          notificationService.error 'product_coupon_validity_tip', false
          return 0
        if vm.discountAmount and vm.couponType is 'discount' and parseFloat(vm.discountAmount) is 0
          notificationService.error 'product_coupon_discount_amount_error_tip', false
          return 0
        if not vm.total or not vm.tip
          return 1
        if vm.total and vm.limit and Number(vm.limit) > Number(vm.total)
          notificationService.error 'product_coupon_limit_error_tip', false
          return 1
        if not vm.usageNote
          return 2
        if vm.couponType is 'gift' and not vm.description
          return 2
        if vm.couponType is 'coupon' and (not vm.description or not vm.phone)
          return 2
        if vm.couponType is 'discount' and not vm.phone
          return 2
        if vm.storeType is 'specify' and vm.storeList.data.length is 0
          notificationService.error 'product_coupon_store_tip', false
          return 3
        return 'pass'

      _createCoupon = ->
        params =
          type: vm.couponType
          title: vm.title
          total: Number(vm.total)
          limit: Number(vm.limit) or 1
          tip: vm.tip
          picUrl: vm.picUrl
          description: vm.description
          usageNote: vm.usageNote
          phone: vm.phone
          storeType: vm.storeType
          stores: vm.storeList.data
          discountAmount: parseFloat(vm.discountAmount)
          discountCondition: parseFloat(vm.discountCondition)
          reductionAmount: parseFloat(vm.reductionAmount)

        time = {}
        if vm.expiryType is 'absolute'
          time =
            type: 'absolute'
            beginTime: vm.startDate
            endTime: vm.endDate
        else if vm.expiryType is 'relative'
          time =
            type: 'relative'
            beginTime: vm.effectTime
            endTime: vm.activeTime
        params.time = angular.copy time

        if vm.createEnable
          restService.post config.resources.coupons, params, (data) ->
            if data.id
              notificationService.success 'product_coupon_create_success', false
              $timeout ->
                $location.url "/marketing/coupon"
              , 1000
          ,(error) ->
            vm.createEnable = true
        vm.createEnable = false

      _setTrangle = (idx) ->
        top = 83
        switch idx
          when 0
            top = 83
          when 1
            top = 165
          when 2
            top = 236
          when 3
            top = 276

        $('.front-triangle').animate {'top': top + 'px'}, 500
        $('.back-triangle').animate {'top': top - 1 + 'px'}, 500

      vm
  ]

  app.registerController 'wm.ctrl.marketing.create.coupon.selectStore', [
    'modalData'
    'restService'
    '$modalInstance'
    '$scope'
    'utilService'
    '$timeout'
    'storeService'
    (modalData, restService, $modalInstance, $scope, utilService, $timeout, storeService) ->
      vm = $scope

      vm.selectedStore = angular.copy modalData.stores or []
      vm.storeList =
        columnDefs: [
          field: 'name'
          label: 'management_store_name'
          cellClass: 'text-el'
        ,
          field: 'branchName'
          label: 'management_store_branch_name'
          cellClass: 'text-el'
        ,
          field: 'address'
          label: 'address'
          cellClass: 'text-el'
        ,
          field: 'phone'
          label: 'content_component_tel'
          cellClass: 'text-el'
        ]
        data: []
        selectable: true

        selectHandler: (checked, idx) ->
          if idx?
            index = utilService.getArrayElemIndex(vm.selectedStore, vm.storeList.data[idx].id, 'id')
            if checked and index is -1
              vm.selectedStore.push vm.storeList.data[idx]
            if not checked and index isnt -1
              vm.selectedStore.splice index, 1
          else
            if checked
              vm.selectedStore = angular.copy vm.storeList.data
            else
              vm.selectedStore = []

      vm.hideModal = ->
        data =
          callback: false
        $modalInstance.close(data)

      vm.submit = ->
        data =
          callback: true
          stores: vm.selectedStore
        $modalInstance.close(data)

      _init = ->
        items = angular.copy storeService.stores
        for item in items
          item.enabled = true
          item.checked = false
        vm.storeList.data = items

        # fix bug about first time checkbox cannot be checked
        $timeout( ->
          checkLen = 0
          for item in vm.storeList.data
            if utilService.getArrayElemIndex(vm.selectedStore, item.id, 'id') isnt -1
              item.checked = true
              checkLen++
          if checkLen > 0 and checkLen is vm.storeList.data.length
            vm.storeList.checkAll = true
        , 20)
      _init()
  ]
