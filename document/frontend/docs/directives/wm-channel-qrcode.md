## wmChannelQrcode

## Directive Info
This directive is used to create qrcodes by channels and display qrcodes and executes at priority level 0.

## Usage
as attribute:
```
<ANY
  wm-channel-qrcode
  qrcode-list=""
  api-info=""
  channel-ids=""
  create-callback=""
  [disable-edit]=""
  [modal-tip=""]>
</ANY>
```

as element:
```html
<wm-channel-qrcode
  qrcode-list=""
  api-info=""
  channel-ids=""
  create-callback=""
  [disable-edit]=""
  [modal-tip=""]>
</wm-channel-qrcode>
```

## Arguments
Param | Type | Default | Details
----- | ---- | ------  | ------
qrcodeList                  | **expression** | []    | Qrcode list array
apiInfo                     | **expression** | []    | Api url and params of create or edit qrcode
channelIds                  | **expression** | []    | Channel id of qrcodes
createCallback(*optional*)  | **function**   |       | Callback when create qrcode successfully
disableEdit(*optional*)     | **expression** | false | Disable edit button
modalTip(*optional*)        | **string**     |  ''   | Illustration of modal


## Example
html
```
<span wm-channel-qrcode qrcode-list="game.qrList" channel-ids="game.channelIds" api-info="game.apiInfo" modal-tip="game_illustration" create-callback="game.qrCallback()"></span>
```
coffee
```
# create qrcode
vm.apiInfo =
  create:
    params:
      gameId: '5600f8cad6f97f300e8b4568'
    resource: '/api/game/game/qrcode'
  edit:
    params:
      gameId: '5600f8cad6f97f300e8b4568'
    resource: '/api/game/game/qrcode'

vm.qrCallback = ->
  _getGameList()

# edit qrcode
vm.qrList = [
  icon: "/images/customer/wechat_service.png"
  link: "http://vincenthou.qiniudn.com/562590d0d6f97f940d8b4568.png"
  name: "wechat_猿粪"
  title: "熊猫Baby"
]

vm.channelIds = ['54d9c155e4b0abe717853ee1']

```