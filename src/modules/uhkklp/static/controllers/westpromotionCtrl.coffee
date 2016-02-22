define [
  'wm/app'
  'wm/config'
], (app, config) ->
  app.registerController 'wm.ctrl.uhkklp.westpromotion', [
    'restService'
    '$stateParams'
    '$scope'
    '$filter'
    '$location'
    'notificationService'
    'localStorageService'
    'validateService'
    '$modal'
    '$http'
    'exportService'
    '$interval'
    '$rootScope'
    '$window'
    (restService, $stateParams, $scope, $filter, $location, notificationService, localStorageService, validateService, $modal, $http, exportService, $interval, $rootScope, $window) ->
      vm = this

      vm.breadcrumb = [
          'uhkklp_west_promotion'
      ]
      _init = ->

        vm.registrationKeyword = ''
        vm.goodsKeyword = ''
        vm.orderKeyword = ''
        vm.activityKeyword = ''
        $scope.totalPageCountRegis = 0
        $scope.totalPageCountGoods = 0
        $scope.totalPageCountOrder = 0
        $scope.totalPageCountActivity = 0
        $scope.sortDescRegis = 'DESC'
        $scope.sortDescGoods = 'DESC'
        $scope.sortDescOrder = 'DESC'
        # $scope.sortDescActivity = 'DESC'
        vm.cacheRegisCheckRows = []
        vm.cacheGoodsCheckRows = []
        vm.cacheOrderCheckRows = []
        vm.cacheActivityCheckRows = []

        vm.tabs = [
          {
            name: 'westpromotion_setting'
            value: 0
          }
          {
            name: 'westpromotion_enrolls'
            value: 1
          }
          {
            name: 'westpromotion_order'
            value: 2
          }
          {
            name: 'westpromotion_goods'
            value: 3
          }
        ]
        tabVal = $location.search().active
        vm.curTab = if tabVal then vm.tabs[tabVal] else vm.tabs[0]
        _checkTab()

        #pagination
        $scope.pageSizeRegis = 10
        $scope.currentPageRegis = 1

        $scope.pageSizeGoods = 10
        $scope.currentPageGoods = 1

        $scope.pageSizeOrder = 10
        $scope.currentPageOrder = 1

        $scope.pageSizeActivity = 10
        $scope.currentPageActivity = 1

        #table
        vm.activityList =
          columnDefs: [
            {
              field: '_id'
              label: 'registration_id'
              # sortable: true
            }
            {
              field: 'name'
              label: 'activity_activity_name'
            }
            {
              field: 'registrationStartDate'
              label: 'news_list_news_begin'
            }
            {
              field: 'registrationEndDate'
              label: 'uhkklp_activity_end_time'
            }
            # {
            #   field: 'registrationDescription'
            #   label: 'activity_description'
            # }
            # {
            #   field: 'registrationRule'
            #   label: 'registration_rule'
            # }
            {
              field: 'isActive'
              label: 'registration_status'
              type: 'status'
            }
            {
              field: 'operations'
              label: 'operations'
              type: 'operation'
            }
          ]
          data: []
          selectable: true
          switchHandler: (idx) ->
            isActivated = not vm.activityList.data[idx].isActivated
            vm.activityList.data[idx].isActive = if isActivated then 'ENABLE' else 'DISABLE'
            id = vm.activityList.data[idx]._id
            # console.log isActivated
            data =
              isActivated: isActivated
              _id : id
            restService.get '/api/uhkklp/west-pro-activity/update-regis-status', data, (data) ->
              if data.msg is 'success'
                notificationService.success 'activity_update_status', false
              _getListActivity()
              return
          editHandler: (item) ->
            $rootScope.uhkklp_activity_condition = {
              currentPage: $scope.currentPageActivity
              pageSize: $scope.pageSizeActivity
              # sort: $scope.sortDescActivity
              keyword : vm.activityKeyword
            }
            $location.url '/uhkklp/edit/campaign/westpromotion?id=' + vm.activityList.data[item]._id + '&active=' + vm.curTab.value
          # sortHandler: (colDef) ->
          #     if colDef.desc
          #       $scope.sortDescRegis = 'ASC'
          #     else
          #       $scope.sortDescRegis = 'DESC'
          #     _getListActivity()
          deleteHandler: (item) ->
            $http
              method: 'POST'
              url: '/api/uhkklp/west-pro-activity/delete-one'
              data: $.param _id: vm.activityList.data[item]._id
              headers:
                'Content-Type': 'application/x-www-form-urlencoded'
            .success (data) ->
              if data['result'] is "success"
                notificationService.success 'sms_list_delete_success_tip', false
                _getListActivity()
              else
                notificationService.error 'sms_list_delete_failed_tip', false
            .error (data) ->
              notificationService.error 'sms_list_delete_failed_tip', false
          selectHandler: (checked, item) ->
            if item? and item > -1
              activity = vm.activityList.data[item]
              if checked
                if ($.inArray activity._id, vm.cacheActivityCheckRows) is -1 and activity.isActive is 'DISABLE'
                  # console.log activity.isActive
                  vm.cacheActivityCheckRows.push activity._id
              else
                position = $.inArray activity._id, vm.cacheActivityCheckRows
                if position > -1
                  vm.cacheActivityCheckRows.splice position, 1
            else
              vm.cacheActivityCheckRows = []
              if checked
                for activity in vm.activityList.data
                  if activity.isActive is 'DISABLE'
                    vm.cacheActivityCheckRows.push activity._id
            console.log vm.cacheActivityCheckRows.length
            return


        vm.registrationList =
          columnDefs: [
            {
              field: '_id'
              label: 'registration_id'
              sortable: true
            }
            {
              field: 'createdAt'
              label: 'registration_time'
            }
            {
              field: 'activityName'
              label: 'activity_activity_name'
            }
            {
              field: 'name'
              label: 'registration_name'
            }
            {
              field: 'mobile'
              label: 'registration_mobile'
            }
            {
              field: 'restaurantName'
              label: 'registration_restaurantName'
            }
            {
              field: 'city'
              label: 'registration_city'
            }
            {
              field: 'address'
              label: 'registration_address'
            }
            {
              field: 'registrationNumber'
              label: 'registration_registrationNumber'
            }
            {
              field: 'operations'
              label: 'operations'
              type: 'operation'
            }
          ]
          data: []
          selectable: true
          editHandler: (item) ->
            $rootScope.uhkklp_regis_condition = {
              currentPage: $scope.currentPageRegis
              pageSize: $scope.pageSizeRegis
              sort: $scope.sortDescRegis
              keyword : vm.registrationKeyword
            }
            $location.url '/uhkklp/edit/westpromotion?id=' + vm.registrationList.data[item]._id + '&active=' + vm.curTab.value
          sortHandler: (colDef) ->
              if colDef.desc
                $scope.sortDescRegis = 'ASC'
              else
                $scope.sortDescRegis = 'DESC'
              _getList()
          deleteHandler: (item) ->
            $http
              method: 'POST'
              url: '/api/uhkklp/registration/delete-one'
              data: $.param _id: vm.registrationList.data[item]._id
              headers:
                'Content-Type': 'application/x-www-form-urlencoded'
            .success (data) ->
              if data['result'] is "success"
                notificationService.success 'sms_list_delete_success_tip', false
                _getList()
              else
                notificationService.error 'sms_list_delete_failed_tip', false
            .error (data) ->
              notificationService.error 'sms_list_delete_failed_tip', false
          selectHandler: (checked, item) ->
            if item? and item > -1
              regis = vm.registrationList.data[item]
              if checked
                if ($.inArray regis._id, vm.cacheRegisCheckRows) is -1
                  vm.cacheRegisCheckRows.push regis._id
              else
                position = $.inArray regis._id, vm.cacheRegisCheckRows
                if position > -1
                  vm.cacheRegisCheckRows.splice position, 1
            else
              vm.cacheRegisCheckRows = []
              if checked
                for regis in vm.registrationList.data
                  vm.cacheRegisCheckRows.push regis._id
            return

        vm.goodsList =
          columnDefs: [
            {
              field: '_id'
              label: 'registration_id'
              sortable: true
            }
            {
              field: 'createdAt'
              label: 'registration_time'
            }
            {
              field: 'name'
              label: 'goods_name'
            }
            {
              field: 'description'
              label: 'goods_description'
            }
            {
              field: 'href'
              label: 'goods_href'
            }
            {
              field: 'image'
              label: 'goods_picture'
              type: 'html'
            }
            {
              field: 'operations'
              label: 'operations'
              type: 'operation'
            }
          ]
          data: []
          selectable: true
          editHandler: (item) ->
            $rootScope.uhkklp_goods_condition = {
              currentPage: $scope.currentPageGoods
              pageSize: $scope.pageSizeGoods
              sort: $scope.sortDescGoods
              keyword : vm.goodsKeyword
            }
            $location.url '/uhkklp/edit/goods/westpromotion?id=' + vm.goodsList.data[item]._id + '&active=' + vm.curTab.value
          sortHandler: (colDef) ->
              if colDef.desc
                $scope.sortDescGoods = 'ASC'
              else
                $scope.sortDescGoods = 'DESC'
              _getListGoods()
          deleteHandler: (item) ->
            $http
              method: 'POST'
              url: '/api/uhkklp/goods/delete-one'
              data: $.param _id: vm.goodsList.data[item]._id
              headers:
                'Content-Type': 'application/x-www-form-urlencoded'
            .success (data) ->
              if data['result'] is "success"
                notificationService.success 'sms_list_delete_success_tip', false
                _getListGoods()
              else
                notificationService.error 'sms_list_delete_failed_tip', false
            .error (data) ->
              notificationService.error 'sms_list_delete_failed_tip', false
          selectHandler: (checked, item) ->
            if item? and item > -1
              goods = vm.goodsList.data[item]
              if checked
                if ($.inArray goods._id, vm.cacheGoodsCheckRows) is -1
                  vm.cacheGoodsCheckRows.push goods._id
              else
                position = $.inArray goods._id, vm.cacheGoodsCheckRows
                if position > -1
                  vm.cacheGoodsCheckRows.splice position, 1
            else
              vm.cacheGoodsCheckRows = []
              if checked
                for goods in vm.goodsList.data
                  vm.cacheGoodsCheckRows.push goods._id
            return

        vm.orderList =
          columnDefs: [
            {
              field: '_id'
              label: 'registration_id'
              sortable: true
            }
            {
              field: 'createdAt'
              label: 'registration_time'
            }
            {
              field: 'activityName'
              label: 'activity_activity_name'
            }
            {
              field: 'name'
              label: 'c_name'
            }
            {
              field: 'mobile'
              label: 'c_mobile'
            }
            {
              field: 'restaurantName'
              label: 'restaurant_name'
            }
            {
              field: 'city'
              label: 'registration_city'
            }
            {
              field: 'address'
              label: 'restaurant_address'
            }
            {
              field: 'registrationNumber'
              label: 'registration_registrationNumber'
            }
            {
              field: 'operations'
              label: 'operations'
              type: 'operation'
            }
          ]
          data: []
          selectable: true
          editHandler: (item) ->
            $rootScope.uhkklp_order_condition = {
              currentPage: $scope.currentPageOrder
              pageSize: $scope.pageSizeOrder
              sort: $scope.sortDescOrder
              keyword : vm.orderKeyword
            }
            $location.url '/uhkklp/edit/order/westpromotion?id=' + vm.orderList.data[item]._id + '&active=' + vm.curTab.value
          sortHandler: (colDef) ->
              if colDef.desc
                $scope.sortDescOrder = 'ASC'
              else
                $scope.sortDescOrder = 'DESC'
              _getListOrder()
          deleteHandler: (item) ->
            $http
              method: 'POST'
              url: '/api/uhkklp/order/delete-one'
              data: $.param _id: vm.orderList.data[item]._id
              headers:
                'Content-Type': 'application/x-www-form-urlencoded'
            .success (data) ->
              if data['result'] is "success"
                notificationService.success 'sms_list_delete_success_tip', false
                _getListOrder()
              else
                notificationService.error 'sms_list_delete_failed_tip', false
            .error (data) ->
              notificationService.error 'sms_list_delete_failed_tip', false
          selectHandler: (checked, item) ->
            if item? and item > -1
              order = vm.orderList.data[item]
              if checked
                if ($.inArray order._id, vm.cacheOrderCheckRows) is -1
                  vm.cacheOrderCheckRows.push order._id
              else
                position = $.inArray order._id, vm.cacheOrderCheckRows
                if position > -1
                  vm.cacheOrderCheckRows.splice position, 1
            else
              vm.cacheOrderCheckRows = []
              if checked
                for order in vm.orderList.data
                  vm.cacheOrderCheckRows.push order._id
            return
        _getList()
        _getListGoods()
        _getListOrder()
        _getListActivity()
        return

      vm.exportRegisList = ->
        if $scope.totalPageCountRegis > 0
          params =
            keyword : vm.registrationKeyword
          exportService.export 'registration_export', '/api/uhkklp/registration/export-registration', params, false
        else
          notificationService.warning 'no_data_tip', false
          return

      vm.exportGoodsList = ->
        if $scope.totalPageCountGoods > 0
          params =
            keyword : vm.goodsKeyword
          exportService.export 'goods_export', '/api/uhkklp/goods/export-goods', params, false
        else
          notificationService.warning 'no_data_tip', false
          return

      vm.exportOrderList = ->
        if $scope.totalPageCountOrder > 0
          params =
            keyword : vm.orderKeyword
          exportService.export 'order_export', '/api/uhkklp/order/export-order', params, false
        else
          notificationService.warning 'no_data_tip', false
          return

      $scope.changePageRegis = (currentPage) ->
        vm.cacheRegisCheckRows = []
        $scope.currentPageRegis = currentPage
        _getList()

      $scope.changePageActivity = (currentPage) ->
        vm.cacheActivityCheckRows = []
        $scope.currentPageActivity = currentPage
        _getListActivity()

      $scope.changePageGoods = (currentPage) ->
        vm.cacheGoodsCheckRows = []
        $scope.currentPageGoods = currentPage
        _getListGoods()

      $scope.changePageOrder = (currentPage) ->
        vm.cacheOrderCheckRows = []
        $scope.currentPageOrder = currentPage
        _getListOrder()

      $scope.changeSizeRegis = (pageSize) ->
        $scope.pageSizeRegis = pageSize
        $scope.currentPageRegis = 1
        _getList()

      $scope.changeSizeActivity = (pageSize) ->
        $scope.pageSizeActivity = pageSize
        $scope.currentPageActivity = 1
        _getListActivity()

      $scope.changeSizeGoods = (pageSize) ->
        $scope.pageSizeGoods = pageSize
        $scope.currentPageGoods = 1
        _getListGoods()

      $scope.changeSizeOrder = (pageSize) ->
        $scope.pageSizeOrder = pageSize
        $scope.currentPageOrder = 1
        _getListOrder()

      _getList = ->
        if $rootScope.uhkklp_regis_condition
          $scope.currentPageRegis = $rootScope.uhkklp_regis_condition.currentPage
          $scope.pageSizeRegis = $rootScope.uhkklp_regis_condition.pageSize
          $scope.sortDescRegis = $rootScope.uhkklp_regis_condition.sort
          vm.registrationKeyword = $rootScope.uhkklp_regis_condition.keyword
        params =
          pageSize: $scope.pageSizeRegis
          currentPage: $scope.currentPageRegis
          sortDesc: $scope.sortDescRegis
          keyword : vm.registrationKeyword
        restService.get '/api/uhkklp/registration/get-list', params, (data) ->
          # console.log '....' + data.totalPageCount
          $scope.totalPageCountRegis = data.totalPageCount
          (
            data.list[i]._id = item._id
            data.list[i].activityName = item.activityName
            data.list[i].name = item.name
            data.list[i].createdAt = item.createdAt
            data.list[i].mobile = item.mobile
            data.list[i].restaurantName = item.restaurantName
            data.list[i].registrationNumber = item.registrationNumber
            data.list[i].address = item.address
            data.list[i].city = item.city
            data.list[i].operations = [
              {
                name: 'edit'
                title: "registration_edit"
              }
              {
                name: 'delete'
                title: 'registration_delete_record'
              }
            ]
          ) for item, i in data.list
          vm.registrationList.data = data.list
          if $rootScope.uhkklp_regis_condition
            delete $rootScope.uhkklp_regis_condition
        return

      _getListActivity = ->
        if $rootScope.uhkklp_activity_condition
          $scope.currentPageActivity = $rootScope.uhkklp_activity_condition.currentPage
          $scope.pageSizeActivity = $rootScope.uhkklp_activity_condition.pageSize
          # $scope.sortDescActivity = $rootScope.uhkklp_activity_condition.sort
          vm.activityKeyword = $rootScope.uhkklp_activity_condition.keyword
        params =
          pageSize: $scope.pageSizeActivity
          currentPage: $scope.currentPageActivity
          # sortDesc: $scope.sortDescActivity
          keyword : vm.activityKeyword
        restService.get '/api/uhkklp/west-pro-activity/get-list', params, (data) ->
          # console.log '....' + data.totalPageCount
          if not data.list?
            return
          $scope.totalPageCountActivity = data.totalPageCount
          (
            data.list[i]._id = item._id
            # console.log item.IsActive
            # console.log typeof item.IsActive
            data.list[i].isActivated = item.IsActive
            data.list[i].isActive = if data.list[i].isActivated then 'ENABLE' else 'DISABLE'
            data.list[i].name = item.name
            data.list[i].registrationStartDate = item.registrationStartDate
            data.list[i].registrationEndDate = item.orderEndDate
            # data.list[i].registrationDescription = item.registrationDescription
            # data.list[i].registrationRule = item.registrationRule
            data.list[i].operations = [
              {
                name: 'edit'
                title: "activity_activity_edit"
              }
              {
                name: 'delete'
                title: 'activity_delete'
              }
            ]
            startDate = parseInt(item.startDate, 10)
            endDate = parseInt(item.endDate, 10)
            currentDate = parseInt(item.currentDate, 10)
            if currentDate < startDate and not item.isActivated
              data.list[i].operations[0].disable = false
              data.list[i].operations[1].disable = false
            if currentDate < startDate and item.isActivated
              data.list[i].operations[0].disable = true
              data.list[i].operations[1].disable = true
            if currentDate > endDate
              item.switchIsDisabled = true
              data.list[i].operations[0].disable = true
              data.list[i].operations[1].disable = false
            if currentDate > startDate and currentDate < endDate and item.isActivated
              data.list[i].operations[0].disable = true
              data.list[i].operations[1].disable = true
            if currentDate > startDate and currentDate < endDate and not item.isActivated
              data.list[i].operations[0].disable = false
              data.list[i].operations[1].disable = false
          ) for item, i in data.list
          vm.activityList.data = data.list
          if $rootScope.uhkklp_activity_condition
            delete $rootScope.uhkklp_activity_condition
        return

      _getListGoods = ->
        if $rootScope.uhkklp_goods_condition
          $scope.currentPageGoods = $rootScope.uhkklp_goods_condition.currentPage
          $scope.pageSizeGoods = $rootScope.uhkklp_goods_condition.pageSize
          $scope.sortDescGoods = $rootScope.uhkklp_goods_condition.sort
          vm.goodsKeyword = $rootScope.uhkklp_goods_condition.keyword
        params =
          pageSize: $scope.pageSizeGoods
          currentPage: $scope.currentPageGoods
          sortDesc: $scope.sortDescGoods
          keyword : vm.goodsKeyword
        restService.get '/api/uhkklp/goods/get-list', params, (data) ->
          # console.log data.totalPageCount
          $scope.totalPageCountGoods = data.totalPageCount
          (
            data.list[i]._id = item._id
            data.list[i].name = item.name
            data.list[i].description = item.description
            data.list[i].createdAt = item.createdAt
            data.list[i].href = item.href
            data.list[i].image = '<img class="mt_video_prev" src="' + item.image + '">'
            data.list[i].operations = [
              {
                name: 'edit'
                title: "goods_edit"
              }
              {
                name: 'delete'
                title: 'goods_delete_record'
              }
            ]
          ) for item, i in data.list
          vm.goodsList.data = data.list
          if $rootScope.uhkklp_goods_condition
            delete $rootScope.uhkklp_goods_condition
        return

      _getListOrder = ->
        if $rootScope.uhkklp_order_condition
          $scope.currentPageOrder = $rootScope.uhkklp_order_condition.currentPage
          $scope.pageSizeOrder = $rootScope.uhkklp_order_condition.pageSize
          $scope.sortDescOrder = $rootScope.uhkklp_order_condition.sort
          vm.orderKeyword = $rootScope.uhkklp_order_condition.keyword
        params =
          pageSize: $scope.pageSizeOrder
          currentPage: $scope.currentPageOrder
          sortDesc: $scope.sortDescOrder
          keyword : vm.orderKeyword
        restService.get '/api/uhkklp/order/get-list', params, (data) ->
          # console.log data.totalPageCount
          $scope.totalPageCountOrder = data.totalPageCount
          (
            data.list[i]._id = item._id
            data.list[i].activityName = item.activityName
            data.list[i].name = item.name
            data.list[i].mobile = item.mobile
            data.list[i].createdAt = item.createdAt
            data.list[i].restaurantName = item.restaurantName
            data.list[i].address = item.address
            data.list[i].city = item.city
            data.list[i].registrationNumber = item.registrationNumber
            data.list[i].operations = [
              {
                name: 'edit'
                title: "order_edit"
              }
              {
                name: 'delete'
                title: 'order_delete_record'
              }
            ]
          ) for item, i in data.list
          vm.orderList.data = data.list
          if $rootScope.uhkklp_order_condition
            delete $rootScope.uhkklp_order_condition
        return

      vm.changeTab = ->
        _checkTab()
        return

      _checkTab = ->
        if vm.curTab.value is 0
          vm.showSetting = true
          vm.showEnrolls = false
          vm.showOrder = false
          vm.showGoods = false
        if vm.curTab.value is 1
          vm.showSetting = false
          vm.showEnrolls = true
          vm.showOrder = false
          vm.showGoods = false
        if vm.curTab.value is 2
          vm.showSetting = false
          vm.showEnrolls = false
          vm.showOrder = true
          vm.showGoods = false
        if vm.curTab.value is 3
          vm.showSetting = false
          vm.showEnrolls = false
          vm.showOrder = false
          vm.showGoods = true
        return

      vm.searchKeyRegistration = ->
        _getList()

      vm.searchKeyActivity = ->
        _getListActivity()

      vm.searchKeyGoods = ->
        _getListGoods()

      vm.searchKeyOrder = ->
        _getListOrder()

      vm.deleteRegis = ($event) ->
        data = {}
        data.ids = vm.cacheRegisCheckRows
        notificationService.confirm $event, {
          title: 'recipe_edit_delete_tip'
          submitCallback: _RegisBatch
          params: [data]
        }

      vm.deleteActivity = ($event) ->
        data = {}
        data.ids = vm.cacheActivityCheckRows
        notificationService.confirm $event, {
          title: 'recipe_edit_delete_tip'
          submitCallback: _ActivityBatch
          params: [data]
        }

      vm.deleteGoods = ($event) ->
        data = {}
        data.ids = vm.cacheGoodsCheckRows
        notificationService.confirm $event, {
          title: 'recipe_edit_delete_tip'
          submitCallback: _GoodsBatch
          params: [data]
        }

      vm.deleteOrder = ($event) ->
        data = {}
        data.ids = vm.cacheOrderCheckRows
        notificationService.confirm $event, {
          title: 'recipe_edit_delete_tip'
          submitCallback: _OrderBatch
          params: [data]
        }

      _RegisBatch = (update) ->
        $http
          method: 'POST'
          url: '/api/uhkklp/registration/batch-delete'
          data: update
          headers:
            'Content-Type': 'application/json'
        .success (data) ->
          notificationService.success 'regist_list_batch_suc', false
          vm.currentPageRegis = 1
          _getList()
          $window.location.href = '/uhkklp/westpromotion?active=' + vm.curTab.value;
        .error (data) ->
          notificationService.error 'regist_list_batch_fail', false

      _ActivityBatch = (update) ->
        $http
          method: 'POST'
          url: '/api/uhkklp/west-pro-activity/batch-delete'
          data: update
          headers:
            'Content-Type': 'application/json'
        .success (data) ->
          notificationService.success 'regist_list_batch_suc', false
          vm.currentPageActivity = 1
          _getListActivity()
          $window.location.href = '/uhkklp/westpromotion?active=' + vm.curTab.value;
        .error (data) ->
          notificationService.error 'regist_list_batch_fail', false

      _GoodsBatch = (update) ->
        $http
          method: 'POST'
          url: '/api/uhkklp/goods/batch-delete'
          data: update
          headers:
            'Content-Type': 'application/json'
        .success (data) ->
          notificationService.success 'regist_list_batch_suc', false
          vm.currentPageGoods = 1
          _getListGoods()
          $window.location.href = '/uhkklp/westpromotion?active=' + vm.curTab.value;
        .error (data) ->
          notificationService.error 'regist_list_batch_fail', false

      _OrderBatch = (update) ->
        $http
          method: 'POST'
          url: '/api/uhkklp/order/batch-delete'
          data: update
          headers:
            'Content-Type': 'application/json'
        .success (data) ->
          notificationService.success 'regist_list_batch_suc', false
          vm.currentPageOrder = 1
          _getListOrder()
          $window.location.href = '/uhkklp/westpromotion?active=' + vm.curTab.value;
        .error (data) ->
          notificationService.error 'regist_list_batch_fail', false

      _init()

      vm
  ]
