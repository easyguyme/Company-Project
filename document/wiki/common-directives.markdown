# wm-wechat-message

```html
<div wm-wechat-message ng-model="user.message"></div>
```

the model can be a string for a text message

```
"This is text message"
```

or object for graphic

```json
{
    "createdAt": "2012-12-20",
    "articles": [
        {
            "title": "title",
            "description": "this is description",
            "picUrl": "http://ww3.sinaimg.cn/bmiddle/005wwp68jw1en4pbjachyj30fq0b1q40.jpg"
        },
        {
            "title": "title",
            "description": "this is description",
            "picUrl": "http://ww3.sinaimg.cn/bmiddle/005wwp68jw1en4pbjachyj30fq0b1q40.jpg"
        },
        {
            "title": "title",
            "description": "this is description",
            "picUrl": "http://ww3.sinaimg.cn/bmiddle/005wwp68jw1en4pbjachyj30fq0b1q40.jpg"
        },
        {
            "title": "title",
            "description": "this is description",
            "picUrl": "http://ww3.sinaimg.cn/bmiddle/005wwp68jw1en4pbjachyj30fq0b1q40.jpg"
        },
        {
            "title": "title",
            "description": "this is description",
            "picUrl": "http://ww3.sinaimg.cn/bmiddle/005wwp68jw1en4pbjachyj30fq0b1q40.jpg"
        }
    ]
}
```

# wm-select
The select directive use like following:
```html
<div wm-select on-change="user.changeSelect" ng-model="user.item" text-field="text" value-field="value" items="user.items" default-text="TEST"></div>
```

The option changed, the function user.changeSelect will be executed, ng-model is the selected **option value**, text-field used to mark the text key, value-field used to mark the value key, default-text is the default text. 
```coffee
wm.items =
  [
    {
      text: "option1"
      value: true
    }
    {
      text: "option2"
      value: false
    }
  ]
wm.item = wm.items[0].value

wm.changeSelect = (value, index)->
  # value is the item value after selection
  # index is the item index which is selected in items array
  console.log val, idx
```

# wm-switch
The switch directive use like following:

```html
<div wm-switch="user.switch(user.item)" on-value="user.switchers.on" off-value="user.switchers.off" ng-model="user.item.status"></div>
```

The status changed, the function switch(user.item) will execute, on-value is the value when switcher turned on, off-value is the value when switcher turned off, ng-model is the status of switcher.

```coffee
wm.switchers =
  on : true
  off : false

wm.switch = (item) ->
  console.log item
  console.log wm.item

wm.item =
  status : true
```

# wm-tabs and wm-tab-panes
The tabs directive use like following:

```html
<div wm-tabs="user.changTab()" tabs="user.tabs" ng-model="user.curTab"></div>
<div wm-tab-panes tabs="user.tabs" module="site"></div>
```

The status changed, the function user.changTab() will execute, user.tabs is the tab list, ng-model is the current tab, module is the frontend module.

```coffee
wm.tabs =
  [
    {
      active: true
      name: "Tab1"
      template: "logout.html"
    }
    {
      active: false
      name: "Tab2"
      template: "login.html"
    }
  ]
wm.curTab = wm.tabs[0]

wm.changTab = ()->
  console.log wm.curTab
```

# wm-pagination
The pagination directive use like following:

```html
<div wm-pagination current-page="currentPage" page-size="pageSize" total-items="totalItems" on-change-size="changePageSize" on-change-page="changePage></div>
```

* **current-page:** The current page number
* **page-size:** The page size for pagination
* **total-items:** The total count for all the data
* **on-change-size:** The callback handler name when changing page size
* **on-change-page:** The callback handler name when changing page number

```coffee
vm.changePage = (pageSize)->
    console.log pageSize
```

# wm-table
The table directive use like following:

```html
<wm-table ng-model="follower.list"></wm-table>
```

