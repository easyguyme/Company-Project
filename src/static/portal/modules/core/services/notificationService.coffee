define [
  'core/coreModule'
], (mod) ->
  mod.factory 'notificationService', [
    '$timeout'
    '$translate'
    '$rootScope'
    ($timeout, $translate, $rootScope) ->
      vm = {}

      vm.showMessage = (message, type) ->
        if ($rootScope.lastMessage and message is $rootScope.lastMessage)
          return
        $rootScope.lastMessage = message
        messageBox = '<div class="message"><i class="icon"></i><span class="text"></span><i class="close-btn"></i></div>'
        $messageBox = $ messageBox
        $('.notification').prepend $messageBox
        $messageBox.slideDown 'fast'
        $messageBox.addClass 'message-' + type
        $messageBox.children('span.text').html message
        $messageBox.children('.close-btn').click ->
          $messageBox.fadeOut ->
            $messageBox.remove()
            delete $rootScope.lastMessage
          return
        # hide after 5s
        $timeout ->
          $messageBox.fadeOut ->
            $messageBox.remove()
            delete $rootScope.lastMessage
        , 5000

        #remove the last message if it already has 4
        if $('.notification').children().length > 4
          $('.message:last').fadeOut ->
            $('.message:last').remove()

      vm.addMessage = (message, plain, type, values) ->
        if not plain
          $translate(message, values).then (messageTranslated) ->
            vm.showMessage messageTranslated, type
            return
        else
          for key, value of values
            message = message.replace '{{' + key + '}}', value
          vm.showMessage message, type
        return

      vm.success = (message, plain, values) ->
        vm.addMessage message, plain, 'success', values

      vm.info = (message, plain, values) ->
        vm.addMessage message, plain, 'info', values

      vm.warning = (message, plain, values) ->
        vm.addMessage message, plain, 'warning', values

      vm.error = (message, plain, values) ->
        vm.addMessage message, plain, 'error', values

      initConfirm = ($event) ->
        $body = if $('.main-content-wrap').length > 0 then $('.main-content-wrap') else $('.viewport-wrap')
        if not $body.children().hasClass('confirm')
          $confirmBox = $ '<div class="confirm"></div>'
          $body.append($confirmBox)
        $confirmMask = $ '<div class="mask-confirm"></div>'
        $body.append($confirmMask)
        $confirmMask.click (event) ->
          event.preventDefault()
          closeConfirm($event)
          return
        return

      closeConfirm = ($event) ->
        $target = $($event.target)

        if $target.length > 0 and $target.data('confirm-target-color')
          $target.removeClass 'confirm-target-show-color'
        $('.confirm').hide()
        $($('.mask-confirm')[0]).remove()
        return

      vm.confirm = ($event, options = {}) ->

        $target = $($event.target)

        initConfirm($event)
        title = options.title or 'delete_confirm'
        $translate([title, 'ok', 'cancel']).then (map) ->
          confirmContent = '<i class="back-triangle"></i><i class="front-triangle"></i><div class="confirm-title"></div>
            <div class="confirm-buttons"><span class="btn btn-success btn-operate-tag btn-tag-ok"></span><span class="btn btn-default btn-operate-tag"></span></div>'
          $confirmContent = $ confirmContent
          $confirmBox = $ '.confirm'
          $confirmMask = $ '.mask-confirm'
          $confirmBox.empty().append $confirmContent

          $confirmBox.show()
          $confirmMask.show()

          $confirmBox.children('.confirm-title').html map[title]
          $confirmContent.children('.btn-success').text map['ok']
          $confirmContent.children('.btn-default').text map['cancel']

          $confirmContent.children('.btn-success').click ->
            if typeof options.submitCallback is 'function'
              options.submitCallback.apply null, options.params
            closeConfirm($event)
            return
          $confirmContent.children('.btn-default').click ->
            $target.removeAttr 'tip-checked'
            if typeof options.cancelCallback is 'function'
              options.cancelCallback.apply null, options.params
            closeConfirm($event)
            return

          if $target.length > 0 and $target.data('confirm-target-color')
            $target.addClass 'confirm-target-show-color'
          confirmBoxTop = $target.offset().top + $target.outerHeight() - $('.viewport-wrap').offset().top + 7
          confirmBoxRight = $(document).width() - $target.offset().left - $target.outerWidth() / 2 - 17
          $confirmBox.css {'top': confirmBoxTop, 'right': confirmBoxRight}
          return
        return

      $rootScope.$on '$stateChangeStart', ->
        $('.notification .message').fadeOut ->
          $('.notification .message').remove()
        return

      vm
  ]
