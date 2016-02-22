## wmQrcode
The `wmQrcode` directive for translate web page address to the qr code, Your can scanne the qr code to access the web page.

---

## Directive Info
This directive executes at priority level 0.

---

## Usage
as attribute:
```
<div wm-qrcode
    text="">
</div>
```

---

## Arguments
Param | Type | Default | Details
----- | ---- | ------ | ----
text | `string`  |      | This is qrcode url path.

---

## Example
html
```
<div 
  wm-qrcode
  text="qrcode.url">
</div>
```

coffee
```
  qrcode =
    url: "https://www.quncrm.com"
```
