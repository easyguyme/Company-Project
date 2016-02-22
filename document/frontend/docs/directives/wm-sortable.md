## wmSortable
The `wmSortable` directive allows you to sort the items.

---

## Directive Info
This directive executes at priority level 0.

---

## Usage
as attribute:
```
<ANY
    wm-sortable="">
    <ANY class="same-class"></ANY>
    <ANY class="same-class"></ANY>
</ANY>
```

---

## Arguments of wm-sortable

(More parameters : https://github.com/RubaXa/Sortable)

Param | Type | Default | Details
----- | ---- | ------ | ----
sort                        | `boolean` |  | Whether the sort
disabled                    | `boolean` |  | Disables the sortable if set to true
animation                    | `number` |  | Dnimation speed moving items when sorting
handle                    | `string` |  | Drag handle selector within list items
draggable                    | `string` |  | Specifies which items inside the element should be sortable
onUpdate                    | `expression` |  | Changed sorting within list

---

## Example
html
```
  <ul wm-sortable="options">
    <li ng-repeat="item in items" class="sort-item sort-{{$index}}" data-id="sort-{{$index}}">
        ...
    </li>
  </ul>
```

coffee

`Attentions: Let the sort item has unqiue parameter of 'data-id' or 'class' or 'src' or 'href' or 'textHTML'`

```
options = 
    sort: true
    disabled: false
    animation: 200
    handle: '.sort-itemt'
    draggable: '.sort-item'
    onUpdate: (items, item) ->
      vm.items = items
```
