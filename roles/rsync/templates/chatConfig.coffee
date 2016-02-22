define ->
  config =
    keys:
      currentUser: 'chat-currentUser'
      accessToken: 'chat-accessToken'
      loginEmail: 'chat-loginEmail'
      loginPassword: 'chat-loginPassword'
      offlineMessages: 'chat-offlineMessages'
      availableCustomers: 'chat-availableCustomers'
    paths:
      login: '/chat/login'
      logout: '/chat/logout'
      helpdesk: '/chat/helpdesk'
      issue: '/chat/issue'
    resources:
      login: '/api/chat/site/login'
      logout: '/api/chat/site/logout'
      helpdesk: '/api/chat/help-desk'
      helpdesks: '/api/chat/help-desks'
      issues: '/api/chat/issues'
      issue: '/api/chat/issue'
      createIssueFromJSSDK: '/api/chat/issue/create-from-js-sdk'
      removeAttachment: '/api/chat/issue/remove-attachment'
      comment: '/api/chat/issue/comment'
      updatepwd: '/api/chat/help-desk/updatepassword'
      resetPasswordEmail: '/api/chat/site/send-reset-password-email'
      resetPassword: '/api/chat/site/reset-password'
      graphicList: '/api/chat/graphics'
      faqCategory: '/api/chat/faq/get-category-list'
      faqs: '/api/chat/faqs'
      viewGraphic: '/api/content/graphic'
    issue:
      channelName: 'presence-wm-issue'
      appId: '{{ tuisongbao.app_id }}'
      event:
        subscribeSuccess: 'engine:subscription_succeeded'
        subscribeFail: 'engine:subscription_error'
        newIssue: 'new_issue'
        issueStatusChanged: 'issue_status_changed'
        commentIssue: 'comment_issue'
        connectionStatusChanged: 'state_changed'
    chat:
      jsBasePath: '/vendor/bower/tuisongbao/'
      systemName: 'system_reply'
      defaultAvatar: '/images/management/image_hover_default_avatar.png'
      appId: '{{ tuisongbao.app_id }}'
      globalChannelName: 'presence-wm-global'
      sourceName: 'website'
      minute: 60000
      action:
        join: 'join'
        transfer: 'transfer'
        transferOut: 'transferOut'
        leave: 'leave'
      url:
        clientOnline: '/api/chat/client/online'
        systemSetting: '/api/chat/settings'
        selfHelpdeskSetting: '/api/chat/setting/self-helpdesk'
        transfer: '/api/chat/conversation/transfer'
        checkAuth: '/api/chat/help-desk/check-auth'
      replyType:
        wait: 'waitting'
        connected: 'success'
        offduty: 'nonworking'
        brake: 'brake'
        close: 'close'
        drop: 'droping'
      notificationType:
        desktopAndMark: 'desktop-mark'
        mark: 'mark'
      selfHelpdeskReplyType:
        back: 'back'
        connect: 'connect'
        reply: 'reply'
