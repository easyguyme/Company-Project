define [
  'wm/app'
  'wm/config'
], (app, config) ->
  app.registerController 'wm.ctrl.uhkklp.edit.recipe', [
    'restService'
    '$stateParams'
    '$scope'
    '$http'
    'localStorageService'
    'notificationService'
    '$location'
    '$rootScope'
    '$filter'
    (restService, $stateParams, $scope, $http, localStorageService, notificationService, $location, $rootScope, $filter) ->
      vm = this

      #breadcrum
      title = if $stateParams.id then 'recipe_edit_title' else 'recipe_add_title'
      vm.breadcrumb = [
        {
          text: 'uhkklp_recipe'
          href: '/uhkklp/recipe'
        }
        title
      ]

      vm.listPath = '/uhkklp/recipe'

      scrollTo 0, 0
      $scope.isSubmitted = false
      $scope.cookbookId = $stateParams.id
      $scope.cookbook = {}
      $scope.newIngredientId = 1
      $scope.newSampleId = 1
      $scope.ingredient = []
      $scope.samples = []
      $scope.formData = {
          active: "Y"
          isSampleOpen: "N"
      }
      $scope.type = []
      $scope.imgDomain = ''
      $scope.isCreating = if $stateParams.id then false else true
      $scope.sampleDate = []

      #getSamples
      _getSamples = ->
        $http
          method: 'GET'
          url: '/api/uhkklp/sample/list-all'
          headers:
            'Content-Type': 'application/x-www-form-urlencoded'
        .success (data) ->
          $scope.sampleDate = data['result']
          for item in $scope.samples
            for sample,i in $scope.sampleDate
              if item.id is sample.id
                $scope.sampleDate.splice i,1
                break
        .error (data) ->
          notificationService.error 'recipe_edit_miss_samples', true

      _getSamples()

      vm.tags = []
      _getCookingType = ->
        vm.tags = []
        $http
          url: '/api/uhkklp/cooking-type/list'
          method: 'GET'
          headers:
            'Content-Type': 'application/x-www-form-urlencoded'
        .success (data) ->
          if (data['code'] is 200)
            for $data,i in data['result']
              if not ($data.category is '大類')
                vm.tags.push({"name": $data.name, "check": false})
          else
            notificationService.error 'recipe_list_get_cook_type_error', false
          _showType()
        .error ->
          notificationService.error 'recipe_list_get_cook_type_error', false

      _getCookingType()

      _showType = ->
        for type in $scope.type
          for tag in vm.tags
            if tag.name is type
              tag.check = true

      #getCookbook
      _getCookbook = ->
        $http
          method: 'GET'
          url: '/api/uhkklp/cookbook/get-cookbook-by-id?cookbookId=' + $scope.cookbookId
          headers:
            'Content-Type': 'application/x-www-form-urlencoded'
        .success (data) ->
          if data['code'] is 200
            $cookbookData = data['result']
            $startDate = $cookbookData['startDate']
            $endDate = $cookbookData['endDate']
            $scope.ingredient = $cookbookData['ingredient']
            $scope.samples = $cookbookData['sample']
            $scope.formData = {
              title: $cookbookData['title']
              content: $cookbookData['content']
              startDate: $startDate * 1000
              endDate: $endDate * 1000
              shareUrl: $cookbookData['shareUrl']
              isSampleOpen: $cookbookData['isSampleOpen']
              active: $cookbookData['active']
              video: $cookbookData['video']
              restaurantName: $cookbookData['restaurantName']
              cookName: $cookbookData['cookName']
              image: $cookbookData['image']
              deliciousSecret: $cookbookData['deliciousSecret']
              creativeExperience: $cookbookData['creativeExperience']
              shareDescription: $cookbookData['shareDescription']
              activitySettingId: $cookbookData['activitySettingId']
              activitySettingName: $cookbookData['activitySettingName']
            }
            $scope.type = $cookbookData['type']
            $asid = $cookbookData['activitySettingId']
            if $asid is "" or $asid is null or $asid is undefined or $asid is 'undefined'
             $asid = $filter('translate')('recipe_edit_no_activity_setting')
            $scope.currentActivitySetting = {
              id: $asid
              name: $cookbookData['activitySettingName']
            }
            _showType()
            _getSamples()
          else
            notificationService.error 'recipe_edit_miss_data', false
        .error (data) ->
          $location.url = vm.listPath
          $rootScope.uhkklp_recipe_tip = 'recipe_edit_miss_data'

      if $stateParams.id
        _getCookbook()

      #newIngredientItem
      $scope.newIngredientItem = ->
        $scope.newIngredientId = $scope.randomCode()
        $scope.ingredient.push {
          "id": $scope.newIngredientId
          "quantity": ''
          "unit": ''
          "name": ''
          "url": ''
        }

      #deleteIngredientItem
      $scope.deleteIngredientItem = ($event, id) ->
        notificationService.confirm  $event, {
          title: 'recipe_edit_delete_tip'
          submitCallback: _deleteIngredientItem
          params: [id]
        }

      _deleteIngredientItem = (id) ->
        for ingredient,i in $scope.ingredient
         if ingredient.id is id
            $scope.ingredient.splice(i, 1)
            break
        $scope.$apply()

      #checkIngredientItem
      $scope.checkIngredientItem = ->
        j = 0
        length = $scope.ingredient.length - 1
        if length < 0
          return
        for i in [0..length]
          ingredient = $scope.ingredient[j]
          if ingredient.quantity is '' and ingredient.unit is '' and ingredient.name is ''
            $scope.ingredient.splice(j,1)
            continue
          j++

      #newSample
      $scope.newSample = ->
        $scope.newSampleId = $scope.randomCode()
        $scope.samples.push {
          "id": $scope.newSampleId
          "quantity": ''
          "name": ''
        }

      #deleteSample
      $scope.deleteSample = ($event, id) ->
        notificationService.confirm $event, {
          title: 'recipe_edit_delete_tip'
          submitCallback: _deleteSample
          params: [id]
        }
      _deleteSample = (id) ->
        for sample,i in $scope.samples
          if sample.id is id
            $scope.sampleDate.push sample
            $scope.samples.splice(i,1)
            break
        $scope.$apply()

      #checkSample
      $scope.checkSample = ->
        j = 0
        length = $scope.samples.length - 1
        if length < 0
          return
        for i in [0..length]
          sample = $scope.samples[j]
          if sample.quantity is '' and sample.name is ''
            $scope.samples.splice(j,1)
            continue
          j++

      #valid is empty
      _validateSubmit = ->
        (
          elem = $ '[name=' + k + ']'
          elem.focus()
          elem.blur()
          if v.$invalid
            if not firstInvalidElemName
              firstInvalidElemName = k
        )for k, v of $scope.editForm when not /^\$/.test k
        elem = $ '[name=' + firstInvalidElemName + ']'
        if firstInvalidElemName
          scrollTo elem[0].offsetLeft, (elem[0].offsetTop - 50)
        return

      $scope.isCheckedUrl = false
      $scope.focusUrl = ->
        $scope.isCheckedUrl = false
      $scope.blurUrl = ->
        _checkShareUrl()
        $scope.isCheckedUrl = true

      $scope.UrlFormat = false
      _checkShareUrl = ->
        $scope.UrlFormat = false
        $shareUrl = $scope.editForm.shareUrl.$viewValue
        if not $shareUrl? or $shareUrl is ''
          return
        check = ///((http|ftp|https)://)(([a-zA-Z0-9\._-]+\.[a-zA-Z]{2,6})|([0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}))(:[0-9]{1,4})*(/[a-zA-Z0-9\&%_\./-~-]*)?///
        if check.test($shareUrl)
          $scope.UrlFormat = false
        else
          $scope.UrlFormat = true

      $scope.isCheckedVideo = false
      $scope.focusVideo = ->
        $scope.isCheckedVideo = false
      $scope.blurVideo = ->
        _checkVideo()
        $scope.isCheckedVideo = true

      $scope.VideoFormat = false
      _checkVideo = ->
        $scope.VideoFormat = false
        $video = $scope.editForm.video.$viewValue
        if not $video? or $video is ''
          return
        check = ///((http|ftp|https)://)(([a-zA-Z0-9\._-]+\.[a-zA-Z]{2,6})|([0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}))(:[0-9]{1,4})*(/[a-zA-Z0-9\&%_\./-~-]*)?///
        if check.test($video)
          $scope.VideoFormat = false
        else
          $scope.VideoFormat = true

      $scope.focusEndTime = ->
        $scope.isSameTime = false

      _getTypeDate = ->
        types = []
        for tag in vm.tags
          if tag.check
            types.push tag.name
        return types

      $scope.startTimeRequired = false
      $scope.endTimeRequired = false
      $scope.isSameTime = false
      $scope.isClickable = false
      $scope.active = $location.search().active
      if not $scope.active? or $scope.active is 'undefined'
        $scope.active = '0'
      #submitInfo
      $scope.submitInfo = (isRequired, isShareUrl, isVideo) ->
        if not $scope.formData['image']? or $scope.formData['image'] is ''
          $scope.editForm.image.$invalid = true
        else
          $scope.editForm.image.$invalid = false

        $scope.isSubmitted = true
        $scope.isClickable = true
        if $scope.formData.startDate is undefined
          $scope.startTimeRequired = true
        if $scope.formData.endDate is undefined
          $scope.endTimeRequired = true

        if isRequired or $scope.endTimeRequired or $scope.startTimeRequired or $scope.editForm.image.$invalid
          notificationService.warning 'recipe_edit_required_tip', false
          _validateSubmit()
          $scope.isClickable = false
          $scope.startTimeRequired = false
          $scope.endTimeRequired = false
          return

        if not ($scope.formData.startDate < $scope.formData.endDate)
          $scope.isSameTime = true
          notificationService.warning 'recipe_edit_invalid_end_time', false
          _validateSubmit()
          $scope.isClickable = false
          return

        if isShareUrl
          notificationService.warning 'recipe_edit_share_url_msg', false
          _validateSubmit()
          $scope.isClickable = false
          return

        if isVideo
          notificationService.warning 'recipe_edit_video_msg', false
          _validateSubmit()
          $scope.isClickable = false
          return

        $scope.checkIngredientItem()
        $scope.checkSample()
        if $scope.formData.isSampleOpen is "Y" and $scope.samples.length is 0
          notificationService.warning 'recipe_edit_sample_tip', false
          _validateSubmit()
          $scope.isClickable = false
          return

        $samples = $scope.samples
        sampleName = ''
        for sample,i in $samples
          sampleName = sample.name
          k = i + 1
          max = $samples.length - 1
          if k > max
            break
          for j in [k..max]
            if sampleName is $samples[j].name
              notificationService.warning 'recipe_edit_sample_repeat_tip', false
              $scope.isClickable = false
              return

        $ingredients = $scope.ingredient
        ingredientName = ''
        for ingredient,i in $scope.ingredient
          ingredientName = ingredient.name
          k = i + 1
          max = $ingredients.length - 1
          if k > max
            break
          for j in [k..max]
            if ingredientName is $ingredients[j].name
              notificationService.warning 'recipe_edit_ingredient_repeat_tip', false
              $scope.isClickable = false
              return

        $scope.formData.ingredient = $scope.ingredient
        $scope.formData.sample = $scope.samples
        $scope.formData.cookbookId = $scope.cookbookId
        $scope.formData.id = localStorageService.getItem(config.keys.currentUser).id
        $scope.formData.type = _getTypeDate()
        $http
          method: 'POST'
          url: '/api/uhkklp/cookbook/save'
          data: $.param($scope.formData)
          headers:
            'Content-Type': 'application/x-www-form-urlencoded'
        .success (data) ->
          $scope.isClickable = false
          if data['code'] is 1209
            $location.url '/site/login'
            $rootScope.uhkklp_recipe_tip = 'recipe_go_to_login_tip'
          else if data['code'] is 1204
            notificationService.error 'sample_edit_miss_tip', false
          else
            if not ($scope.active is '1')
              $scope.active = '0'
            $location.url '/uhkklp/recipe?active=' + $scope.active
            $rootScope.uhkklp_recipe_tip = 'recipe_edit_save_success'
        .error (data) ->
          notificationService.error 'recipe_edit_save_error', false
        $scope.isClickable = false

      #randomCode
      $scope.randomCode = ->
        if not chars
          chars = "abcdefghijkmnpqrstuvwxyzABCDEFGHJKMNPQRSTUVWXYZ1234567890"
        randomChars = ""
        for x in [0..10]
          i = Math.floor (Math.random() * chars.length)
          randomChars = randomChars + chars.charAt(i)
        randomChars

      $scope.change = (item, sample) ->
        if not (item.name is '')
          $scope.sampleDate.push({
              "id": item.id
              "quantity": item.quantity
              "name": item.name
            })
        item.id = sample.id
        item.name = sample.name
        item.quantity = sample.quantity
        for itemSample,i in $scope.sampleDate
          if itemSample.id is item.id
            $scope.sampleDate.splice i, 1
            break

      $scope.activeSettings = []
      $scope.activitySettingId = ''
      _getActivitySetting = ->
        $http
          method: 'GET'
          url: '/api/uhkklp/cookbook/get-is-active-list'
        .success (data) ->
          if data['code'] is 200
            $scope.activeSettings = []
            $scope.activeSettings.push {
              id: $filter('translate')('recipe_edit_no_activity_setting')
              name: ""
            }
            for item in data['list']
              $scope.activeSettings.push item

      _getActivitySetting()

      $scope.setActiveSetting = (currentActivitySetting) ->
        if currentActivitySetting['id'] is $filter('translate')('recipe_edit_no_activity_setting')
          $scope.formData.activitySettingId = null
          $scope.formData.activitySettingName = null
          return
        for activeSetting in $scope.activeSettings
          if activeSetting['id'] is currentActivitySetting['id']
            $scope.formData.activitySettingId = currentActivitySetting['id']
            $scope.formData.activitySettingName = currentActivitySetting['name']
            return
      vm
  ]
