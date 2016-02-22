## wmBreadcrumb
The `wmBreadcrumb` directive is breadcrumb navigation.

---

## Directive Info
This directive executes at priority level 0.

---

## Usage
as attribute:
```
<div 
    wm-breadcrumb="">
</div>
```

---

## Arguments
Param | Type | Default | Details
----- | ---- | ------ | ----
wmBreadcrumb | `expression` |     | Configuration parameters like below example. 

---

## Example
html
```
<div 
  wm-breadcrumb="breadcrumb">
</div>
```

coffee
```
breadcrumb = [
  text: 'channel_management' (parent tab name)
  href: '/management/channel' (parent tab url)
,
  'management_view_store' (current tab name)
]
  
```
