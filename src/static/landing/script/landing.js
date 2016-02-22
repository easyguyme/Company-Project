$(function() {
  var isPlain = false;

  function loadImages($wrapper) {
    $wrapper.find('img').each(function(){
      $this = $(this);
      $this.attr('src', $this.data('original'));
      $this.removeAttr('data-original');
    })
  }

  $('#freeTryBtnOne').click(function() {
    var locationUrl = '/site/signup';

    if ($('#freeTryEmailOne').val()) {
      locationUrl += '?email=' + $('#freeTryEmailOne').val();
    }

    window.location.href = locationUrl;
  });

  $('#freeTryBtnTwo').click(function() {
    var locationUrl = '/site/signup';

    if ($('#freeTryEmailTwo').val()) {
      locationUrl += '?email=' + $('#freeTryEmailTwo').val();
    }

    window.location.href = locationUrl
  });

  $('.button-backtotop').on('click', function() {
    if ($.isFunction($.fn.fullpage.moveTo)) {
      $.fn.fullpage.moveTo(1);
    }
  });

  $fullPage = $('#fullpage')
  // Only landing page need full page handling
  if ($fullPage.length) {
    $fullPage.fullpage({
      resize: true,
      paddingTop: '68px',
      responsiveWidth: 1024,
      onLeave: function(index, nextIndex, direction) {
        if(nextIndex === 1){
          $('.button-backtotop').hide();
        } else {
          $('.button-backtotop').show();
        }
        !isPlain && loadImages($('.fp-section').eq(nextIndex));
      }
    });
    isPlain = $('.fp-responsive').length;
    !isPlain && loadImages($('.fp-section').eq(1));
  }
  isPlain && $('img').lazyload({effect: 'fadeIn'});
});
