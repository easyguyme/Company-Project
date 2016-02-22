## wmDatetimePicker
The `wmDatetimePicker` is native AngularJS datetime picker directive styled by Twitter Bootstrap 3.

---

## Directive Info
This directive executes at priority level 0.

---

## Usage
as attribute:
```
<ANY
    wm-datetime-picker
    ng-model=""
    [picker-id=""]
    [format-type=""]
    [placeholder=""]
    [min-date-picker-id=""]
    [max-date-picker-id=""]
    [time-handler=""]
    [hide-handler=""]
    [less-than-yesterday=""]
    [less-than-today=""]
    [more-than-today=""]
    [required-field=""]
    [icon=""]
    [view-mode=""]
    [config=""]
    [is-disabled=""]>
</ANY>
```
as element
```
<wm-slider
    ng-model=""
    [picker-id=""]
    [format-type=""]
    [placeholder=""]
    [min-date-picker-id=""]
    [max-date-picker-id=""]
    [time-handler=""]
    [hide-handler=""]
    [less-than-yesterday=""]
    [less-than-today=""]
    [more-than-today=""]
    [required-field=""]
    [icon=""]
    [view-mode=""]
    [config=""]
    [is-disabled=""]>
</wm-slider>
```

---

## Arguments
Param | Type | Default | Details
----- | ---- | ------- | ----
ngModel                        | `string`     | | Assignable angular expression to data-bind to.
pickerId (*optional*)          | `string`     | | Set the id of the picker widget when it's created.
formatType (*optional*)        | `string`     | 'YYYY-MM-DD HH:mm:ss'     | See [momentjs' docs](http://momentjs.com/docs/#/displaying/format/) for valid formats. Format also dictates what components are shown, e.g. MM/dd/YYYY will not display the time picker.
placeholder (*optional*)       | `string`     | | Set the placeholder of the datetime picker input element.
minDatePickerId (*optional*)   | `string`     | | The id of the linked picker, the linked picker will prevents date/time selections after this picker's value
maxDatePickerId (*optional*)   | `string`     | | The id of the linked picker, the linked picker will prevents date/time selections before this picker's value
timeHandler (*optional*)       | `expression` | | Angular expression to be executed when the date is changed.
hideHandler (*optional*)       | `expression` | | Angular expression to be executed when the picker widget is hidden.
lessThanYesterday (*optional*) | `boolean`    | | Prevents date/time selections before yesterday
lessThanToday (*optional*)     | `boolean`    | | Prevents date/time selections before today
moreThanToday (*optional*)     | `boolean`    | | Prevents date/time selections after today.
requiredField (*optional*)     | `boolean`    | false      | Whether must choose a date or time on this widget
icon (*optional*)              | `string`     | 'calendar' | Change the default icons for the pickers functions, such as 'calendar', 'time'...
viewMode (*optional*)          | `string`     | 'days'     | The default view to display when the picker is shown.
config (*optional*)            | `string`     | | See [datetimepicker' options](http://eonasdan.github.io/bootstrap-datetimepicker/Options/) for valid config options. Set the picker widget config options.
isDisabled (*optional*)        | `boolean`    | false      | Set the picker widget disabled.

---

## Example
html
```
<div wm-datetime-picker ng-model="picker.time"
  picker-id="beginDatePicker"
  format-type="YYYY-MM-DD HH:mm:00"
  min-date-picker-id="endDatePicker"
  more-than-today="true"
  required-field="true"
  is-disabled="true"
  config="picker.config"
  hide-handler="picker.hideHandler"
  time-handler="picker.changeHandler"></div>
```

coffee
```
picker =
  time: null
  config:
    minDate: moment()
    stepping: 1
  hideHandler: ->
    console.log 'hide picker widget'
  changeHandler: ->
    console.log 'change date or time'
```