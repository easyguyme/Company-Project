define [
  'core/coreModule'
], (mod) ->
  mod.filter 'formatdate', [
    '$filter'
    ($filter) ->
      (time) ->
        today = moment().format('YYYY-MM-DD')
        tomorrow = moment().add(1, 'day').format('YYYY-MM-DD')
        yesterday = moment().subtract(1, 'day').format('YYYY-MM-DD')

        if time
          time = moment(time).format('YYYY-MM-DD HH:mm:ss')
          date = time.substring(0,10)
          dateTime = time.substring(11,19)

          switch date
            when today then time = $filter('translate')("broadcast_time_today") + dateTime
            when yesterday then time = $filter('translate')("broadcast_time_yesterday") + dateTime
            when tomorrow then time = $filter('translate')("broadcast_time_tomorrow") + dateTime
            else time = time

        time
  ]

