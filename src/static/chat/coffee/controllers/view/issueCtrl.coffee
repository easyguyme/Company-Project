define [
  'core/coreModule'
  'chat/config'
], (app, config) ->
  app.controller 'wm.ctrl.issue.detail', [
    '$rootScope'
    '$scope'
    '$stateParams'
    'restService'
    '$timeout'
    '$state'
    'notificationService'
    'issueService'
    'ISSUE_STATUSES'
    'sessionService'
    '$location'
    '$interval'
    'fileDownloadService'
    '$modal'
    ($rootScope, $scope, $stateParams, restService, $timeout, $state, notificationService, issueService, ISSUE_STATUSES, sessionService, $location, $interval, fileDownloadService, $modal) ->
      vm = $scope
      rvm = $rootScope
      rvm.isHelpdesk = true
      rvm.isDetailPage  = true
      vm.comment = ''

      vm.issueStatuses = ISSUE_STATUSES

      _init = ->
        vm.removeFirstMask()
        if sessionService.getCurrentUser()
          # Perform this method after 0.3s to fix bug issue-detail page can not scroll in Chrome browser.
          $timeout ->
            _getIssueDetailById $stateParams.id
          , 300
          $body = $(document.body)
          $confirmMask = $ '<div class="issue-mask-confirm"></div>'
          $body.append($confirmMask)
          $confirmMask.click (event) ->
            event.preventDefault()
            issueService.closeIssueDetailPage()
          $confirmMask.show()
        else
          $location.path config.paths.logout

      vm.sendComment = ->
        issue =
          description: vm.comment
          socketId: issueService.socketId
        restService.post config.resources.comment + '/' + vm.issueDetailData.id, issue, (data) ->
          vm.comment = ''
          issueService.repaintPageAfterHasNewComment vm, data

      vm.updateIssueStatus = ($event) ->
        statusToChange = vm.issueDetailData.currentIssueStatus.nextStatus
        issue =
          status: statusToChange

        if statusToChange is 'resolved'
          notificationService.confirm $event, {
            title: "send_email_after_issue_resolved"
            submitCallback: _updateStatusHandler
            params: [issue]
          }
        else
          _updateStatusHandler(issue)

      _updateStatusHandler = (issue) ->
        issue.socketId = issueService.socketId
        restService.put config.resources.issue + '/' + vm.issueDetailData.id, issue, (data) ->
          issueService.repaintPageAfterIssueStatusChanged $rootScope, data
          # notificationService will not show message when message equal $rootScope.lastMessage
          $rootScope.lastMessage = ''
          notificationService.success vm.issueDetailData.currentIssueStatus.notification
          issueService.closeIssueDetailPage()

      vm.closeIssue = ->
        issue =
          status: 'closed'
          socketId: issueService.socketId
        restService.put config.resources.issue + '/' + vm.issueDetailData.id, issue, (data) ->
          issueService.repaintPageAfterIssueStatusChanged $rootScope, data
          issueService.closeIssueDetailPage()
          notificationService.success 'issue_close_successfully'

      vm.closeDetailPage = ->
        issueService.closeIssueDetailPage()

      vm.deleteIssue = ($event) ->
        if $event
          notificationService.confirm $event, {
              title: 'issue_delete_confirm'
              submitCallback: _deleteIssueHandler
            }
        else
          _deleteIssueHandler()


      _deleteIssueHandler = ->
        data =
          socketId: issueService.socketId
        restService.del config.resources.issue + '/' + vm.issueDetailData.id, data, (data) ->
          issueService.repaintPageAfterIssueStatusChanged $rootScope, data
          issueService.closeIssueDetailPage()
          # notificationService will not show message when message equal $rootScope.lastMessage
          $rootScope.lastMessage = ''
          notificationService.success 'issue_delete_successfully'

      vm.downloadAll = ->
        if sessionService.getCurrentUser()
          downloadUrls = []
          for resource in vm.issueDetailData.attachments
            url = resource.url + '?attname=' + resource.name + '.' + resource.type
            downloadUrls.push url
          fileDownloadService.multiDownload downloadUrls, 'server'
        else
          _logout()

      _logout = ->
        vm.isShowAttachment = false
        vm.removeFirstMask()
        $location.path config.paths.logout

      vm.hideWrapper = ->
        vm.isShowAttachment = false

      vm.creatorDetail = (creator) ->
        if not creator
          notificationService.error 'issue_creator_not_found'
          return

        _openModal creator

      _openModal = (creator) ->
        modalInstance = $modal.open
          templateUrl: 'creatorData.html'
          controller: 'wm.ctrl.issue.creator.detail'
          windowClass: 'user-dialog'
          resolve:
            modalData: ->
              creator
        .result.then (data) ->
          issueService.log data

      _getIssueDetailById = (id) ->
        restService.get config.resources.issue + '/' + id, {}, (data) ->
          vm.initThumbnailName data.attachments
          vm.issueDetailData = data
          issueService.issueDetailData = vm.issueDetailData
          vm.issueDetailData.currentLatestActivity = vm.issueDetailData.activities.slice(-1)[0]
          vm.issueDetailData.currentIssueStatus = issueService.getCurrentIssueStatus vm.issueDetailData.status
          $('.description').html vm.issueDetailData.description
          $('.abbreviative-description').html _strip_tags(vm.issueDetailData.description)
          _dotdotdot()
        return

      _strip_tags = (input, allowed) ->
        input = input.replace(/\s*&nbsp;\s*/gi, '')
          .replace(/\s(?=\s)/gi, '')
          .replace(/[\n\r\t]/gi, '')

        allowed = (((allowed or '') + '')
          .toLowerCase()
          .match(/<[a-z][a-z0-9]*>/g) or [])
          .join('')
        tags = /<\/?([a-z][a-z0-9]*)\b[^>]*>/gi
        commentsAndPhpTags = /<!--[\s\S]*?-->|<\?(?:php)?[\s\S]*?\?>/gi
        input.replace(commentsAndPhpTags, '')
          .replace(tags, ($0, $1) ->
            isIndexOfAllowed = allowed.indexOf('<' + $1.toLowerCase() + '>') > -1
            if isIndexOfAllowed is true then return $0 else return ''
          )

      _dotdotdot = ->
        oldDomHeight = $('.abbreviative-description').height()
        time = $interval ->
          newDomHeight = $('.abbreviative-description').height()
          if oldDomHeight isnt newDomHeight
            $('.abbreviative-description').dotdotdot()
            $interval.cancel(time)
        , 100

      _init()
  ]

  .controller 'wm.ctrl.issue.creator.detail', [
    'restService'
    '$scope'
    '$modalInstance'
    'modalData'
    (restService, $scope, $modalInstance, modalData) ->
      vm = $scope

      vm.closeDialog = ->
        $modalInstance.dismiss 'cancel'

      _initHelpdeskData = ->
        vm.content =
          title: 'channel_menu_helpdesk'
          origin: 'helpDesk'
          avatar: modalData.avatar
          details: [
              name: 'issue_creator_detail_account'
              value: modalData.email
            ,
              name: 'issue_creator_detail_number'
              value: modalData.badge
            ,
              name: 'issue_creator_detail_nickname'
              value: modalData.name
          ]

      _initUserData = ->
        vm.content =
          title: 'channel_menu_user'
          origin: 'user'
          details: [
              name: 'issue_creator_detail_name'
              value: modalData.name
            ,
              name: 'issue_creator_detail_email'
              value: modalData.email
            ,
              name: 'issue_creator_detail_phone'
              value: modalData.phone
          ]

      _init = ->
        if modalData?.origin then _initUserData() else _initHelpdeskData()

      _init()
  ]
