## wmCheckbox
The `wmCheckbox` directive allows you to select single or multiple value by mouse.

---

## Directive Info
This directive executes at priority level 0.

---

## Usage
as attribute:
```
<ANY
    wm-checkbox
    ng-model=""
    [isDisabled=""]>
</ANY>
```
as element:
```
<wm-checkbox
    ng-model=""
    [isDisabled=""]>
</wm-checkbox>
```

---

## Arguments
Param | Type | Default | Details
----- | ---- | ------ | ----
ngModel                      | `boolean`    | false | Assignable angular expression to data-bind to.
isDisabled (*optional*)        | `boolean`    | false | Set the check box disabled.


---

## Example
html
```
<div wm-checkbox ng-model="checkboxOptions.checked"></div>
```

coffee
```
checkboxOptions =
  checked: true
```
