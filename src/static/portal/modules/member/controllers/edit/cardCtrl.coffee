define [
  'wm/app'
  'wm/config'
  'core/directives/wmColorPicker'
], (app, config) ->
  app.registerController 'wm.ctrl.member.edit.card', [
    'restService'
    '$scope'
    '$stateParams'
    'notificationService'
    '$state'
    '$rootScope'
    '$modal'
    'validateService'
    '$filter'
    '$timeout'
    (restService, $scope, $stateParams, notificationService, $state, $rootScope, $modal, validateService, $filter, $timeout) ->
      vm = this

      defaultPoster = '/images/mobile/membercard.png'

      _setLanguage = ->
        vm.language = $scope.user.language or 'zh_cn'
        $rootScope.$on '$translateChangeSuccess', (event, data) ->
          vm.language = data.language
        return

      _init = ->
        vm.focusStyle = false
        vm.cardNameShow = false
        vm.membershipCardId = $stateParams.id
        bascCardTitle = 'customer_card_' + if vm.membershipCardId then 'edit' else 'create'
        vm.breadcrumb = [
          {
            text: 'customer_card'
            href: '/member/card'
          }
          bascCardTitle
        ]
        vm.months = []
        for num in [1..12]
          vm.months.push
            text: num.toString()
            value: num
        vm.cardAutomaticZeroMonth = vm.months[0].value
        setDaySelectList(vm.months[0].value)
        _setLanguage()

        if vm.membershipCardId
          restService.get config.resources.card + '/' + vm.membershipCardId, (data) ->
            vm.membershipCardList = data
            vm.membershipCardList.poster = vm.membershipCardList.poster or defaultPoster
            if data.scoreResetDate
              vm.isAutomaticReset = true
              vm.cardAutomaticZeroMonth = data.scoreResetDate.month
              setDaySelectList(data.scoreResetDate.month)
              vm.cardAutomaticZeroDay = data.scoreResetDate.day
        else
          vm.membershipCardList =
            id: ''
            name: ''
            fontColor: '#fefefe'
            privilege: ''
            poster: defaultPoster
            condition:
              minScore: ''
              maxScore: ''
            usageGuide: ''
            isDefault: ''
            isDeleted: ''
            createdAt: ''
            updatedAt: ''
            accountId: ''

      _checkScore = ->
        $formTip = $('.card-upgrade-wrapper').find('.highlight .form-tip')
        reg = /^[1-9]\d*|0$/
        minScore = if vm.membershipCardList.condition?.minScore? then vm.membershipCardList.condition.minScore else ''
        maxScore = if vm.membershipCardList.condition?.maxScore? then vm.membershipCardList.condition.maxScore else ''
        if not reg.test(minScore) or not reg.test(maxScore)
          $formTip.text $filter('translate')('customer_card_formtip_score')
          return false
        else if Number(minScore) > Number(maxScore)
          $formTip.text $filter('translate')('customer_card_formtip_score')
          return false
        return true

      onfocusErrorForm = (name) ->
        $target = $(event.target)
        $input = $target.children().find(name)
        $input.focus()
        vm.focusStyle = true

      setDaySelectList = (month) ->
        monthDayMaps = [31, 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31]
        vm.days = []
        day = monthDayMaps[month - 1]
        for num in [1..day]
          vm.days.push
            text: num.toString()
            value: num
        vm.cardAutomaticZeroDay = vm.days[0].value if not vm.cardAutomaticZeroDay

      vm.hideTip = ->
        if not vm.membershipCardList.isAutoUpgrade
          vm.isShowTip = false

      vm.showTip = ->
        vm.isShowTip = true

      vm.checkName = ->
        formTip_name = ''
        length = vm.membershipCardList.name.trim().length
        if length < 4 or length > 10
          formTip_name = 'customer_card_formtip_name'
        else
          vm.cardNameShow = true
        formTip_name

      vm.checkPoster = ->
        formTip_poster = ''
        if not vm.membershipCardList.poster
          formTip_poster = 'customer_card_formtip_poster'
        formTip_poster

      vm.openPointsDialog = ->
        modalInstance = $modal.open(
          templateUrl: 'points.html'
          controller: 'wm.ctrl.member.points'
          windowClass: 'user-dialog'
          resolve:
            modalData: ->

          ).result.then( (data) ->

        )

      vm.changeAutomaticZeroMonth = (selectedMonth) ->
        setDaySelectList(selectedMonth)

      saveMembershipCard = (data, event) ->
        membershipCardList = angular.copy data

        validated = true

        if vm.checkName()
          validated = false
          onfocusErrorForm('.input-name')
        else
          $('.input-name').next().remove()
          $('#cardName').removeClass('form-control-error')

        if vm.checkPoster()
          validated = false

        if vm.membershipCardList.isAutoUpgrade and not _checkScore()
          validated = false
        else
          $('.input-score').next().remove()
          $('.input-score').next().removeClass('form-control-error')


        if validated
          if membershipCardList.isAutoUpgrade
            membershipCardList.condition.maxScore = Number(membershipCardList.condition.maxScore)
            membershipCardList.condition.minScore = Number(membershipCardList.condition.minScore)
          if vm.membershipCardId
            delete membershipCardList.createdAt
            delete membershipCardList.updatedAt
            restService.put config.resources.card + '/' + vm.membershipCardId, membershipCardList, (data) ->
              notificationService.success 'customer_card_update_success', false
              $state.go 'member-card'
              return
          else
            restService.post config.resources.cards, membershipCardList, (data) ->
              notificationService.success 'customer_card_create_success', false
              $state.go 'member-card'
              return

      vm.submit = (event) ->
        angular.forEach vm.membershipCardList, (membershipCard) ->
          if membershipCard and membershipCard.id is vm.membershipCardList.id
            membershipCard = angular.extend membershipCard, vm.membershipCardList
          return
        if vm.isAutomaticReset
          vm.membershipCardList.scoreResetDate =
            month: vm.cardAutomaticZeroMonth
            day: vm.cardAutomaticZeroDay
        else
          vm.membershipCardList.scoreResetDate = null if vm.membershipCardList.scoreResetDate
        saveMembershipCard vm.membershipCardList, event
        return

      _init()

      vm
  ]

  .registerController 'wm.ctrl.member.points', [
    'modalData'
    'restService'
    '$modalInstance'
    '$scope'
    (modalData, restService, $modalInstance, $scope) ->
      vm = $scope

      vm.list =
        columnDefs: [
          {
            field: 'name'
            label: 'customer_card_name2'
            cellClass: 'text-el'
          }, {
            field: 'range'
            label: 'member_points_range'
            type: 'translateValues'
          }
        ]
        data: []
        nodata: 'no_data'

      _getCardList = ->
        params =
          where: '{"isAutoUpgrade": true}'
          orderBy: '{"condition.minScore":"asc"}'
          unlimited: true

        restService.get config.resources.cards, params, (data) ->
          items = data.items
          if items.length > 0
            for item in items
              range = item.condition.minScore + '-' + item.condition.maxScore
              item.range =
                key: 'member_card_points_key'
                values:
                  points: range

          vm.list.data = items

      _getCardList()

      vm.hideModal = ->
        $modalInstance.close()

      vm

  ]
