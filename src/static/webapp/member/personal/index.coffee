$('title')[0].innerText = '个人信息'

defaultPropertyName =
  name: '姓名'
  gender: '性别'
  birthday: '生日'
  email: '邮箱'

properties = []
presentYear = null
presentMonth = null
presentDay = null

member = null

calendarItemHeight = 36

redirectUrl = ''

$presentDatePickerTarget = null
$presentRadioPickerTarget = null
$presentCheckboxPickerTarget = null

$wmDateContainer = $('#wmDatePicker')
$wmRadioContainer = $('#wmRadioPicker')
$wmCheckboxContainer = $('#wmCheckboxPicker')

_init = ->
  searchMsg = window.location.search if window.location.search
  searchArray = searchMsg.slice(1).split '&'
  memberId = ''
  searchArray.forEach (item) ->
    if item.indexOf('memberId') isnt -1
      memberId = item.split('=')[1]
    else if item.indexOf('redirect') isnt -1
      redirectUrl = item.split('=')[1]

  # Get member
  $.get '/api/member/member/' + memberId, (data) ->
    member = data if data

    telValue = getPropertyValueByName(member.properties, 'tel') if member.properties and getPropertyValueByName(member.properties ,'tel')
    $('#personalPhone').text(telValue)

    # Get access token
    condition =
      where: JSON.stringify {'isVisible': true}
      unlimited: true
    $.get '/api/common/member-propertys', condition, (data) ->
      if data?.items?
        properties = data.items
        data.items.sort (first, second) ->
          if first.order > second.order then 1 else -1
        data.items.forEach (property, index) ->
          propertyVal = ''
          propertyVal = getPropertyValueByName(member.properties ,property.name) if member.properties and getPropertyValueByName(member.properties ,property.name)
          property.value = propertyVal if propertyVal

          isRequired = if property.isRequired then 'required' else ''
          validateLength = if property.name is 'name' then 'maxlength="30" minlength="2"' else ''
          if property.name is 'gender' and propertyVal
            if propertyVal is 'male'
              propertyVal = '男'
            else if propertyVal is 'female'
              propertyVal = '女'
            else
              propertyVal = ''
          if property.type is 'date' and propertyVal
            propertyVal = moment(propertyVal).format('YYYY年MM月DD日')
          if property.type is 'checkbox' and propertyVal
            propertyVal = ''
            property.value.forEach (item) ->
              propertyVal += item + ' '
          labelName = property.name
          labelName = defaultPropertyName[property.name] if defaultPropertyName[property.name]
          if property.name isnt 'tel'
            $propertyLastElem = $('.mb-personal-item').last()
            $elem = $ '<div class="mb-personal-item">' +
                              '<div class="mb-input-component mb-component-' + property.type + '">' +
                                  '<label class="input-component-item input-component-label text-el">' + labelName + ': </label>' +
                                  '<input id="' + property.id + '" name="' + property.name + '" type="text" class="property-item input-component-item input-component-text text-el" value="' +
                                    propertyVal + '" ' + isRequired + ' ' + validateLength + '>' +
                              '</div>' +
                          '</div>'
            switch property.type
              when 'input'
                $propertyLastElem.after $elem
              when 'radio', 'checkbox','date'
                $elem.find('.mb-input-component').addClass 'mb-flipselect-component'
                $radioElem = $elem.find('input')
                $radioElem.prop 'readonly', true
                $radioElem.prop 'disabled', true
                $radioElem.after '<div class="select-popup-icon"></div>'
                # if property is birthday, then show the birthday tip
                if property.name is 'birthday'
                  if propertyVal is ''
                    $elem.find('.mb-input-component').data 'has-value', 'false'
                    $formTip = $ '<span class="mb-personal-item personal-birthday-tip">请填写您的真实信息，生日当天您将会收到惊喜。生日为您专享生日特权的唯一凭证，一旦提交，将无法修改。</span>'
                  else
                    $elem.find('.mb-input-component').css {
                      'box-shadow': 'none'
                      'border': 'none'
                      'height': '34px'
                    }
                    $elem.find('input').css 'margin', '7px 0px'
                    $elem.css 'line-height', '34px'
                    $elem.find('label').css 'line-height', '34px'
                    $elem.find('.select-popup-icon').remove()
                    $elem.find('.mb-input-component').data 'has-value', 'true'
                    $formTip = $ '<span class="mb-personal-item personal-birthday-tip">生日是您领取专享生日礼品的唯一凭证，不能轻易修改，如有特殊情况需要修改，请联系客服。</span>'
                  $propertyLastElem.after $formTip
                $propertyLastElem.after $elem
              when 'textarea'
                $elem = $ '<div class="mb-personal-item">' +
                                  '<div class="mb-textarea-component mb-component-' + property.type + '">' +
                                      '<label class="textarea-component-item textarea-component-label text-el">' + labelName + ': </label>' +
                                      '<textarea id="' + property.id + '" name="' + property.name + '" class="property-item textarea-component-item textarea-component-text" rows="3" ' +
                                        isRequired + '>' + propertyVal + '</textarea>' +
                                  '</div>' +
                              '</div>'
                $propertyLastElem.after $elem
              when 'email'
                $elem.find('input').attr 'type', 'email'
                $propertyLastElem.after $elem
            $newElem = $propertyLastElem.next()
            inputWidth = $newElem.width() - $newElem.find('.input-component-label').width() - 10
            $iconElem = $newElem.find('.select-popup-icon')
            if $iconElem.length > 0
              inputWidth = inputWidth - $iconElem.width()
            $newElem.find('input').css('width', inputWidth)
          else
            property.value = telValue

      ###
      # Set textarea width
      ###
      $('.mb-textarea-component').forEach (elem) ->
        $elem = $(elem)
        $elem.find('textarea').width($elem.width() - $elem.find('label').width() - 10)

      ###
      # click event to display radio component
      ###
      $('.mb-component-radio').forEach (elem) ->

        if $(':focus').length isnt 0
          $(':focus').forEach (elem) ->
            $(elem).trigger('blur')
        hammer = new Hammer.Manager elem
        hammer.add new Hammer.Tap { event: 'singletap' }

        hammer.on 'singletap', (event) ->
          $target = $(event.target)
          if not $target.hasClass 'mb-component-radio'
            $target = $target.parent('.mb-component-radio')
          $presentRadioPickerTarget = $target
          propertyName = $target.find('input').attr 'name'
          labelName = propertyName
          labelName = defaultPropertyName[propertyName] if defaultPropertyName[propertyName]
          $wmRadioContainer.find('.radio-pane-header').text(labelName)
          property = getPropertyByName properties, propertyName if propertyName and getPropertyByName properties, propertyName
          if property.options
            $options = $wmRadioContainer.find('.radio-content-options')
            $options.empty()
            property.options.forEach (option) ->
              isChecked = ''
              if property.value and option is property.value
                isChecked = 'icon-checked'
              if propertyName is 'gender' and option is 'male'
                $options.append '<div class="radio-content-item" data-option-value="' + option + '"><span class="icon-radio ' + isChecked + '"></span>男</div>'
              else if propertyName is 'gender' and option is 'female'
                $options.append '<div class="radio-content-item" data-option-value="' + option + '"><span class="icon-radio ' + isChecked + '"></span>女</div>'
              else if propertyName isnt 'gender'
                $options.append '<div class="radio-content-item" data-option-value="' + option + '"><span class="icon-radio ' + isChecked + '"></span>' + option + '</div>'
          $wmRadioContainer.show()
          return

      ###
      # click event to display checkbox component
      ###
      $('.mb-component-checkbox').forEach (elem) ->
        if $(':focus').length isnt 0
          $(':focus').forEach (elem) ->
            $(elem).trigger('blur')

        hammer = new Hammer.Manager elem
        hammer.add new Hammer.Tap { event: 'singletap' }

        hammer.on 'singletap', (event) ->
          $target = $(event.target)
          if not $target.hasClass 'mb-component-checkbox'
            $target = $target.parent('.mb-component-checkbox')
          $presentCheckboxPickerTarget = $target
          propertyName = $target.find('input').attr 'name'
          $target.find('input').focus()
          property = getPropertyByName properties, propertyName if propertyName and getPropertyByName properties, propertyName
          if property.options
            $options = $wmCheckboxContainer.find('.checkbox-content-options')
            $options.empty()
            property.options.forEach (option) ->
              isChecked = ''
              if property.value and $.inArray(option, property.value) isnt -1
                isChecked = 'icon-checked'
              $options.append '<div class="checkbox-content-item"><span class="icon-checkbox ' + isChecked + '"></span>' + option + '</div>'
          $wmCheckboxContainer.show()
          return

      ###
      # click event to display date component
      ###
      $('.mb-component-date').forEach (elem) ->
        if $(':focus').length isnt 0
          $(':focus').forEach (elem) ->
            $(elem).trigger('blur')

        hammer = new Hammer.Manager elem
        hammer.add new Hammer.Tap { event: 'singletap' }

        hammer.on 'singletap', (event) ->
          $target = $(event.target)
          if not $target.hasClass 'mb-component-date'
            $target = $target.parent('.mb-component-date')
          $presentDatePickerTarget = $target

          propertyName = $target.find('input').attr 'name'
          $target.find('input').focus()

          dateStr = $target.find('input').val()
          # if birthday property has value, then can select date
          if $target.data('has-value') and propertyName is 'birthday'
            return
          propertyDate = if not dateStr or dateStr is '' then moment() else moment(dateStr, 'YYYY年MM月DD日')

          presentYear = propertyDate.year()
          presentMonth = propertyDate.month() + 1
          presentDay = propertyDate.date()

          # init calendar component year options
          changeYearOptions presentYear
          # init calendar component month options
          changeMonthOptions presentMonth
          # init calendar component day options
          changeDayOptions presentYear, presentMonth, presentDay

          $wmDateContainer.show()
          return
    return

