define [
  'angular'
  'jqueryBundle'
  'chat/app'
], (angular) ->
  # Bootstrap angular app
  $(document).ready ->
    # Hack for page height doesn't fit full screen
    windowHeight = $(window).height() or 450
    $('.content').css('height', windowHeight - 52)
    angular.bootstrap document, ['wm']
