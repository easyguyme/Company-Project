## wmQuarterPicker
The `wmQuarterPicker` directive allows you to select a quarter of a year.

---

## Directive Info
This directive executes at priority level 0.

---

## Usage
as attribute:
```
<ANY
    wm-quarter-picker
    ng-model=""
    [max-year=""]
    [change-handler="">
</ANY>
```

---

## Arguments
Param | Type | Default | Details
----- | ---- | ------ | ----
ngModel                    | `expression` | |Assignable angular expression to data-bind to.
maxYear (*optional*)       | `number`     | new Date().getFullYear() | Maximum possible value of a year.
changeHandler (*optional*) | `expression` | | Angular expression to be executed when ngModel has changed.

---

## Example
html
```
<div wm-quarter-picker ng-model="quarter.quarterDate" change-handler="quarter.selectQuarter()"></div>
```

coffee
```
quarter =
  quarterDate:
    year: parseInt moment().format('YYYY')
    quarter: Math.ceil(parseInt(moment().format('MM')) / 3)
  selectQuarter: ->
    console.log 'change quarter of a year'
```
