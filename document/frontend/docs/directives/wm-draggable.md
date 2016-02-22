## wmDraggable
The `wmDraggable` directive allows you to drag the tag from one place to another.(With wmDroppable)

---

## Directive Info
This directive executes at priority level 0.

---

## Usage
as attribute:
```
<ANY
    wm-draggable=""
    draggable-target="">
</ANY>
```

---

## Arguments
Param | Type | Default | Details
----- | ---- | ------ | ----
wmDraggable                        | `string` |  | The name of this tag.
draggableTarget                    | `string` |  | The tag's class of destination place.

---

## Example
html
```
  <li wm-draggable="name" draggable-target="tagClass"></li>
```

coffee
```
name = "nav"
tagClass = ".mobile-content"
```
