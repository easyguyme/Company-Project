define [
  'core/coreModule'
  'chat/config'
], (app, config) ->
  app.controller 'wm.ctrl.helpdesk.tabs.issue', [
    '$scope'
    'restService'
    '$filter'
    'sessionService'
    ($scope, restService, $filter, sessionService) ->
      vm = $scope

      status =
        OPEN: 'open'
        ASSIGNED: 'assigned'
        RESOLVED: 'resolved'
        CLOSED: 'closed'

      _init = ->
        vm.currentPage = 1
        vm.totalItems = 0
        vm.pageSize = 10

        vm.list =
          columnDefs: [
            field: 'createdAt'
            label: 'time'
            cellClass: 'issues-cells issues-time-cell'
            headClass: 'issues-headers'
          ,
            field: 'title'
            label: 'title'
            type: 'link'
            headClass: 'issues-headers'
            cellClass: 'issues-cells text-el'
          ,
            field: 'status'
            label: 'status'
            type: 'textColor'
            headClass: 'issues-headers'
            cellClass: 'issues-cells'
          ]
          data: []
          emptyMessage: 'helpdesk_no_issue_records'

        _getIssues()

      _getIssues = ->
        params =
          'page': vm.currentPage
          'per-page': vm.pageSize

        restService.get config.resources.issues, params, (data) ->
          _getIssuesHandler data.issues

          vm.totalItems = data.totalCount
          vm.pageCount = data.pageCount

      _getIssuesHandler = (items) ->
        issues = []
        for item in items
          issue =
            createdAt: $filter('date')(item.createdAt, 'yyyy-MM-dd')
            title:
              text: item.title
              link: '/chat/issue/' + item.id
              target: '_blank'
          issueStatus = {}

          switch item.status
            when status.OPEN
              issueStatus =
                text: 'issue_open_status'
                color: 'issue-status-open'
            when status.ASSIGNED
              issueStatus =
                text: 'issue_assigned_status'
                color: 'issue-status-assigned'
            when status.RESOLVED
              issueStatus =
                text: 'issue_resolved_status'
                color: 'issue-status-resolved'
            when status.CLOSED
              issueStatus =
                text: 'issue_closed_status'
                color: 'issue-status-closed'

          issue.status = issueStatus

          issues.push issue

        vm.list.data = angular.copy issues

      vm.changeSize = (pageSize) ->
        vm.pageSize = pageSize
        vm.currentPage = 1
        _getIssues()

      vm.changePage = (currentPage) ->
        vm.currentPage = currentPage
        _getIssues()

      vm.createIssue = ->
        if sessionService.getCurrentUser()
          window.open '/chat/issue/add'
          return
        else
          $location.path config.paths.logout

      _init()

      vm
  ]
