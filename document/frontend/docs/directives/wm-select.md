## wmSelect
The `wmSelect` directive allows you to create a dropdown list with your model data and given selected value.
The model data should be an object array, each object has two property: textField and valueField.
The given selected value will be used to display as the selected item.
---

## Directive Info
This directive executes at priority level 0.

---

## Usage
as attribute:
```
<ANY
    wm-select
    items=""
    [text-field=""]
    [value-field=""]
    [on-change=""]
    [ng-model=""]
    [direction=""]
    [type=""]
    [is-disabled=""]
    [default-text=""]
    >
</ANY>
```
as element
```
<wm-select
    items=""
    ng-model="">
</wm-select>
```

---

## Arguments
Param | Type | Default | Details
----- | ---- | ------- | ----
items                        | `expression` | []        | Set the data of object array([{textField:"value1", valueField:"value2"}]) to data-bind to.
textField (*optional*)       | `string`     | 'text'    | Property of select item object.
valueField (*optional*)      | `string`     | 'value'   | Property of select item object.
onChange (*optional*)        | `expression` |           | Angular expression to be executed when the selected item changed
ngModel (*optional*)         | `string`     | ''        | Value of selected item valueField.
direction (*optional*)       | `string`     | ''        | Set dropdown-list's display position. If direction="up", dropdown list will be displayed on the top of parent element, else displayed on the bottom.
type (*optional*)            | `string`     | ''        | According to this type set the type of dropdown list, including icon, iconText and default.
isDisabled (*optional*)      | `boolean`    | false     | Set the dropdown-list disabled.
defaultText (*optional*)     | `string`     | undefined | If the ngModel is invalid, set the defaultText value as selected item

## Example
html
```
<div wm-select
  items="follower.genderOptions" text-field="text" value-field="value" on-change="follower.changeGender" ng-model="follower.gender" direction="up" type="iconText" is-disabled="false" default-text="{{'channel_wechat_mass_male' | translate}}" class="select-sex-style">
</div>
```

coffee
```
follower =
  genderOptions: [
      text: "channel_wechat_mass_unlimited"
      value: 0
    ,
      text: "channel_wechat_mass_male"
      value: "MALE"
    ,
      text: "channel_wechat_mass_female"
      value: "FEMALE"
    ,
      text: "unknown"
      value: "UNKNOWN"
  ]

  gender = 0

  changeGender = (gender, idx) ->
    vm.params.gender = gender
    vm.genderText = vm.genderOptions[idx].text
    return
```