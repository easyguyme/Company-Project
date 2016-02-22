## wmColorPicker
The `wmColorPicker` directive allows you to pick color by mouse in a color palette.

---

## Directive Info
This directive executes at priority level 0.

---

## Usage
as attribute:
```
<ANY
    wm-color-picker
    ng-model="">
</ANY>
```
as element:
```
<wm-color-picker
    ng-model="">
</wm-color-picker>
```

---

## Arguments
Param | Type | Default | Details
----- | ---- | ------ | ----
ngModel                        | `string` | '#fefefe' | Assignable angular expression to data-bind to.

---

## Example
html
```
<wm-color-picker ng-model="color"></wm-color-picker>
```

coffee
```
color = "#2e4358"
```
