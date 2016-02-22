###
# Get Url Id
###
_getUrlId = ()->
  id = null
  pathName = window.location.pathname if window.location.pathname
  id = pathName.slice pathName.lastIndexOf('/')+1
  splitArray = [':', '?', '&']
  splitArray.forEach (splitStr)->
    index = id.lastIndexOf(splitStr)
    if id and index and index isnt -1
      id = id.slice 0, index
  return id

_delHtmlTag = (str)->
  str.replace /<[^>]+>/g, ""

_init = ()->
  $("body").css "margin", 0
  $(".article-picture-box").height $(".article-picture-box").width() * 138 / 275
  pathName = window.location.pathname if window.location.pathname
  articleId = _getUrlId()

  # Get Article
  $.get '/api/article/' + articleId, (data) ->
    article = data if data
    if article.name
      document.title = article.name
      #$(".article-title-box").html article.name
      $(".article-content-name").html article.name
    article.picUrl = article.picUrl or "/images/mobile/article_default.png"
    $(".article-picture-box").css "background-image", "url(" + article.picUrl + ")"
    if article.content
      article.content = article.content.replace(/<img/g, "<img style='max-width: 100%;'")
      $(".article-content-value").html article.content


    if article.fields and article.fields.length > 0
      $articleContainerElem = $('.article-container')
      article.fields.forEach (field)->
        fieldValue = ""
        switch field.type
          when "date"
            fieldValue = new moment(field.content).format "YYYY-MM-DD" if field.content
          when "time"
            fieldValue = new moment(field.content).format "HH:mm" if field.content
          else
            fieldValue = field.content if field.content
        if fieldValue isnt ""
          if field.type is "image"
            $articleContainerElem.append("<div class='article-field-box'>" +
                                                          "<div class='article-field-name'>"+field.name+"</div>" +
                                                          "<img style='width: 100%;' src='"+fieldValue+"'>" +
                                                      "</div>")
          else
            $articleContainerElem.append("<div class='article-field-box'>" +
                                                          "<div class='article-field-name'>"+field.name+"</div>" +
                                                          "<div class='article-field-value'>"+fieldValue+"</div>" +
                                                      "</div>")

    wx.config({
      appId: options.appId
      timestamp: options.timestamp
      nonceStr: options.nonceStr
      signature: options.signature
      jsApiList: [
        'onMenuShareAppMessage',
        'onMenuShareTimeline',
        'onMenuShareQQ',
        'onMenuShareWeibo'
      ]
    });
    message =
      title   : article.name
      desc    : _delHtmlTag article.content
      link    : article.url
      imgUrl  : article.picUrl
    wx.ready(() ->
      wx.onMenuShareAppMessage message
      wx.onMenuShareQQ message
      wx.onMenuShareWeibo message
      wx.onMenuShareTimeline message
      return
    )
    return
_init()
