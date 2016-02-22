$ ->
  $('title').html '意见反馈'

  issue = {}
  issue.attachments = []
  errorBorderColor = '#b42d14'
  defaultBorderColor = '#d8d8d8'
  originalTextareaWrapperHeight = $('.textarea-wrapper').height()
  onUploading = false
  onSubmitting = false

  fields = [
    id: 'name'
    name: '姓名'
    required: true
  ,
    id: 'email'
    name: '邮箱'
    regExp: new RegExp('^[a-zA-Z0-9_-]+@[a-zA-Z0-9_-]+(\.[a-zA-Z0-9_-]+)+$')
    required: true
  ,
    id: 'phone'
    name: '手机号'
    regExp: new RegExp('^0?1[0-9]{10}$')
    required: true
  ,
    id: 'title'
    name: '标题'
    required: true
  ,
    id: 'description'
    name: '问题描述'
    required: true
  ,
    id: 'attachment'
    name: '附件'
    required: false
  ]

  showSuccessPage = ->
    $('.feedback-success-wrapper').show()

  $('#submitBtn').click ->
    return if onSubmitting
    return displayErrorMsg('attachment', '请等待图片上传完毕') if onUploading
    channelInfo =
      'openId': util.queryMap['openId']
      'channelId': util.queryMap['channelId']
      'origin': util.queryMap['origin']

    if validate()
      onSubmitting = true
      $.extend issue, channelInfo
      rest.post '/chat/issues', issue
      , (result) ->
        if result.errors
          for id, messages of result.errors
            displayErrorMsg id, messages[0]
          return
        $('.feedback-add-wrapper').remove()
        showSuccessPage()

  displayErrorMsg = (id, msg) ->
    $('.center-' + id + '-form-tip').text msg
    $('.' + id).css 'border', "1px solid #{errorBorderColor}"

  clearErrorMessage = (id) ->
    $('.center-' + id + '-form-tip').text ''
    $('.' + id).css 'border', "1px solid #{defaultBorderColor}"

  clearAllErrorMsgs = ->
    for field in fields
      clearErrorMessage field.id

  validate = ->
    verified = true
    clearAllErrorMsgs()

    for field in fields
      id = field.id
      value = $('#' + id).val()
      if id is 'description'
        value = value.slice 5
      if field.required and typeof value is 'undefined' or not value
        displayErrorMsg id, field.name + '不能为空'
        verified = false
      else
        if field.regExp
          if not field.regExp.test value
            displayErrorMsg id, field.name + '格式不正确'
            verified = false
            continue
        issue[id] = value

    return verified

  $('#attachment').on 'change', (event) ->
    files = $(this)[0].files
    if files.length
      if checkFormat files
        qiniuUpload files

  qiniuUpload = (files) ->
    rest.get '/qiniu-token/generate', (data) ->
      uploadFiles files, data

  checkFileExists = (file) ->
    flag = file.size + file.lastModified
    for attachment in issue.attachments
      if attachment.flag is flag
        return true
    return false

  clearFileValue = ->
    $('#attachment').val ''

  fileIndex = 0
  uploadFiles = (files, qiniu) ->
    if fileIndex is files.length
      fileIndex = 0
      clearFileValue()
      onUploading = false
    else
      onUploading = true
      file = files[fileIndex]
      if checkFileExists file
        displayErrorMsg 'attachment', "文件#{file.name}已经存在"
        fileIndex = 0
        clearFileValue()
        onUploading = false
        return
      lastIndex = file.name.lastIndexOf '.'
      # Generate unique file name using guid
      fileName = guid() + file.name.slice lastIndex
      formData = new FormData()
      formData.append 'file', file
      formData.append 'key', fileName
      formData.append 'token', qiniu.token

      xhr = new XMLHttpRequest()
      xhr.open 'POST', qiniu.uploadDomain, true
      xhr.send formData

      xhr.onreadystatechange = ->
        if xhr.readyState is 4 and xhr.status is 200
          fileUrl = qiniu.domain + '/' + fileName
          attachment =
            name: file.name.slice 0, lastIndex
            url: fileUrl
            type: file.name.slice(lastIndex + 1).toLowerCase()
            format: 'Img'
            flag: file.size + file.lastModified

          if file.size < 1024
            attachment.size = file.size + 'B'
          else if file.size > 1024 and file.size < Math.pow(1024, 2)
            attachment.size = (file.size / Math.pow(10, 3)).toFixed(1) + 'KB'
          else
            attachment.size = (file.size / Math.pow(10, 6)).toFixed(1) + 'MB'

          issue.attachments.push attachment

          updateThumbnail fileUrl
          checkAttachmentsWidth()
          fileIndex++
          uploadFiles files, qiniu

  #Generate UUID partial
  S4 = ->
    (((1 + Math.random()) * 0x10000) | 0).toString(16).substring 1
  #Generate UUID
  guid = ->
    S4() + S4() + S4() + S4() + S4() + S4()

  getQiniuKey = (url) ->
    return url.split('/').slice -1

  updateThumbnail = (url, width = 60, height = 60) ->
    url += "?imageView2/1/w/#{width}/h/#{height}"
    thumbnailHTML = '<div class="thumbnail-wrapper">
                      <img src="' + url + '">
                      <img class="btn-delete-attachment" src="/images/mobile/feedback/phone_deletephoto_normal.png">
                     </div>'
    $('.input-wrapper').before thumbnailHTML

  checkAttachmentsWidth = ->
    length = issue.attachments.length
    attachmentsWrapperWidth = $('.attachments-wrapper').width()
    # Adds 8(px) because of the margin length of attachment.
    attachmentWrapperWidth = $('.thumbnail-wrapper').width() + 8
    attachmentWrapperHeight = $('.thumbnail-wrapper').height() + 8
    # Adds 1 because of the adding attachment button.
    currentLines = Math.floor attachmentWrapperWidth * (length + 1) / attachmentsWrapperWidth
    lastLines = 0 if lastLines is 'undefined'
    if lastLines isnt currentLines
      $('.textarea-wrapper').height (currentLines * attachmentWrapperHeight) + originalTextareaWrapperHeight
      lastLines = currentLines

  # Define the format of picture.
  imageContentTypes = ->
    return 'image/jpg,image/png,image/jpeg,image/pjpeg,image/x-png,image/gif'

  checkFormat = (files) ->
    allowedImageTypes = imageContentTypes().split ','
    for file in files
      if $.inArray(file.type, allowedImageTypes) is -1
        displayErrorMsg 'attachment', "文件#{file.name}不是图片类型"
        return false
    clearErrorMessage 'attachment'
    clearErrorMessage 'description'
    return true
  bindDeleteAttachmentListener = ->
    $('.attachments-wrapper').on 'click', '.btn-delete-attachment', (event) ->
      thumbnailUrl = $(this).siblings('img').attr('src')
      originalUrl = thumbnailUrl.split('?')[0]
      rest.del '/chat/issue/remove-attachment', {qiniu: getQiniuKey(originalUrl)[0]}
      , (data) =>
        if data.message is 'ok'
          $($(this).parent()[0]).remove()
          for attachment, index in issue.attachments
            if attachment.url is originalUrl
              issue.attachments.splice index, 1
              break
          checkAttachmentsWidth()

  checkDescriptionContent = ->
    $('#description').on 'input propertychange', (event) ->
      value = $(this).val()
      if value.length < 5 or value.indexOf('问题描述：') isnt 0
        $(this).val '问题描述：'

  bindExitListener = ->
    window.onbeforeunload = (event) ->
      if issue.attachments.length > 0
        for attachment in issue.attachments
          rest.del '/chat/issue/remove-attachment', getQiniuKey(attachment.url)


  init = ->
    bindDeleteAttachmentListener()
    checkDescriptionContent()
    bindExitListener()

  init()
