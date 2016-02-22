define [
  "angular"
  "wm/app"
], (angular) ->
  # Bootstrap angular app
  $(document).ready ->
    # Hack for page height doesn't fit full screen
    $doc = $(document)
    $('.viewport').css('min-height', $doc.height() - 52)
    angular.bootstrap document, ["wm"]
    # If login page, set horizonal scroll to center
    if location.href.indexOf('login') isnt -1
      centerLength = (document.body.scrollWidth - document.body.clientWidth) / 2
      window.scrollTo centerLength, 0

    return