###
# Get property value according property name
###
getPropertyValueByName = (properties, name) ->
  value = null
  if properties and properties.length isnt 0
    properties.forEach (item) ->
      if item.name is name
        value = item.value if item.value
  return value

###
# Get property according property name
###
getPropertyByName = (properties, name) ->
  property = null
  if properties and properties.length isnt 0
    properties.forEach (item) ->
      if item.name is name
        property = item
  return property

###
# Set property value according name and value
###
setPropertyValueByName = (properties, name, value) ->
  if properties and properties.length isnt 0
    properties.forEach (item) ->
      if item.name is name
        property = getPropertyByName properties, name if name and getPropertyByName properties, name
        item.value = value if property.type isnt 'checkbox' and property.type isnt 'radio' and property.type isnt 'date'
        if property.type is 'date'
          item.value = moment(value, 'YYYY年MM月DD日').valueOf() if value and value isnt ''
  return

###
# Get elements relative to the height of the window
###
getPositionTop = (elem) ->
  offset = elem.offsetTop
  if elem?.offsetParent
    offset += getPositionTop elem.offsetParent
  offset

###
# Scroll to first error message element
###
scrollToFirstErrorElem = ->
  $errors = $('.member-center-error-msg')
  if $errors.length > 0
    top = getPositionTop $errors[0].parentNode
    setTimeout ->
      $('body').scrollTop top
    , 100

