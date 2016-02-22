## wmWechatMessage

## Directive Info
This directive is used to display origin pictures and executes at priority level 0.

## Usage
as attribute:
```
<ANY
    wm-picture-lib
    pictures=""
    is-show=""
    index="">
</ANY>
```

as element:
```html
<wm-picture-lib
    pictures=""
    is-show=""
    index=""
></wm-picture-lib>
```

## Arguments
Param | Type | Default | Details
----- | ---- | ------  | ------
pictures | **expression**  | []    | Assignable angular expression to data-bind to.
isShow   | **expression**  | false |Show or hide the origin pictures
index    | **expression**  |       |The picture's index

## Example
html
```
<div wm-picture-lib ng-if="product.isShow" pictures="product.pictures" is-show="product.isShow" index="product.index"></div>
```
coffee
```
product.index = 1
product.isShow = true
product.pictures = [
  {
    name: "0"
    size: "0.06"
    url: "https://dn-quncrm.qbox.me/dad6c43a5eb04842a9274a63.jpg"
  }
  {
    name: "6"
    size: "0.03"
    url: "https://dn-quncrm.qbox.me/f49215188bace608d52501ba.jpg"
  }
  {
    name: "0"
    size: "0.06"
    url: "https://dn-quncrm.qbox.me/581e84cb52a912e1dfdfc8c4.jpg"
  }
]

```