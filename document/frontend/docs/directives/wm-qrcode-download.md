## wmQrcodeDownload
The `wmQrcodeDownload`directive for translate web page address to the qr code, Your can scanne the qr code to access the web page. also you can download the qr code to the local.

---

## Directive Info
This directive executes at priority level 0.

---

## Usage
as attribute:
```
<div wm-qrcode-download
    is-url=""
    url-link=""
    [qrcode-title=""]
    is-show=""
    self-style="">
</div>
```

---

## Arguments
Param | Type | Default | Details
----- | ---- | ------ | ----
isUrl                    | `boolean`  | | mark the url is a web link if true, otherwise is a image url link.
urlLink                  | `string`   | | urlLink is required when 'is-url' is true.
imageLink                | `string`   | | imageLink is required when 'is-url' is false.
qrcodeTitle(*optional*)  | `string`   | "qrcode" |the download qrcode image title.
isShow                   | `boolean`  | | make qrcode display or not.
selfStyle                | `int`      | | css style.

---

## Example
html
```
<div
  wm-qrcode-download
  is-url="qrcode.isUrl"
  url-link="qrcode.urlLink"
  qrcode-title="qrcode.title"
  is-show="qrcode.isShowQrcodeDropdown"
  self-style="qrcode.style"
</div>

or

<div
  wm-qrcode-download
  is-url="qrcode.isUrl"
  image-link="qrcode.imageUrl"
  qrcode-title="qrcode.title"
  is-show="qrcode.isShowQrcodeDropdown"
  self-style="qrcode.style">
</div>
```

coffee
```
qrcode =
  isUrl: true
  urlLink: "https://www.quncrm.com"
  title: "quncrm"
  isShow: true
  style:
    top: 300
    right: 50

or

qrcode =
  isUrl: false
  imageUrl: "https://mp.weixin.qq.com/cgi-bin/showqrcode?ticket=gQEy8ToAAAAAAAAAASxodHRwOi8vd2VpeGluLnFxLmNvbS9xL1FFaVpfVS1tTUpsSktXSVZZbVRQAAIE7LfEVQMEAAAAAA=="
  title: "quncrm"
  isShow: true
  style:
    top: 300
    right: 50
```

