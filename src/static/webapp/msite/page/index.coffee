$ ->
  frameDOM = window.frameElement
  isPC = !!frameDOM
  clickName = if isPC then 'click' else 'tap'
  #tab component

  activeCallback = ($this, idx) ->
    $tabs = $this.parent().parent().children()
    $anotherTab = $tabs.eq(idx)
    $anotherTitle = if 'undefined' is typeof idx then $this else $anotherTab.children('h2')
    extraClass = if $anotherTitle.hasClass('m-tab-title-bgcolor') then 'm-tab-title-bgcolor' else ''
    $tabs.removeClass('active').children('h2').removeClass('m-color m-border-color').addClass extraClass
    $anotherTitle.addClass('m-color m-border-color').parent().addClass 'active'
    'undefined' is typeof idx and ($anotherTab = $tabs.filter('li.active'))
    $anotherTab.find('.m-swipe li.active').removeClass 'active'
    $anotherTab.find('.m-load-pic').lazyload()
    $anotherTab.find('.m-swipe-wrap .m-slide-pic:first-child').trigger 'appear'
    $.each slideSwipes, (index, swipe) ->
      swipe.kill()
      return
    bindSwipe $anotherTab.find('.m-swipe')
    #
    $anotherTab.find('.m-album').each (index, elem) ->
      $albumWrapper = $(elem).find('.m-album-items')
      $images = $albumWrapper.find('a')
      width = $images.width() - 5
      1 is $albumWrapper.data('column') and (width *= 0.67)
      $images.height width
      return
    #
    $anotherTab.find('.m-article').each (index, elem) ->
      $articles = $(elem)
      style = parseInt($articles.data('style'))
      if style is 3
        $articles.find('.m-article-single-pic').height $articles.find('.m-article-single-pic').width() * 0.667
      else if style is 4
        $articles.find('.m-article-doubble-pic').height $articles.find('.m-article-doubble-pic').width()
      return
    #
    $anotherTab.find('.m-map-container').each (index, elem) ->
      $mapIcon = $(elem).find('#mapBgIcon')
      mapBgIcon = $mapIcon.width() / 576 * 500
      $mapIcon.height mapBgIcon
      return
    return

  #Map related
  mapBgIcon = $('#mapBgIcon').width() / 576 * 500
  $('#mapBgIcon').height mapBgIcon
  #Slide related
  $sliderBox = $('#slider')
  picBoxHeight = $sliderBox.width()
  not picBoxHeight and (picBoxHeight = $sliderBox.closest('.m-tab').width())
  picBoxHeight = picBoxHeight * 2 / 3
  $('.m-default-wrap').height picBoxHeight - $('.m-title').height()
  #Pic related
  $picBox = $('#m-wrap-pic-box')
  picBoxHeight = $picBox.width()
  not picBoxHeight and (picBoxHeight = $picBox.closest('.m-tab').width())
  picBoxHeight = picBoxHeight * 2 / 3
  $('.m-pic-bg').height picBoxHeight
  $('.m-default-wrap').height picBoxHeight - $('.m-title').height()
  $('.m-picture-swipe').find('.m-loading-box').height picBoxHeight
  #text component
  baseFont = Number($('html').css('font-size').slice(0, -2))
  # ueditor default font size is 16px
  textBaseRem = (16 / baseFont).toFixed(2)
  $('.m-text-size').css('font-size', "#{textBaseRem}rem")
  mores = $('.m-text-more')
  if mores isnt null
    mores.each (index, elem) ->
      $(elem)[clickName] (->
        $this = $(this)
        $this.toggleClass('m-text-less')
        contentLess = $this.parent().find('.m-text-content-less')
        contentMore = $this.parent().find('.m-text-content-more')
        if $this.hasClass('m-text-less')
          contentLess.hide()
          contentMore.show()
        else
          contentLess.show()
          contentMore.hide()
        return
      ), false
      return
  #nav component
  $('.m-nav-more-click').click ->
    that = $(this)
    that_li = that.parent()
    nav_wrap = that.parents('.m-nav-layout')
    navs = nav_wrap.find('ul')
    nav_more_line = nav_wrap.find('.m-nav-more-up')
    nav_more_down = nav_wrap.find('.m-nav-more-down')
    if that.hasClass('m-nav-more-down')
      that_li.hide()
      navs.children().removeClass 'm-nav-hidden'
      nav_more_line.show()
    else
      columnNumber = nav_wrap.data('column')
      hideIndex = columnNumber * 2 - 2
      navs.children().each (index) ->
        if index > hideIndex
          $(this).addClass 'm-nav-hidden'
        return
      nav_more_line.hide()
      nav_more_down.parent().show()
    return
  #cover3 component

  calculationNavBoxHeight = ->
    $navBox = $('.nav-background-box')
    marginBottomLength = 0
    marginRightLength = 0
    switch $navBox.length
      when 1, 2, 3
        for $item in $navBox
          $item = $ $item
          marginBottomLength += parseFloat $item.css('margin-bottom').replace('px', '')
        boxHeight = ($navBox.parent().height() - marginBottomLength) / $navBox.length
        $navBox.height boxHeight
      when 4, 6
        marginRightLength += parseFloat $($navBox[0]).css('margin-right').replace('px', '')
        for $item in $navBox
          $item = $ $item
          marginBottomLength += parseFloat $item.css('margin-bottom').replace('px', '')
        marginBottomLength = marginBottomLength / 2
        boxHeight = ($navBox.parent().height() - marginBottomLength) / ($navBox.length / 2)
        boxWidth = ($navBox.parent().width() - marginRightLength - 2) / 2
        $navBox.height boxHeight
        $navBox.width boxWidth
      when 5
        marginRightLength += parseFloat $($navBox[0]).css('margin-right').replace('px', '')
        marginBottomLength = 2 * parseFloat $($navBox[0]).css('margin-bottom').replace('px', '')
        boxHeight = ($navBox.parent().height() - marginBottomLength) / 3
        boxWidth = ($navBox.parent().width() - marginRightLength - 2) / 2
        $navBox.height boxHeight
        $navBox.width boxWidth
        $($navBox[2]).width '100%'

  if not isPC
    calculationNavBoxHeight()
  else
    setTimeout ->
      calculationNavBoxHeight()
    , 500

  if not isPC
    $('.m-tab').css 'min-height', 0
    $('.m-tab-body').css 'padding-bottom', 0
  $('.m-tab-title')[clickName] ->
    not $(this).parent().hasClass('active') and activeCallback($(this))
    return
  #Cover 3 related
  $cover3Btn = $('.m-cover3-bottom')
  if $cover3Btn.length
    totalHeight = $('#cpt-wrap').height()
    # titleHeight = $('.m-page-title').height()
    divHeight = $cover3Btn.height()
    imgHeight = totalHeight - divHeight
    # titleHeight and (imgHeight -= titleHeight)
    $('.m-img-bg').height imgHeight
    $('.m-img-bg a').height imgHeight
    $('#m-default-pic-box').height imgHeight - $('.m-title').height()
  #Cover 1/2 related
  totalHeight = $('#cpt-wrap').height()
  # titleHeight = $('.m-page-title').height()
  imgHeight = totalHeight
  # titleHeight and (imgHeight -= titleHeight)
  $('.m-cover1-image').height imgHeight
  $('.m-cover1-image>a').height imgHeight
  $('.m-load-pic').each ->
    $this = $(this)
    $this.filter(->
      !!$this.data('original')
    ).lazyload()
    return

  if isPC
    wrapDOM = document.getElementById('cpt-wrap')
    cptName = wrapDOM.getAttribute 'data-type'
    if cptName
      ('cover3' is cptName or 'slide' is cptName or 'album' is cptName) and (frameDOM.style.height = 'initial')
      height = wrapDOM.offsetHeight
      height < 20 and (height = 20)
      isCover = cptName.indexOf('cover') isnt -1
      heightStyle = if isCover then '100%' else height + 'px'
      frameDOM.style.height = '100%'
      frameDOM.style.width = '100%' if isCover
      # Ajust the component height in editing page
      if (not frameDOM.nextElementSibling or frameDOM.nextElementSibling.className isnt 'm-tab') and not isCover
        frameDOM.parentNode.style.height = (height - 2) + 'px'
        frameDOM.parentNode.parentNode.style.height = heightStyle if not isCover and not frameDOM.parentNode.parentNode.classList.contains('mobile-content')

  if window.wx
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
    })

    message =
      title: page.title
      desc: page.desc
      link: page.url
    wx.ready( ->
      wx.onMenuShareAppMessage message
      wx.onMenuShareQQ message
      wx.onMenuShareWeibo message
      wx.onMenuShareTimeline message
      return
    )
  return
