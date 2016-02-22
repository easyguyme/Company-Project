define [
  'wm/app'
  'wm/config'
], (app, config) ->
  app.registerController 'wm.ctrl.uhkklp.edit.activity', [
    'restService'
    '$stateParams'
    '$scope'
    '$filter'
    '$location'
    'notificationService'
    'localStorageService'
    'validateService'
    (restService, $stateParams, $scope, $filter, $location, notificationService, localStorageService, validateService) ->
      vm = this

      _init = ->
        vm.thanToday = if $stateParams.id then false else true
        #breadcrum
        title = if $stateParams.id then 'activity_edit' else 'activity_add'
        vm.breadcrumb = [
          {
            text: 'uhkklp_activity'
            href: '/uhkklp/activity'
          }
          title
        ]

        vm.prizes = []
        vm.listPath = '/uhkklp/activity'
        vm.editPath = '/uhkklp/edit/activity'
        vm.activity = {
          _id: 'create'  # default create : create activity
          status: 'Y'
          mainImgUrl: 'http://vincenthou.qiniudn.com/3a514aaebacff37d011ad829.png'
        }
        _getActivityById()
        return

      vm.addPrize = ->
        prize =
          _id: ""
          name: ""
          type: "littlePrize"
          prizeImgUrl: "http://vincenthou.qiniudn.com/17ebc4729b906deba7c65951.png"
          isPoint: "Y"
          points: null
          quantity: null
          startDate: ""
          endDate: ""
        vm.prizes.push prize
        return

      vm.deletePrize = (index, prizeId, $event) ->
        notificationService.confirm $event, {
            submitCallback: _deletePrizeHandler
            title: 'activity_prize_delete_tip'
            params: [index, prizeId]
          }
        return

      vm.submit = ->
        if _validateSubmit()
          notificationService.warning '請正確填寫所有必填項！',true
          return

        user = localStorageService.getItem config.keys.currentUser
        if not user
          window.location.href = '/site/login'
        vm.activity.operator = user.name

        i = 0
        while i < vm.prizes.length
          if typeof vm.prizes[i]['_id'] is 'object'
            vm.prizes[i]['_id'] = vm.prizes[i]['_id'].$id
          i++

        data =
          activity: vm.activity
          prizes: vm.prizes

        $("#submitForm").attr "disabled",true

        if data.activity._id is 'create'
          restService.post config.resources.activityCreate, data, (data) ->
            if data.code is 1000
              notificationService.warning '已存在上架拉霸，您不能新增上架拉霸！',true
              $("#submitForm").attr "disabled",false
            if data.code is 200
              # $location.url vm.listPath
              window.location.href = vm.listPath
            if data.code is 500
              notificationService.error '創建拉霸失敗，請稍後刷新重試！',true
              window.location.href = vm.editPath + '/' + data.activityId.$id
              # $location.url vm.listPath + '/' + data.activityId.$id
            return
        else
          restService.put config.resources.activityUpdate + '/' + vm.activity._id, data, (data) ->
            if data.code is 1000
              notificationService.warning '已存在上架拉霸，您不能上架拉霸！',true
              $("#submitForm").attr "disabled",false
            if data.code is 200
              # $location.url vm.listPath
              window.location.href = vm.listPath
            if data.code is 500
              notificationService.error '更新拉霸失敗，請稍後刷新重試！',true
              # window.location.href = vm.editPath + '/' + data.activityId.$id
              $("#submitForm").attr "disabled",false
            return
        return

      vm.checkInt100 = (id, number) ->
        tip = ''
        reg = /^(0|[0-9]{1,2}|100)$/
        if not reg.test number
          tip = 'activity_probability_tip'
          validateService.highlight($('#' + id), $filter('translate')('activity_probability_tip'))
        tip

      vm.checkPositiveInt = (id, number) ->
        tip = ''
        reg = /^(0|([1-9][0-9]*))$/
        if not reg.test number
          tip = 'activity_check_positive_int_tip'
          validateService.highlight($('#' + id), $filter('translate')('activity_check_positive_int_tip'))
        tip

      _validateSubmit = ->
        result = false
        $scope.submitted = true
        vm.activity.probability = parseInt vm.activity.probability
        if vm.activity.probability and vm.checkInt100('probability', vm.activity.probability)
          result = true
        for key, value of vm.prizes
          vm.prizes[key].points = parseInt vm.prizes[key].points
          vm.prizes[key].quantity = parseInt vm.prizes[key].quantity
          if vm.prizes[key].prizeImgUrl is ""
            validateService.highlight($('#prizeImgUrl' + key), $filter('translate')('activity_image_validate_tip'))
            result = true
          if vm.prizes[key].isPoint is 'Y' and vm.prizes[key].points and not /^[1-9][0-9]*$/.test vm.prizes[key].points
            result = true
          if vm.prizes[key].quantity and not /^[1-9][0-9]*$/.test vm.prizes[key].quantity
            result = true

        (
          if v.$invalid
            elem = $ '[name=' + k + ']'
            elem.focus()
            elem.blur()
            if not firstInvalidElemName
              firstInvalidElemName = k
              break
        )for k, v of $scope.activityForm when not /^\$/.test k
        elem = $ '[name=' + firstInvalidElemName + ']'
        if firstInvalidElemName
          # console.log firstInvalidElemName
          scrollTo elem[0].offsetLeft, elem[0].offsetTop
          # console.log elem[0].offsetTop
          result = true
        result

      _getActivityById =  ->
        if $stateParams.id isnt undefined
          restService.get config.resources.getActivity + '/' + $stateParams.id, (data) ->
            if data
              vm.activity = data.activity
              vm.prizes = data.prizes
              # console.log data
              return
        return

      _deletePrizeHandler = (index,prizeId) ->
        if prizeId.length is 0
          vm.prizes.splice parseInt(index),1
          $scope.$apply()
          notificationService.success '成功移除該獎項！',true
        else
          restService.del config.resources.deletePrize + '/' + prizeId.$id, (data) ->
            if data
              if data.code is 200
                vm.prizes.splice parseInt(index),1
                notificationService.success '成功移除該獎項！',true
            return
        return

      _init()
      vm
  ]
