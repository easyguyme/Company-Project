## wmCopy
The `wmCopy` directive allows you to copy url content to clipboard by clicking the icon.

---

## Directive Info
This directive executes at priority level 0.

---

## Usage
as attribute:
```
<ANY
    wm-copy
    clipboard-text="">
</ANY>
```
---

## Arguments
Param | Type | Default | Details
----- | ---- | ------ | ----
clipboardText              | `string` | '' | Set the url content that will copied to the clipboard.

---

## Example
html
```
<i wm-copy clipboard-text="url"></i>
```

coffee
```
url = "http://u.augmarketing.com/BPyA"
```
