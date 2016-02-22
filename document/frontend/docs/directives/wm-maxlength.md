## wmMaxlength

## Directive Info
This directive is used to limit user to enter text and executes at priority level 0.

## Usage
as attribute:
```
<textarea wm-maxlength="" ng-model=""></textarea>
```

## Arguments
Param | Type | Default | Details
----- | ---- | ------  | ------
ngModel     | **string**  | '' | Assignable angular expression to data-bind to.
wmMaxlength | **number**  |    |Max length you want to limit user to input

## Example
html
```
<textarea ng-model="product.description" wm-maxlength="200"></textarea>
```