## wmTooltip
The `wmTooltip` directive shows information when mouser hover on the element like the original attribute title.

---

## Directive Info
This directive executes at priority level 0.

---

## Usage
as attribute:
```
<ANY
  wm-tooltip="">
</ANY>
```

---

## Arguments
Param | Type | Default | Details
----- | ---- | ------ | ----
wmTooltip       | `string`        | | Tooltip text, any string which can contain {{}} markup.

---

## Example
html
```
<span wm-tooltip="{{tooltip}}"></span>
```

coffee
```
tooltip = "this is tooltip"
```
