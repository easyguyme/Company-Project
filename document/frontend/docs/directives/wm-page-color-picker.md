## wmPageColorPicker
The `wmPageColorPicker` directive allows you to pick or input a color to render pages components.

---

## Directive Info
This directive executes at priority level 0.

---

## Usage
as attribute:
```
<ANY
    wm-page-color-picker=""
    pick-handler=""
    [is-disabled=""]>
</ANY>
```

---

## Arguments
Param | Type | Default | Details
----- | ---- | ------- | ----
wmPageColorPicker          | `string`     | | Assignable angular expression to data-bind to.
pickHandler                | `expression` | | Angular expression to be executed when the user picked or inputted a new color.
isDisabled (*optional*)    | `boolean`    | false | Set the color picker widget disabled.
---

## Example
html
```
<div wm-page-color-picker="page.pickedColor"
    pick-handler="page.pickedHandler"
    is-disabled="false"></div>
```

coffee
```
page =
  pickedColor: '#6ab3f7'
  pickedHandler: ->
    console.log 'picked color location'
```