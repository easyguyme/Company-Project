<steps>
  <section class="c-steps">
    <panel>
      <ul class="c-steps__wrapper">
        <li class="c-steps__wrapper__item" each={ step, i in parent.steps }>
          <i class="c-steps__wrapper__item__icon { step.iconClass }"></i>
          <div class="c-steps__wrapper__item__name">{ step.text }</div>
          <div if={ i != parent.parent.statusLen - 1 } class="c-steps__wrapper__item__line"></div>
        </li>
      </ul>
    </panel>
  </section>

  let self = this,
      status,
      extendStatus,
      textMap,
      statusMap,
      statusObjMap,
      _filterStatus,
      _getObj,
      _activeStatus,
      _display,
      _render;

  status = opts.status
  extendStatus = opts.extendStatus

  textMap = {
    commited: '订单提交',
    paid: '等待确认',
    inservice: '待服务',
    canceled: '订单取消',
    completed: '服务完成'
  }

  statusMap = {
    all: ['commited', 'canceled', 'paid', 'inservice', 'torefund', 'completed'],
    normal: ['commited', 'paid', 'inservice', 'completed'],
    cancel: ['commited', 'canceled']
  }

  statusObjMap = {
    all: [],
    normal: [],
    cancel: []
  }

  for (let i = 0, len = statusMap.all.length; i < len; i++) {
    let item = statusMap.all[i]
    let obj = {
      order: i,
      name: item,
      text: textMap[item],
      iconClass: item
    }
    statusObjMap['all'].push(obj)
  }

  _filterStatus = (type) => {
    for (let i = 0, len = statusMap[type].length; i < len; i++) {
      item = statusMap[type][i]
      statusObjMap[type].push(_getObj(item))
    }
    return statusObjMap[type]
  }

  _getObj = (name) => {
    for (let i = 0, len = statusObjMap.all.length; i < len; i++) {
      let obj = statusObjMap.all[i]
      if (name == obj.name) {
        return obj
      }
    }
    return null
  }

  _activeStatus = (type) => {
    let order = _getObj(status).order
    for (let i = 0, len = statusObjMap[type].length; i < len; i++) {
      item = statusObjMap[type][i]
      if (item.order <= order) {
        item.iconClass = item.name + '-active'
      }
    }
  }

  _display = (type) => {
    _filterStatus(type)
    _activeStatus(type)
    self.steps = statusObjMap[type]
    self.statusLen = statusObjMap[type].length
  }

  // different status width different processes
  _render = (status) => {
    if (status == 'canceled' || status == 'torefund' || (status == 'completed' && extendStatus == 'refundCompleted')) {
      _display('cancel')
    } else {
       _display('normal')
    }
  }

  _render(status)

</steps>