###
# click event to update member information
###
hammer = new Hammer.Manager $('#btnUpdateMember')[0]
hammer.add new Hammer.Tap { event: 'singletap' }

hammer.on 'singletap', (event) ->
  $btnUpdate = $('#btnUpdateMember')

  if not checkFormData()
    scrollToFirstErrorElem()
    return false

  # Avoid duplication
  if $btnUpdate.hasClass('mb-buttom-disabled')
    return false

  $btnUpdate.addClass 'mb-buttom-disabled'
  updateProperties = []
  properties.forEach (item, index) ->
    if item.value? and item.value isnt ''
      updateProperty = {
        id: item.id
        type: item.type
        name: item.name
        value: item.value
      }
      if item.type is 'checkbox'
        if item.value.length isnt 0
          updateProperties.push updateProperty
      else
        updateProperties.push updateProperty
  condition =
    memberId: member.id
    properties: updateProperties
  $.ajax {
    type: 'POST'
    url: '/api/member/member/personal'
    data: JSON.stringify condition
    dataType: 'json'
    success: (data) ->
      if redirectUrl
        redirectUrl = decodeURIComponent(redirectUrl)
        str = '?'
        if redirectUrl.indexOf('?') isnt -1
          str = '&'
        window.location.href = redirectUrl + str + 'memberId=' + member.id
      else
        window.location.href = '/mobile/member/center?memberId=' + member.id
    error: (xMLHttpRequest, errorType, error) ->
      if xMLHttpRequest.status is 440
        response = $.parseJSON xMLHttpRequest.response if xMLHttpRequest.response
        for key, value of $.parseJSON response.message
          displayErrorMsg $('#' + key), value
      $btnUpdate.removeClass 'mb-buttom-disabled'
  }

