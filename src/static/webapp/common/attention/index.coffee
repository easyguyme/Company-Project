$ ->
  #imageUrl must be the last param of url
  title = util.queryMap.title or '关注我们'
  searchMsg = window.location.search if window.location.search
  if searchMsg.indexOf('imageUrl') isnt -1
    imageUrl = searchMsg.split('imageUrl')[1].slice(1)

  $('title')[0].innerText = title
  $('.attention-qrcode').attr('src', imageUrl) if imageUrl
