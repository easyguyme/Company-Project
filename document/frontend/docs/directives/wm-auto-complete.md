## wmMultiQrcode

## Directive Info
This directive is used to auto complete with user's input and executes at priority level 0.

## Usage
as attribute:
```
<ANY
  wm-auto-complete
  id=""
  ng-model=""
  [localdata=""]
  [callback-url=""]
  [search-key=""]>
</ANY>
```

as element:
```
<wm-auto-complete
  id=""
  ng-model=""
  [localdata=""]
  [callback-url=""]
  [search-key=""]>
</wm-auto-complete>
```

## Arguments
Param | Type | Default | Details
----- | ---- | ------  | ------
id                      | **string**     | ''    | Id of the element
ngModel                 | **expression** | []    | Tags of input
localdata(*optional*)   | **expression** | []    | Data of array in select box
callbackUrl(*optional*) | **string**     | ''    | Api url for getting data to display in select box
searchKey(*optional*)   | **string**     | ''    | Search key of api


## Example

### Usage 1
Fetch all data in init method to display in select box

html
```
<div wm-auto-complete localdata="broadcast.autoCompleteItems" ng-model="broadcast.tags" id="broadcast-tag">
```
coffee
```
broadcast.autoCompleteItems = [
  '阳光'
  '金卡'
]

```

### Usage 2
Use api to fetch data with user's input and display in select box

html
```
<div wm-auto-complete callback-url="/api/member/member/card-number" search-key="number" ng-model="score.numbers" id="score-number"></div>
```

