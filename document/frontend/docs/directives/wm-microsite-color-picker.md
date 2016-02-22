## wmMicrositeColorPicker
The `wmMicrositeColorPicker` directive allows you to select a color to render components from given colors array.

---

## Directive Info
This directive executes at priority level 0.

---

## Usage
as attribute:
```
<ANY
    wm-microsite-color-picker=""
    colors=""
    pick-color=""
    [is-disabled=""]>
</ANY>
```

---

## Arguments
Param | Type | Default | Details
----- | ---- | ------- | ----
wmMicrositeColorPicker     | `string`     | | Assignable angular expression to data-bind to.
pickColor                  | `expression` | | Angular expression to be executed when the selected color value is changed.
colors                     | `expression` | | The array of given colors.
isDisabled (*optional*)    | `boolean`    | false | Set the color picker widget disabled.
---

## Example
html
```
<div wm-microsite-color-picker="page.chosenColor"
    colors="page.colors"
    pick-color="page.pickedHandler"
    is-disabled="false"></div>
```

coffee
```
page =
  chosenColor: '#f2f2f2'
  colors: [
    '#f2f2f2', '#f7f7f7', '#ddd9c3', '#c6d9f0', '#dbe5f1',
    '#f2dcdb', '#ebf1dd', '#e5e0ec', '#dbeef3', '#fdeada',
    '#d8d8d8', '#595959', '#c4bd97', '#8db3e2', '#b8cce4',
    '#e5b9b7', '#d7e3bc', '#ccc1d9', '#b7dde8', '#fbd5b5',
    '#bfbfbf', '#3f3f3f', '#938953', '#548dd4', '#95b3d7',
    '#d99694', '#c3d69b', '#b2a2c7', '#92cddc', '#fac08f',
    '#a5a5a5', '#262626', '#494429', '#1f497d', '#4f81bd',
    '#c0504d', '#9bbb59', '#8064a2', '#4bacc6', '#f79646',
    '#7f7f7f', '#0c0c0c', '#1d1b10', '#0f243e', '#244061',
    '#632423', '#4f6128', '#3f3151', '#205867', '#974806',
    '#c00000', '#ff0000', '#ffc000', '#ffff00', '#92d050',
    '#00b050', '#6ab3f7', '#0070c0', '#002060', '#7030a0'
  ]
  pickedHandler: ->
    console.log 'picked color location'
```