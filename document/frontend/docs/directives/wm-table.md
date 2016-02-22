## wmTable & wmFixedTable

## Directive Info
This directive is used to show table list with many types and executes at priority level 0.
wmFixedTable's header is fixed when scrollbar appears.

## Usage
as attribute:
```
<ANY
  wm-table
  ng-model=""
  [is-select-all=""]
</ANY>
```

as element:
```html
<wm-table
  ng-model=""
  [is-select-all=""]>
</wm-table>
```

## Arguments of ngModel
Param | Type | Default | Details
----- | ---- | ------  | ------
columnDefs | **expression** |     | Define table column field
data       | **expression** |     | Data filled in table
selectable | **boolean** |     | Whether the table has checkbox
deleteTitle| **string** |     | Delete confirm title if table has delete operation
editHandler| **function** |     | Edit callback with edit operation
deleteHandler| **function** |     | Delete callback with delete operation
switchHandler| **function** |     | Switch callback with switch operation
selectHandler| **function** |     | Select callback with click checkbox
sortHandler| **function** |     | Sort callback with sort operation
statisticsHandler| **function** |     | View statistics callback with statistics operation
qrcodeHandler| **function** |     | Qrcode callback with qrcode operation
tagHandler| **function** |     | Bind tag callback with bind operation
sendHandler| **function** |     | Send email callback with send operation
viewHandler| **function** |     | View callback with view operation
downloadHandler| **function** |     | Download callback with download operation
refreshHandler| **function** |     | Refresh callback with refresh operation
exportHandler| **function** |     | Export callback with export operation
importHandler| **function** |     | Import callback with import operation
goodsHandler| **function** |     | Redeem goods callback with redeem operation
linkHandler| **function** |     | Click url callback

*Note: If operation icon's name is 'edit', it correspond to editHandler. When you use handler callback means "#{operationName}Handler".*


## Example

html
```
<wm-table ng-model="channel.list"></wm-table>
```
coffee
```
channel.tableData = {
  columnDefs: [
    {
      field: 'number'
      label: 'product_goods_number'
      cellClass: 'text-el'
    }, {
      field: 'usedAmount'
      label: 'product_goods_used_count'
      sortable: true
    }, {
      field: 'onSaleTime'
      label: 'product_goods_sale_time'
      type: 'date'
    }, {
      field: 'shelveStatus'
      label: 'product_onshelves'
      type: 'status'
    }, {
        field: 'operations'
        label: 'operations'
        type: 'operation'
    }
  ],
  data: []
  selectable: true
  deleteTitle: 'product_item_delete'
  editHandler: (idx) ->


  deleteHandler: (idx) ->


  switchHandler: (idx) ->


  selectHandler: (checked, idx) ->
    if idx?
      ...
    else
      ...

  sortHandler: (colDef) ->
    key = colDef.field
    value = if colDef.desc then 'desc' else 'asc'
    vm.params.orderBy = '{"' + key + '":' + '"' + value + '"}'
    vm.params.page = 1
    ...
}

```

## Definition of columnDefs

###1. date
```coffee
columnDefs: {
  type: 'date'
  format: 'yyyy-MM-dd' # Optional
}

variable = 1445566209408

```

###2. icon
```coffee
columnDefs:
  type: 'account'

variable =
  icon: "/images/customer/weibo_disabled.png"
  status: false
  text: "O_o佳矣"
  type: "weibo"

```

###3. goodsIcon
```coffee
columnDefs:
  type: 'goodsIcon'
  seperate: 'true' # Optional, whether picture and text in same line or two lines

variable =
  name: "大白兔奶糖"
  url: "http://vincenthou.qiniudn.com/3cb18.jpg'
```

###4. status
```coffee
columnDefs:
  type: 'status'

variable = 'ENABLE' # 'ENABLE or DISABLE'

```

###5. currency (￥)
```coffee
columnDefs:
  type: 'currency'

variable = 20.5

```

###6. html
```coffee
columnDefs:
  type: 'html'

variable =
  text: '<span class="member-hint-keyword">Hello</span>
  tooltip: 'Hello' # Optional

```

###7. translate
```coffee
columnDefs:
  type: 'translate'

variable = 'cancel_ok'

```

###8. textColor
```coffee
columnDefs:
  type: 'textColor'

variable =
  color: 'wm-red-color' # css class
  text: 'Redeemed'

```

###9. translateValues
```coffee
columnDefs:
  type: 'translateValues'

variable =
  key: "customer_score_birthday_week_rule"
  values:
    score: 20

```

###10. modify
```coffee
columnDefs:
  type: 'modify'
  kind: 'plain' # Optional, Whether need to translate

# kind is 'plain'
variable = 12

# kind is not 'plain'
variable =
  key: 'product_coupon_validation_key'
  values:
    endTime: '2015-10-25'
    startTime: '2015-10-25

```

###11. link
```coffee
columnDefs:
  type: 'link'

variable =
  link: '/member/view/member' # Optipnal, if has link, it will display in link style; if no link, it will display in plain text
  text: 'member_name'
  tooltip: 'Name' # Optional
  explaination: 'menu_unbind' # Optional, illustration text

```

###12. multiLink
```coffee
columnDefs:
  type: 'multiLink'

# Optional
linkHandler: (idx) ->
  # callback

variable = [
  {
    link: '/'
    tooltip: ''
    text: ''
  }
]

```

###13. operation
```coffee
columnDefs:
  type: 'operation'

editHandler: (idx) ->
  # callback

deleteHandler: (idx) ->
  # callback

variable = [
  disable: true # Optional, disable or enable status
  name: 'edit'
  title: 'product_cannot_edit_tip' # Optional, tooltip
,
  name: 'delete'
  title: 'product_cannot_delete_tip' # Optional, delete title

```

###14. operationText
```coffee
columnDefs:
  type: 'operationText'

newqrcodeHandler: (idx) ->
  #callback

variable = [
  link: '#'
  name: 'newqrcode'
]

```

###15. label
```coffee
columnDefs:
  type: 'label'

variable =
  type: 'TEXT' # 'TEXT' or 'NEWS'
  content: 'hello'

```

###16. iconText
```coffee
columnDefs:
  type: 'iconText'

variable =
  icon: '/images/channel/default.png'
  text: '0 / 12'

```

###17. copy
```coffee
columnDefs:
  type: 'copy'

variable = 'http://www.baidu.com'

```

###18. input
```coffee
columnDefs:
  type: 'input'

```

###19. scoreChannels
```coffee
columnDefs:
  type: 'scoreChannels'

variable:
  icon: '/images/customer/portal.png'
  suffix: '(admin)'
  text: 'SCRM后台'

```

###20. transLink

It is used as the link type, but will be translated with i18n value

```coffee
columnDefs:
  type: 'link'

variable =
  link: '/member/view/member'
  text: 'member_name'
  tooltip: 'Name' # Optional
  explaination: 'menu_unbind' # Optional, illustration text

```
