corePath = '../../../build/modules/core/'
define [
  corePath + 'services/restService'
  corePath + 'services/validateService'
  corePath + 'services/authService'
  corePath + 'services/uploadService'
  corePath + 'services/localStorageService'
  corePath + 'services/localSessionService'
  corePath + 'services/notificationService'
  corePath + 'services/heightService'
  #corePath + 'services/exceptionService'
  corePath + 'services/debounceService'
  corePath + 'services/userService'
  corePath + 'services/judgeDeviceService'
  corePath + 'directives/wmCenterImage'
  corePath + 'directives/wmFormValidation'
  corePath + 'directives/wmFileUpload'
  corePath + 'directives/wmFooter'
  corePath + 'directives/wmCheck'
  corePath + 'directives/wmVerticalNav'
  corePath + 'directives/wmTopNav'
  corePath + 'directives/wmTooltip'
  corePath + 'directives/wmWechatGraphic'
  corePath + 'directives/wmWechatMessage'
  corePath + 'directives/wmSelect'
  corePath + 'directives/wmSearch'
  corePath + 'directives/wmEnter'
  corePath + 'directives/wmTabs'
  corePath + 'directives/wmBreadcrumb'
  corePath + 'directives/wmPagination'
  corePath + 'directives/wmTable'
  corePath + 'directives/wmDatetimePicker'
  corePath + 'directives/wmContentEditable'
  corePath + 'filter/qiniuFilter'
  corePath + 'filter/stringFilter'
  corePath + 'controllers/userCtrl'
  corePath + 'controllers/graphicCtrl'
  './services/chatService'
  './services/issueService'
  './services/sessionService'
  './services/fileDownloadService'
  './controllers/loginCtrl'
  './controllers/logoutCtrl'
  './controllers/userCtrl'
  './controllers/resetpasswordCtrl'
  './controllers/helpdeskCtrl'
  './controllers/clientCtrl'
  './controllers/issueCtrl'
  './controllers/add/issueCtrl'
  './controllers/view/issueCtrl'
  './controllers/helpdesk/infoCtrl'
  './controllers/helpdesk/issueCtrl'
  './controllers/helpdesk/messageCtrl'
  './controllers/helpdesk/wikiCtrl'
  './controllers/baseCtrl'
  './directives/issueAttachmentUpload'
  './directives/chatIssueInfiniteDrop'
  './controllers/feedbackCtrl'
  './controllers/wechatcphelpdeskCtrl'
], ->
