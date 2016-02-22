## wmStore
The `wmStore` directive allows you to select the store by province, city, region and store name. 

---

## Directive Info
This directive executes at priority level 0.

---

## Usage
as attribute:
```
<ANY 
	wm-store 
	ng-model=""
	on-change=""
	[channelId=""]>
</ANY>
```

---

## Arguments
Param | Type | Default | Details
----- | ---- | ------  | ----
ngModel      | `expression`     |  | Assignable angular expression to data-bind to.
channelId    | `string`         |  | The id of channel.
onChange     | `expression`     |  | Angular expression to be executed when store name changes. 

---

## Example
html
```
<div 
	wm-store 
	ng-model="store" 
	on-change="getStoreInfo()">
</div>
```

coffee
```
  store =
    province: "上海市"
    city: "浦东新区"
    region: "张江镇"
    store: "552496a61374739d3a8b4569"

  getStoreInfo: (storeId) ->
  	console.log 'storeId:' + storeId
```

