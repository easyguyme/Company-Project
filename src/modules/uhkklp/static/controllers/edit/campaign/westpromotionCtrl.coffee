define [
  'wm/app'
  'wm/config'
], (app, config) ->
  app.registerController 'wm.ctrl.uhkklp.edit.campaign.westpromotion', [
    'restService'
    '$stateParams'
    '$scope'
    '$filter'
    '$location'
    'notificationService'
    'localStorageService'
    'validateService'
    '$modal'
    '$http'
    'exportService'
    '$interval'
    '$timeout'
    (restService, $stateParams, $scope, $filter, $location, notificationService, localStorageService, validateService, $modal, $http, exportService, $interval, $timeout) ->
      vm = this

      $scope.active = $location.search().active
      $scope.submitted = false
      # vm.startDate = 1451577600000 # 1/1
      # vm.endDate = 1456761599000 # 2/29
      # vm.startDateOrder = 1451577600000 # 1/1
      # vm.endDateOrder = 1456761599000 # 2/29
      vm.name = ''
      vm.activityColor = ''
      vm.activityDescription = ''
      vm.registrationRule = ''
      vm.orderRule = ''
      vm.orderDescription = ''
      vm.registrationOptions = []
      realOptions = []
      vm.promotionOrders = []
      $scope.allPictures = []
      $scope.showEmptyError = false
      $scope.pic = ''
      vm.tags = []
      vm.tagsOrder = []
      vm.isSub = false
      vm.image = ''
      vm.orderImage = ''

      vm.tagAll = false
      vm.tagAllOrder = false

      vm.params =
        tags: []

      vm.paramsOrder =
        tagsOrder: []

      restService.get '/api/channel/tags', (data) ->
        vm.tags = data.items
        return

      restService.get '/api/channel/tags', (data) ->
        vm.tagsOrder = data.items
        return

      restService.get '/api/uhkklp/goods/get-product-list', null, (data) ->
        $scope.allPictures = data.list

      title = if typeof $location.search().id is 'boolean' then 'activity_add_title' else 'activity_edit_title'
      vm.breadcrumb = [
        {
          text: 'uhkklp_activity'
          href: '/uhkklp/westpromotion?active=' + $scope.active
        }
        title
      ]

      randomCode = ->
        if not chars
          chars = "abcdefghijkmnpqrstuvwxyzABCDEFGHJKMNPQRSTUVWXYZ1234567890"
        randomChars = ""
        for x in [0..10]
          i = Math.floor (Math.random() * chars.length)
          randomChars = randomChars + chars.charAt(i)
        randomChars

      $scope.addRegistrationNo = ->
        optionIndex = randomCode()
        vm.registrationOptions.push({
          id : optionIndex
          readOnly : false
          content : ''
          showConfirm : true
        })

      $scope.reset = ->
        $scope.submitted = false

      $scope.confirmRegistrationNo = (content, id) ->
        if content is ''
           return
        flag = false
        angular.forEach(realOptions, (item) ->
          if item is content
            flag = true
        )
        if flag
          notificationService.warning 'option_repeat',false
          return
        tmpIdIndex = getIndex(vm.registrationOptions, id)
        realOptions.push content
        vm.registrationOptions[tmpIdIndex].showConfirm = false
        vm.registrationOptions[tmpIdIndex].readOnly = true

      $scope.deleteRegistrationNo = (id) ->
        tmpIdIndex = getIndex(vm.registrationOptions, id)
        if vm.registrationOptions[tmpIdIndex].showConfirm is false
          tmpIndex = realOptions.indexOf(vm.registrationOptions[tmpIdIndex].content)
          realOptions.splice(tmpIndex, 1)
        vm.registrationOptions.splice(tmpIdIndex, 1)

      getIndex = (array, id) ->
        tmpItem = {}
        angular.forEach(array, (item) ->
          if item.id is id
            tmpItem = item
        )
        return array.indexOf(tmpItem)

      $scope.addOrderOption = (content) ->
        if content is ''
          return
        vm.promotionOrders.push(content)
        $scope.allPictures.splice($scope.allPictures.indexOf(content), 1)
        $scope.pic = ''

      $scope.deleteOrderOption = (item) ->
        $scope.allPictures.push(item)
        vm.promotionOrders.splice(vm.promotionOrders.indexOf(item), 1)

      vm.selectAllTag = ->
        if vm['tagAll']
          items = []
          angular.forEach vm['tags'], (item) ->
            items.push item['name']
            item.check = true
          vm.params['tags'] = items
        else
          angular.forEach vm['tags'], (item) ->
            item.check = false
          vm.params['tags'] = []
        return

      vm.selectAllTagOrder = ->
        if vm['tagAllOrder']
          items = []
          angular.forEach vm['tagsOrder'], (item) ->
            items.push item['name']
            item.check = true
          vm.paramsOrder['tagsOrder'] = items
        else
          angular.forEach vm['tagsOrder'], (item) ->
            item.check = false
          vm.paramsOrder['tagsOrder'] = []
        return

      vm.selectTag = (tag) ->
        index = $.inArray(tag['name'], vm.params['tags'])
        if tag.check
          if index is -1
            vm.params['tags'].push tag['name']
        else
          if index isnt -1
            vm.params['tags'].splice(index, 1)

        if vm.params['tags'].length is vm['tags'].length
          vm['tagAll'] = true
        else
          vm['tagAll'] = false
        return

      vm.selectTagOrder = (tag) ->
        index = $.inArray(tag['name'], vm.paramsOrder['tagsOrder'])
        if tag.check
          if index is -1
            vm.paramsOrder['tagsOrder'].push tag['name']
        else
          if index isnt -1
            vm.paramsOrder['tagsOrder'].splice(index, 1)

        if vm.paramsOrder['tagsOrder'].length is vm['tagsOrder'].length
          vm['tagAllOrder'] = true
        else
          vm['tagAllOrder'] = false
        return

      if typeof $location.search().id isnt 'boolean'
          $http
            method: 'POST'
            url: '/api/uhkklp/west-pro-activity/get-one'
            data: $.param _id : $location.search().id
            headers:
              'Content-Type': 'application/x-www-form-urlencoded'
          .success (data) ->
            if data['result'] is 'success'
             vm.name = data['name']
             vm.activityColor = data['activityColor']
             vm.image = data['image']
             vm.orderImage = data['orderImage']
             vm.startDate = data['registrationStartDate']
             vm.endDate = data['registrationEndDate']
             vm.params.tags = data['registrationTags']
             angular.forEach vm.tags, (item) ->
               angular.forEach vm.params.tags, (it) ->
                 if item.name is it
                   item.check = true
             if vm.tags.length is vm.params.tags.length
               vm.tagAll = true
             vm.activityDescription = data['registrationDescription']
             vm.registrationRule = data['registrationRule']
             realOptions = data['registrationNumber']
             angular.forEach realOptions, (item) ->
              optionIndex = randomCode()
              vm.registrationOptions.push({
                id : optionIndex
                readOnly : true
                content : item
                showConfirm : false
              })
             vm.startDateOrder = data['orderStartDate']
             vm.endDateOrder = data['orderEndDate']
             vm.paramsOrder.tagsOrder = data['orderTags']
             angular.forEach vm.tagsOrder, (item) ->
               angular.forEach vm.paramsOrder.tagsOrder, (it) ->
                 if item.name is it
                   item.check = true
             if vm.tagsOrder.length is vm.paramsOrder.tagsOrder.length
               vm.tagAllOrder = true
             vm.orderDescription = data['orderDescription']
             vm.orderRule = data['orderRule']
             vm.promotionOrders = data['promotionProducts']
             angular.forEach vm.promotionOrders, (item) ->
              angular.forEach $scope.allPictures, (it, index) ->
                if item.name is it.name
                  $scope.allPictures.splice(index, 1)
            else
              notificationService.error 'registration_edit_error', false
          .error (data) ->
            notificationService.error 'registration_edit_error', false

      vm.save = ->
        if vm.activityColor is ''
          $scope.submitted = true
        if vm.image is '' or vm.orderImage is ''
          vm.isSub = true
        if vm.startDateOrder < vm.endDate
          notificationService.warning 'westpromotion_date_tip',false
          return
        if vm.startDate > vm.endDate or vm.startDateOrder > vm.endDateOrder
          return
        # if vm.params.tags.length is 0 or vm.paramsOrder.tagsOrder.length is 0
        #   notificationService.warning 'westpromotion_tags_tip',false
        #   return
        if realOptions.length is 0
          notificationService.warning 'regis_num_tip',false
          return
        if $scope.activityForm.$invalid or vm.promotionOrders.length is 0 or vm.image is '' or vm.orderImage is '' or vm.activityColor is ''
          notificationService.warning 'all_required',false
          return
        params =
          name: vm.name
          activityColor: vm.activityColor
          image: vm.image
          orderImage: vm.orderImage
          registrationStartDate: vm.startDate
          registrationEndDate: vm.endDate
          registrationTags : vm.params.tags
          registrationDescription: vm.activityDescription
          registrationRule: vm.registrationRule
          registrationNumber: realOptions
          startDateOrder: vm.startDateOrder
          endDateOrder: vm.endDateOrder
          tagsOrder : vm.paramsOrder.tagsOrder
          orderDescription : vm.orderDescription
          orderRule : vm.orderRule
          promotionProducts : vm.promotionOrders
          id : localStorageService.getItem(config.keys.currentUser).id
        if typeof $location.search().id isnt 'boolean'
          url = '/api/uhkklp/west-pro-activity/update'
          params._id = $location.search().id
        else
          url = '/api/uhkklp/west-pro-activity/save'
        params = $.param params
        $http
          method: 'POST'
          url: '/api/uhkklp/west-pro-activity/validate-activity-exist'
          data: $.param activityName : vm.name
          headers:
            'Content-Type': 'application/x-www-form-urlencoded'
        .success (data) ->
          if data.result is 'exist' and typeof $location.search().id is 'boolean'
            notificationService.warning 'activity_exist',false
          else
            restService.post url, params, (data) ->
              if data.msg is 'success'
                notificationService.success 'ac_success_tip',false
                $timeout(() ->
                        $location.url '/uhkklp/westpromotion?active=' + $scope.active,
                2000)
              else
                notificationService.error 'ac_failed_tip',false
              return
        return

      vm
  ]

