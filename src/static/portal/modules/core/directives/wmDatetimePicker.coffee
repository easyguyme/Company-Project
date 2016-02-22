define [
  'core/coreModule'
  ], (mod) ->
    mod.directive('wmDatetimePicker', [
      '$timeout'
      'validateService'
      'judgeDeviceService'
      '$filter'
      '$rootScope'
      ($timeout, validateService, judgeDeviceService, $filter, $rootScope) ->
        return (
          restrict: 'EA'
          replace: true
          scope:
            pickerId: '@'
            formatType: '@'
            placeholder: '@'
            minDatePickerId: '@'
            maxDatePickerId: '@'
            ngModel: '='
            timeHandler: '&'
            hideHandler: '&'
            lessThanYesterday: '@'
            lessThanToday: '@'
            moreThanToday: '@'
            requiredField: '@'
            icon: '@'
            viewMode: '@'
            config: '='
            isDisabled: '@'
          template: '<div class="input-group date date-time-picker-div form-group">
                        <label></label>
                        <input id="{{pickerId}}" onkeydown="return false;" type="text" class="form-control date-time-picker-input" ng-class="{\'cp\': isDisabled != \'true\'}"
                          placeholder="{{placeholder || \'\' | translate}}" required="true" without-star ng-disabled="isDisabled == \'true\'" readonly="readonly"/>
                        <span class="input-group-addon cp date-time-picker-span">
                          <span class="glyphicon glyphicon-time"></span>
                        </span>
                    </div>'
          link: (scope, elem, attrs) ->
            rvm = $rootScope

            # isFirstLoad = true
            scope.isShowWidget = false
            # is used click calendar to change ngModel
            scope.isClickPicker = false

            $elem = $(elem)
            $elem.find('label').remove()
            $('.date-time-picker-div').removeClass('form-group')
            $datetimePickerWidget = $elem.find('input')
            $datetimePickerWidget.removeAttr('readonly') if not judgeDeviceService.isMobile()
            $datetimePickerWidget.removeAttr('required') if not scope.requiredField

            if scope.icon
              $elem.find('.glyphicon').attr 'class', "glyphicon glyphicon-#{scope.icon}"
            else if scope.formatType and scope.formatType.toUpperCase() is 'YYYY-MM-DD'
              $elem.find('.glyphicon').attr 'class', 'glyphicon glyphicon-calendar'

            formatType = 'YYYY-MM-DD HH:mm:ss'
            formatType = scope.formatType if scope.formatType

            # Set datetime picker local
            language = rvm.user.language or 'zh_cn'
            language = 'zh_tw' if language is 'zh_tr'
            language = language.replace /_/g, '-'

            options =
              format: formatType
              locale: language
              ignoreReadonly: judgeDeviceService.isMobile()
            config = scope.config if angular.isObject(scope.config) and not $.isEmptyObject(scope.config)

            options = $.extend options, config if config

            options.viewMode = scope.viewMode if scope.viewMode
            $datetimePickerWidget.datetimepicker(options)

            picker = $datetimePickerWidget.data('DateTimePicker')

            safeApply = (scope, fn) ->
              phase = if scope.$root then scope.$root.$$phase else ''
              if phase is '$apply' or phase is '$digest'
                fn() if fn and ( typeof fn is 'function')
              else
                scope.$apply(fn)

            removeError = ->
              validateService.restore($datetimePickerWidget)
              validateService.restore($('#' + scope.minDatePickerId)) if scope.minDatePickerId
              validateService.restore($('#' + scope.maxDatePickerId)) if scope.maxDatePickerId
              return

            checkError = ->
              if scope.minDatePickerId and scope.ngModel
                $compareElem = $('#' + scope.minDatePickerId)
                inputTimestamp = moment(moment(scope.ngModel).format(formatType)).valueOf()
                if $compareElem.val()
                  compareTimestamp = moment($compareElem.val()).valueOf()
                  if inputTimestamp > compareTimestamp
                    validateService.highlight($datetimePickerWidget, $filter('translate')('helpdesk_setting_time_error'))
                    validateService.highlight($compareElem, '')
                  else
                    removeError()

              if scope.maxDatePickerId and scope.ngModel
                $compareElem = $('#' + scope.maxDatePickerId)
                inputTimestamp = moment(moment(scope.ngModel).format(formatType)).valueOf()
                if $compareElem.val()
                  compareTimestamp = moment($compareElem.val()).valueOf()
                  if inputTimestamp < compareTimestamp
                    validateService.highlight($compareElem, $filter('translate')('helpdesk_setting_time_error'))
                    validateService.highlight($datetimePickerWidget, '')
                  else
                    removeError()

            ###
            # check this datetime picker wedget value is suitable or not
            ###
            checkDate = ->
              datetimeStr = $datetimePickerWidget.val()
              if datetimeStr is ''
                scope.ngModel = null
                picker.date(null)
              else
                if moment(datetimeStr, formatType).format(formatType) is 'Invalid date'
                  restoreLastPickerValue()
                else
                  flag = true
                  inputTimestamp = moment(moment(datetimeStr).format(formatType)).valueOf()

                  if scope.lessThanYesterday is 'true'
                    compareTimestamp = moment(moment().subtract(1, 'days').format(formatType)).valueOf()
                    if inputTimestamp > compareTimestamp
                      restoreLastPickerValue()
                      flag = false
                  if scope.lessThanToday is 'true'
                    compareTimestamp = moment(moment().format(formatType)).valueOf()
                    if inputTimestamp > compareTimestamp
                      restoreLastPickerValue()
                      flag = false
                  if scope.moreThanToday is 'true'
                    compareTimestamp = moment(moment().format(formatType)).valueOf()
                    if inputTimestamp < compareTimestamp
                      restoreLastPickerValue()
                      flag = false
                  if scope.minDatePickerId
                    $compareElem = $('#' + scope.minDatePickerId)
                    if $compareElem.val()
                      compareTimestamp = moment($compareElem.val()).valueOf()
                      if inputTimestamp > compareTimestamp
                        restoreLastPickerValue()
                        flag = false
                  if scope.maxDatePickerId
                    $compareElem = $('#' + scope.maxDatePickerId)
                    if $compareElem.val()
                      compareTimestamp = moment($compareElem.val()).valueOf()
                      if inputTimestamp < compareTimestamp
                        restoreLastPickerValue()
                        flag = false

                  if flag
                    scope.ngModel = moment(moment(datetimeStr).format(formatType)).valueOf()
                    $datetimePickerWidget.val(moment(datetimeStr).format(formatType))
                    picker.hide() if scope.isShowWidget
              return

            ###
            # restore last datetime picker widget value
            ###
            restoreLastPickerValue = ->
              scope.ngModel = scope.ngModel or null
              if scope.ngModel
                $datetimePickerWidget.val(moment(scope.ngModel).format(formatType))
              else
                picker.date(null)
              return

            ###
            # todo, set date when user fill the input
            ###
            scope.blurDatePicker = ->
              checkDate()

            ###
            # check date when the user press the enter key and delete key
            ###
            scope.enterDatePicker = ($event) ->
              if $event.which is 13
                checkDate()
                $event.stopPropagation()
              else if $event.which is 8
                picker.hide() if scope.isShowWidget

            $datetimePickerWidget.on 'dp.change', (e) ->
              safeApply scope, ->
                scope.ngModel = picker.date().valueOf() if picker.date()?

              # if scope.timeHandler and not isFirstLoad
              if scope.timeHandler
                safeApply scope, ->
                  scope.timeHandler()

            ###
            # according user chosen language to modiy datetime picker locale
            ###
            rvm.$on '$translateChangeSuccess', (event, data) ->
              options = picker.options()
              data.language = 'zh_tw' if data.language is 'zh_tr'
              data.language = data.language.replace /_/g, '-'
              options.locale = data.language
              picker.options(options)

            scope.$watch 'config', (newVal, oldVal) ->
              if newVal
                options = picker.options()
                options = $.extend options, newVal
                picker.options(options)
            , true

            scope.$watch 'ngModel', (newVal, oldVal) ->
              if not newVal?
                picker.date(null)
              else
                newVal = parseInt(newVal) if angular.isString newVal
                newVal = moment(newVal) if angular.isNumber newVal

                $('#' + scope.minDatePickerId).data('DateTimePicker').minDate(newVal) if scope.minDatePickerId and $('#' + scope.minDatePickerId).length
                $('#' + scope.maxDatePickerId).data('DateTimePicker').maxDate(newVal) if scope.maxDatePickerId and $('#' + scope.maxDatePickerId).length

                picker.date(moment(newVal).format(formatType)) if not scope.isClickPicker

                checkError()

            $datetimePickerWidget.on 'dp.show', (e) ->
              #make canlendar choose more than today or less than today
              picker.maxDate(false)
              picker.minDate(false)
              picker.maxDate(moment().subtract(1, 'days')) if scope.lessThanYesterday is 'true'
              picker.maxDate(moment()) if scope.lessThanToday is 'true'
              picker.minDate(moment()) if scope.moreThanToday is 'true'

              # limit input invalid date
              if scope.maxDatePickerId
                $compareElem = $('#' + scope.maxDatePickerId)
                if $compareElem.val()
                  compareTimestamp = moment($compareElem.val()).valueOf()
                  picker.minDate(moment(compareTimestamp))
              if scope.minDatePickerId
                $compareElem = $('#' + scope.minDatePickerId)
                if $compareElem.val()
                  compareTimestamp = moment($compareElem.val()).valueOf()
                  picker.maxDate(moment(compareTimestamp))

              # isFirstLoad = false

              removeError()
              scope.isClickPicker = true
              $timeout (->
                scope.isShowWidget = true
              ), 500
              return

            $datetimePickerWidget.on 'dp.error', (e) ->
              datePicker = $datetimePickerWidget.data('DateTimePicker')
              minDate = datePicker.minDate()
              maxDate = datePicker.maxDate()
              if e.date < minDate
                datePicker.date(moment(minDate).clone().add(1, 'seconds'))
              if e.date > maxDate
                datePicker.date(moment(maxDate).clone().subtract(1, 'seconds'))

            $datetimePickerWidget.on 'dp.hide', (e) ->
              scope.isClickPicker = false
              $timeout (->
                scope.isShowWidget = false
              ), 500

              $datetimePickerWidget.trigger('blur')

              checkError()
              scope.hideHandler() if scope.hideHandler
              return

            ###
            # click datetime picker widget to hide the picker if the picker showing
            ###
            $datetimePickerWidget.click(->
              if scope.isShowWidget
                $datetimePickerWidget.data('DateTimePicker').hide()
            )

        )
    ])
