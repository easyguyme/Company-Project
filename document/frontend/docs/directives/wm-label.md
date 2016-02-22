## wmLabel
The `wmLabel` directive allows you to create, update, delete custom tags, it also used to bind tags to members or followers.

---

## Directive Info
This directive executes at priority level 0.

---

## Usage
as attribute:
```
<ANY
    wm-label
    checked-item-store=""
    module=""
    type=""
    [selected-account=""]
    [bound-tags=""]
    [on-change=""]
    [on-close=""]>
</ANY>
```
as element
```
<wm-label
    checked-item-store=""
    module=""
    type=""
    [selected-account=""]
    [bound-tags=""]
    [on-change=""]
    [on-close=""]>
</wm-label>
```

---

## Arguments
Param | Type | Default | Details
----- | ---- | ------- | ----
checkedItemStore               | `string`     | | The members or followers which will bind tags to.
module                         | `string`     | | 'member' or 'follower'.
type                           | `string`     | | Give a follower(member) bound tags or Give followers(members) bound tags, e.g 'batch' or 'single'.
selectedAccount (*optional*)   | `string`     | | The social account which the checked follower belongs to, if bind tags to a follower.
boundTags (*optional*)         | `expression` | []     | The member or follower has bound tags array.
onChange (*optional*)          | `expression` | | Angular expression to be executed when create, update or delete tags.
onClose (*optional*)           | `expression` | | Angular expression to be executed when hide the bind tags widget.

---

## Example
html
```
<div wm-label
  checked-item-store="member.checkedMembersStore"
  module="member"
  on-change="member.updateTags()"
  type='single'
  bound-tags="member.targetBoundTags"
  on-close="member.hideTagModal"></div>
```

coffee
```
member =
  checkedMembersStore: ['55c4896ed6f97f24468b457d']
  targetBoundTags: ['白金会员', '白银会员', '倒霉会员']
  updateTags: ->
    console.log 'create, update or delete tags handler'
  hideTagModal: ->
    console.log 'hide widget handler'
```