If the operations are the same in the table, the table model schema following the pattern below:
```json
{
  columnDefs: [
    {
      field: 'id'
      label: 'follower_number'
    }, {
      field: 'nickname'
      label: 'nickname'
    }, {
      field: 'language'
      label: 'language'
    }, {
      field: 'subscribeTime' 
      label: 'follow_time'   
      sortable: true         
      type: 'date'           
    }, {
      field: 'status'        
      label: 'status'        
      type: 'status'      
    }, {
      field: 'tag'
      label: 'tag'
      type: 'html'        
    }
  ],
  data: data                 
  operations: [
    {
      name: 'edit',          
      link: 'http://wm.com'  
    }, {
      name: 'delete'
    }, {
      name: 'tag'
    }
  ],
  switchHandler: (idx)->     
    console.log idx
  selectable: true
}
```
If the operations are not the same in the table, the sort is not suitable for you, the table model schema following the pattern below:
```json
{
  columnDefs: [
    {
      field: 'id'
      label: 'follower_number'
    }, {
      field: 'nickname'
      label: 'nickname'
    }, {
      field: 'language'
      label: 'language'
    }, {
      field: 'subscribeTime' 
      label: 'follow_time'   
      sortable: true
      type: 'html' 
      sortHandler: ()->     
    }, {
      field: 'status'        
      label: 'status'        
      type: 'status'      
    }, {
      field: 'tag'
      label: 'tag'
      type: 'html'        
    }, {
      field: 'operations'
      label: 'operations'
      type: 'operation'       
    }
  ],
  data: data                 
  
  switchHandler: (idx)->     
    console.log idx
  selectable: true
}
```
You should define operations field in data
```coffee
operations = [
  {
    name: 'send'
  }
  {
    name: 'edit'
  }
]
```
* **columnDefs** (object array) The table column definition
Fields: 
**type** (string) The field type, available types (html, status, date, tag, translate).
</br>
**field** (string) The field name in data.
</br>
**label** (string) The translate key defined in i18n file.
</br>
**sortable** (boolean) Whether the column is sortable.
</br>
* **data** (object array) The table raw data
* **operations** (object array) The operation button to be displayed on the right
* **switchHandler** (function) The switch component callback
* **selectable** (boolean) There are checkboxes on the left which is used to filter data

# wm-search

```
<wm-search ng-model="searchEmail" click-func="search()" placeholder="{{'USER_SEARCH_BY_EMAIL' | translate}}"></wm-search>
```

ng-model is the string for the search condition, click-func is a function triggered when the search button is clicked, placeholder will be displayed in the input box as default. 

# wm-graphic 
This is directive for graphic display

```html
<div wm-wechat-graphic graphic="graphic"></div>
```

graphic is a object with the structure:
```
{
  articles: [{
    title: "title",
    description: "this is description",
    picUrl: "link to image",
    ...},
    ...
  ],
}
```

# wm-waterfall
This is for waterfall container

```html
<div wm-waterfall='{ "transitionDuration" : "10s" , "itemSelector" : ".waterfall-item"}'>
```

transitionDuration stands for the duration before the waterfall is fulled stretched, itemSelector is for the class of the wm-waterfall-tile elements. If the class is "item" then you need not set the value.


# wm-waterfall-tile

```html
<div ng-repeat="graphic in graphicList track by $index" class="waterfall-item" wm-waterfall-tile>
```

# wm-location

```html
<div wm-location ng-model="follower.location"></div>
```

location model format sample: {"country":"中国","province":"上海","city":"浦东"}

If you add the static attribute, it will use the local data.

```html
<div wm-location static ng-model="member.location"></div>
```

# wm-microsite-location

```html
<div wm-microsite-location ng-model="map.data.location"></div>
```
microsite location model format sample: {"province":"上海市","city":"浦东新区",county: "张江镇"}

# wm-checkbox

```html
<wm-checkbox ng-model="checked"></wm-checkbox>
```

Use it as normal checkbox, only UI is changed here

# wm-radio

```html
<wm-radio ng-model="level" value="top"></wm-radio>
<wm-radio ng-model="level" value="middle"></wm-radio>
<wm-radio ng-model="level" value="bottom"></wm-radio>
```

