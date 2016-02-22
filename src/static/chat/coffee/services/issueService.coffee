define [
  'core/coreModule'
  'chat/config'
], (mod, config) ->
  mod.factory 'issueService', [
    '$q'
    '$location'
    'restService'
    'localStorageService'
    '$state'
    '$timeout'
    'ISSUE_STATUSES'
    ($q, $location, restService, localStorageService, $state, $timeout, ISSUE_STATUSES) ->

      issue = {}

      issue.log = ->
        console.log.apply console, arguments if console

      issue.init = (helpdesk, connectionHandler) ->
        self = this
        self.firstLoad = true
        options =
          authEndpoint: config.chat.url.checkAuth
          authData: 'h:' + helpdesk.id

        @engine = new Engine config.issue.appId, options
        @channelName = config.issue.channelName + helpdesk.accountId
        @channel = @engine.channels.subscribe(@channelName)
        @channel.bind config.issue.event.subscribeSuccess, (users) ->
          self.defered.resolve users if self.defered
          self.log "#{helpdesk.name} subscribed channel " + self.channelName

        @channel.bind config.issue.event.subscribeFail, (err) ->
          self.log "failed to subscribe channel " + self.channelName, err

        @engine.connection.bind config.issue.event.connectionStatusChanged, (state) ->
          if state.current is 'connected'
            issue.socketId = @engine.connection.socketId
            self.log "reload data since network reconnected.", state
            connectionHandler() unless self.firstLoad
            self.firstLoad = false
        return

      issue.bind = (eventName, callback) ->
        self = this
        if @channel and eventName
          @channel.bind eventName, callback if typeof callback is 'function'
        else
          self = this
          @defered = $q.defer()
          @defered.promise.then(->
            self.channel.bind eventName, callback if typeof callback is 'function'
          )
        self.log "binded event #{eventName} on #{self.channelName}"

      issue.destory = (helpdesk) ->
        if @engine
          if helpdesk and helpdesk.accountId
            @engine.channels.unsubscribe(@channelName)
            @log "#{helpdesk.id} unsubscribed channel #{@channelName}"

      issue.safeApply = (scope, fn) ->
        phase = if scope.$root then scope.$root.$$phase else ''
        if phase is '$apply' or phase is '$digest'
          fn() if fn and ( typeof fn is 'function')
        else
          scope.$apply(fn)

      issue.repaintPageAfterIssueAdded = (scope, issue) ->
        scope.$broadcast 'issue_added', issue

      issue.repaintPageAfterHasNewComment = (scope, newActivity) ->
        self = this
        if self.issueDetailData and self.issueDetailData.id is newActivity.issueId
          self.safeApply scope, ->
            self.issueDetailData.activities.push newActivity
            self.issueDetailData.currentLatestActivity = newActivity
            self.scrollToBottom()

      issue.repaintPageAfterIssueStatusChanged = (scope, issue) ->
        self = this
        scope.$broadcast 'issue_status_changed', issue
        # updates issue detail UI when issue detail page is open

        if self.issueDetailData and self.issueDetailData.id is issue.id
          self.safeApply scope, ->
            if issue.isDeleted
              self.closeIssueDetailPage()
            else
              self.issueDetailData.status = issue.status
              self.issueDetailData.assignee = issue.assignee
              self.issueDetailData.currentLatestActivity = issue.newActivity
              self.issueDetailData.currentIssueStatus = self.getCurrentIssueStatus issue.status
              self.issueDetailData.activities.push issue.newActivity
              self.scrollToBottom()
        return

      issue.closeIssueDetailPage = ->
        $('.help-issue-detail').removeClass('in').addClass 'out'
        self = this
        self.removeSelectedLink()
        $('.issue-mask-confirm:first').remove()
        $timeout ->
          $state.go 'issue'
        , 500

      issue.removeSelectedLink = ->
        $('.issue-selected-link').removeClass 'issue-selected-link'

      issue.scrollToBottom = ->
        $timeout( ->
          scrollHeight = $('.scroll-content').height()
          $('.detail-content').scrollTop(scrollHeight)
        , 200)

      issue.getCurrentIssueStatus = (status) ->
        for issueStatus in ISSUE_STATUSES
          return issueStatus if issueStatus.status is status

      issue.clearIssueDetailData = ->
        delete this.issueDetailData

      issue
    ]
