## wmMicrositeLocation
The `wmMicrositeLocation` directive allows you to select location which include province, city and county widgets.

---

## Directive Info
This directive executes at priority level 0.

---

## Usage
as attribute:
```
<ANY
    wm-microsite-location
    ng-model=""
    [change-handler=""]
    [vertical=""]>
</ANY>
```
as element
```
<wm-microsite-location
    ng-model=""
    [change-handler=""]
    [vertical=""]>
</wm-microsite-location>
```

---

## Arguments
Param | Type | Default | Details
----- | ---- | ------- | ----
ngModel                        | `string`     | | Assignable angular expression to data-bind to.
changeHandler (*optional*)     | `expression` | | Angular expression to be executed when the location(province, city, or county)'s value is changed.
vertical (*optional*)          | `boolean`    | 'false' | Angular expression to be executed when the picker widget is hidden.
---

## Example
html
```
<div wm-microsite-location
  ng-model="channel.location"
  vertical="true"
  change-handler="channel.changeHandler()"></div>
```

coffee
```
channel =
  location:
    province: '上海市'
    city: '浦东新区'
    county: '张江镇'
  changeHandler: ->
    console.log 'change location'
```