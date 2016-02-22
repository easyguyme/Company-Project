define [
  'core/coreModule'
], (mod) ->
  mod.filter 'countdown', [
    '$filter'
    ($filter) ->
      (millisecond, type) ->
        expirationTime = ''
        if type is 1
          if millisecond > 0
            day = Math.floor millisecond / (1000 * 60 * 60 * 24)
            hour = (Math.floor millisecond / (1000 * 60 * 60)) % 24
            minute = (Math.floor millisecond / (1000 * 60)) % 60

            expirationTime = $filter('translate')('management_expiration_time')
            if day > 0
              expirationTime += day  + ' ' + $filter('translate')('management_unit_day') + ' '
            if hour > 0 or (hour is 0 and day > 0 and minute > 0)
              expirationTime += hour  + ' ' + $filter('translate')('management_unit_hour') + ' '
            if minute > 0
              expirationTime += minute  + ' ' + $filter('translate')('management_unit_minute')

          else
            expirationTime = $filter('translate')('management_account_expired')
        else if type is 2
          interactTime = new Date millisecond
          overplusMillisecond = new Date() .getTime() - millisecond
          day = Math.floor overplusMillisecond / (1000 * 60 * 60 * 24)
          hour = (Math.floor overplusMillisecond / (1000 * 60 * 60)) % 24
          minute = (Math.floor overplusMillisecond / (1000 * 60)) % 60

          interactMonth = if interactTime.getMonth() + 1 < 10 then '0' + (interactTime.getMonth() + 1).toString() else (interactTime.getMonth() + 1).toString()
          interactDate = if interactTime.getDate() < 10 then '0' + interactTime.getDate().toString() else interactTime.getDate().toString()
          interactHour = if interactTime.getHours() < 10 then '0' + interactTime.getHours().toString() else interactTime.getHours().toString()
          interactMinute = if interactTime.getMinutes() < 10 then '0' + interactTime.getMinutes().toString() else interactTime.getMinutes().toString()

          if day is 0 and hour is 0 and minute is 0
            expirationTime = $filter('translate')('just_now')
          else if day is 0 and hour is 0 and minute > 0
            expirationTime = $filter('translate')('today') + ' ' + interactHour + ':' + interactMinute
          else if day is 1 and hour is 0 and minute > 0
            expirationTime = $filter('translate')('yesterday') + ' ' + interactHour + ':' + interactMinute
          else if day is 2 and hour is 0 and minute > 0
            expirationTime = $filter('translate')('before_yesterday') + ' ' + interactHour + ':' + interactMinute
          else
            expirationTime = interactMonth + $filter('translate')('unit_month') + interactDate + $filter('translate')('unit_day') + ' ' + interactHour + ':' + interactMinute
        expirationTime
  ]
