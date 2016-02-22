## wmCenterImg
The `wmCenterImg` directive allows you to make image in the center of the parent element, you can use ng-src to set a image.

---

## Directive Info
This directive executes at priority level 0.

---

## Usage
as attribute:
```
<ANY
    wm-center-img>
</ANY>
```

---

## Arguments
Param | Type | Default | Details
----- | ---- | ------ | ----

---

## Example
html
```
<img wm-center-img ng-src="{{message.articles[0].url}}" />
```

coffee
```
message.articles[0].url = "https://dn-quncrm.qbox.me/92b2c6411e492fe95473c898.jpeg"
```
