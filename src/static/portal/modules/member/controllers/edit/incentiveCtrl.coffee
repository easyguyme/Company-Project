define [
  'wm/app'
  'wm/config'
], (app, config) ->
  app.registerController 'wm.ctrl.member.edit.incentive', [
    'restService'
    '$stateParams'
    'notificationService'
    '$location'
    '$timeout'
    '$rootScope'
    (restService, $stateParams, notificationService, $location, $timeout, $rootScope) ->
      vm = this
      id = $stateParams.id
      TIME = 'time'
      vm.showCouponError = false
      vm.isCreate = not id

      vm.detail = {
        name: ''
        code: ''
        limit: {}
      }
      vm.properties = []
      vm.breadcrumb = [
        text: 'member_incentive'
        href: '/member/incentive'
      ]

      vm.breadcrumb.push 'member_new_incentive' if not id

      vm.triggerTimeItems = [
        value: 'day',
        text: 'customer_day'
      ,
        value: 'week',
        text: 'customer_week'
      ,
        value: 'month',
        text: 'customer_month'
      ]
      vm.detail.triggerTime = vm.triggerTimeItems[0].value
      vm.couponAvailable = $rootScope.enabledModules.indexOf('marketing') > -1

      if vm.isCreate
        vm.detail.limit.type = 'unlimited'
        vm.detail.rewardType = 'score'

      _getProperty = ->
        condition =
          where:
            isVisible: true
          order: 'asc'
          unlimited: true
        restService.get config.resources.memberProperties, condition, (data) ->
          vm.properties = data.items if data?.items
          if not vm.detail.properties
            vm.detail.properties = []
          angular.forEach vm.properties, (property) ->
            if $.inArray(property.id, vm.detail.properties) isnt -1
              property.check = true
        return

      _getCoupon = ->
        param =
          unexpired: moment().valueOf()
          unlimited: true
        restService.get config.resources.coupons, param, (data) ->
          if data
            vm.coupons = angular.copy data.items
            vm.defaultText = if vm.coupons and vm.coupons.length > 0 then 'microsite_coupon_select' else 'member_coupon_none'

      _getDetail = ->
        restService.get config.resources.scoreRule + '/' + id, (data) ->
          vm.detail = data
          if vm.detail.isDefault
            vm.breadcrumb.push 'member_excitation_' + vm.detail.name
          else
            vm.breadcrumb.push vm.detail.name

          vm.detail.triggerTime = vm.triggerTimeItems[0].value if not data.triggerTime
          vm.detail.fullname = 'member_' + vm.detail.name
          vm.detail.rewardType = 'score' if not vm.detail.rewardType?

          if vm.detail.name is 'perfect_information'
            _getProperty()
        return

      _postData = (url, method, param, tip) ->
        restService[method] url, param, (data) ->
          notificationService.success tip, false
          $timeout (->
            $location.url '/member/incentive'
          ), 1000

      _isValid = ->
        valid = true
        if vm.detail.rewardType is 'score' and vm.checkPoint()
          valid = false
        if vm.required
          valid = false
        if vm.detail.rewardType is 'coupon' and vm.showCouponError
          valid = false
        if vm.isCreate or not vm.detail.isDefault
          if vm.checkName()
            valid = false
          if vm.checkCode()
            valid = false
          if vm.detail.limit.type is 'day' and vm.checkTimes()
            valid = false

        valid

      _checkField = ->
        vm.required = ''
        vm.showCouponError = false
        vm.detail.properties = []
        if vm.properties.length > 0
          angular.forEach vm.properties, (property) ->
            vm.detail.properties.push property.id if property.check
          if vm.detail.properties.length is 0
            vm.required = 'required_field_tip'

        if vm.detail.rewardType is 'coupon'
          if vm.coupons.length is 0
            vm.showCouponError = true
          else if not vm.detail.couponId? or vm.detail.couponId.length is 0
            vm.showCouponError = true

      _getDetail() if id
      _getCoupon() if vm.couponAvailable

      vm.generateCode = ->
        restService.noLoading().get config.resources.generateCode, (data) ->
          vm.detail.code = data.code
          $('#code').focus()

      vm.checkPoint = ->
        intRegx =  /^[0-9]*[1-9][0-9]*$/
        error = ''
        if not intRegx.test vm.detail.score
          error = 'customert_point_input_string'
        error

      vm.checkTimes = ->
        intRegx =  /^[0-9]*[1-9][0-9]*$/
        error = ''
        if not intRegx.test vm.detail.limit.value
          error = 'customert_point_input_string'
        error

      vm.checkName = ->
        error = ''
        if vm.detail.name.length < 4 or vm.detail.name.length > 30
          error = 'content_name_tip'
        error

      vm.checkCode = ->
        error = ''
        if vm.detail.code.length < 6 or vm.detail.code.length > 10
          error = 'member_incentive_code_tip'
        error

      vm.changeLimitType = (type) ->
        if type is 'unlimited'
          vm.detail.limit =
            type: 'unlimited'
        else
          vm.detail.limit =
            type: 'day'

      vm.changeRewardType = (type) ->
        if type is 'score'
          vm.detail.score = ''

      vm.save = ->
        _checkField()

        if _isValid()
          param =
            name: vm.detail.name
            code: if vm.detail.code then vm.detail.code else ''
            limit: vm.detail.limit
            triggerTime: vm.detail.triggerTime
            score: if vm.detail.rewardType is 'score' then parseInt vm.detail.score
            description: vm.detail.description
            isEnabled: vm.detail.isEnabled
            properties: vm.detail.properties
            rewardType: vm.detail.rewardType
            couponId: if vm.detail.rewardType is 'coupon' then vm.detail.couponId else ''
          param.type = TIME if vm.detail.triggerTime

          if vm.isCreate
            _postData(config.resources.scoreRules, 'post', param, 'customer_score_rule_create_success')
          else
            _postData(config.resources.scoreRule + '/' + id, 'put', param, 'customer_score_rule_update_success')

      vm.hidePropertyError = ->
        vm.required = ''

      vm.hideCouponError = ->
        vm.showCouponError = false

      vm.cancel = ->
        $location.url '/member/incentive'

      return

  ]
