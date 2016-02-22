<form-group>
  <li class="c-form-group">
    <div class="c-form-group__icon" if={ !!icon } style="background-image:url({ icon })"></div>
    <div class="c-form-group__content" if={ !!type && ((!!subtype && ['date', 'time', 'datetime'].indexOf(subtype) === -1) || !subtype) }>
      <input class="c-input" if={ type === 'text' && !!subtype && subtype !== 'location'} type="{ subtype }" name="{ name }" value="{ value }" placeholder="{ placeholder }" onfocus={ resetTip } onblur={ validate }/>
      <input class="c-input" if={ type === 'text' && !!subtype && subtype === 'location'} type="{ subtype }" name="{ name }" value="{ value }" placeholder="{ placeholder }" readonly onclick={ clickHandler }/>
      <textarea class="c-textarea" if={ type === 'textarea' } name="{ name }" value="{ value }" placeholder="{ placeholder }" onfocus={ resetTip } onblur={ validate }></textarea>
    </div>
    <div class="c-form-group__content" if={ !!type && !!subtype && ['date', 'time', 'datetime'].indexOf(subtype) != -1}>
      <input if={ !hourOnly } class="c-input c-input--datetime" type="{ subtype === 'datetime' ? subtype + '-local' : subtype }" name="{ name }" value="{ value }" placeholder="{ placeholder }" onchange={ changeDatetimeHandler } if={ isIOS }/>
      <input if={ !hourOnly } class="c-input c-input--datetime" type="{ subtype === 'datetime' ? subtype + '-local' : subtype }" name="{ name }" value="{ value }" placeholder="{ placeholder }" onblur={ handleDateTimeChange } onclick={ handleDateTimeClick } if={ !isIOS }/>
      <input if={ !hourOnly } class="c-input" type="text" name="{ name }_placeholder" value="{ displayValue }" placeholder="{ placeholder }"/>
      <input if={ hourOnly } class="c-input" data-field="datetime" type="text" name="{ name }" placeholder="{ placeholder }" value={ value } readonly />
      <div if={ hourOnly } class="dtBox datepicker-hour"></div>
    </div>
    <div class="c-form-group__expand" if={ !!subtype && ['date', 'time', 'datetime', 'location'].indexOf(subtype) != -1 } onclick={ clickHandler }></div>
  </li>
  <span class="c-form-tip c-form-tip--error" if={ !!errortip }>{ errortip }</span>

  <script>
    var self = this, _init, _packageWidget, _initDatePicker, _isFunction, _parseDatetimeValue, dateTypes;

    const C_FUNCTION = 'function';
    const C_STRING = 'string';
    const C_DATE = 'date';
    const REQUIRED_TIP = '请填写此字段';


    _initDatePicker = () => {
      $(document).ready(function() {
        var options = {
          dateTimeFormat: 'MM-dd-yyyy HH:mm',
          language: 'zh-CN',
          buttonsToDisplay: ['SetButton', 'ClearButton'],
          minDateTime: self.minDateTime,
          settingValueOfElement: function(value) {
            if (value) {
              self.errortip = '';
              self.update();
            }
          }
        }
        $('.dtBox').DateTimePicker(options);
      });
    }

    _init = () => {
      dateTypes = ['date', 'time', 'datetime'];

      if (self.hourOnly) {
        _initDatePicker();
      } else {
        _packageWidget();
      }

    }

    _packageWidget = () => {
      self.isIOS = false;
      if(_isFunction(util.detect) && _isFunction(util.detect().isIOS)) {
        self.isIOS = util.detect().isIOS();
      }

      if (['date', 'time', 'datetime'].indexOf(self.subtype) != -1 && self.value) {
        self.displayValue = _parseDatetimeValue(self.value);
      }

      self.update()
    }

    _isFunction = (fuc) => {
      return fuc && typeof fuc === C_FUNCTION;
    }

    _parseDatetimeValue = (value) => {
      var parseDate, timeoffset, valueType;

      parseDate = value;

      if (value) {
        timeoffset = new Date().getTimezoneOffset() / 60;
        valueType = Object.prototype.toString.call(value).slice(8, -1).toLowerCase();

        if (valueType !== C_DATE) {
          parseDate = new Date(parseDate)
        }

        parseDate = parseDate.valueOf() + timeoffset * 3600 * 1000;
        if (util.dateFormat && _isFunction(util.dateFormat)) {
          parseDate = util.dateFormat(new Date(parseDate), 'yyyy-MM-dd hh:mm');
        }
      }

      return parseDate;
    }


    this.showError = (item, msg) => {
      if(typeof(item) === C_STRING) {
        msg = item;
        item = self;
      }
      msg = msg || REQUIRED_TIP;
      item.errortip = msg;

      if(item.update && typeof(item.update) === C_FUNCTION) {
        item.update();
      }
    }

    this.restore = (item) => {
      item = item || self;
      item.errortip = '';

      if(item.update && typeof(item.update) === C_FUNCTION) {
        item.update();
      }
    }

    if(typeof(this.validate) !== C_FUNCTION) {
      this.validate = (e) => {
        if (this.required) {
          if(self[e.item.name] && !self[e.item.name].value) {
             self.showError(e.item, REQUIRED_TIP);
          }
        }
      }
    }

    this.resetTip = (e) => {
      self.restore(e.item);
    }

    this.changeDatetimeHandler = (e) => {
      self.value = e.item.value = e.target.value;
      self.displayValue = _parseDatetimeValue(self.value);
      if (e.target.value) {
        self.restore(e.item);
      }
    }

    this.handleDateTimeClick = (e) => {
      var datetimeDOM = e.target;
      var oldValue = datetimeDOM.value;

      self.interval = setInterval(() => {
        if (datetimeDOM.value !== oldValue) {
          datetimeDOM.blur();
        }
      }, 200);
    }

    this.handleDateTimeChange = (e) => {
      clearInterval(self.interval);

      self.value = e.item.value = e.target.value;
      self.displayValue = _parseDatetimeValue(self.value);
      if (e.target.value) {
        self.restore(e.item);
      }
    }

    this.on('updated', () => {
      _packageWidget();
    })

    _init();

  </script>
</form-group>
