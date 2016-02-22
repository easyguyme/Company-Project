define [
  'wm/app'
  'wm/config'
], (app, config) ->
  app.registerController 'wm.ctrl.member.edit.member', [
    'restService'
    '$stateParams'
    'notificationService'
    '$location'
    '$filter'
    'validateService'
    (restService, $stateParams, notificationService, $location, $filter, validateService) ->
      vm = this

      _init = ->
        vm.memberTitle = if $stateParams.id then 'customer_edit_member' else 'customer_create_member'
        vm.defaultCard = 'customer_member_card_select'
        vm.cardOptions = []
        vm.associator =
          avatar: config.defaultAvatar
          cardId: ''
          location:
            country: ''
            province: ''
            city: ''
            detail: ''
          tags: []

        vm.breadcrumb = [
          {
            text: 'customer_member_management'
            href: '/member/member'
          }
          vm.memberTitle
        ]


        # Get all the availablle tags
        restService.get config.resources.tags, (data) ->
          angular.forEach data.items, (tag) ->
            tag.check = false
          vm.optionTags = angular.copy data.items

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

          # Is update member
          if $stateParams.id?
            vm.memberId = $stateParams.id
            # Query the member according to member id
            restService.get config.resources.member + '/' + vm.memberId, (data) ->
              vm.associator = data if data?
              vm.cardExpiredAt = if data.cardExpiredAt isnt '' then moment(data.cardExpiredAt) else null
              if vm.associator.properties? and vm.memberProperties?
                angular.forEach vm.associator.properties, (property) ->
                  angular.forEach vm.memberProperties, (item) ->
                    if property.id is item.id
                      item.value = property.value
                      if item.type is "date" and item.value is ""
                        item.value = null
              if vm.associator.card?
                vm.defaultCard = vm.associator.card.name
                vm.associator.cardId = vm.associator.card.id
              if vm.associator.tags?
                angular.forEach vm.associator.tags, (tag) ->
                  angular.forEach vm.optionTags, (item) ->
                    if item.name is tag
                      item.check = true

              angular.forEach vm.memberProperties, (item) ->
                if item.type is "checkbox" and item.value and item.value.length > 0
                  angular.forEach item.value, (value) ->
                    angular.forEach item.options, (option) ->
                      if option.name is value
                        option.check = true
                if item.type is 'radio' and not item.value
                  item.value = item.defaultValue or item.options[0]
      _init()

      vm.submit = (event) ->
        if event.valid? and not event.valid
          return
        if vm.associator.tags?
          vm.associator.tags.length = 0
        else
          vm.associator.tags = []
        angular.forEach vm.optionTags, (tag) ->
          vm.associator.tags.push tag.name if tag.check
        if vm.associator.properties?
          vm.associator.properties.length = 0
        else
          vm.associator.properties = []
        angular.forEach vm.memberProperties, (item) ->
          property =
            id: item.id
            name: item.name
          property.value = item.value if item.value?
          if item.type is 'checkbox'
            property.value = []
            item.options.forEach (option) ->
              property.value.push option.name if option.check
          if property.value?
            if property.value instanceof Array
              vm.associator.properties.push property
            else
              vm.associator.properties.push property
        for option in vm.associator.properties
          vm.tel = option.value if option.name is 'tel'
        if vm.checkName() isnt "" or not vm.checkCheckbox() or vm.checkTelNum(vm.tel) isnt ""
          return

        # Create member or Update member
        condition =
          avatar: vm.associator.avatar
          location: vm.associator.location
          tags: vm.associator.tags
          properties: vm.associator.properties

        if vm.memberId?
          restService.put config.resources.member + '/' + vm.memberId, condition, (data) ->
            notificationService.success 'customer_member_update_success', false
            $location.url '/member/member'
        else
          restService.post config.resources.members, condition, (data) ->
            notificationService.success 'customer_member_create_success', false
            $location.url '/member/member'

      vm.checkTelNum = (tel) ->
        validateService.checkTelNum tel

      vm.checkCheckbox = ->
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
        return result

      vm.checkName = ->
        nameTip = ''
        angular.forEach vm.memberProperties, (item) ->
          if item.name is "name"
            if item.value and (item.value.length < 2 or item.value.length > 30)
              nameTip = 'customer_member_name_tip'
        nameTip

      vm.cancel = ->
        $location.url '/member/member'

      return vm
  ]
