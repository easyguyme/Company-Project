<status-btn>
  <btn each={ btns } type="hollow" text={ text } link={ link } clickHandler={ clickHandler }></btn>

  var self = this,
      status,
      evaluate,
      orderNumber,
      reservationId,
      productId,
      operationMap,
      operations,
      queryMap,
      _parseQuery,
      _getQueryStr,
      _stringifyQuery,
      _init;

  const DOMAIN = location.protocol + '//' + location.host
  const BASE_URL = '/webapp/reservation/reservation/order'
  const OAUTH_LINK = '/api/mobile/pay'

  operationMap = {
    pay: {
      link: DOMAIN + '/webapp/common/pay/reservation',
      text: '付款'
    },
    cancel: {
      link: BASE_URL + '/cancel',
      text: '取消预约'
    },
    reserve: {
      link: BASE_URL + '/place',
      text: '再次预约'
    },
    evaluate: {
      link: BASE_URL + '/evaluate',
      text: '评价'
    },
    ensureService: {
      link: '',
      text: '确认完成'
    }
  }

  // each status can do some operations
  operations = {
    commited: ['pay', 'cancel'],
    paid: [],
    inservice: ['ensureService'],
    completed: ['reserve'],
    canceled: ['reserve'],
    torefund: ['reserve']
  }

  _parseQuery = () => {
    let arr,
        queryMap,
        item,
        parts;
    arr = location.search.slice(1).split('&')
    queryMap = {}
    for(let i = 0, len = arr.length; i < len; i++) {
      item = arr[i]
      parts = item.split('=')
      queryMap[parts[0]] = parts[1]
    }
    return queryMap
  }

  _stringifyQuery = (queryMap) => {
    let querySearchStr = ''
    if(queryMap && Object.prototype.toString.apply(queryMap).slice(8, -1) == 'Object') {
      querySearchStr = '?'
      for(let key in queryMap) {
        if(queryMap.hasOwnProperty(key))
          querySearchStr += key + '=' + queryMap[key] + '&'
      }
      querySearchStr = querySearchStr.replace(/\&$/, '')
    }
    if(querySearchStr == '?')
      querySearchStr = ''
    return querySearchStr
  }


  _init = () => {
    let operation, tempLink

    queryMap = _parseQuery()
    self.btns = []
    operations.completed = ['reserve']

    if(status == 'completed' && evaluate) {
      operations.completed.unshift('evaluate')
    }

    for(let i = 0, len = operations[status].length; i < len; i++) {
      operation = operations[status][i]
      operationMap[operation].orderNumber = orderNumber
      operationMap[operation].reservationId = reservationId
      if(operationMap[operation].link) {
        tempLink = operationMap[operation].link + _getQueryStr(operation)
        if(operation == 'pay') {
          let encodeLink = encodeURIComponent(tempLink)
          tempLink = OAUTH_LINK + '?channelId=' + queryMap['channelId'] + '&redirect=' + encodeLink
        }
      }
      self.btns.push($.extend({} ,operationMap[operation], {link: tempLink}))
    }
  }

  _getQueryStr = (operation) => {
    let map = {
      channelId: queryMap.channelId,
      memberId: queryMap.memberId
    }
    switch(operation) {
      case 'pay':
        map.orderId = orderId
        break
      case 'cancel':
        map.orderNumber = orderNumber
        break
      case 'evaluate':
        map.reservationId = reservationId
        break
      case 'reserve':
        map.productId = productId
        break
    }

    return _stringifyQuery(map)
  }

  self.clickHandler = (e) => {
    if (!e.item.link) {
      opts['click-handler'](e.item.orderNumber, e.item.reservationId)
    }
  }

  self.on('update', function() {
    status = opts.status
    evaluate = opts.evaluate
    orderNumber = opts['order-number']
    orderId = opts['order-id']
    reservationId = opts['reservation-id']
    productId = opts['product-id']
    _init()
  })

</status-btn>
