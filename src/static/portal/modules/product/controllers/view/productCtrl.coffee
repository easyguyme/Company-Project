define [
  'wm/app'
  'wm/config'
], (app, config) ->
  app.registerController 'wm.ctrl.product.view.product', [
    '$scope'
    'restService'
    '$stateParams'
    ($scope, restService, $stateParams) ->
      vm = this

      productId = $stateParams.id if $stateParams.id
      vm.breadcrumb = []

      vm.bread = [
        text: 'product_management'
        href: '/product/product'
      ,
        'product_description'
      ]

      vm.tabs = [
        active: true
        name: 'product_description'
        template: 'product.html'
      ,
        active: false
        name: 'product_promocode'
        template: 'promocode.html'
      ]
      vm.curTab = vm.tabs[0]

      _getProduct = ->
        restService.get config.resources.product + '/' + productId, (data) ->
          if data.type is 'product'
            vm.showTab = true
          else
            vm.bread[0].href = '/product/product?active=1'
            vm.bread[1] = 'product_service_details'
          vm.breadcrumb = vm.bread

      _getProduct()

      vm
  ]

  .registerController 'wm.ctrl.product.detail.product', [
    'restService'
    'notificationService'
    '$location'
    '$stateParams'
    '$scope'
    '$sce'
    '$modal'
    '$interval'
    'exportService'
    'utilService'
    '$filter'
    (restService, notificationService, $location, $stateParams, $scope, $sce, $modal, $interval, exportService, utilService, $filter) ->
      vm = this
      vm.product = {}
      productId = $stateParams.id if $stateParams.id

      vm.breadcrumb = [
        {
          text: 'product_management'
          href: '/product/product'
        }
        'product_description'
      ]

      vm.rows = 1
      vm.rowNum = []
      vm.colNum = []
      vm.specifications = []

      _getProduct = ->
        restService.get config.resources.product + '/' + productId, (data) ->
          product = data
          url = product.qrcode.qrcodeUrl or ''
          vm.qrcodeUrl =
            'background-image': 'url(' + url + ')'

          if product.category
            if product.category.name is 'service'
              product.category.name = $filter('translate')('category_service')
            productProperties = product.category.properties
          product.intro = $sce.trustAsHtml product.intro

          if product.specifications
            vm.specifications = angular.copy product.specifications

            vm.colNum.length = product.specifications.length
            for specification, index in product.specifications
              specification.properties.push '' if specification.properties.length is 0
              vm.rows = vm.rows * specification.properties.length
              for col, idx in vm.colNum
                vm.colNum[idx] = 1 if not col?
                vm.colNum[idx] = vm.colNum[idx] * specification.properties.length if index > idx

            while vm.rowNum.length < vm.rows
              vm.rowNum.push vm.rowNum.length

          if not $.isArray product.category
            for property in productProperties
              if product.category.name is $filter('translate')('category_service') and property.name is 'price'
                property.name = $filter('translate')('category_property_price')
              if angular.isArray(property.value)
                property.value = property.value.join('， ') or '-'
              else
                property.value = property.value or '-'

            restService.get config.resources.categories, (data) ->
              categories = data.items
              for category in categories
                if category.id is product.category.id
                  catProperties = category.properties
                  for property in catProperties
                    if utilService.getArrayElemIndex(productProperties, property, 'id') is -1
                      productProperties.push {name: property.name, value: '-'}
                  break

          vm.product = product

      _getProduct()

      vm
  ]

  .registerController 'wm.ctrl.product.detail.promocode', [
    'restService'
    'notificationService'
    '$location'
    '$stateParams'
    '$scope'
    '$modal'
    '$interval'
    'exportService'
    'utilService'
    '$filter'
    (restService, notificationService, $location, $stateParams, $scope, $modal, $interval, exportService, utilService, $filter) ->
      vm = this
      productId = $stateParams.id if $stateParams.id
      vm.totalItems = 0

      vm.list =
        columnDefs: [
          {
            field: 'createdAt'
            label: 'product_promotion_code_upload_time'
          }, {
            field: 'used'
            label: 'product_promotion_code_used'
          }, {
            field: 'all'
            label: 'product_promotion_total_count'
          },  {
            field: 'operations'
            label: 'operations'
            type: 'operation'
          }
        ]
        data: []
        selectable: false
        nodata: 'no_data'
        loadingText: 'product_loading_import_generate_code'

        deleteHandler: (idx) ->
          params =
            productId: productId
            createdAt: vm.list.data[idx].timestamp
          restService.post config.resources.delCodeHistory, params, (data) ->
            if data.message is 'OK'
              vm.list.data.splice idx, 1

        exportHandler: (idx) ->
          promoCode = vm.list.data[idx] if idx?
          params =
            productId: productId
            createdAt: promoCode.timestamp
          exportService.export 'promo-code', config.resources.exportPromoCode, params, false
          promoCode.operations[0].disable = true

      $scope.$on 'exportDataPrepared', (event, type, params) ->
        if type is 'promo-code' and params
          idx = -1
          angular.forEach vm.list.data, (item, index) ->
            idx = index if params.hasOwnProperty('createdAt') and item.timestamp is params.createdAt
          if idx isnt -1
            vm.list.data[idx].operations[0].disable = false

      vm.openModal = ->
        modalInstance = $modal.open(
          templateUrl: 'promocode.html'
          controller: 'wm.ctrl.product.importCode'
          windowClass: 'user-dialog'
          resolve:
            modalData: ->
              product: vm.product

          ).result.then( (data) ->
            if data
              vm.list.hasLoading = true
              _checkJob(data)
          ,(data) ->
            item = angular.element('.user-dialog').scope()
            if item.codeType is 'import' and item.filename
              param =
                filename: item.filename
                productId: vm.product.sku
              restService.get config.resources.delUploadFile, param, (data) ->
        )

      _getCodeHistory = ->
        restService.get config.resources.codeHistory, {productId: productId}, (data) ->
          historyList = data
          if historyList.length > 0
            for item in historyList
              item.operations = [
                {
                  name: 'export'
                }
                {
                  name: 'delete'
                  disable: true if item.used > 0 or not item.enable
                }
              ]
            vm.list.data = historyList
            vm.totalItems = historyList.length
            vm.list.hasLoading = false

      _getProduct = ->
        restService.get config.resources.product + '/' + productId, (data) ->
          vm.product = data

      _checkJob = (info) ->
        timer = $interval( ->
          if $location.absUrl().indexOf('/product/view/product') isnt -1
            param =
              token: info.token
              productId: vm.product.sku
              filename: info.filename
            restService.noLoading().get config.resources.checkAnalyze, param, (data) ->
              if data.status is 3 #fail
                notificationService.error 'product_upload_import_fail', false
                vm.list.hasLoading = false
                $interval.cancel timer
              else if data.status is 4 #finish
                if data.wrong is '-2' #promo code repeat
                  notificationService.error 'product_upload_code_repeat', false
                  vm.list.hasLoading = false
                $interval.cancel timer
                _getCodeHistory()
            , ->
              vm.list.hasLoading = false
          else
            $interval.cancel timer
        , 2000)

      _init = ->
        _getCodeHistory()
        _getProduct()

      _init()

      vm
    ]

  .registerController 'wm.ctrl.product.importCode', [
    'modalData'
    'restService'
    '$modalInstance'
    '$scope'
    '$timeout'
    '$interval'
    'localStorageService'
    '$upload'
    'notificationService'
    '$location'
    'validateService'
    '$filter'
    (modalData, restService, $modalInstance, $scope, $timeout, $interval, localStorageService, $upload, notificationService, $location, validateService, $filter) ->
      vm = $scope

      delayTime = 5000

      product = modalData.product

      vm.codeType = 'generate'
      vm.fileTypes = ['application/vnd.ms-excel', 'application/octet-stream', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'text/csv']

      _showLoading = ->
        document.getElementById('upload-loading').style.display = 'block'

      _hideLoading = ->
        document.getElementById('upload-loading').style.display = 'none'

      _checkJob = ->
        timer = $interval( ->
          if $location.absUrl().indexOf('/product/view/product') isnt -1
            param =
              token: vm.token
              productId: product.sku
              filename: vm.filename

            restService.noLoading().get config.resources.checkAnalyze, param, (data) ->
              vm.wrongStatus = true

              if data.status is 3 #fail
                notificationService.error 'product_upload_fail', false
                vm.uploading = false
                _hideLoading()
                $interval.cancel timer
              else if data.status is 4 #finish
                vm.uploading = false
                _hideLoading()
                $interval.cancel timer

                if data.wrong is '-1'  #sku error
                  notificationService.error 'product_upload_fail_sku', false
                  _deleteCache()

                else if data.right > 0 #normal
                  vm.wrongStatus = false
                  notificationService.info 'product_detail_upload_success', false
                else #import 0 codes
                  notificationService.error 'product_upload_fail_zero', false
          else
            $interval.cancel timer
        , delayTime)

      _deleteCache = ->
       param =
          filename: vm.filename
          productId: product.sku
        restService.get config.resources.delUploadFile, param, (data) ->

      vm.checkCount = (id, value) ->
        tip = ''
        if vm.codeType is 'generate'
          flag = /^[1-9][0-9]*$/.test(vm.count)
          if not flag or Number(vm.count) > Math.pow(10, 5)
            tip = 'product_promotion_code_count_errortip'
            validateService.highlight($('#' + id), $filter('translate')(tip))
        tip

      vm.upload = (files) ->
        vm.wrongStatus = false

        for file in files
          if $.inArray(file.type, vm.fileTypes) is -1
            notificationService.error 'product_file_type_error', false
            vm.file = ''
          else
            if(file.size >= Math.pow(10, 6) * 3)
              delayTime = Math.pow(10, 4) * 3
            else if(file.size > Math.pow(10, 6))
              delayTime = Math.pow(10, 4) * 1.5

            vm.file = file.name.substring(0, file.name.lastIndexOf('.'))
            vm.uploading = true
            _showLoading()
            tmoffset = new Date().getTimezoneOffset() / 60
            $upload.upload({
              url: config.resources.uploadPromoCode + '?tmoffset=' + tmoffset
              headers:
                'Content-Type': 'multipart/form-data'
              file: file
              data:
                productId: product.sku
              method: 'POST'
            }).progress((evt) ->

            ).success((data, status, headers, config) ->
              notificationService.info 'product_uploading_tip', false
              vm.token = data.data.token
              vm.filename = data.data.filename
              _checkJob()
            ).error (error) ->
              status = error.status
              if status is 403
                $location.path config.forbiddenPage
              else
                vm.uploading = false
                _hideLoading()
                notificationService.error 'product_have_deleted', false

      vm.hideModal = ->
        if vm.codeType is 'import' and vm.filename
          _deleteCache()

        $modalInstance.close()

      vm.save = ->
        if vm.codeType is 'generate' and vm.checkCount('codeCount', vm.count)
          return

        params =
          productId: product.id
          codeType: vm.codeType

        if vm.codeType is 'import'
          params.import = true
          params.filename = vm.filename
        else
          params.count = Number(vm.count)

        restService.post config.resources.importPromoCode, params, (data) ->
          data =
            token: data.data

          if vm.codeType is 'import'
            data.filename = vm.filename

          $modalInstance.close(data)

      vm
    ]

  ###
  .registerController 'wm.ctrl.product.view.product', [
    'restService'
    'notificationService'
    '$location'
    '$stateParams'
    '$scope'
    '$sce'
    '$modal'
    '$interval'
    'exportService'
    'utilService'
    '$filter'
    (restService, notificationService, $location, $stateParams, $scope, $sce, $modal, $interval, exportService, utilService, $filter) ->
      vm = this
      vm.product = {}
      productId = $stateParams.id if $stateParams.id

      vm.breadcrumb = [
        {
          text: 'product_management'
          href: '/product/product'
        }
        'product_description'
      ]

      vm.list =
        columnDefs: [
          {
            field: 'createdAt'
            label: 'product_promotion_code_upload_time'
          }, {
            field: 'used'
            label: 'product_promotion_code_used'
          }, {
            field: 'all'
            label: 'product_promotion_total_count'
          },  {
            field: 'operations'
            label: 'operations'
            type: 'operation'
          }
        ]
        data: []
        selectable: false
        nodata: 'no_data'
        loadingText: 'product_loading_import_generate_code'

        deleteHandler: (idx) ->
          params =
            productId: productId
            createdAt: vm.list.data[idx].timestamp
          restService.post config.resources.delCodeHistory, params, (data) ->
            if data.message is 'OK'
              vm.list.data.splice idx, 1

        exportHandler: (idx) ->
          promoCode = vm.list.data[idx] if idx?
          params =
            productId: productId
            createdAt: promoCode.timestamp
          exportService.export 'promo-code', config.resources.exportPromoCode, params, false
          promoCode.operations[0].disable = true

      _getProduct = ->
        restService.get config.resources.product + '/' + productId, (data) ->
          product = data
          if product.category
            if product.category.name is 'service'
              product.category.name = $filter('translate')('category_service')
            productProperties = product.category.properties
          product.intro = $sce.trustAsHtml product.intro
          if not $.isArray product.category
            for property in productProperties
              if product.category.name is $filter('translate')('category_service') and property.name is 'price'
                property.name = $filter('translate')('category_property_price')
              if angular.isArray(property.value)
                property.value = property.value.join('， ') or '-'
              else
                property.value = property.value or '-'

            restService.get config.resources.categories, (data) ->
              categories = data.items
              for category in categories
                if category.id is product.category.id
                  catProperties = category.properties
                  for property in catProperties
                    if utilService.getArrayElemIndex(productProperties, property, 'id') is -1
                      productProperties.push {name: property.name, value: '-'}
                  break

          vm.product = product

      _getCodeHistory = ->
        restService.get config.resources.codeHistory, {productId: productId}, (data) ->
          historyList = data
          if historyList.length > 0
            for item in historyList
              item.operations = [
                {
                  name: 'export'
                }
                {
                  name: 'delete'
                  disable: true if item.used > 0 or not item.enable
                }
              ]
            vm.list.data = historyList
            vm.list.hasLoading = false

      _checkJob = (info) ->
        timer = $interval( ->
          if $location.absUrl().indexOf('/product/view/product') isnt -1
            param =
              token: info.token
              productId: vm.product.sku
              filename: info.filename
            restService.noLoading().get config.resources.checkAnalyze, param, (data) ->
              if data.status is 3 #fail
                if data.wrong is '-2' #promo code repeat
                  notificationService.error 'product_upload_code_repeat', false
                else
                  notificationService.error 'product_upload_import_fail', false
                vm.list.hasLoading = false
                $interval.cancel timer
              else if data.status is 4 #finish
                $interval.cancel timer
                _getCodeHistory()
            , ->
              vm.list.hasLoading = false
          else
            $interval.cancel timer
        , 2000)

      _init = ->
        _getProduct()
        _getCodeHistory()

      $scope.$on 'exportDataPrepared', (event, type, params) ->
        if type is 'promo-code' and params
          idx = -1
          angular.forEach vm.list.data, (item, index) ->
            idx = index if params.hasOwnProperty('createdAt') and item.timestamp is params.createdAt
          if idx isnt -1
            vm.list.data[idx].operations[0].disable = false

      _init()

      vm.openModal = ->
        modalInstance = $modal.open(
          templateUrl: 'promocode.html'
          controller: 'wm.ctrl.product.importCode'
          windowClass: 'user-dialog'
          resolve:
            modalData: ->
              product: vm.product

          ).result.then( (data) ->
            if data
              vm.list.hasLoading = true
              _checkJob(data)
          ,(data) ->
            item = angular.element('.user-dialog').scope()
            if item.codeType is 'import' and item.filename
              param =
                filename: item.filename
                productId: vm.product.sku
              restService.get config.resources.delUploadFile, param, (data) ->

        )

      vm
  ###