Use it as normal radio buttom, only UI is changed here

# angular-ueditor

```html
<div class="ueditor" config="config" ready="ready" ng-model="content"></div>
```

* Config is the configuration for ueditor, reference [here](http://fex-team.github.io/ueditor/#start-config). Toolbars options define the function shown on the top, if default configuration is not enough for you, you can override it by passing new one.

* Registers a listener callback to be executed whenever the editor ready, editor is the parameter for ready callback.

* Content model is the plain html for ueditor.

Detailed introduction [here](https://github.com/zqjimlove/angular-ueditor)

# wm-file-upload

```html
<image ng-src="{{url}}"></image>
<div id="upload" wm-file-upload class="file-upload-wrap" ng-model="url">
  <span>Upload</span>
</div>
```

Required fields

* **id:** the uuid for the upload component
* **wm-file-upload:** the directive name
* **file-upload-wrap:** the wrapper for your customized button
* **process-bar:** indicate whether the progress bar shows up
* **max-size:** the max file size limitation for file uploading, default value is 300
* **ng-model:** the model to get uploaded file url

The inner button can be any tag and any style, span tag just an example as shown above.

# wm-color-picker

```html
<div wm-color-picker="color"></div>
```

The model is bind on the wm-color-picker attribute.

# wm-breadcrumb

```html
<div wm-breadcrumb="['channel_wechat_mass_title','channel_wechat_mass_title_add_broadcast']"></div>
```

The model is bind on the wm-breadcrumb attribute, the breadcrumb model is an translate key array as the example above.

# wm-qrcode

```html
<div wm-qrcode text="article.data.url"></div>
```

The model is bind on the text attribute. The qrcode's width and height is based on the div. If the text changed, the qrcode also will regenerate.

# wm-copy

```html
<button wm-copy clipboard-text="article.data.url" class="btn btn-default btn-copy">copy</button>
```

The model is bind on the clipboard-text attribute

# wm-tooltip

```html
<i class="icon-copy pull-right" wm-tooltip="{{'helpdesk_setting_hover_tip' | translate}}" tooltip-max-width="160"></i>
```

If you want to update tooltip content, use it like the followings
```coffee
$scope.$broadcast 'updateTooltip'
```

# wm-region-distribution

```html
<div wm-region-distribution header="score.header" distribution-data="score.dataList" max-user-count="score.maxUserCount" total-items="score.totalItems" current-page="score.currentPage" page-size="score.pageSize" on-change-page="score.getData"></div>
```

>1. distribution-data: data you need to render
>2. max-user-count: max user count of all the locations
>3. total-items: all items count
>4. current-page: current page
>5. page-size: page size
>6. on-change-page: call back to get data you need to render
>7. header: i18n key, display table head

```
vm.header = ['distribution_province', 'distribution_amount']
vm.dataList = []
vm.currentPage = 1
vm.pageSize = 8
vm.getData = (currentPage)->
    //send ajax request
    vm.totalItems = 21
    vm.maxUserCount = 5566
    vm.dataList = [
      {
        value: '上海'
        userCount: 324
      }
      {
        value: '北京'
        userCount: 312
      }
    ] 
```

# wm-yesterday-statistics

```
<div wm-yesterday-statistics statistics-title="yesterday_key_index" overview="score.overviewList"></div>
```

>1. statistics-title: panel title
>2. overview: render data

```
vm.overviewList = [
    {
      title: '图文页阅读次数' //i18n key
      value: 3056
      statistics:[
        {
          type: 'day'
          growth: 40 // percent
        }
        {
          type: 'week'
          growth: -10
        }
        {
          type: 'month'
          growth: 20
        }
      ]
    }
    ......
    ]
```

# wm-link-select

```
<div wm-link-select horizontal="true" ng-model="score.link1"></div>
<div wm-link-select ng-model="score.link2"></div>
```

>1. If you need horizontal link select, you should set horizontal="true", just like eg.1
>2. If you need vertical link select, just like eg.2 above

```