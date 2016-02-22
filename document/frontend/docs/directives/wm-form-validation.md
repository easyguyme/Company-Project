## wmFormValidation
The `wmFormvaildation` directive is used to apply easy form validation.<br/>
* `form-control` class is required for input tag.<br/>
* `form-group` class is required for wrapping form field group.<br/>
The `required` directive allows you to validate whether input content is null.<br/>
The `wm-url` directive allows you to validate whether the url is correct.<br/>
The `wm-email` directive allows you to validate the format of emial.<br/>
The `wm-validate` directive allows you to validate content with a function.<br/>
The `wm-max-character-size` directive allows you to limit the size of input content, Such as "2" or "{'chinese': 5, 'english': 2, 'size': 10}".<br/>
---

## Directive Info
This directive executes at priority level 0.

---

## Usage
as attribute:
```
<ANY
	ng-model=""
	required>
</ANY>
<input ng-model="" wm-url />
<input ng-model="" wm-email />
<input ng-model="" wm-validate="" />
<input ng-model="" wm-max-character-size="" />
```

---

## Arguments
Param | Type | Default | Details
----- | ---- | ------- | ----
ng-model               | `string` | | Assignable angular expression to data-bind to.
wm-validate            | `string` | | Assignable angular expression to data-bind to.
wm-max-character-size  | `string` | | Set Chinese and English character size and total size.

---

## Example
html
```
<form ng-submit="submit()">
  <div class="form-group">
    <label for="name">Label field with a star</label>
	<input id="name" class="form-control" required/>
  </div>
  <div class="form-group">
    <label>Label field without a star</label>
	<input class="form-control" required without-star/>
  </div>
  <div class="form-group">
    <label>Email Field</label>
    <input type="email" class="form-control" wm-email/>
  </div>
  <div class="form-group">
    <label>URL Field</label>
    <input type="url" class="form-control" wm-url/>
  </div>
  <div class="form-group">
    <label>Customized field</label>
    <input id="my-number" class="form-control" wm-validate="user.checkMyNumber" form-tip="{{user.formTip|translate}}"/>
  </div>
  <div class="form-group">
    <label>Customized field</label>
    <input type="text" class="form-control" ng-model="selectedMenu.name" wm-max-character-size="{'chinese': 5, 'english': 2, 'size': 10}" />
  </div>
  <input type="submit">
</form>
```

coffee
```
vm.checkName = ->
  formTip = ''
  if name and name.length < 4 and name.length > 30
    formTip = 'Name must be 4-30 characters long'
  formTip

vm.submit = ()->
  # Check if the validaion message is empty
  if !vm.checkMyNumber() # Empty message
    restService.post url, params, ()->
       # Update successfully
```