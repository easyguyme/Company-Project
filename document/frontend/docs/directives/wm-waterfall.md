## wmWaterfall
The `wmWaterfall` directive is display items like water fall.

---

## Directive Info
This directive executes at priority level 0.

---

## Usage
as attribute:
```
<div 
  wm-waterfall="">
</div>
```

---

## Arguments
Param | Type | Default | Details
----- | ---- | ------ | ----
wmWaterfall | `expression`  | | Configuration parameters. See [masonry.desandro.com](http://masonry.desandro.com) or [masonryjs.com](http://masonryjs.com) for valid config options.

---

## Example
html
```
<div 
  wm-waterfall='waterfallOptions'>
</div>
```

coffee
```
waterfallOptions =
  transitionDuration: '0.1s'
  itemSelector: '.waterfall-item'
  gutter: 10,
  isFitWidth: true
  
```
