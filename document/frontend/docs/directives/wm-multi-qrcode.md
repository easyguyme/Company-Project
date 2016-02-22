## wmMultiQrcode

## Directive Info
This directive is used to display one or more qrcodes and executes at priority level 0.

## Usage
as attribute:
```
<ANY
  wm-multi-qrcode
  qrcode-list=""
  is-show=""
  qrcode-title=""
  [index=""]
  [edit-handler=""]
  [channel=""]
  [self-style=""]
  [disable-edit=""]>
</ANY>
```

as element:
```html
<wm-multi-qrcode
  qrcode-list=""
  is-show=""
  qrcode-title=""
  [index=""]
  [edit-handler=""]
  [channel=""]
  [self-style=""]
  [disable-edit=""]>
</wm-multi-qrcode>
```

## Arguments
Param | Type | Default | Details
----- | ---- | ------  | ------
qrcodeList              | **expression** | []    | Qrcode list array
isShow                  | **expression** | false | Show or hide the qrcode
qrcodeTitle             | **string**     | ''    | Title of the qrcode
index(*optional*)       | **expression** |       | The index of qrcode when in list
editHandler(*optional*) | **function**   |       | Edit call back when need edit the qrcode content
channel(*optional*)     | **expression** |       | Channel of the qrcode to show in the bottom
selfStyle(*optional*)   | **expression** |       | Position or other style of qrcode
disableEdit(*optional*) | **expression** |       | Disable edit button


## Example
###Usage 1
html
```
<div wm-multi-qrcode index="channel.storeIndex" qrcode-list="channel.qrcodeList" is-show="channel.isShowQrcodeDropdown" self-style="channel.position" edit-handler="channel.editQrcode" qrcode-title="scan_qrcode_view_graphic"></div>
```
coffee
```
channel.storeIndex = 1
channel.qrcodeList = [
  {
    link: "https://mp.weixin.qq.com/cgi-bin/showqrcode?ticket=gQHh8DoAAAAAAAAAASxodHRwOi8vd2VpeGluLnFxLmNvbS9xL0ZVTzBNZHZtTzRSQ0NLLUYtMjlLAAIEZtB3VQMEAAAAAA=="
    name: "wechat_测试的门店，不要动_测试的门店，不要动"
    title: "wechat_qrcode"
  }
  {
    link: "https://api.weibo.com/2/eps/qrcode/show?ticket=a7766b0e2c86697befc3ffd7208331690451"
    name: "weibo_测试的门店，不要动_测试的门店，不要动"
    title: "weibo_qrcode"
  }
]
channel.position =
  top: '100px'
  left: '200px'

channel.editQrcode = (index) ->
  channel.isShowQrcodeDropdown = false
  ...

```

### Usage 2
html
```
<div wm-multi-qrcode qrcode-list="info.wechatQrcode" is-show="info.isShowWechatQrcode" self-style="info.wechat.style" channel="info.wechatQrcode[0].channel" qrcode-title="scan_qrcode_view_graphic"></div>
```
coffee
```
info.isShowWechatQrcode = true
info.wechatQrcode = [
  {
    channel: "熊猫Baby"
    link: "https://mp.weixin.qq.com/cgi-bin/showqrcode?ticket=gQE88ToAAAAAAAAAASxodHRwOi8vd2VpeGluLnFxLmNvbS9xL0UwamZBTzdtZnBrSElqSGtKR1RQAAIEBojRVQMEAAAAAA=="
    name: "川渝小吃1"
    title: "wechat_qrcode"
  }
]

info.wechat.style =
  left: '694px'
  top: '122px'
```