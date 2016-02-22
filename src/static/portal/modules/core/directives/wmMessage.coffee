define [
  'core/coreModule',
  'wm/config'
], (mod, config) ->
  mod.directive 'wmMessage', [
    'restService'
    'messageService'
    '$compile'
    '$sce'
    '$rootScope'
    '$timeout'
    '$filter'
    (restService, messageService, $compile, $sce, $rootScope, $timeout, $filter) ->
      return (
          restrict: 'EA'
          replace: true
          template: '<a href="#" class="message-nav" ng-class="{active:isShowList}" wm-tooltip="{{\'message\'|translate}}" ng-click="toggleList()">
                      <i class="glyphicon-nav glyphicon glyphicon-bell"></i>
                      <span class="badge" ng-show="newMsgCount">{{newMsgCount}}</span>
                    </a>'
          link: (scope, elem, attrs) ->
            $messageListHtml = angular.element(
              '<div class="message-list-content" ng-show="isShowList">
                  <div class="message-list-header">
                    <button type="button" class="close popup-close replay-dialog-close" ng-click="closePopup()"></button>
                    <h4 class="message-list-title">{{isShowUnreadMsg ? "unread" : "read" | translate}}</h4>
                  </div>
                  <div class="message-list-body clearfix">
                    <div class="clearfix" ng-show="totalCount > 0">
                      <span class="message-operation pull-right" ng-click="readHandler()">
                        <span class="mark-icon" ng-class="{\'mark-icon\': isShowUnreadMsg, \'delete-icon\': !isShowUnreadMsg}"></span>
                        <span>{{isShowUnreadMsg ? "mark_all_as_read" : "empty_read_items" | translate}}</span>
                      </span>
                    </div>
                    <ul class="messages">
                      <li class="message" ng-repeat="message in messages">
                        <h5 class="message-title">
                          <span class="message-status-icon {{message.status}}-icon"></span>
                          <span class="message-title-content">{{message.title|translate}}</span>
                        </h5>
                        <p class="message-content" ng-bind-html="message.content" ng-click="checkMessage($event, message)"></p>
                        <span class="message-is-read-mark" ng-class="{\'message-read-icon\': message.isRead, \'message-unread-icon\': !message.isRead}"></span>
                      </li>
                    </ul>
                    <div class="broadcast-pagination" ng-if="pageCount > 0">
                      <div wm-pagination current-page="page" page-size="pageSize" total-items="totalCount" on-change-size="changeSize" on-change-page="changePage"></div>
                    </div>
                  </div>
                  <div class="message-list-footer" ng-click="showReadMessage()" ng-show="isShowUnreadMsg">
                    <span class="message-status-icon view-read-message-icon"></span>
                    <span class="view-read-message-title">{{"click_to_view_read_items" | translate}}</span>
                  </div>
                  <div class="no-unread-message" ng-show="isShowUnreadMsg && totalCount == 0">{{"no_unread_items" | translate}}</div>
              </div>')

            $portalNotification = angular.element(
              '<div class="message message-warning">
                <div class="message-content">
                  <ul class="portal-list">
                    <li ng-repeat="message in portalMessages track by $index">
                      <i class="icon"></i>
                      <span class="text" ng-bind-html="message.content"></span>
                    </li>
                  </ul>
                </div>
              </div>'
            )

            _getCurrentMsgType = ->
              if scope.isShowUnreadMsg then 0 else 1

            _getMessages = (isRead) ->
              params =
                isRead: isRead
                page: scope.currentPage
                'per-page': scope.pageSize
              restService.noLoading().get config.resources.commonMessage, params, (data) ->

                if data
                  # translate
                  scope.messages = []
                  if data.items.length > 0
                    for item in data.items
                      content = ''
                      contentStr = item.content.split(" ")
                      for str in contentStr
                        if str.indexOf('_') > 0
                          content += $filter('translate')(str)
                          content += ' '
                        else
                          content += str
                          content += ' '
                      delete item.content
                      item.content = content
                      scope.messages.push item

                  scope.pageCount = data._meta.pageCount
                  scope.totalCount = data._meta.totalCount
                  scope.newMsgCount = data._meta.totalCount if isRead is 0
                  return

            _getPortalMessages = ->
              restService.noLoading().get config.resources.portalMessage, (data) ->
                if data and data.length > 0 and $rootScope.isLogined
                  $('.portal-message').find('.message').show()
                  scope.portalMessages = data
                  $timeout( ->
                    navHeight = $('.navbar').height()
                    $('.main-content-view').css('margin-top', navHeight + 'px')
                  ,50)

            _getUnreadMessages = ->
              _getMessages(0)

            _getReadMessages = ->
              _getMessages(1)

            scope.toggleList = ->
              scope.isShowList = not scope.isShowList

              $body = $(document.body)
              $confirmMask = $ '<div class="mask-confirm"></div>'
              $body.append($confirmMask)
              $confirmMask.click (event) ->
                event.preventDefault()
                $('.mask-confirm').remove()
                scope.$apply ->
                  scope.isShowList = false
                return
              $confirmMask.show()

              if $(document).scrollTop() < $(document).height() - $(window).height()
                $('.message-list-footer').css({"bottom": "0px"})
              else
                $('.message-list-footer').css({"bottom": "40px"})

              if scope.isShowList
                _getUnreadMessages()
                scope.isShowUnreadMsg = true

            _init = ->
              #if element '.message-list-content' don't exsit, so append
              if $('.message-list-content').length is 0
                angular.element('.viewport').append $compile($messageListHtml)(scope)
              $messageListHtml.find('.message-list-content').css('min-height', $(window).height() - 52)
              #if element '.portal-message' hasn't appended children, so append
              if $('.portal-message').children().length is 0
                angular.element('.portal-message').append $compile($portalNotification)(scope)

              scope.pageSize = 10
              scope.currentPage = 1

              scope.isShowList = false
              scope.isShowUnreadMsg = true
              scope.newMsgCount = 0

              _getMessages(_getCurrentMsgType())
              _getPortalMessages()

              messageService.bind config.push.event.newMessage, (data) ->
                if data.to.target is 'account' or (data.to.target is 'user' and $rootScope.user.id is data.to.id)
                  scope.$apply ->
                    if scope.isShowUnreadMsg and scope.isShowList
                      _getMessages(0)
                    else
                      scope.newMsgCount++

            scope.showReadMessage = ->
              scope.isShowUnreadMsg = false
              _getReadMessages()

            scope.checkMessage = (event, message) ->
              if $(event.toElement).prop('tagName').toLowerCase() is 'a'
                scope.closePopup()
                if scope.isShowUnreadMsg
                  params =
                    isRead: 1
                  restService.put config.resources.updateMessageOne + '/' + message.id, params, (data) ->
                    if data
                      if data.status is "ok"
                        _getUnreadMessages()
                        scope.newMsgCount--
                    return

            scope.readHandler = ->
              if scope.totalCount
                params =
                  isRead: _getCurrentMsgType()
                restService.put config.resources.updateMessage, params, (data) ->
                  _getMessages(params.isRead)

            scope.closePopup = ->
              scope.isShowList = false
              $($('.mask-confirm')[0]).remove()
              return

            # Determine whether the scroll bar is located at the bottom of the browser.
            window.onscroll = ->
              if $(document).scrollTop() >= $(document).height() - $(window).height()
                $('.message-list-footer').css({"bottom": "40px"})
              else
                $('.message-list-footer').css({"bottom": "0px"})

            scope.changeSize = (pageSize) ->
              scope.pageSize = pageSize
              scope.currentPage = 1
              isRead = _getCurrentMsgType()
              _getMessages(isRead)

            scope.changePage = (currentPage) ->
              scope.currentPage = currentPage
              isRead = _getCurrentMsgType()
              _getMessages(isRead)

            # Watch for the login status
            $rootScope.$watch 'isLogined', (logined) ->
              _init() if logined
        )
  ]

