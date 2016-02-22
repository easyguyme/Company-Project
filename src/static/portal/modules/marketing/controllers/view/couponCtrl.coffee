define [
  'wm/app'
  'wm/config'
], (app, config) ->
  app.registerController 'wm.ctrl.marketing.view.coupon', [
    'restService'
    '$location'
    '$stateParams'
    '$scope'
    "$filter"
    "$sce"
    '$sanitize'
    (restService, $location, $stateParams, $scope, $filter, $sce, $sanitize) ->
      vm = this
      vm.couponId = $stateParams.id
      vm.url = "/mobile/product/couponSingle?couponId=" + vm.couponId + "&preview=1"
      vm.storeAll = false

      vm.list =
        columnDefs: [
          field: 'name'
          label: 'product_coupon_store_name'
          cellClass: 'text-el'
        ,
          field: 'branchName'
          label: 'product_coupon_branch_name'
          cellClass: 'text-el'
        ,
          field: 'address'
          label: 'product_coupon_branch_address'
          cellClass: 'text-el'
        ,
          field: 'phone'
          label: 'product_coupon_branch_tel'
          cellClass: 'text-el'
        ]
        data: []
        nodata: 'no_data'

      vm.breadcrumb = [
        text: 'product_coupon_list'
        href: '/marketing/coupon'
      ,
        'product_coupon_detail'
      ]

      _getCouponDetail = ->
        restService.get config.resources.coupon + '/' + $stateParams.id, (data) ->
          if data
            vm.type = data.type
            vm.discountCondition = data.discountCondition or '-'
            vm.discountAmount = data.discountAmount or '-'
            vm.reductionAmount = data.reductionAmount or '-'
            vm.title = data.title
            vm.total = data.total + $filter('translate')('product_total_account')
            vm.limit = $filter('translate')('product_customer_limit', {'limit': data.limit})
            vm.tip = data.tip or '-'
            vm.description = if data.description then $sce.trustAsHtml($sanitize(data.description.replace /\n/g, '<br>')) else '-'
            vm.usageNote = if data.usageNote then $sce.trustAsHtml($sanitize(data.usageNote.replace /\n/g, '<br>')) else '-'
            vm.phone = data.phone or '-'
            if data.time.type is 'relative'
              if data.time.beginTime is 0
                beginTime = $filter('translate')('current_day')
              else if data.time.beginTime is 1
                beginTime = $filter('translate')('one_day')
              else
                beginTime = data.time.beginTime + $filter('translate')('management_unit_day')
              if data.time.endTime is 1
                endTime = $filter('translate')('one_day')
              else
                endTime = data.time.endTime + $filter('translate')('management_unit_day')
              vm.validity =
                $filter('translate')('product_coupon_receive_time', {'startTime': beginTime, 'endTime': endTime})
            else
              vm.validity = $filter('translate')('product_coupon_validation_key', {'startTime': data.time.beginTime.substring(0,10), 'endTime': data.time.endTime.substring(0,10)})
            if data.storeType is 'all'
              vm.storeAll = true
            else
              vm.list.data = data.stores

      _getCouponDetail()

      vm
  ]
