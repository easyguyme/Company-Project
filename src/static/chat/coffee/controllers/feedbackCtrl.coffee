define [
  'core/coreModule'
  'chat/config'
], (app, config) ->
  app.controller 'wm.ctrl.feedback', [
    '$scope'
    'restService'
    'notificationService'
    '$state'
    '$location'
    '$translate'
    'validateService'
    ($scope, restService, notificationService, $state, $location, $translate, validateService) ->
      vm = $scope
      vm.isDisabled = false
      vm.disableName = false
      vm.disableEmail = false
      vm.isUpdateAttachement = false
      vm.issue =
        attachments: []
        origin: 'visitor'

      window.onbeforeunload = ->
        if not vm.isUpdateAttachement and vm.issue.attachments
          for value in vm.issue.attachments
            restService.del config.resources.removeAttachment, _getQiniuKey(value.url), (data) ->
        return

      _initParams = (params) ->
        if not params?.accountId
          vm.isDisabled = true
          notificationService.error 'helpdesk_feedback_required_accountId'
          return

        language = params.language or 'zh_cn'

        $translate.use language

        vm.issue.accountId = params.accountId

        vm.fields = params?.fields
        vm.issue.email = _getEmail vm.fields

        if params?.origin
          vm.issue.origin = params.origin

      _getEmail = (fields) ->
        return if not fields
        for index, item of fields
          return item.value if item.type is 'email'
        return null

      _init = ->
        params = $location.search().params

        if params
          params = $.parseJSON decodeURI(params)
          _initParams params

      _init()

      vm.addAttachments = (attachmentInfos) ->
        _formatImgType attachmentInfos
        for attachmentInfo in attachmentInfos
          vm.issue.attachments.push attachmentInfo

      vm.cancelAttachment = (index, event) ->
        qiniuKey = _getQiniuKey(vm.issue.attachments[index].url)[0]
        restService.del config.resources.removeAttachment, {qiniu: qiniuKey}, (data) ->
          vm.issue.attachments.splice index, 1

      vm.submit = ->
        if vm.validateEmail() or vm.validateTel()
          return

        issue = []
        for field in vm.fields
          if field.type isnt 'email'
            value = $("##{field.name}").val()
            issue[field.name] = value

        $.extend(vm.issue, issue)

        restService.post config.resources.createIssueFromJSSDK, vm.issue, (data) ->
          vm.isUpdateAttachement = true
          $state.go 'feedbacksuccess'

      vm.validateTel = ->
        validateService.checkTelNum vm.issue.phone

      vm.validateEmail = ->
        emailPattern = /^[a-zA-Z0-9_-]+@[a-zA-Z0-9_-]+(\.[a-zA-Z0-9_-]+)+$/
        if not emailPattern.test vm.issue.email
          return 'feedback_customer_email_type_error'
        return ''

      _getType = (url) ->
        sliceStartIndex = url.lastIndexOf('.') + 1
        url.slice sliceStartIndex, url.length

      _getQiniuKey = (url) ->
        splitUrl = url.split '/'
        param = splitUrl.slice(splitUrl.length - 1, splitUrl.length)
        param

      _formatImgType = (attachmentInfos) ->
        for attachmentInfo in attachmentInfos
          attachmentInfo.type = _getType attachmentInfo.url
          attachmentInfo.format = 'Img'
  ]
