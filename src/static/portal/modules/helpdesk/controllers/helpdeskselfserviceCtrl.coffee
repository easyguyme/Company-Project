define [
  'wm/app'
  'wm/config'
  'core/coreModule'
], (app, config, mod) ->
  app.registerController 'wm.ctrl.helpdesk.helpdeskselfservice', [
    'restService'
    'notificationService'
    '$modal'
    (restService, notificationService, $modal) ->
      vm = this

      ACTIVENOTHING = -1

      # used to judge the type of reply or keyword
      type =
        back: "back"
        reply: "reply"
        connect: "connect"

      # used in function _updateSettings to judge the operation type
      operationType =
        edit: "edit"      # edit the reply or keyword
        create: "create"  # insert a new reply or keyword
        delete: "delete"  # delete a new reply or keyword

      vm.firstLevel = []  # it will be [{}, {}, {}, {}....]
      vm.secondLevel = [] # it will be [{}, {}, {}, {}....]
      vm.thirdLevels = [] # it is different! will be [[{}, {}, {}..], [{}, {}...]...]
      vm.chosenThird = [] # it will be [{}, {}, {}, {}....], and is one item in the vm.thirdLevels

      vm.activeFirst = ACTIVENOTHING
      vm.activeSecond = ACTIVENOTHING
      vm.activeThird = ACTIVENOTHING

      vm.breadcrumb = [
        text: 'helpdesk_self_service'
        help: 'helpdesk_self_service_menu_info'
      ]

      # take apart the settings opject and put the data into three arrays
      _renderLevels = ->
        _emptyLevels()
        return if not vm.settings?
        first = {
          content: vm.settings.content
          type: vm.settings.type
        }
        vm.firstLevel.push first
        angular.forEach vm.settings.menus, (secondLevel, keyword) ->
          second = {
            keyword: keyword
            content: secondLevel.content
            type: secondLevel.type
          }
          vm.secondLevel.push second
          thirdArray = null
          angular.forEach secondLevel.menus, (thirdLevel, keyword) ->
            thirdArray = [] unless thirdArray instanceof Array
            third = {
              keyword: keyword
              content: thirdLevel.content
              type: thirdLevel.type
            }
            thirdArray.push third
          if thirdArray instanceof Array then vm.thirdLevels.push thirdArray else vm.thirdLevels.push []
        _initActiveLevels()
        vm.chosenThird = vm.thirdLevels[vm.activeSecond]
        vm.showThirdAdd = not (not vm.secondLevel[vm.activeSecond]? or vm.secondLevel[vm.activeSecond]?.type is type.back or vm.secondLevel[vm.activeSecond]?.type is type.connect)


      _emptyLevels = ->
        vm.firstLevel = []
        vm.secondLevel = []
        vm.thirdLevels = []
        vm.chosenThird = []

      # set the activeFirst/Second/Third to 0 if it haven't been set yet.
      _initActiveLevels = ->
        vm.activeFirst = 0 if vm.firstLevel?.length > 0 and vm.activeFirst is ACTIVENOTHING
        vm.activeSecond = 0 if vm.secondLevel?.length > 0 and vm.activeSecond is ACTIVENOTHING
        vm.activeThird = 0 if vm.thirdLevels[vm.activeSecond]?.length > 0 and vm.activeThird is ACTIVENOTHING

      _resetLevels = ->
        vm.activeFirst = ACTIVENOTHING
        vm.activeSecond = ACTIVENOTHING
        vm.activeThird = ACTIVENOTHING

      # extract the data in three arrays and combines them into settings object
      _renderSettings = ->
        # if the first level contains no reply, then give the api a null var
        # cannot give an empty object, or the display will be bad
        return null if not vm.firstLevel[0]?
        settings = {
          type: type.reply
          content: vm.firstLevel[0]?.content
          menus: {}
        }
        angular.forEach vm.secondLevel, (second, index) ->
          settings.menus[second.keyword] = {}
          secondKey = settings.menus[second.keyword]
          if secondKey?
            secondKey.content = second.content
            secondKey.type = second.type
            secondKey.menus = {}
          if secondKey.type is type.reply
            angular.forEach vm.thirdLevels[index], (third, index) ->
              secondKey.menus[third.keyword] = {}
              thirdKey = secondKey.menus[third.keyword]
              if thirdKey?
                thirdKey.content = third.content
                thirdKey.type = third.type
        settings

      vm.selectSecond = (index) ->
        vm.activeSecond = index
        vm.chosenThird = vm.thirdLevels[index] or []
        vm.activeThird = 0
        vm.showThirdAdd = not (vm.secondLevel[index]?.type is type.back or vm.secondLevel[index]?.type is type.connect)

      vm.selectThird = (index) ->
        vm.activeThird = index

      _newReplyModal = (data) ->
        modalInstance = $modal.open(
          templateUrl: "addReply.html"
          controller: "wm.ctrl.helpdesk.helpdeskselfservice.addreply"
          size: "md"
          resolve:
            modalData: ->
              if data then angular.copy data else {
                keyword: ""
                content: "您好，很高兴为您服务，您可以输入序号查看以下内容：\r\n[1] 卡片申请\r\n[2] 办卡寄送进度查询\r\n[3] 开卡/新卡激活\r\n[4] 推荐亲友办卡"
                type: type.reply
              }
        )
        modalInstance

      _newKeywordModal = (data, level) ->
        modal = if data then angular.copy data else {
          keyword: ""
          content: ""
          type: if level is 2 then type.reply else type.back
        }
        modal.level = level
        modalInstance = $modal.open(
          templateUrl: "addKeyword.html"
          controller: "wm.ctrl.helpdesk.helpdeskselfservice.addkeyword"
          size: "md"
          resolve:
            modalData: ->
              modal
            levelContent: ->
              return vm.secondLevel if level is 2
              return vm.chosenThird if level is 3
        )
        modalInstance

      _getSettings = ->
        restService.get  config.resources.helpdeskselfservice, {}, (data) ->
          vm.settings = data.settings
          vm.isPublished = data.isPublished
          _renderLevels()

      _updateSettings = (operation) ->
        settings = _renderSettings()
        restService.post config.resources.helpdeskselfservice, {settings: settings}, (data) ->
          if data.status is "ok"
            switch operation
              when operationType.create then notificationService.success 'helpdesk_self_service_create_success', false
              when operationType.delete then notificationService.success 'helpdesk_self_service_delete_success', false
              when operationType.edit then notificationService.success 'helpdesk_self_service_edit_success', false
            vm.settings = settings
            vm.isPublished = false
            _renderLevels()
          else
            _renderLevels()

      vm.insertReply = ->
        _newReplyModal().result.then (data) ->
          if data
            vm.firstLevel.push data
          _updateSettings(operationType.create)
          return

      vm.editReply = (cell) ->
        _newReplyModal(cell).result.then (data) ->
          if data
            vm.firstLevel[0] = data
          _updateSettings(operationType.edit)
          return

      vm.deleteReply = ($event) ->
        $event.stopPropagation()
        notificationService.confirm $event, {
          title: 'helpdesk_self_service_delete_reply'
          params: []
          submitCallback: _deleteReply
        }

      _deleteReply = ->
        _resetLevels()
        _emptyLevels()
        _updateSettings(operationType.delete)

      vm.insertKeyword = (level) ->
        _newKeywordModal(null, level).result.then (data) ->
          if data
            switch level
              when 2 then vm.secondLevel.push data
              when 3 then vm.chosenThird.push data
          _updateSettings(operationType.create)
          return

      vm.editKeyword = (level, cell, index) ->
        _newKeywordModal(cell, level).result.then (data) ->
          if data
            switch level
              when 2 then vm.secondLevel[index] = data
              when 3 then vm.chosenThird[index] = data
          _updateSettings(operationType.edit)
          return

      vm.deleteKeyword = (level, index, $event) ->
        $event.stopPropagation()
        notificationService.confirm $event, {
          title: 'helpdesk_self_service_delete_keyword'
          params: [level, index]
          submitCallback: _deleteKeyword
        }

      _deleteKeyword = (level, index) ->
        if level is 2
          vm.secondLevel[index ... index + 1] = []
          vm.thirdLevels[index ... index + 1] = [] # remove the child of level 2
        if level is 3
          vm.chosenThird[index ... index + 1] = []
        _updateSettings(operationType.delete)

      vm.publish = ->
        restService.post config.resources.helpdeskselfservicepublish, {}, (data) ->
          if data.status is "ok"
            notificationService.success 'helpdesk_self_service_publish_success', false
            vm.isPublished = true

      _init = ->
        _getSettings()

      _init()

      vm
  ]
  .registerController 'wm.ctrl.helpdesk.helpdeskselfservice.addreply', [
    'restService'
    '$scope'
    '$modalInstance'
    'modalData'
    (restService, $scope, $modalInstance, modalData) ->
      vm = $scope
      vm.cell = modalData
      vm.isEdit = if modalData.keyword? then false else true # init the modal window title

      vm.ok = (event) ->
        return if not event.valid
        $modalInstance.close vm.cell
        return

      vm.hideModal = ->
        $modalInstance.dismiss()
        return

      vm
  ]
  .registerController 'wm.ctrl.helpdesk.helpdeskselfservice.addkeyword', [
    'restService'
    '$scope'
    '$modalInstance'
    'modalData'
    'levelContent'
    (restService, $scope, $modalInstance, modalData, levelContent) ->
      vm = $scope
      vm.cell = modalData
      oldKeyword = modalData.keyword
      vm.isEdit = if modalData.keyword then true else false # init the modal window title

      # return true if the keyword has repetition, else return false
      _judgeKeywordRepetition = ->
        repeatKeyword = false
        angular.forEach levelContent, (data) ->
          if data.keyword is vm.cell.keyword and oldKeyword isnt vm.cell.keyword
            repeatKeyword = true
        repeatKeyword

      vm.ok = (event) ->
        return if _judgeKeywordRepetition() or not event.valid
        $modalInstance.close vm.cell
        return

      vm.checkRepeatKeyword = ->
        'helpdesk_self_service_keyword_repeat' if _judgeKeywordRepetition()

      vm.hideModal = ->
        $modalInstance.dismiss()
        return

      vm
  ]
