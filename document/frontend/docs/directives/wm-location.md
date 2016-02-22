## wmLocation
The `wmLocation` directive allows you to choose the country, province and city to locate the place.

---

## Directive Info
This directive executes at priority level 0.

---

## Usage
as attribute:
```
<ANY
    wm-location
    ng-model=""
    [channel-id=""]>
</ANY>
```
as element:
```
<wm-location
    ng-model=""
    [channel-id=""]>
</wm-location>
```

---

## Arguments
Param | Type | Default | Details
----- | ---- | ------ | ----
ngModel                      | `expression` |    | Assignable angular expression to data-bind to.
channelId (*optional*)       | `string`     | '' | Set the id of a channel in order to get the specific channel data.


---

## Example
html
```
<div wm-location ng-model="location"></div>
```

coffee
```
location =
  country: "中国"
  province: "上海"
  city: "浦东新区"
```
