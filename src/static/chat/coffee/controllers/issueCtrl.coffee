define [
  'core/coreModule'
  'chat/config'
], (app, config) ->
  app.constant 'ISSUE_STATUSES', [
    index: 0
    status: 'open'
    nextStatus: 'assigned'
    title: 'issue_open_status'
    notification: ''
    btn: 'action_issue_claim'
  ,
    index: 1
    status: 'assigned'
    nextStatus: 'resolved'
    title: 'issue_assigned_status'
    notification: 'issue_claim_successfully'
    btn: 'action_issue_complete'
  ,
    index: 2
    status: 'resolved'
    nextStatus: 'closed'
    title: 'issue_resolved_status'
    notification: 'issue_resolve_successfully'
    btn: 'action_issue_close'
  ,
    index: 3
    status: 'closed'
    title: 'issue_closed_status'
    notification: 'issue_close_successfully'
    btn: 'action_issue_delete'
  ]

  app.controller 'wm.ctrl.issue', [
    '$scope'
    '$rootScope'
    '$timeout'
    'restService'
    'issueService'
    '$location'
    '$state'
    '$stateParams'
    'ISSUE_STATUSES'
    'sessionService'
    'fileDownloadService'
    ($scope, $rootScope, $timeout, restService, issueService, $location, $state, $stateParams, ISSUE_STATUSES, sessionService, fileDownloadService) ->
      vm = $scope
      rvm = $rootScope

      rvm.isHelpdesk = true
      rvm.isIssuePage = true
      rvm.isHelpdeskPage = false
      rvm.isDetailPage = false

      vm.issueStatuses = ISSUE_STATUSES

      vm.previewAttachment = (attachment) ->
        vm.isShowAttachment = true
        vm.showAttachment = attachment

      vm.showInWindow = (picUrl) ->
        window.open(picUrl)
        return

      _logout = ->
        vm.isShowAttachment = false
        vm.removeFirstMask()
        $location.path config.paths.logout

      vm.hideWrapper = ->
        vm.isShowAttachment = false

      vm.downloadAttachment = (resource) ->
        url = resource.url + '?attname=' + resource.name + '.' + resource.type
        downloadUrl = [url]
        fileDownloadService.multiDownload downloadUrl, 'server'

      vm.initThumbnailName = (attachments) ->
        for value in attachments
          if value.format is 'Img'
            value.thumbnailName = 'Img'
          else if value.format is 'rar'
            value.thumbnailName = 'rar'
          else if value.format is 'doc'
            value.thumbnailName = 'Word'
          else if value.format is 'excel'
            value.thumbnailName = 'Excel'
          else if value.format is 'psd'
            value.thumbnailName = 'Psd'
          else if value.format is 'others'
            typeName = value.type
            if typeName.length > 5
              value.thumbnailName = typeName.substr(0, 3) + '..'
            else
              value.thumbnailName = typeName

      vm.removeFirstMask = ->
        $('.issue-mask-confirm:first').remove()

      vm.changeStateToIssue = ->
        $('.add-issue-page').removeClass('in').addClass 'out'
        vm.removeFirstMask()
        $timeout ->
          $state.go 'issue'
        , 500

      reloadIssueHandler = ->
        $scope.$broadcast 'refresh-issues-list'

        if issueService.issueDetailData
          issueDetailData = issueService.issueDetailData
          restService.get config.resources.issue + '/' + issueDetailData.id, {}, (issue) ->
            issueService.safeApply $scope, ->
              if issue.isDeleted
                issueService.closeIssueDetailPage()
              else
                issueDetailData.status = issue.status
                issueDetailData.assignee = issue.assignee
                for activity, index in issue.activities
                  if activity.createdAt.sec > issueDetailData.currentLatestActivity.createdAt.sec
                    Array.prototype.push.apply(issueDetailData.activities, issue.activities.slice index)
                    break
                issueDetailData.currentLatestActivity = issueDetailData.activities.slice(-1)[0]
                issueDetailData.currentIssueStatus = issueService.getCurrentIssueStatus issueDetailData.status
                issueService.scrollToBottom()
        return

      _init = ->
        if rvm.isLogined and sessionService.getCurrentUser()
          issueService.init(rvm.user, reloadIssueHandler)

          # bind event on issue, which will be triggered by tuisongbao.
          issueService.bind config.issue.event.newIssue, (issue) ->
            issueService.repaintPageAfterIssueAdded $scope, issue
            issueService.log 'reload data since an issue has been added', issue

          issueService.bind config.issue.event.issueStatusChanged, (issue) ->
            issueService.repaintPageAfterIssueStatusChanged $scope, issue
            issueService.log 'reload data since an issue has been deleted or its status has been changed', issue

          issueService.bind config.issue.event.commentIssue, (newActivity) ->
            issueService.repaintPageAfterHasNewComment $scope, newActivity
            issueService.log 'reload data since there has a new comment', newActivity

        else
          $location.path config.paths.login

      _init()

      vm
  ]
