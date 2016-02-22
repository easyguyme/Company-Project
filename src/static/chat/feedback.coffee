###*
 * @description
 *
 * This is Helpdesk Feedback JsSDK.
 *
 * @param {Object} options The configurations for Feedback;
 *    options = {
 *      host: 'wm.com', // optional, Helpdesk Feedback JsSdk's domain, default: www.quncrm.com
 *      height: 450, // optional, The height of the window, default 450px
 *      width: 400, // optional, The width of the window, default 400px
 *      left: 400, // optional, The left position of the window. Negative values not allowed
 *      top: 400, // optional, The top position of the window. Negative values not allowed
 *      user: // optional, used for pre-filled information
 *        accountId: '54f6cfef8f5e88b96a8b4567'
 *        language: 'zh_cn', // optional, Default: zh_cn
 *        avatar: currentUser.avatar
 *        origin: 'portal'
 *        fields: [
 *          label: '邮箱'
 *          name: 'email'
 *          value: currentUser.email
 *          type: 'text'
 *          readonly: true
 *        ,
 *          label: '企业名称'
 *          name: 'company'
 *          value: currentUser.company
 *          type: 'text'
 *          readonly: true
 *        ,
 *          label: '联系人'
 *          name: 'name'
 *          value: currentUser.name
 *          type: 'text'
 *          readonly: true
 *        ]
 *      }
 *    }
###

FeedbackJsSDK = ->
  url = ''
  windowParams = ''

  this.config = (options) ->
    host = options.host or 'www.quncrm.com'
    height = options.height or 600
    width = options.width or 470
    left = options.left or window.innerWidth - width
    top = options.top or window.innerHeight - height

    windowParams = "height=#{height},width=#{width},left=#{left},top=#{top},toolbar=no,menubar=no,scrollbars=no,resizable=no,location=no,status=no"

    url = "http://#{host}/chat/feedback"

    if options.user
      params = JSON.stringify options.user
      url = "#{url}?params=#{params}"

    url = encodeURI url

  this.open = ->
    window.open(url, 'newwindow', windowParams)
    return

  this

Feedback = new FeedbackJsSDK()
