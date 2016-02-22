## wmRadio
The `wmRadio` directive allows you to select only one value by mouse.

---

## Directive Info
This directive executes at priority level 0.

---

## Usage
as attribute:
```
<ANY
    wm-radio
    ng-model=""
    value=""
    [disabled=""]>
</ANY>
```
as element:
```
<wm-radio
    ng-model=""
    value=""
    [disabled=""]>
</wm-radio>
```

---

## Arguments
Param | Type | Default | Details
----- | ---- | ------ | ----
ng-model                   | `string`     | | Assignable angular expression to data-bind to.
value                      | `string`     | | Set value of a radio in a group of radio buttons.
disabled (*optional*)      | `boolean`    | false | Set the radio button disabled.


---

## Example
html
```
<wm-radio ng-model="radioOptions.value" value="first"></wm-radio>
<wm-radio ng-model="radioOptions.value" value="second"></wm-radio>
```

coffee
```
radioOptions =
  value: "second"
```
