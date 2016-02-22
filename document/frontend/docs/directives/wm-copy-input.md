## wmCopyInput
The `wmCopyInput` directive allows you to copy url to clipboard by clicking icon or input box.

---

## Directive Info
This directive executes at priority level 0.

---

## Usage
as attribute:
```
<ANY
    wm-copy-input
    text=""
    [tooltip=""]>
</ANY>
```
as element:
```
<wm-copy-input
    text=""
    [tooltip=""]>
</wm-copy-input>
```

---

## Arguments
Param | Type | Default | Details
----- | ---- | ------ | ----
text                     | `string`     | '' | Set the url content that will copied to the clipboard.
tooltip (*optional*)     | `string`     | '' | Set tooltip of copy icon when the mouse hover on it.

---

## Example
html
```
<div wm-copy-input text="{{url}}"></div>
```

coffee
```
url = "http://u.augmarketing.com/BPyA"
```
