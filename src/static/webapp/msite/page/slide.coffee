slideSwipes = []

bindSwipe = ($swipes, isFirst) ->
  $swipes.each (index, elem) ->
    $elem = $(elem)
    $swipes.filter(->
      ! !$(this).attr('id') and $(this).attr('id') isnt 'cover1'
    ).height $('#cpt-wrap').width() * 70 / 105
    # titleHeight = $('.m-page-title').height()
    titleHeight = 0
    $elem.attr('id') is 'cover1' and $elem.height($('#cpt-wrap').height() - $('.m-cover1-nav').height() - titleHeight)
    swipePicCount = $elem.find('.m-img-bg').length
    ($elem.attr('id') is 'cover3' or $elem.attr('id') is 'slider') and $elem.find('m-slide-title').html($elem.find('.m-swipe-wrap').attr('data-name-list').split(',')[0])
    $elem.find('.m-swipe-dot:nth-child(1)').addClass 'active'
    swipe = new Swipe(elem,
      auto: parseInt($elem.find('.m-swipe-wrap').attr('data-auto'))
      continuous: true
      callback: (index, elem) ->
        count = index + 1
        $elem = $(elem)
        $slider = $elem.parent().parent()
        $slider.find('.m-swipe-dot.active').removeClass 'active'
        $slider.find('.m-swipe-dot:nth-child(' + count + ')').addClass 'active'
        typeof $elem.parent().data('name-list') isnt 'undefined' and $slider.find('.m-slide-title').html($elem.parent().data('name-list').split(',')[index])
        return
      transitionEnd: (index, elem) ->
        swipePicCount > 1 and $(elem).lazyload()
        return
)
    typeof isFirst is 'undefined' and $elem.parent().parent().hasClass('wm-tab-body') and slideSwipes.push(swipe)
    return
  return

$ ->
  isPC = !!window.frameElement
  bindSwipe $('.m-swipe')
  return
