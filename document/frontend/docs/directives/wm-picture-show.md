## wmPictureShow
The `wmPictureShow` directive is used to display goods‘s images.

---

## Directive Info
This directive executes at priority level 0.

---

## Usage
as attribute:
```
<ANY
    wm-picture-show
    pictures=""
    [index=""]>
</ANY>
```
as element
```
<wm-picture-show
    pictures=""
    [index=""]>
</wm-picture-show>
```

---

## Arguments
Param | Type | Default | Details
----- | ---- | ------- | ----
pictures                    | `expression` | | Images array which are displayed by this widget.
index (*optional*)          | `string`     | 0      | Set the selected index of this widget when it's created.
---

## Example
html
```
<div wm-picture-show index="0" pictures="goods.pictures"></div>
```

coffee
```
goods =
  pictures: [
    "http://vincenthou.qiniudn.com/b011780d4904e7fd263843e7.jpg"
    "http://vincenthou.qiniudn.com/43bd51557718dec52cd96b56.jpg"
    "http://vincenthou.qiniudn.com/c290e70522242c0e9cd3fd3b.jpg"
  ]

```