## wmEnter
The `wmEnter` directive is used to executed a expression after user hit enter key.

---

## Directive Info
This directive executes at priority level 0.

---

## Usage
as attribute:
```
<ANY wm-enter=""></ANY>
```
---

## Arguments
Param | Type | Default | Details
----- | ---- | ------- | ----
wmEnter          | `expression`     | | Angular expression to be executed when hit enter key.
---

## Example
html
```
<input type="search" ng-model="model" wm-enter="clickFunc()">
```

coffee
```
clickFunc = ->
  console.log 'hit enter key'
```