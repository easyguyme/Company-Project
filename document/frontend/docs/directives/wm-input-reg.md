## wmInputReg
The `wmInputReg` directive is used to limit the value of the input.

---

## Directive Info
This directive executes at priority level 0.

---

## Usage
as attribute:
```
<input wm-input-reg ng-model="" data-reg=""/>

or

<textarea wm-input-reg ng-model="" data-reg=""></textarea>
```
---

## Arguments
Param | Type | Default | Details
----- | ---- | ------- | ----
ngModel          | `string`     | | Assignable angular expression to data-bind to.
dataReg          | `string`     | | The RegExp to limit the input value.
---

## Example
html
```
<input wm-input-reg
  type="text"
  ng-model="score"
  data-reg="(^[1-9]\d*$)|(^0$)" />
```
