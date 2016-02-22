define [
  'core/coreModule'
  'chat/config'
  'jquery'
], (mod, config, $) ->
  mod.directive 'chatIssueInfiniteDrop', [
    'restService'
    'debounceService'
    '$timeout'
    '$state'
    'issueService'
    '$interval'
    (restService, debounceService, $timeout, $state, issueService, $interval) ->
      return (
        restrict: 'E'
        scope:
          issueStatus: '@'
        replace: true
        templateUrl: '/build/chat/coffee/directives/chatIssueInfiniteDrop.html'
        link: (scope, elem, attrs) ->
          scope.issues = []
          scope.currentPage = 0
          scope.pageCount = 1
          scope.openStatus = 'open'
          perPage = 100

          _init = ->
            _getIssueList()

          _getIssueList = ->

            if scope.currentPage <= scope.pageCount or scope.pageCount is 0
              condition =
                'per-page': perPage
                'page': parseInt(scope.currentPage) + 1
                'status': scope.issueStatus
              restService.get config.resources.issues, condition, (data) ->
                scope.pageCount = data.pageCount
                scope.currentPage = data.currentPage
                scope.issues = scope.issues.concat data.issues
                scope.totalCount = data.totalCount
                _updateTotalCount()
                _watchDOM()

                if scope.totalCount <= perPage or scope.issues.length is scope.totalCount
                  _hideMaskLoading()
                return
            else
              _hideMaskLoading()
            return

          $(elem).find('.issue-body').scroll debounceService.callback( ->
            bodyHeight = $(elem).find('.issue-body').height()
            listHeight = $(elem).find('.issue-list-scroll').outerHeight()
            scrollHeight = $(elem).find('.issue-body').scrollTop()
            result = listHeight - bodyHeight - scrollHeight

            if result <= 2
              $timeout( ->
                _getIssueList()
              , 400)

          , 200)

          if scope.issueStatus is scope.openStatus
            $(window).resize( ->
              _changeAddBtnPosition()
            )

          scope.$on 'refresh-issues-list', ->
            scope.currentPage = 0
            scope.issues = []
            _getIssueList()

          scope.$on 'issue_added', (event, issue) ->
            _watchDOM()
            _updateIssue issue

          scope.$on 'issue_status_changed', (event, issue) ->
            _watchDOM()
            _updateIssue issue unless issue.isDeleted
            _removePrevious(issue)

          scope.goIssueDetail = ($event, id) ->
            issueService.removeSelectedLink()
            $($event.target).addClass 'issue-selected-link'
            $state.go 'issue.detail', {id: id}

          scope.goAddIssue = ($event) ->
            $($event.target).css 'text-decoration', 'none'
            issueService.removeSelectedLink()
            $state.go 'issue.add'

          safeApply = (scope, fn) ->
            phase = if scope.$root then scope.$root.$$phase else ''
            if phase is '$apply' or phase is '$digest'
              fn() if fn and ( typeof fn is 'function')
            else
              scope.$apply(fn)

          _updateIssue = (issue) ->
            safeApply scope, ->
              if scope.issueStatus is issue.status
                scope.issues = new Array(issue).concat scope.issues
                scope.totalCount++
                _updateTotalCount()

          _removePrevious = (newIssue) ->
            safeApply scope, ->
              if scope.issueStatus is newIssue.previousStatus
                for issue, index in scope.issues
                  if issue.id is newIssue.id
                    scope.issues.splice index, 1
                    break
                scope.totalCount--
                _updateTotalCount()

          _hideMaskLoading = ->
            $(elem).find('.issue-mask-loading').css 'display', 'none'

          _updateTotalCount = ->
            $(elem).parent().find('.issues-count').html scope.totalCount

          _changeAddBtnPosition = ->
            scrollHeight = $(elem).find('.issue-list-scroll').outerHeight()
            infiniteDropHeight = $(elem).height()
            # the height of botton 'add-issue' is 43px
            if infiniteDropHeight - scrollHeight - 43 < 0
              $(elem).find('.issue-body').addClass 'issue-body-open'
            else
              $(elem).find('.issue-body').removeClass 'issue-body-open'
              $(elem).find('.issue-body').css 'height', 'auto'

          _watchDOM = ->
           if scope.issueStatus is scope.openStatus
            oldScrollHeight = $(elem).find('.issue-list-scroll').outerHeight()
            domTime = $interval ->
              newScrollHeight = $(elem).find('.issue-list-scroll').outerHeight()
              if oldScrollHeight isnt newScrollHeight
                _changeAddBtnPosition()
                $interval.cancel(domTime)
            , 300

          _init()
      )
  ]
