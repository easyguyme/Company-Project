## wmLinkSelect

## Directive Info
This directive is used to fill inter link or outer link url and executes at priority level 0.

## Usage
as attribute:
```
<ANY wm-link-select
ng-model=""
[horizontal=""]
[no-empty=""]>
</ANY>
```

as elememt:
```
<wm-link-select
ng-model=""
[horizontal=""]
[no-empty=""]>
</wm-link-select>
```

## Arguments
Param | Type | Default | Details
----- | ---- | ------  | ------
ngModel                  | **expression** | ''      | Bind inter or outer link url
horizontal(*optional*)   | **string**     | false   | Horizontal or vertical style
noEmpty(*optional*)      | **string**     | false   | Whether need 'empty' option

## Example
html
```
<div wm-link-select ng-model="product.url" horizontal="true" no-empty="true"></div>
```