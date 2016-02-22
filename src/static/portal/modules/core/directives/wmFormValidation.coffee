define ['core/coreModule'], (mod) ->

  regx =
    required: /\S+/
    email: /^[A-Za-z0-9._-]+@[A-Za-z0-9._-]+\.[A-Za-z]{2,6}$/
    url: /^(ftp|http|https):\/\/([\w-]+\.)+(\w+)(:[0-9]+)?(\/|([\w#!:.?+=&%@!\-\/]+))?$/
  # Map angular invalid field class to angular translate key for core types
  typeMap =
    'ng-invalid-required': 'required_field_tip'
    'ng-invalid-email': 'invalid_email_tip'
    'ng-invalid-url': 'invalid_url_tip'

  translateError = (service, transKey, $target, validateFun) ->
    if not transKey and validateFun and validateFun() and angular.isFunction validateFun()
      transKey = validateFun()($target.val())
    if transKey
      service.translate(transKey).then (message) ->
        return false if $target.hasClass('form-control-error')
        message = '' if $target.attr('no-required-tip') is 'true' and not $target.val()
        service.validateService.highlight $target, message

  validateAgain = (transKey, $target) ->
    if not transKey
      type = $target.attr('type')
      type = 'required' if $target.attr('required') and type not in ['email', 'url']
      val = $target.val()
      transKey = 'ng-invalid-' + type if type and type isnt 'text' and not regx[type].test val
    transKey

  validateInput = ($target, service, wmValidate) ->
    # Use default HTML5 validation
    transKey = if $target.hasClass('ng-invalid-required') then typeMap['ng-invalid-required'] else ''

    needToValidate = false
    # validate input if contains required attr
    needToValidate = true if transKey isnt 'ng-invalid-required' and ($target.attr('required') or $target.val())
    # if don't contains wm-email and wm-url then validate no matter if the value is empty
    needToValidate = true if (not $target.attr('wm-email')?) and (not $target.attr('wm-url')?) and $target.val()?
    # if contains wm-email or wm-url, and the value isn't empty, then validate
    needToValidate = true if ($target.attr('wm-email')? or $target.attr('wm-url')?) and $target.val()
    if needToValidate
      transKey = validateAgain transKey, $target
      transKey = typeMap[transKey] if transKey of typeMap
      translateError service, transKey, $target, wmValidate
      not transKey

  # Bind submit event handler for fields
  bindInvalidHandler = (scope, elem, service, attr) ->
    validateService = service.validateService
    formTip = attr.formTip or ''
    # Default bind invalid event handler for standrad form field check
    $elem = $(elem)
    $form = $elem.closest('form')
    validateService.addFormTip $elem, formTip
    if not $elem.attr('binded')
      $elem.attr('binded', true).data('scope', scope).on 'focusout', (e) ->
        $target = $(this)
        scope = $target.data('scope')
        validateInput($target, service, scope.wmValidate)

      # Bind form validation event
      $elem.on 'invalid', (e) ->
        validateInput($(this), service)
        false

    if not $form.attr('binded')
      $form.attr('binded', true).on 'submit', (e) ->
        valid = true
        # Get all the form elements
        inputElements = $(this).find('input[binded="true"]')
        for inputElement in inputElements
          $inputElement = $(inputElement)
          scope = $inputElement.data('scope')
          valid = validateInput($inputElement, service, scope.wmValidate) and valid if scope
        e.preventDefault() if not valid
        e.valid = valid
        valid
        ###
        setTimeout(()->
          $target.focus()
        , 2000)
        ###

    scope.$on 'clearValidityError', ->
      validateService.restore $elem, formTip

  mod.directive('required', [
    '$translate'
    'validateService'
    ($translate, validateService) ->
      return (scope, elem, attr) ->
        # Add * form required field label
        unless attr.hasOwnProperty('withoutStar')
          $label = $(elem).closest('.form-group').children('label')
          if $label.length
            $label.addClass 'required-field'
          else
            elem.parent().addClass 'required-tip'
        # Bind for required field
        service =
          translate: $translate
          validateService: validateService
        bindInvalidHandler scope, elem, service, attr
        return
  ]).directive('wmUrl', [
    '$translate'
    'validateService'
    ($translate, validateService) ->
      return (scope, elem, attr) ->
        # Bind for URL type field
        service =
          translate: $translate
          validateService: validateService
        bindInvalidHandler scope, elem, service, attr
        return
  ]).directive('wmEmail', [
    '$translate'
    'validateService'
    ($translate, validateService) ->
      return (scope, elem, attr) ->
        # Bind for email type field
        service =
          translate: $translate
          validateService: validateService
        bindInvalidHandler scope, elem, service, attr
        return
  ]).directive 'wmValidate', [
    '$translate'
    'validateService'
    ($translate, validateService) ->
      return (
        scope:
          wmValidate: '&'
        link: (scope, elem, attr) ->
          # Bind for customized type field
          service =
            translate: $translate
            validateService: validateService
          bindInvalidHandler scope, elem, service, attr
          return
      )
  ]
  .directive 'wmMaxCharacterSize', [
    'validateService'
    (validateService) ->
      return(
        restrict: 'A'
        scope:
          ngModel: '='
        link: (scope, elem, attr, ctrl) ->
          formTip = attr.formTip or ''
          validateService.addFormTip elem, formTip

          scope.$watch 'ngModel', (newValue, oldValue) ->
            characterInput = 0
            initData = JSON.parse attr.wmMaxCharacterSize.replace(/'/g, '"')
            chineseLength = 2
            englishLength = 1
            remainingLength = 1
            if typeof initData is 'object'
              chineseLength = parseInt(initData.chinese) if initData.chinese
              englishLength = parseInt(initData.english) if initData.english
              remainingLength = parseInt(initData.size) if initData.size
            else
              remainingLength = parseInt(initData, 10)
            for index of newValue
              if newValue.charCodeAt(index) < 299 # charCode less than 299 means it is not a chinese character
                remainingLength -= englishLength
              else
                remainingLength -= chineseLength
              if remainingLength < 0
                break
              characterInput++
            if remainingLength < 0
              if newValue.charCodeAt(newValue.length - 1) < 299
                scope.ngModel = oldValue
              else
                scope.ngModel = scope.ngModel.substr 0, characterInput
      )
    ]
  return
