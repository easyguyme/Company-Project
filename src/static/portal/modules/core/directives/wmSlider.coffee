define ['wm/app', 'bootstrapSlider'], (app) ->
  app.registerDirective 'wmSlider', [
    ->
      return (
        replace: true
        restrict: 'EA'
        scope:
          max: "="
          min: "="
          step: "="
          ngModel: '='
          range: '='
          sliderId: '='
          ticks: '='
          ticksLabels: '='
          disable: '@'
          hasHandlerNum: '@'
          formatter: '&'
          onSlideStart: '&'
          onSlideStop: '&'
          onSlide: '&'
          onChange: '&'
        template: '<div class="slider-wrapper">
                    <input class="slider-input" type="text"/>
                   </div>'
        link: (scope, elem, attr) ->
          $elem = $(elem)

          slider = $elem.find('.slider-input').eq(0)

          _initSlider = ->
            options = {}

            setOption = (key, value, defaultValue) ->
              options[key] = value or defaultValue

            setFloatOption = (key, value, defaultValue) ->
              options[key] = if value then parseFloat(value) else defaultValue

            setBooleanOption = (key, value, defaultValue) ->
              options[key] = if value then (value + '' is 'true') else defaultValue

            setArrayOption = (key, value, defaultValue) ->
              options[key] = if value then (value + '' is 'true') else defaultValue

            getArrayOrValue = (value) ->
              if (angular.isString(value) and value.indexOf("[") is 0) then angular.fromJson(value) else value

            setOption('id', scope.sliderid) if scope.sliderid
            setOption('orientation', attr.orientation, 'horizontal')
            setOption('selection', attr.selection, 'before')
            setOption('tooltip', attr.tooltip, 'hide')
            setOption('tooltipseparator', attr.tooltipseparator, '-')
            setOption('ticks', scope.ticks) if scope.ticks
            setOption('ticks_labels', scope.ticksLabels) if scope.ticksLabels

            setOption('formatter', (value) ->
              scope.formatter.call(null, value)
            ) if scope.formatter

            setFloatOption('min', scope.min, 0)
            setFloatOption('max', scope.max, Number.MAX_VALUE)
            setFloatOption('step', scope.step, 1)

            setBooleanOption('tooltip_split', attr.tooltipsplit, false)
            setBooleanOption('enabled', attr.enabled, true)
            setBooleanOption('natural_arrow_keys', attr.naturalarrowkeys, false)
            setBooleanOption('reversed', attr.reversed, false)
            setBooleanOption('focus', attr.canFocus, true)
            setBooleanOption('range', scope.range, false)

            if options.range
              if angular.isArray(scope.ngModel)
                options.value = scope.ngModel
              else if angular.isString(scope.ngModel)
                options.value = getArrayOrValue(scope.ngModel)
                if not angular.isArray(options.value)
                    value = if isNaN(scope.ngModel) then 0 else parseFloat(scope.ngModel)
                    if value < scope.min
                      value = scope.min
                      options.value = [value, options.max]
                    else if value > scope.max
                        value = scope.max
                        options.value = [options.min, value]
                    else
                      options.value = [options.min, options.max]
              else
                options.value = [options.min, options.max]
              scope.ngModel = options.value
            else
              setFloatOption('value', scope.ngModel, 0)

            slider.slider(options)
            slider.slider('enable')
            slider.slider('disable') if scope.disable is 'true'

            scope.$watch 'ngModel', (value) ->
              slider.slider('setValue', value)
              if scope.hasHandlerNum and scope.hasHandlerNum is 'true'
                $handlers = $elem.find('.slider-handle')
                angular.forEach $handlers, (item, index) ->
                  $handle = $ item
                  textVal = ''
                  if angular.isArray(value) and value.length > index
                    textVal = value[index]
                  else
                    textVal = value

                  if scope.ticks and scope.ticksLabels
                    ticksIndex = $.inArray textVal, scope.ticks
                    if ticksIndex is (scope.ticks.length - 1) and isNaN(scope.ticksLabels[ticksIndex])
                      textVal = ''

                  $handle.text textVal

            slideEvents = ['slideStart', 'slide', 'slideStop']
            angular.forEach slideEvents, (prop) ->
              slider.slider().on prop, (ev) ->
                funcStr = 'on' + prop.charAt(0).toUpperCase() + prop.slice(1)
                scope[funcStr]() if scope[funcStr]

            slider.slider().on 'change', (ev) ->
              scope.$apply( ->
                scope.ngModel = angular.copy ev.value.newValue
              )
              scope.onChange() if scope.onChange

          watchers = ['min', 'max', 'step', 'range']
          angular.forEach watchers, (prop) ->
            scope.$watch prop, ->
              _initSlider()

          _initSlider()

          if scope.ticks
            $elem.addClass 'ticks-slider-wrapper'

      )
  ]
