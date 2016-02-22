define [
  'wm/app'
  'wm/config'
], (app, config) ->
  app.registerController 'wm.ctrl.product.edit.management.promotion', [
    'restService'
    '$stateParams'
    '$modal'
    'notificationService'
    '$scope'
    '$location'
    '$timeout'
    'validateService'
    '$filter'
    '$interval'
    '$upload'
    'localStorageService'
    (restService, $stateParams, $modal, notificationService, $scope, $location, $timeout, validateService, $filter, $interval, $upload, localStorageService) ->
      vm = this
      vm.id = $stateParams.id

      vm.code = {
        gift: {
          config: {
            prize: []
          }
        }
      }

      vm.fileTypes = ['application/vnd.ms-excel', 'application/octet-stream', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'text/csv']

      vm.fakeHistory = []
      vm.historyList = []

      vm.title = if vm.id then 'product_edit_association' else 'product_new_association'
      vm.breadcrumb = [
        {
          text: 'product_promotion_management'
          href: '/product/promotion?active=1'
        }
        vm.title
      ]

      vm.giftTypes = [
        {
          name: 'member_score'
          value: 'score'
        }
        {
          name: 'product_promotion_lotto'
          value: 'lottery'
        }
      ]

      vm.sendLottoTypes = [
        {
          name: 'product_promotion_lotto_send_by_scale'
          value: 'scale'
        }
        {
          name: 'product_promotion_lotto_send_by_number'
          value: 'number'
        }
      ]

      vm.lottoPrizes = [
        {
          name: ''
          number: ''
        }
      ]

      vm.sendLottoTypesGiftInfo =
        scale:
          numberTitle: 'product_promotion_winning_odds'
          numberUnit: '%'
        number:
          numberTitle: 'product_promotion_gift_number'
          numberUnit: 'product_promotion_gift_unit'

      vm.code.gift.type = vm.giftTypes[0].value
      vm.codeType = 'generate'

      _getCodeHistory = ->
        restService.get config.resources.codeHistory, {productId: vm.chosenProduct.id}, (data) ->
          historyList = data
          if historyList.length > 0
            for item in historyList
              item.used = item.all - item.rest
              item.operations = [
                {
                  name: 'delete'
                  disable: true if item.used > 0
                }
              ]
            vm.list.data = historyList
            vm.historyList = angular.copy historyList

      vm.addLottoPrizes = ->
        vm.lottoPrizes.push {name: '', number: ''}

      vm.removeLottoPrizes = (index, $event) ->

        if vm.lottoPrizes.length isnt 1
          notificationService.confirm $event,{
            title: 'product_promotion_gift_delete_confirm'
            submitCallback: _removeLottoPrizesHandler
            params: [index]
          }

      _checkJob = ->
        timer = $interval( ->
          if $location.absUrl().indexOf('/product/edit/management/promotion') isnt -1
            param =
              token: vm.token
              productId: vm.chosenProduct.sku
              filename: vm.filename
            restService.noLoading().get config.resources.checkAnalyze, param, (data) ->
              if data.status is 3 #fail
                notificationService.error 'product_upload_fail', false
                vm.uploading = false
                vm.file = ''
                $interval.cancel timer
              else if data.status is 4 #finish
                vm.uploading = false
                vm.file = ''
                $interval.cancel timer

                if data.wrong is '-1'  #sku error
                  notificationService.error 'product_upload_fail_sku', false
                  param =
                    filename: vm.filename
                    productId: vm.chosenProduct.sku
                  restService.get config.resources.delUploadFile, param, (data) ->

                else if data.right > 0 #normal
                  values =
                    wrong: data.wrong
                    right: data.right
                  notificationService.info 'product_upload_success', false, values
                  vm.fakeHistory.push(
                    {
                      createdAt: moment().format('YYYY-MM-DD HH:mm:ss')
                      used: 0
                      all: data.right
                      fake: true
                      filename: vm.filename
                      operations: [{name: 'delete'}]
                    }
                  )
                  vm.list.data.splice 0, 0, vm.fakeHistory[0]
                else #import 0 codes
                  notificationService.error 'product_upload_fail_zero', false
          else
            $interval.cancel timer
        , 3000)

      vm.upload = (files) ->
        vm.showCodesErr = false

        if not vm.chosenProduct
          vm.showErr = true
          return

        for file in files
          if $.inArray(file.type, vm.fileTypes) is -1
            notificationService.error 'product_file_type_error', false
          else
            vm.file = file.name.substring(0, file.name.lastIndexOf('.'))
            vm.uploading = true
            tmoffset = new Date().getTimezoneOffset() / 60
            $upload.upload({
              url: config.resources.importPromoCode + '?tmoffset=' + tmoffset
              headers:
                'Content-Type': 'multipart/form-data'
              file: file
              data:
                productId: vm.chosenProduct.sku
              method: 'POST'
            }).progress((evt) ->

            ).success((data, status, headers, config) ->
              notificationService.info 'product_uploading_tip', false
              vm.token = data.data.token
              vm.filename = data.data.filename
              _checkJob()
            ).error ->
              vm.uploading = false
              notificationService.error 'product_have_deleted', false

      vm.showGift = ->
        if vm.enableAssociation
          vm.code.gift = {}
          vm.code.gift.type = 'score'

      _removeLottoPrizesHandler = (index) ->
        $scope.$apply( ->
          vm.lottoPrizes.splice index, 1
        )

      vm.changeSendLottoTypes = ->
        vm.lottoPrizes = [
          {
            name: ''
            number: ''
          }
        ]

      vm.changeGiftType = (type) ->
        vm.code.gift.config = {}
        if type is 'lottery'
          vm.code.gift.config.method = 'scale'
        else
          vm.lottoPrizes = [
            {
              name: ''
              number: ''
            }
          ]

      vm.changeCodeType = ->
        vm.list.data = angular.copy vm.historyList
        vm.fakeHistory = []
        vm.file = ''
        vm.showCodesErr = false

      vm.checkCount = (id, value) ->
        tip = ''
        vm.count = parseInt(vm.count)
        if vm.count
          if vm.count > Math.pow(10, 5)
            tip = 'product_promotion_code_count_errortip'
            validateService.highlight($('#' + id), $filter('translate')(tip))
        else
          tip = 'product_promotion_code_count_errortip'
          validateService.highlight($('#' + id), $filter('translate')(tip))
        tip

      vm.checkPrizeName = (id, value) ->
        tip = ''
        if not value or value.length < 4 or value.length > 30
          tip = 'character_length_tip'
          validateService.highlight($('#' + id), $filter('translate')('character_length_tip', {'name': 'Prize name', 'minNumber': 4, 'maxNumber': 30}))
        tip

      vm.checkPositiveInt = (id, number) ->
        tip = ''
        reg = /^[1-9][0-9]*$/
        if number and not reg.test number # if the number is '' then do not check
          tip = 'product_promotion_activity_member_number_tip'
          validateService.highlight($('#' + id), $filter('translate')('product_promotion_activity_member_number_tip'))
        tip

      vm.checkPrizeNumber = (id, number) ->
        tip = ''
        if isNaN number
          tip = 'product_promotion_winning_odds_tip'
          validateService.highlight($('#' + id), $filter('translate')('product_promotion_winning_odds_tip'))
        else
          if typeof number is 'string'
            number = parseFloat number

          if number > 100 or number < 0
            tip = 'product_promotion_winning_odds_tip'
            validateService.highlight($('#' + id), $filter('translate')('product_promotion_winning_odds_tip'))
        tip

      if vm.id
        restService.get config.resources.productAssociation + '/' + vm.id, (data) ->
          vm.code = data
          vm.chosenProduct =
            id: vm.code.productId
            sku: vm.code.sku
            name: vm.code.productName

          vm.lottoPrizes = vm.code.gift.config.prize if vm.code.gift?.config?.prize
          vm.enableAssociation = true if vm.code.gift

          _getCodeHistory()

      vm.generate = ->
        vm.showCodesErr = false
        if not vm.checkCount('codeCount', vm.count)
          vm.fakeHistory.push(
            {
              createdAt: moment().format('YYYY-MM-DD HH:mm:ss')
              used: 0
              all: vm.count
              fake: true
              operations: [{name: 'delete'}]
            }
          )

          vm.list.data.splice 0, 0, vm.fakeHistory[0]

          vm.count = ''

      _checkFields = ->
        result = true
        if not vm.chosenProduct
          vm.showErr = true
          result = false
        result = false if vm.codeType is 'generate' and vm.count and vm.checkCount('codeCount', vm.count)

        if vm.list.data.length is 0
          vm.showCodesErr = true
          result = false

        if vm.enableAssociation
          switch vm.code.gift.type
            when 'score'
              result = false if vm.checkPositiveInt('giftRewardScore', vm.code.gift.config.number)

            when 'lottery'
              for prize, index in vm.lottoPrizes
                result = false if vm.checkPrizeName('prizeName' + index, prize.name)
                if vm.code.gift.config.method is 'scale'
                  result = false if vm.checkPrizeNumber('prizeNumber' + index, prize.number)
                else
                  result = false if vm.checkPositiveInt('prizeNumber' + index, prize.number)
        result

      _unsetFields = ->
        delete vm.code.id
        delete vm.code.used
        delete vm.code.all
        delete vm.code.rest
        delete vm.code.isAssociated
        delete vm.code.productName
        delete vm.code.sku

      vm.submit = ->
        if _checkFields()
          vm.code.productId = vm.chosenProduct.id
          vm.code.count = vm.fakeHistory[0].all if vm.fakeHistory.length > 0 and not vm.fakeHistory[0].filename
          if vm.fakeHistory.length > 0 and vm.fakeHistory[0].filename
            vm.code.import = true
            vm.code.filename = vm.fakeHistory[0].filename
          if vm.enableAssociation
            switch vm.code.gift.type
              when 'score'
                delete vm.code.gift.config.prize
                vm.code.gift.config.number = Number vm.code.gift.config.number
                vm.code.gift.config.method = 'score'
              when 'lottery'
                delete vm.code.gift.config.number
                for prize in vm.lottoPrizes
                  prize.number = Number prize.number
                vm.code.gift.config.prize = vm.lottoPrizes
          else
            delete vm.code.gift

          if not vm.id
            method = 'post'
            url = config.resources.productAssociations
          else
            method = 'put'
            url = config.resources.productAssociation + '/' + vm.id
            _unsetFields()

          restService[method] url, vm.code, (data) ->
            $location.url '/product/promotion?active=1'

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

        deleteHandler: (idx) ->
          if vm.list.data[idx].fake
            if vm.list.data[idx].filename
              param =
                filename: vm.list.data[idx].filename
                productId: vm.chosenProduct.sku
              restService.get config.resources.delUploadFile, param, (data) ->

            $scope.$apply( ->
              vm.fakeHistory.splice 0, 1
              vm.list.data.splice idx, 1
            )

          else
            params =
              productId: vm.chosenProduct.id
              createdAt: vm.list.data[idx].timestamp
            restService.post config.resources.delCodeHistory, params, (data) ->
              if data.message is 'OK'
                vm.list.data.splice idx, 1

      vm.cancel = ->
        $location.url '/product/promotion?active=1'

      vm.associatedGoods = ->
        modalInstance = $modal.open(
          templateUrl: 'associatedGoods.html'
          controller: 'wm.ctrl.product.edit.management.associatedGoods'
          windowClass: 'associated-goods-dialog'
          resolve:
            modalData: -> vm.chosenProduct
        ).result.then( (data) ->
          if data
            vm.chosenProduct = angular.copy data
            vm.showErr = false
        )

      vm

  ]

  app.registerController 'wm.ctrl.product.edit.management.associatedGoods', [
    '$scope'
    '$modalInstance'
    'restService'
    'notificationService'
    'modalData'
    'utilService'
    '$location'
    ($scope, $modalInstance, restService, notificationService, modalData, utilService, $location) ->
      vm = $scope

      vm.currentPage = $location.search().currentPage or 1
      vm.pageSize = $location.search().pageSize or 10
      vm.checkAll = false

      restService.get config.resources.categories, (data) ->
        vm.categories = data.items

      vm.checkAllCat = (checkAll) ->
        _checkAll checkAll

      vm.checkItem = (checked) ->
        if not checked
          vm.checkAll = checked
        else
          vm.checkAll = vm.categories.filter( (item) ->
            return item.check
          ).length is vm.categories.length

      _checkAll = (isCheckAll) ->
        for category in vm.categories
          category.check = isCheckAll

      _getCategories = ->
        items = []
        for category in vm.categories
          items.push category.id if category.check
        vm.categoryIds = items.join(',')

      vm.showFilter = ->
        vm.isShow = not vm.isShow

      vm.search = ->
        _getCategories()
        vm.currentPage = 1
        _getGoodsList()

      vm.clear = ->
        vm.searchKey = ''
        vm.checkAll = false
        _checkAll false

      _init = ->

        vm.list =
          radioColumn: 'sku'
          columnDefs: [
            {
              field: 'sku'
              label: 'product_promotion_goods_sku'
            }, {
              field: 'name'
              label: 'product_promotion_goods_name'
              type: 'mark'
              markText: 'product_promotion_association_assign_mark'
              markTip: 'product_promotion_association_assign'
              cellClass: 'table-mark-cell'
            },{
              field: 'cat'
              label: 'product_promotion_code_association_category'
            }
          ],
          data: []

          checkHandler: (idx, checked) ->
            if idx?
              index = utilService.getArrayElemIndex(vm.list.data, vm.list.radioValue, 'sku')
              vm.chosenProduct = vm.list.data[index] if index > -1
            return

        if modalData
          vm.chosenProduct = angular.copy modalData
          vm.list.radioValue = vm.chosenProduct.sku

        _getGoodsList()

      _getGoodsList = ->
        params =
          category: vm.categoryIds
          'per-page': vm.pageSize
          page: vm.currentPage
          orderBy: '{"createdAt": "desc"}'
          searchKey: vm.searchKey

        restService.get config.resources.products, params,(data) ->
          if data
            vm.totalItems = data._meta.totalCount

            vm.list.data = angular.copy data.items

            angular.forEach vm.list.data, (goods, index) ->
              goods.cat = goods.category.name or '--'
              goods.enabled = true
              if goods.isAssigned
                goods.enabled = false

      _init()

      vm.changePage = (currentPage) ->
        vm.currentPage = currentPage
        _getGoodsList()

      vm.submit = ->
        $modalInstance.close(vm.chosenProduct)

      vm.hideModal = ->
        $modalInstance.close()

      vm
  ]