###
# init or swipe to change dispaly year options
###
changeYearOptions = (activedYear) ->
  $yearOptions = $wmDateContainer.find '.year-options'
  $yearOptions.empty()
  for i in [-15..15]
    yearOptionsItem = ''
    if i isnt 0
      yearOptionsItem = '<li data-value=' + (activedYear - i) + '>' + (activedYear - i) + '年</li>'
    else
      yearOptionsItem = '<li data-value=' + (activedYear - i) + ' class="wm-calendar-active">' + (activedYear - i) + '年</li>'
    $yearOptions.prepend yearOptionsItem
  $yearOptions.css 'margin-top', -calendarItemHeight * 13 + 'px'
  return

###
# init or swipe to change dispaly month options
###
changeMonthOptions = (activedMonth) ->
  $monthOptions = $wmDateContainer.find '.month-options'
  $monthOptions.empty()
  for i in [-6..5]
    monthMun = (12 + activedMonth - i) % 12
    monthMun = 12 if monthMun is 0
    if monthMun is 0
      monthStr = '12'
    else if monthMun < 10
      monthStr = '0' + monthMun
    else
      monthStr = '' + monthMun
    monthOptionsItem = ''
    if i isnt 0
      monthOptionsItem = '<li data-value=' + monthMun + '>' + monthStr + '月</li>'
    else
      monthOptionsItem = '<li data-value=' + monthMun + ' class="wm-calendar-active">' + monthStr + '月</li>'
    $monthOptions.prepend monthOptionsItem
  $monthOptions.css 'margin-top', -(calendarItemHeight * 3) + 'px'

###
# init or swipe to change dispaly day options
###
changeDayOptions = (activedYear, activedMonth, activedDay) ->
  countDays = new Date(activedYear,activedMonth,0).getDate()
  $dayOptions = $wmDateContainer.find '.day-options'
  $dayOptions.empty()
  for i in [3 - Math.floor(countDays / 2)..2 + Math.ceil(countDays / 2)]
    dayMun = (countDays + activedDay - i) % countDays
    dayMun = countDays if dayMun is 0
    if dayMun is 0
      dayStr = '' + countDays
    else if dayMun < 10
      dayStr = '0' + dayMun
    else
      dayStr = '' + dayMun
    dayOptionsItem = ''
    if i isnt 0
      dayOptionsItem = '<li data-value=' + dayMun + '>' + dayStr + '日</li>'
    else
      dayOptionsItem = '<li data-value=' + dayMun + ' class="wm-calendar-active">' + dayStr + '日</li>'
    $dayOptions.prepend dayOptionsItem
    $dayOptions.css 'margin-top', (Math.floor(countDays / 2) - countDays) * calendarItemHeight + 'px'

###
# click event to select member property which type is radio
###
hammer = new Hammer.Manager $wmRadioContainer[0]
hammer.add new Hammer.Tap { event: 'singletap' }

hammer.on 'singletap', (event) ->
  $target = $(event.target)
  if $target.attr('id') is 'wmRadioPicker'
    $wmRadioContainer.hide()
  else
    if $target.hasClass 'radio-pane-cannel'
      $wmRadioContainer.hide()
    else if $target.hasClass 'radio-content-item'
      targetText = $target.text()
      $checkboxInput = $presentRadioPickerTarget.find('input')
      propertyName = $checkboxInput.attr 'name'
      property = getPropertyByName properties, propertyName if propertyName and getPropertyByName properties, propertyName
      property.value = $target.data('option-value')
      $checkboxInput.val(targetText)
      $wmRadioContainer.hide()
  return

###
# click event to select member property which type is checkbox
###
hammer = new Hammer.Manager $wmCheckboxContainer[0]
hammer.add new Hammer.Tap { event: 'singletap' }

