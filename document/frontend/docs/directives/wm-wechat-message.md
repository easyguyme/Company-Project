## wmWechatMessage
The `wmWechatMessage` directive allows you to send message in the form of text or graphic.

---

## Directive Info
This directive executes at priority level 0.

---

## Usage
as attribute:
```
<ANY
    wm-wechat-message
    ng-model=""
    [disabled-field=""]
    [preview=""]
    [preview-func=""]
    [max-length=""]
    [path=""]
    [placeholder=""]
    [keyup-handler=""]>
</ANY>
```

---

## Arguments
Param | Type | Default | Details
----- | ---- | ------ | ----
ngModel                      | `expression` | []    | Set the data of message to data-bind to.
disabledField (*optional*)   | `boolean`    | false | Set the field disabled.
preview (*optional*)         | `boolean`    | false | Set the wechat message whether can preview by wechat.
previewFunc (*optional*)     | `expression` |       | Angular expression to be executed when the preview label is clicked.
maxLength (*optional*)       | `number`     |       | Maximum possible value of message's length.
path (*optional*)            | `string`     | '/api/chat/graphics' | Defines the path to get the graphics.
placeholder (*optional*)     | `string`     | ''    | Set the placeholder of text area.
keyupHandler (*optional*)    | `expression` |       | Angular expression to be executed when key up.

## Example
html
```
<div wm-wechat-message ng-model="broadcast.message"></div>
```
coffee
```
broadcast =
  message:
    "articles": [
        "content": "<p>test</p>"
        "contentUrl": "https://dn-quncrm.qbox.me/d150dd7c-05c0-f384-5e57-25acffad994b.html"
        "picUrl": "https://dn-quncrm.qbox.me/736553b17bcd571c38177bd9.JPG"
        "title": "徒步2"
      ,
        "content": "<p>大峡谷</p>"
        "contentUrl": "https://dn-quncrm.qbox.me/16bce960-c4be-98c6-36aa-91fd83148802.html"
        "picUrl": "https://staging.quncrm.com/images/content/default_small.png"
        "title": "大峡谷"
      ,
        "content": "<p>茶马古道</p>"
        "contentUrl": "https://dn-quncrm.qbox.me/0fee2405-63a7-28c7-f530-d38fe951d0a9.html"
        "description": ""
        "picUrl": "https://staging.quncrm.com/images/content/default_small.png"
        "title": "茶马古道"
    ]
```
