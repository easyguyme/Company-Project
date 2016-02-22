$ ->

  formatStyle =
    'date': 'yyyy-MM-dd'
    'time': 'hh:mm:ss'

  Date::format = (fmt) ->
    o =
      'M+': @getMonth() + 1    #月份
      'd+': @getDate()          #日
      'h+': @getHours()           #小时
      'm+': @getMinutes()         #分
      's+': @getSeconds()         #秒
      'q+': Math.floor((@getMonth() + 3) / 3)    #季度
      'S': @getMilliseconds()       #毫秒
    if /(y+)/.test(fmt)
      fmt = fmt.replace(RegExp.$1, (@getFullYear() + '').substr(4 - RegExp.$1.length))
    for k of o
      if new RegExp('(' + k + ')').test(fmt)
        fmt = fmt.replace(RegExp.$1, if RegExp.$1.length is 1 then o[k] else ('00' + o[k]).substr(('' + o[k]).length))
    fmt

  # remove '' in array
  removeEmptyStr = (oriArr) ->
    temp = []
    for f in oriArr
      if f
        temp.push f
    temp

  # if article fields do not have the field id needed to display on page, so need to get field name by id
  getFieldName = (id, fieldObj) ->
    for obj in fieldObj
      if obj.id is id
        return obj.name

  getDefaultText = (field, info) ->
    fieldName = getFieldName(field, info.fieldObj)

    if info.style is 2
      property = if fieldName then defaultText else ''
    else
      property = if fieldName then fieldName + ': ' + defaultText else ''

  # get style by custome fields
  formateCustomFields = (article, info) ->
    otherInfo = ''
    articleFields = article.fields
    for field in info.fields
      if articleFields.length > 0
        for articleField in articleFields
          if articleField.id is field
            switch articleField.type
              when 'date', 'time'
                if info.style is 2
                  property = if articleField.content then new Date(articleField.content).format(formatStyle[articleField.type]) else defaultText
                else
                  property = articleField.name + ': ' + if articleField.content then new Date(articleField.content).format(formatStyle[articleField.type]) else defaultText
              # when 'image'
              #   if style is 2
              #     # image
              #     property = '我是图片'
              #   else
              #     property = 'articleField.name + ': '我是图片'
              else
                if info.style is 2
                  property = articleField.content or defaultText
                else
                  property = articleField.name + ': ' + (articleField.content or defaultText)
            break

          property = getDefaultText(field, info)
      else
        property = getDefaultText(field, info)

      otherInfo += '<div class="m-custom-field m-text-overflow">' + property + '</div>'
    return otherInfo

  getArticles = (listWrapper, info) ->
    ## show loading animation
    listWrapper.find('.m-load-more').hide()
    listWrapper.find('.m-loading').show()

    $.ajax
      type: 'GET'
      url: '/api/articles' + '?time=' + new Date().getTime()
      data: {
        channel: info.channelId
        'per-page': info.pageSize
        page: info.currentPage
      }
      dataType: 'json'
      success: (data) ->
        info.currentPage = data._meta.currentPage
        info.totalPage = data._meta.pageCount
        articles = data.items
        htmlContainer = ''

        ## hide loading animation
        listWrapper.find('.m-load-more').show()
        listWrapper.find('.m-loading').hide()

        if articles.length is 0
          listWrapper.find('.m-load-more').text('没有文章，赶快创建吧')
        else if info.currentPage is info.totalPage
          listWrapper.find('.m-load-more').text('亲，没有更多了哟')

        for article in articles
          otherInfo = ''
          picUrl = article.picUrl or defaultImage

          if info.style is 1
            content = $(article.content).text()
            content = if content.length > 25 then content.substring(0, 25) + '...' else content
            otherInfo = '<div class="m-content">' + content + '</div>'
          else
            otherInfo = formateCustomFields(article, info)

          htmlContainer += '<a href="' + article.url + '"><div class="m-article-wrapper clearfix" data-id="' + article.id + '">' +
            '<div class="m-pic-box m-pull-left" style="background-image:url(' + picUrl + ')"></div>' +
            '<div class="m-content-detail m-pull-left"><div class="m-article-title m-text-overflow">' + article.name + '</div>' + otherInfo + '</div>' +
            '<div class="m-content-view"></div></div></a>'

        listWrapper.find('.m-article-list').append($(htmlContainer))

        #ajust frame height but exclude microsite page
        frameDOM = window.frameElement
        if frameDOM and $('#cpt-wrap').data('type') is 'articles'
          cptHeight = $('#cpt-wrap').height()
          frameDOM.style.height = cptHeight + 'px'
          if not frameDOM.parentNode.parentNode.classList.contains('mobile-content')
            frameDOM.parentNode.parentNode.style.height = (cptHeight + 2) + 'px'
          else
            frameDOM.parentNode.style.height = (cptHeight + 2) + 'px'

      error: ->

  defaultImage = '/images/content/default.png'
  defaultText = '暂无'
  cacheInfo = []  #cache article information such as channel id, pageSize, currentPage
  $articles = $('.m-article')
  $articles.each (index) ->
    $wrapper = $(this)
    ## cache nessary information
    cacheInfo.push({
      pageSize: $wrapper.data('shownum')
      currentPage: 1
      totalPage: 0
      channelId: $wrapper.data('channel')
      style: $wrapper.data('style')
      fieldObj: []  ## get all fields information of a channel
      fields: removeEmptyStr $wrapper.data('fields').split(',')
    })

    $wrapper.find('.m-load-more').on('click', ->
      if cacheInfo[index].currentPage < cacheInfo[index].totalPage
        cacheInfo[index].currentPage++
        getArticles($wrapper, cacheInfo[index])
    )

    # if have channel id, need to get articles by ajax, else display style one
    if cacheInfo[index].channelId
      $wrapper.find('.m-article-list').empty()
      $wrapper.find('.m-load-more').show()

      # get fields by channel
      $.ajax
        type: 'GET'
        url: '/api/article-channel/' + cacheInfo[index].channelId + '?time=' + new Date().getTime()
        dataType: 'json'
        success: (data) ->
          cacheInfo[index].fieldObj = data.fields
          getArticles($wrapper, cacheInfo[index])

        error: ->

    else
      content = $wrapper.find('.m-content').text()
      content = if content.length > 25 then content.substring(0, 25) + '...' else content
      $wrapper.find('.m-content').text(content)
