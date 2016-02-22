## wmSelectTable
The `wmSelectTable` directive allows you to declare checkable tables.

---

## Directive Info
This directive executes at priority level 0.

---

## Usage
as attribute:
```
<ANY
    wm-select-table
    ng-model="">
</ANY>
```
as element
```
<wm-select-table
    ng-model="">
</wm-label>
```

---

## Arguments
Param | Type | Default | Details
----- | ---- | ------- | ----
ngModel                        | `expression`     | | Assignable angular expression to data-bind to.
---

## Example
html
```
<div wm-select-table
  ng-model="promotion.products"></div>
```

coffee
```
promotion =
  products:
    isCheckBox: true
    columnDefs: [
        field: 'sku'
        label: 'product_promotion_goods_sku'
      ,
        field: 'name'
        label: 'product_promotion_goods_name'
        type: 'mark'
        markText: 'product_promotion_goods_has_associated_mark'
        markTip: 'product_promotion_goods_has_associated'
        cellClass: 'table-mark-cell'
      ,
        field: 'codeNum'
        label: 'product_promotion_code_number'
        type: 'number'
    ]
    data: [
        codeNum: 100
        enabled: true
        checked: false
        name: "琼浆玉露"
        sku: "1439968765123735"
      ,
        codeNum: 122
        enabled: true
        checked: true
        name: "银色手表"
        sku: "1434694075421934"
      ,
        codeNum: 10
        enabled: true
        checked: false
        name: "人参果酒"
        sku: "1434453408825773"
    ]
    checkHandler: ->
      console.log 'checked this rows'
```