$ ->
  $albums = $('.m-album')
  len = $albums.length
  $albums.each ->
    $this = $(this)
    $atlasWrapper = $this.children('.m-album-items')
    $images = $atlasWrapper.find('a')
    imageCount = $images.length
    $dots = $('.m-album-dot', this)
    new Swipe($atlasWrapper[0],
      callback: (index, element) ->
        $('.m-album-dot.m-album-bgcolor', $this).removeClass 'm-album-bgcolor'
        $dots.eq(index).addClass 'm-album-bgcolor'
      transitionEnd: (index, element) ->
        $(element).find('.wm-load-pic').lazyload()
    )

    if imageCount
      # set height of picture in album according to width
      width = $images.width() - 5
      1 == $this.find('.m-album-items').data('column') and (width *= 0.67)
      $images.height width
      options =
        captionAndToolbarOpacity: 1
        captionAndToolbarAutoHideDelay: 0
        captionAndToolbarFlipPosition: true
        getImageCaption: (el) ->
          $img = $(el).children()
          $picIdx = $('<span>').text($img.attr('id')).addClass('m-album-pic-idx')
          $picTitle = $('<p>').text($img.attr('title')).addClass('m-album-pic-title')
          $wrap = $('<div>').addClass('m-album-pic-desc-wrap')
          desc = $img.attr('alt')
          if $.trim(desc)
            $picDesc = $('<p>').text(desc).addClass('m-album-pic-desc').data('top', 0)
            $wrap.append $picDesc
            $picDesc.on('touchstart', (e) ->
              $(this).data 'pageY', e.touches[0].pageY
              return
            ).on 'touchend', (e) ->
              $this = $(this)
              max = $('.m-album-pic-desc-wrap').height() - $this.height()
              top = $this.data('top')
              top = parseInt(top)
              step = parseInt($this.data('pageY')) - e.changedTouches[0].pageY
              newTop = top + step / -2
              newTop > 0 and (newTop = 0)
              newTop < max and (newTop = max)
              $this.css 'margin-top', newTop
              $this.data 'top', newTop
              return
          $wrap.append $picIdx
          $wrap.append $picTitle
          $wrap[0]
        getToolbar: ->
          '<div class="ps-toolbar-close m-toolbar"></div>'
    Code.PhotoSwipe.attach $images, options
    return
  return
