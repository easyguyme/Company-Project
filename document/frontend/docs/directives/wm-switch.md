## wmSwitch
The `wmSwitch` directive is selected switch.

---

## Directive Info
This directive executes at priority level 0.

---

## Usage
as attribute:
```
<div wm-switch=""
    ng-model=""
    on-value=""
    off-value=""
    [is-disabled=""]>
</div>
```
---

## Arguments
Param | Type | Default | Details
----- | ---- | ------ | ----
wmSwitch                | `expression` |  | Angular expression to be executed when the switch clicked.
ngModel                 | `string`     |  | Assignable angular expression to data-bind to.
onValue                 | `string`     |  | Set the switch swithed value.
offValue                | `string`     |  | Set the close closed values.
isDisabled (*optional*) | `boolean`    |  | If the 'isDisabled' is truthy, then special attribute "disabled" will be set on the element.

---

## Example
html
```
<div wm-switch="switchOptions.switch()"
  ng-model="switchOptions.status"
  on-value="switchOption.on" 
  off-value="switchOptions.off"
  is-disabled="switchOptions.isDisabled"></div>
```

coffee
```
switchOptions =
  switch: ->
    console.log 'switch checkbox'
  status: 'ENABLE' or 'DISABLE'
  on: 'ENABLE'
  off: 'DISABLE'
  isDisabled: false
```
