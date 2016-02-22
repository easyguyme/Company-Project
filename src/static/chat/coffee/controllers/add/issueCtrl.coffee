define [
  'core/coreModule'
  'chat/config'
], (app, config) ->
  app.controller 'wm.ctrl.issue.add', [
    '$rootScope'
    '$scope'
    'restService'
    '$location'
    'notificationService'
    '$state'
    'issueService'
    'sessionService'
    '$window'
    ($rootScope, $scope, restService, $location, notificationService, $state, issueService, sessionService, $window) ->
      rvm = $rootScope
      vm = $scope
      rvm.isHelpdesk = true
      vm.isShowAttachment = false
      vm.isUploadAttachment = false
      vm.isAddedIssue = false

      _init = ->
        vm.removeFirstMask()
        if sessionService.getCurrentUser()
          vm.issue =
            title: ''
            description: ''
            attachments: []

          $body = $(document.body)
          $confirmMask = $ '<div class="issue-mask-confirm"></div>'
          $body.append($confirmMask)
          $confirmMask.click (event) ->
            _checkUploadAttachment()
            event.preventDefault()
            $('.issue-mask-confirm').remove()
            vm.changeStateToIssue()
          $confirmMask.show()

          vm.config =
            toolbars: [
              ['fontfamily', 'fontsize', '|', 'blockquote', 'horizontal', '|', 'link', 'unlink', '|', 'insertimage', '|'],
              ['bold', 'italic', 'underline', 'forecolor', 'backcolor', '|',
               'justifyleft', 'justifycenter', 'justifyright', 'justifyjustify', '|',
               'rowspacingtop', 'rowspacingbottom', 'lineheight', '|',
               'insertorderedlist', 'insertunorderedlist', '|',
               'imagenone', 'imageleft', 'imageright', 'imagecenter']
            ]

            initialStyle: 'ol, ul{width:initial!important}'
            initialFrameHeight: 250
            scaleEnabled: true
        else
          $location.path config.paths.logout

      vm.addIssue = ->
        if vm.issue.title and vm.issue.description
          issueToAdd = angular.copy vm.issue
          issueToAdd.socketId = issueService.socketId
          issueToAdd.origin = 'helpDesk'

          for attachment in issueToAdd.attachments
            delete attachment.thumbnailName

          restService.post config.resources.issues, issueToAdd, (data) ->
            issueService.repaintPageAfterIssueAdded $rootScope, data
            # notificationService will not show message when message equal $rootScope.lastMessage
            $rootScope.lastMessage = ''
            notificationService.success 'issue_create_successfully', false
            vm.isAddedIssue = true
            # cannot input anything after add issue in browser IE
            if isIE()
              $window.location.href = config.paths.issue
              vm.removeFirstMask()
            else
              vm.changeStateToIssue()
        return

      isIE = ->
        explorer = window.navigator.userAgent
        if explorer.indexOf("Trident/7.0") >= 0 or explorer.indexOf("MSIE") >= 0
          return true
        return false

      vm.closeAddPage = ->
        _checkUploadAttachment()
        vm.changeStateToIssue()

      vm.addAttachments = (attachmentInfos) ->
        vm.initThumbnailName attachmentInfos
        for attachmentInfo in attachmentInfos
          vm.issue.attachments.push attachmentInfo
        vm.isUploadAttachment = true

      vm.cancelAttachment = (index) ->
        url = vm.issue.attachments[index].url
        qiniuKey = _getQiniuKey(url)[0]
        params =
          qiniu: qiniuKey
        restService.del config.resources.removeAttachment, params, (data) ->
          vm.issue.attachments.splice(index, 1)
          $scope.$broadcast 'updateTooltip'

      _checkUploadAttachment = ->
        if vm.isUploadAttachment and not vm.isAddedIssue
          for value in vm.issue.attachments
            qiniuKey = _getQiniuKey(value.url)[0]
            params =
              qiniu: qiniuKey
            restService.del config.resources.removeAttachment, params, (data) ->
          vm.issue.attachments = []

      _getQiniuKey = (url) ->
        urlSplit = url.split('/')
        param = urlSplit.slice(urlSplit.length - 1, urlSplit.length)
        return param

      _init()
  ]
