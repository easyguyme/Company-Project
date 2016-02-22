script = document.getElementById 'wm-chat-script'
hostInfo = script.getAttribute 'host'
accountId = script.getAttribute 'account'
width = 400
height = 450
btn = document.createElement 'div'
styleMap =
  position: 'fixed'
  right: '20px'
  bottom: '20px'
  cursor: 'pointer'
  width: '115px'
  color: '#fff'
  fontSize: '18px'
  paddingTop: '90px'
  paddingBottom: '5px'
  textAlign: 'center'
  borderRadius: '10px'
  background: "url(#{hostInfo}/images/helpdesk/helpdesk.png) no-repeat 18px 8px #2e3045"
  fontFamily: 'Microsoft Yahei, WenQuanYi Micro Hei, sans-serif';

text = if ('en-US' == navigator.language) then 'Helpdesk' else '在线客服';
for key, value of styleMap
  btn.style[key] = styleMap[key]

btn.innerHTML = text
btn.onclick = ()->
  left = window.innerWidth - width
  top = window.innerHeight - height
  path = "#{hostInfo}/chat/client?cid=#{accountId}#bottom"
  params = "height=#{height},width=#{width},left=#{left},top=#{top},toolbar=no,menubar=no,scrollbars=no,resizable=no,location=no,status=no"
  win = window.open(path, 'newwindow', params)
  win.focus()

document.body.appendChild(btn)
