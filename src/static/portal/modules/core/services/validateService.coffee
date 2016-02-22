define [
  'core/coreModule'
  'wm/config'
], (mod, config) ->
  mod.factory 'validateService', [
    '$timeout'
    'notificationService'
    ($timeout, notificationService) ->
      timeoutProcess = undefined
      validator = {}
      validator.showErrors = (obj) ->
        if $.isPlainObject(obj)
          for id, message of obj
            $elem = $('#' + id)
            if $elem.length
              $elem.removeClass('ng-valid').addClass('ng-invalid')
              @showError $elem, message
            else
              notificationService.error message, true
        notificationService.error obj, true if $.type(obj) is 'string'
        return

      validator.showError = ($elem, message) ->
        $formTip = validator.addFormTip $elem
        validator.highlight $elem, message

      validator.addFormTip = ($elem, message) ->
        $formTip = $elem.next('.form-tip')
        len = $formTip.length
        if not len
          $formTip = $('<span class="form-tip normal">' + message + '</span>')
          $elem.after $formTip
        # Remove the form error tip which following div element After 1500ms #1043.
        elemName = $elem.get(0).tagName.toLowerCase()
        if elemName isnt 'input' and elemName isnt 'textarea'
          $timeout( ->
            $elem.removeClass('ng-invalid').next('.form-tip').remove()
          , 5000)
        # Remove the form error tip when focusing on the field
        $elem.on 'focusin', (e) ->
          validator.restore $(e.target), message
        $formTip

      validator.highlight = ($target, message) ->
        $formTip = $target.removeClass('ng-valid').addClass('ng-invalid').next('.form-tip').removeClass('normal')
        len = $formTip.length
        $target.after '<span class="form-tip min-width-500">' + message + '</span>' if message and not len
        $target.addClass('form-control-error').parent().addClass 'highlight'
        $formTip.html message

      validator.restore = ($target, originalTip) ->
        $target.removeClass('form-control-error').parent().removeClass 'highlight'
        if originalTip
          $target.next('.form-tip').addClass('normal').html originalTip
        else
          $target.removeClass('ng-invalid').next('.form-tip').remove()

      validator.checkTelNum = (tel) ->
        telFormTip = 'site_tel_form_tip'
        if tel and config.telRegs
          for reg in config.telRegs
            re = new RegExp(reg)
            if re.test(tel)
              telFormTip = ''
              break
        telFormTip

      validator
  ]