hammer.on 'singletap', (event) ->
  $target = $(event.target)
  if $target.attr('id') is 'wmCheckboxPicker'
    $wmCheckboxContainer.hide()
  else
    if $target.hasClass 'checkbox-operation-cannel'
      $wmCheckboxContainer.find('.icon-checkbox').forEach (elem) ->
        $(elem).removeClass 'icon-checked'
      $wmCheckboxContainer.hide()
    else if $target.hasClass 'checkbox-operation-submit'
      textValue = ''
      $options = $wmCheckboxContainer.find('.checkbox-content-item')
      $checkboxInput = $presentCheckboxPickerTarget.find('input')
      propertyName = $checkboxInput.attr 'name'
      property = getPropertyByName properties, propertyName if propertyName and getPropertyByName properties, propertyName
      property.value = []

      length = $options.length
      $options.forEach (elem, index) ->
        $elem = $(elem)
        if $elem.find('.icon-checkbox').hasClass 'icon-checked'
          if index is (length - 1)
            textValue += $elem.text()
          else
            textValue += $elem.text() + ' '
          property.value.push $elem.text()

      $checkboxInput.val textValue
      $wmCheckboxContainer.hide()
    else if $target.hasClass 'checkbox-content-item'
      if $target.find('.icon-checkbox').hasClass 'icon-checked'
        $target.find('.icon-checkbox').removeClass 'icon-checked'
      else
        $target.find('.icon-checkbox').addClass 'icon-checked'
  return


###
# click event to sure selected property which type is date
###
hammer = new Hammer.Manager $wmDateContainer[0]
hammer.add new Hammer.Tap { event: 'singletap' }

hammer.on 'singletap', (event) ->
  $target = $(event.target)
  if $target.attr('id') is 'wmDatePicker'
    $wmDateContainer.hide()
  else
    if $target.hasClass 'calendar-operation-cannel'
      $wmDateContainer.hide()
    else if $target.hasClass 'calendar-operation-submit'
      $yearOptions = $wmDateContainer.find '.year-options'
      $monthOptions = $wmDateContainer.find '.month-options'
      $dayOptions = $wmDateContainer.find '.day-options'

      # Set birthday
      presentYear = $yearOptions.find('.wm-calendar-active').data 'value'
      presentMonth = $monthOptions.find('.wm-calendar-active').data 'value'
      presentDay = $dayOptions.find('.wm-calendar-active').data 'value'

      showBirthdayStr = $yearOptions.find('.wm-calendar-active').text() + $monthOptions.find('.wm-calendar-active').text() + $dayOptions.find('.wm-calendar-active').text()

      $presentDatePickerTarget.find('input').val showBirthdayStr
      $wmDateContainer.hide()
  return

###
# swipe calendar component to change calendar options
###
swipeCalendar = ($options, increment) ->

  propertyName = $presentDatePickerTarget.find('input').attr 'name'
  currentDate = moment()
  currentYear = currentDate.year()
  currentMonth = currentDate.month() + 1
  currentDay = currentDate.date()

  if $options.hasClass('year-options')
    presentYear = presentYear + increment
    if propertyName is 'birthday'
      if presentYear > currentYear
        presentYear = currentYear
      if presentMonth + presentDay / 100 > currentMonth + currentDay / 100
        if presentYear is currentYear
          presentYear = currentYear - increment
    changeYearOptions presentYear

    countDays = new Date(presentYear,presentMonth,0).getDate()
    if presentDay > countDays
      presentDay = countDays
    else if presentDay <= 0
      presentDay = presentDay + countDays
    changeDayOptions presentYear, presentMonth, presentDay

  else if $options.hasClass('month-options')
    presentMonth = presentMonth + increment
    if propertyName is 'birthday'
      if presentYear is currentYear
        if presentDay > currentDay and presentMonth is currentMonth
          presentMonth = presentMonth - increment
        if presentMonth > currentMonth or presentMonth is 0
          presentMonth = presentMonth - increment
    if presentMonth <= 0
      presentMonth = presentMonth + 12
    else if presentMonth > 12
      presentMonth = presentMonth - 12
    changeMonthOptions presentMonth

    countDays = new Date(presentYear,presentMonth,0).getDate()
    if presentDay > countDays
      presentDay = countDays
    else if presentDay <= 0
      presentDay = presentDay + countDays
    changeDayOptions presentYear, presentMonth, presentDay

  else if $options.hasClass('day-options')
    countDays = new Date(presentYear,presentMonth,0).getDate()
    presentDay = presentDay + increment
    if propertyName is 'birthday'
      if presentYear is currentYear and presentMonth is currentMonth
        if presentDay > currentDay or presentDay is 0
          presentDay = presentDay - increment
    if presentDay is 0
      presentDay = countDays
    else if presentDay > countDays
      presentDay  = 1
    changeDayOptions presentYear, presentMonth, presentDay

