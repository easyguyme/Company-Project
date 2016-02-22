mapInfo =
  position:
    lat: null
    lng: null

map = null
marker = null
infoWindow = null

mapInfo = {}
sourceWindow = null


_setPosition = (point) ->
  mapInfo.position = point if point

_createMap = (id, point, zoom) ->
  point = point or new BMap.Point(116.404, 39.915)
  zoom = zoom or 14

  map = new BMap.Map(id)
  map.centerAndZoom(point, zoom)
  map.enableScrollWheelZoom()
  map.enableDragging()

  scaleControl = new BMap.ScaleControl {
    anchor: BMAP_ANCHOR_TOP_LEFT
  }
  navigationControl = new BMap.NavigationControl()

  map.addControl scaleControl
  map.addControl navigationControl

  return map

map = _createMap('mapContainer')

_createMarker = (point) ->
  point = point or map.getCenter()

  marker = new BMap.Marker(point)
  map.addOverlay(marker)
  marker.enableDragging()
  marker.openInfoWindow(infoWindow)

  marker.addEventListener 'dragstart', ->
    this.closeInfoWindow()

  marker.addEventListener 'dragend', (e)->
    this.openInfoWindow(infoWindow)
    map.setCenter e.point if e.point

  marker.addEventListener 'click', ->
    this.openInfoWindow(infoWindow)

  return marker

_geocoderAddress = (addr, city, callback) ->
  geocoder = new BMap.Geocoder()
  geocoder.getPoint addr, (point) ->
    if point
      map.centerAndZoom(point, 14)
      callback() if callback
  , city

_initMapComponents = (mapInfo) ->

  if mapInfo
    address = mapInfo.location.province + mapInfo.location.city + mapInfo.location.county + mapInfo.town

    content = '<div class="fs14" style="padding: 5px 0;">
                  <h4 class="fs14" style="margin:0 0 5px 0;padding:0.2em 0;text-align:center">把当前位置设为精确定位?</h4>
                  <div style="text-align:center;">
                    <div style="color:#2788da;margin-bottom: 10px;">(继续拖动图标以重新定位)</div>
                    <div>
                      <input id="ok" class="btn btn-success" type="button" value="确定" onclick="mapOk();" style="padding: 4px 12px;">
                      <input id="cancel" class="btn btn-default" type="button" value="取消" onclick="mapCancel();" style="padding: 4px 12px;">
                    </div>
                  </div>
                </div>'

    opts =
      width: 240
      enableMessage:false
    infoWindow = new BMap.InfoWindow(content, opts)

    if not mapInfo.position? or not mapInfo.position.lng? or not mapInfo.position.lat?
      _geocoderAddress address, mapInfo.location.city , ->
        _createMarker()
    else
      point = new BMap.Point(mapInfo.position.lng, mapInfo.position.lat)
      map = _createMap 'mapContainer', point
      map.clearOverlays()
      marker = _createMarker point

$(document).ready ->
  #store = JSON.parse localStorage.getItem 'store' if localStorage.getItem 'store'
  _initMapComponents()

  window.opener.postMessage 'ready', $('.microsite-map-components').data 'domain'


window.addEventListener 'message', (event) ->
  mapInfo = event.data

  _initMapComponents mapInfo

window.mapOk = ->
  _setPosition marker.getPosition()

  if mapInfo.position and mapInfo.position.lng and mapInfo.position.lat
    sourceWindow = window.opener
    sourceWindow.postMessage mapInfo, $('.microsite-map-components').data 'domain'

  _closeWindow()


window.mapCancel = ->
  _closeWindow()

_closeWindow = ->
  window.close()


