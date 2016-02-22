## wmSearch
The `wmSearch` directive allows you to search the messages by the search criteria which you input in the input field.

---

## Directive Info
This directive executes at priority level 0.

---

## Usage
as element:
```
<wm-search
	ng-model=""
	click-func=""
	placeholder="">
</wm-search>
```

---

## Arguments
Param | Type | Default | Details
----- | ---- | ------  | ----
ngModel      | `string`     |  | Assignable angular expression to data-bind to.
clickFunc    | `expression` |  | Angular expression to be executed when click search button.
placeholder  | `string`     |  | The expected value of the input field. 

---

## Example
html
```
<wm-search 
	ng-model="searchCriteria"
	click-func="search()"
	placeholder="placeHolder">
</wm-search>
```

coffee
```
searchCriteria = ''
placeHolder = 'Please enter the search criteria'
search: ->
	console.log 'searching...'
```

