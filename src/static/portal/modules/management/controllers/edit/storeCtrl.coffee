define [
  'wm/app'
  'wm/config'
], (app, config) ->
  eventService = ( ->
    _addHandler = (win, eventName, eventHandler) ->
      eventHandler = eventHandler or (event) ->
        console.log(event) if console and event

      eventListenerArgs = [eventName, eventHandler]
      if typeof win.addEventListener isnt 'undefined'
        eventListener = win.addEventListener
        eventListenerArgs.push false
      else if typeof win.attachEvent isnt 'undefined'
        eventListener = win.attachEvent
        eventName = "on#{eventName}"

      eventListener.apply win, eventListenerArgs

    {
      addHandler: _addHandler
    }

  )()

  app.registerController 'wm.ctrl.management.edit.store', [
    'restService'
    '$location'
    '$stateParams'
    'notificationService'
    '$timeout'
    '$scope'
    '$filter'
    'storeService'
    'utilService'
    (restService, $location, $stateParams, notificationService, $timeout, $scope, $filter, storeService, utilService) ->
      vm = this

      serviceTypeStorehouses = [
        {
          'name': '美食',
          'c': [
            {'name': '江浙菜'},
            {'name': '粤菜'},
            {'name': '川菜'},
            {'name': '湘菜'},
            {'name': '东北菜'},
            {'name': '徽菜'},
            {'name': '闽菜'},
            {'name': '鲁菜'},
            {'name': '台湾菜'},
            {'name': '西北菜'},
            {'name': '东南亚菜'},
            {'name': '西餐'},
            {'name': '日韩菜'},
            {'name': '火锅'},
            {'name': '清真菜'},
            {'name': '小吃快餐'},
            {'name': '海鲜'},
            {'name': '烧烤'},
            {'name': '自助餐'},
            {'name': '面包甜点'},
            {'name': '茶餐厅'},
            {'name': '其它美食'}
          ]
        },
        {
          'name': '基础设施',
          'c': [
            {'name': '交通设施'},
            {'name': '公共设施'},
            {'name': '道路附属'},
            {'name': '其它基础设施'}
          ]
        },
        {
          'name': '医疗保健',
          'c': [
            {'name': '专科医院'},
            {'name': '综合医院'},
            {'name': '诊所'},
            {'name': '急救中心'},
            {'name': '药房药店'},
            {'name': '疾病预防'},
            {'name': '其它医疗保健'}
          ]
        },
        {
          'name': '生活服务',
          'c': [
            {'name': '家政'},
            {'name': '宠物服务'},
            {'name': '旅行社'},
            {'name': '摄影冲印'},
            {'name': '洗衣店'},
            {'name': '票务代售'},
            {'name': '邮局速递'},
            {'name': '通讯服务'},
            {'name': '彩票'},
            {'name': '报刊亭'},
            {'name': '自来水营业厅'},
            {'name': '电力营业厅'},
            {'name': '教练'},
            {'name': '生活服务场所'},
            {'name': '信息咨询中心'},
            {'name': '招聘求职'},
            {'name': '中介机构'},
            {'name': '事务所'},
            {'name': '丧葬'},
            {'name': '废品收购站'},
            {'name': '福利院养老院'},
            {'name': '测字风水'},
            {'name': '其它生活服务'}
          ]
        },
        {
          'name': '休闲娱乐',
          'c': [
            {'name': '洗浴推拿足疗'},
            {'name': 'KTV'},
            {'name': '酒吧'},
            {'name': '咖啡厅'},
            {'name': '茶馆'},
            {'name': '电影院'},
            {'name': '棋牌游戏'},
            {'name': '夜总会'},
            {'name': '剧场音乐厅'},
            {'name': '度假疗养'},
            {'name': '户外活动'},
            {'name': '网吧'},
            {'name': '迪厅'},
            {'name': '演出票务'},
            {'name': '其它娱乐休闲'}
          ]
        },
        {
          'name': '购物',
          'c': [
            {'name': '综合商场'},
            {'name': '便利店'},
            {'name': '超市'},
            {'name': '花鸟鱼虫'},
            {'name': '家具家居建材'},
            {'name': '体育户外'},
            {'name': '服饰鞋包'},
            {'name': '图书音像'},
            {'name': '眼镜店'},
            {'name': '母婴儿童'},
            {'name': '珠宝饰品'},
            {'name': '化妆品'},
            {'name': '食品烟酒'},
            {'name': '数码家电'},
            {'name': '农贸市场'},
            {'name': '小商品市场'},
            {'name': '旧货市场'},
            {'name': '商业步行街'},
            {'name': '礼品'},
            {'name': '摄影器材'},
            {'name': '钟表店'},
            {'name': '拍卖典当行'},
            {'name': '古玩字画'},
            {'name': '自行车专卖'},
            {'name': '文化用品'},
            {'name': '药店'},
            {'name': '品牌折扣店'},
            {'name': '其它购物'}
          ]
        },
        {
          'name': '运动健身',
          'c': [
            {'name': '健身中心'},
            {'name': '游泳馆'},
            {'name': '瑜伽'},
            {'name': '羽毛球馆'},
            {'name': '乒乓球馆'},
            {'name': '篮球场'},
            {'name': '足球场'},
            {'name': '壁球场'},
            {'name': '马场'},
            {'name': '高尔夫场'},
            {'name': '保龄球馆'},
            {'name': '溜冰'},
            {'name': '跆拳道'},
            {'name': '海滨浴场'},
            {'name': '网球场'},
            {'name': '橄榄球'},
            {'name': '台球馆'},
            {'name': '滑雪'},
            {'name': '舞蹈'},
            {'name': '攀岩馆'},
            {'name': '射箭馆'},
            {'name': '综合体育场馆'},
            {'name': '其它运动健身'}
          ]
        },
        {
          'name': '汽车',
          'c': [
            {'name': '加油站'},
            {'name': '停车场'},
            {'name': '4S店'},
            {'name': '汽车维修'},
            {'name': '驾校'},
            {'name': '汽车租赁'},
            {'name': '汽车配件销售'},
            {'name': '汽车保险'},
            {'name': '摩托车'},
            {'name': '汽车养护'},
            {'name': '洗车场'},
            {'name': '汽车俱乐部'},
            {'name': '汽车救援'},
            {'name': '二手车交易市场'},
            {'name': '车辆管理机构'},
            {'name': '其它汽车'}
          ]
        },
        {
          'name': '酒店宾馆',
          'c': [
            {'name': '星级酒店'},
            {'name': '经济型酒店'},
            {'name': '公寓式酒店'},
            {'name': '度假村'},
            {'name': '农家院'},
            {'name': '青年旅社'},
            {'name': '酒店宾馆'},
            {'name': '旅馆招待所'},
            {'name': '其它酒店宾馆'}
          ]
        },
        {
          'name': '旅游景点',
          'c': [
            {'name': '公园'},
            {'name': '风景名胜'},
            {'name': '植物园'},
            {'name': '动物园'},
            {'name': '水族馆'},
            {'name': '城市广场'},
            {'name': '世界遗产'},
            {'name': '国家级景点'},
            {'name': '省级景点'},
            {'name': '纪念馆'},
            {'name': '寺庙道观'},
            {'name': '教堂'},
            {'name': '海滩'},
            {'name': '其它旅游景点'}
          ]
        },
        {
          'name': '文化场馆',
          'c': [
            {'name': '博物馆'},
            {'name': '图书馆'},
            {'name': '美术馆'},
            {'name': '展览馆'},
            {'name': '科技馆'},
            {'name': '天文馆'},
            {'name': '档案馆'},
            {'name': '文化宫'},
            {'name': '会展中心'},
            {'name': '其它文化场馆'}
          ]
        },
        {
          'name': '教育学校',
          'c': [
            {'name': '小学'},
            {'name': '幼儿园'},
            {'name': '培训'},
            {'name': '大学'},
            {'name': '中学'},
            {'name': '职业技术学校'},
            {'name': '成人教育'},
            {'name': '其它教育学校'},
          ]
        },
        {
          'name': '银行金融',
          'c': [
            {'name': '银行'},
            {'name': '自动提款机'},
            {'name': '保险公司'},
            {'name': '证券公司'},
            {'name': '财务公司'},
            {'name': '其它银行金融'}
          ]
        },
        {
          'name': '地名地址',
          'c': [
            {'name': '交通地名'},
            {'name': '地名地址信息'},
            {'name': '道路名'},
            {'name': '自然地名'},
            {'name': '行政地名'},
            {'name': '门牌信息'},
            {'name': '其它地名地址'}
          ]
        },
        {
          'name': '房产小区',
          'c': [
            {'name': '住宅区'},
            {'name': '产业园区'},
            {'name': '商务楼宇'},
            {'name': '其它房产小区'}
          ]
        },
        {
          'name': '丽人',
          'c': [
            {'name': '美发'},
            {'name': '美容'},
            {'name': 'SPA'},
            {'name': '瘦身纤体'},
            {'name': '美甲'},
            {'name': '写真'}
          ]
        },
        {
          'name': '结婚',
          'c': [
            {'name': '婚纱摄影'},
            {'name': '婚宴'},
            {'name': '婚戒首饰'},
            {'name': '婚纱礼服'},
            {'name': '婚庆公司'},
            {'name': '彩妆造型'},
            {'name': '司仪主持'},
            {'name': '婚礼跟拍'},
            {'name': '婚车租赁'},
            {'name': '婚礼小商品'},
            {'name': '婚房装修'}
          ]
        },
        {
          'name': '亲子',
          'c': [
            {'name': '亲子摄影'},
            {'name': '亲子游乐'},
            {'name': '亲子购物'},
            {'name': '孕产护理'}
          ]
        },
        {
          'name': '公司企业',
          'c': [
            {'name': '公司企业'},
            {'name': '农林牧渔基地'},
            {'name': '企业/工厂'},
            {'name': '其它公司企业'}
          ]
        },
        {
          'name': '机构团体',
          'c': [
            {'name': '公检法机构'},
            {'name': '外国机构'},
            {'name': '工商税务机构'},
            {'name': '政府机关'},
            {'name': '民主党派'},
            {'name': '社会团体'},
            {'name': '传媒机构'},
            {'name': '文艺团体'},
            {'name': '科研机构'},
            {'name': '其它机构团体'}
          ]
        }
      ]

      vm.isSaved = false

      path = ''
      win = null

      _init = ->
        vm.storeTitle = if $stateParams.id then 'management_edit_store' else 'management_create_store'
        vm.breadcrumb = [
          {
            text: 'store_management'
            href: '/management/store'
          }
          vm.storeTitle
        ]

        vm.positionIcon = ''
        vm.locationTip = 'management_store_repeat_address'

        vm.store =
          name: ''
          branchName: ''
          type: ''
          subtype: ''
          telephone: ''
          position:
            longitude: null
            latitude: null
          image: ''
          businessHours: ''
          location:
            province: ''
            city: ''
            county: ''
            detail: ''
          description: ''

        if $stateParams.id
          _getStore()
        else
          _changeServiceTypeSelect()

      _getStore = ->
        restService.get config.resources.store + '/view/' + $stateParams.id, (data) ->
          data.location.county = data.location.district
          vm.store = angular.copy data
          vm.detail = vm.store.location.detail
          _changeServiceTypeSelect vm.store.type, vm.store.subtype
          vm.positionIcon = "http://api.map.baidu.com/staticimage?center=#{vm.store.position.longitude},#{vm.store.position.latitude}&
                            width=520&height=290&zoom=14&markers=#{vm.store.position.longitude},#{vm.store.position.latitude}&markerStyles=-1,http://api.map.baidu.com/images/marker_red.png,-1"

      _changeServiceTypeSelect = (typeVal, subtypeVal) ->
        selectSubtypes = serviceTypeStorehouses[0].c
        types = []
        angular.forEach serviceTypeStorehouses, (type) ->
          if typeVal? and typeVal is type.name
            selectSubtypes = angular.copy type.c
          types.push
            text: type.name
            value: type.name
          return
        vm.types = angular.copy types
        vm.store.type = vm.types[0].value if not typeVal? or typeVal is ''

        subtypes = []
        angular.forEach selectSubtypes, (subtype) ->
          subtypes.push
            text: subtype.name
            value: subtype.name
          return
        vm.subtypes = angular.copy subtypes

        vm.store.subtype = vm.subtypes[0].value if not subtypeVal? or subtypeVal is ''

      vm.checkNameAndAddr = ->
        flag = true
        if vm.store.location.province is ''
          _setLocationMsg 'management_store_select_province_msg'
          flag = false
        else if not vm.detail and not vm.store.name
          _setLocationMsg 'management_store_fill_addressname_msg'
          flag = false
        else
          _clearLocationMsg()
        return flag

      vm.changeLocation = ->
        _clearLocationMsg()

      vm.changeDetail = ->
        _clearLocationMsg()

      _resetPosition = ->
        vm.store.position =
          longitude: null
          latitude: null

      _setLocationMsg = (message) ->
        $addressBox = $('#addressBox').addClass('highlight')
        $addressBox.find('.form-tip').text($filter('translate')(message))
        return

      _clearLocationMsg = ->
        $addressBox = $('#addressBox')
        if $addressBox.hasClass 'highlight'
          $addressBox.removeClass 'highlight'
          $addressBox.find('.form-tip').text($filter('translate')('management_store_repeat_address'))
        return

      vm.changeType = (val) ->
        _changeServiceTypeSelect val

      vm.submit = ->
        vm.store.location.detail = vm.detail

        if not vm.checkNameAndAddr()
          return

        if utilService.checkLocationIllegal vm.store.location
          return

        if not vm.store.position or not vm.store.position.latitude or not vm.store.position.longitude
          notificationService.warning 'management_store_locate_address'
          return

        vm.store.location.district = vm.store.location.county
        delete vm.store.location.county if vm.store.location.county

        url = config.resources.store
        method = 'post'

        if $stateParams.id
          method = 'put'
          url = config.resources.store + '/update/' + $stateParams.id
        else
          url = config.resources.store + '/create'

        restService[method] url, vm.store, (data) ->
          vm.isSaved = true
          if method is 'post'
            notificationService.success 'management_store_create_success'
          else
            notificationService.success 'management_store_update_success'
          storeService.setStore data
          $timeout (->
            $location.url '/management/store'
          ), 500
          return

      vm.cancel = ->
        $location.url '/management/store'

      _openMapComponent = ->
        host = $location.$$host
        path = "http://#{host}/map/store"
        width = 800
        height = 600
        left = (window.outerWidth - width) / 2
        top = (window.outerHeight - height) / 2
        params = "height=#{height},width=#{width},left=#{left},top=#{top},toolbar=no,menubar=no,scrollbars=no,
                  resizable=no,location=no,status=no,directories=no,titlebar=no,alwaysRaised=yes"

        vm.store.location.detail = vm.detail
        info =
          name: '搜索定位'
          store: vm.store
          language: $scope.user.language or 'zh_cn'

        info = JSON.stringify(info)

        win = window.open(path, info, params)
        win.focus()

      _onmessage = (event) ->
        data = event.data

        if typeof data is 'string' and /^{[\s\S]*}$/.test(data) and typeof JSON.parse(data) is 'object'
          data = JSON.parse(data)

        if typeof data is 'object'
          switch data.status
            when 'ready'
              vm.store.location.detail = vm.detail
              options =
                store: vm.store
                language: $scope.user.language or 'zh_cn'

              options = JSON.stringify(options)
              win = event.source if (not win or $.isEmptyObject(win)) and event.source
              if not path
                host = $location.$$host
                path = "http://#{host}/map/store"
              win.postMessage(options, path) if win and angular.isFunction(win.postMessage)
            when 'data'
              store = data.store

              vm.store.position = angular.copy store.position if store.position
              vm.store.location = angular.copy store.location if store.location
              vm.detail = store.location.detail if store.location and store.location.detail?

              if store.position
                $scope.$apply ->
                  vm.positionIcon = "http://api.map.baidu.com/staticimage?center=#{store.position.longitude},#{store.position.latitude}&
                                width=520&height=290&zoom=14&markers=#{store.position.longitude},#{store.position.latitude}&markerStyles=-1,http://api.map.baidu.com/images/marker_red.png,-1"

      _onbeforeunload = ->
        win.close() if win

      eventService.addHandler window, 'message', _onmessage
      eventService.addHandler window, 'beforeunload', _onbeforeunload

      vm.locatePosition = ->
        if not vm.checkNameAndAddr()
          return
        _openMapComponent()

      _init()

      vm
  ]