###
# According form data to set properties value and check form data is reasonable
###
checkFormData = ->
  flag = true
  $elems = $('#updateMemberForm').find('input')
  $elems.forEach (elem) ->
    $elem = $(elem)
    setPropertyValueByName properties, $elem.attr('name'), $elem.val() if properties
    if $elem.val()
      if $elem.attr('type') is 'email'
        if not isEmail $elem.val()
          flag = false
          displayErrorMsg $elem, '邮箱格式不正确'
      if $elem.attr('name') is 'name'
        if $elem.val().length < 2 or $elem.val().length > 30
          flag = false
          displayErrorMsg $elem, '请输入2-30个字符'
    else
      if $elem.prop('required')
        flag = false
        displayErrorMsg $elem, '请填写此字段'

  $elems = $('#updateMemberForm').find('textarea')
  $elems.forEach (elem) ->
    $elem = $(elem)
    setPropertyValueByName properties, $elem.attr('name'), $elem.val() if properties and $elem.val()
    if not $elem.val()
      if $elem.prop('required')
        flag = false
        displayErrorMsg $elem, '请填写此字段'
  return flag

###
# Show error message when the form data isn't reasonable
###
displayErrorMsg = ($elem, msg) ->
  if $elem.parents('.mb-personal-item').find('.member-center-error-msg').length is 0
    $elem.parent().css {
      'box-shadow': '0px 0px 7px rgba(180, 45, 20, 0.24)'
      'border-color': '#b42d14'
    }
    $elem.parent().after('<span class="member-center-error-msg">' + msg + '</span>')
    setTimeout ->
      $elem.parent().css {
        'box-shadow': '0px 0px 7px rgba(193, 193, 193, 0.24)'
        'border': '1px #d8d8d8 solid'
      }
      $elem.parents('.mb-personal-item').find('.member-center-error-msg').remove()
    , 3000

###
# validation email
###
isEmail = (str) ->
  reg = /^[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Za-z]{2,6}$/
  return reg.test str

stopDefault = (e) ->
  if e and e.preventDefault
    e.preventDefault()
  if e and e.stopPropagation
    e.stopPropagation()
  if window.event.returnValue
    window.event.returnValue = false
  return false

$options = $wmDateContainer.find('ul')

$options.forEach (elem) ->
  hammer = new Hammer elem, { domEvents: true, prevent_default: true}
  hammer.get('pan').set { direction: Hammer.DIRECTION_VERTICAL }

  hammer.on 'panup pandown', (event) ->
    $target = $(event.target)
    $options = $target
    if $target[0].nodeName is 'LI'
      $options = $target.parent()

    switch event.type
      when 'panup'
        increment = 1
        swipeCalendar $options, increment
      when 'pandown'
        increment = -1
        swipeCalendar $options, increment
    event.preventDefault()

###touch.on $options, 'swiping', (event)->
  $target = $(event.target)
  $options = $target

  if $target[0].nodeName is "LI"
    $options = $target.parent()

  $options.find("li.wm-calendar-active").removeClass("wm-calendar-active")
  if event.y > calendarItemHeight
    $options.find("li.wm-calendar-active").next().addClass("wm-calendar-active")

  $options.find("li").forEach (elem)->
    $elem = $(elem)
    elem.style.webkitTransition = "all ease 0.2s"
    elem.style.webkitTransform = "translate3d(0," + event.y + "px,0)"

  $options.css "margin-top", (parseInt($options.css("margin-top").slice(0, -2))+event.y) + "px"###

_init()
