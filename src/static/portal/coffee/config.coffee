define [
  'jqueryBundle'
], ->
  # Global configuration
  mainConfig =
    debug: false
    online: true
    role:
      admin: 'admin'
      guest: 'guest'
      operator: 'operator'
    keys:
      currentUser: 'currentUser'
      accessToken: 'accessToken'
      loginEmail: 'loginEmail'
      loginPassword: 'loginPassword'
      exportJobs: 'exportJobs'
    loginPath: '/site/login'
    chatLoginPath: '/chat/login'
    adminLoginPath: '/admin/login'
    forbiddenPage: '/site/forbidden'
    missingPage: '/site/missing'
    noAccount: '/channel/noaccount'
    modulePath: '/build/modules'
    defaultLang: 'zh_cn'
    defaultAvatar: '/images/management/image_hover_default_avatar.png'
    cptUrl: '/webapp/widget?n='
    push:
      appId: '551ba61ce944fbf0571b19f4'
      channel: 'presence-message-wm-global'
      event:
        subscribeSuccess: 'engine:subscription_succeeded'
        subscribeFail: 'engine:subscription_error'
        newMessage: 'new_message'
        onlineStatusChange: 'online_status_change'
    modules: {}
    introduction: {}
    resources:
      graphicList: '/api/content/graphics'
      commonUser: '/api/common/user'
      imageDownload: '/api/image/download'
      moduleConfig: '/api/common/module/config'
      pushAuth: '/api/tuisongbao/auth'
      commonMessage: '/api/common/message'
      portalMessage: '/api/common/message/portal-message'
      updateMessage: '/api/common/message/update'
      jobsStatus: '/api/common/job/status'
      klpJobsStatus: '/api/common/job/klp-status'
      updateMessageOne: '/api/common/message/update-one'
    states: []
    helpdeskPullingTime: 10000
    telRegs: ['^0?1[0-9]{10}$', '^09[0-9]{8}$', '^\\d{8}$', '^853[0-9]{8}$']

  # Merge all the states
  for mod, modConfig of mainConfig.modules
    mainConfig.states.push(state) for state in modConfig.states
  # Merge all resourece paths
  if mainConfig.online
    for mod, modConfig of mainConfig.modules
      $.extend true, mainConfig.resources, modConfig.resources
  else
    # Support resource mapping to the json files in module
    for mod, modConfig of mainConfig.modules
      mainConfig.resources[reource] = '/build/modules/' + mod + '/json/' + reource + '.json' for reource of modConfig.resources

  mainConfig
