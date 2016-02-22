define [
  'core/coreModule'
], (mod) ->
  mod.factory 'heightService', [
    ->
      height = {}

      height.beforeLogin = (className, styleName) ->
        $(className).css(styleName, $(window).height())
        return

      height.afterLogin = (className, styleName) ->
        $(className).css(styleName, $(window).height() - 52)
        return

      height
  ]
