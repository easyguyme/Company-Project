define [
  'wm/app'
  'wm/config'
  './edit/tagsCtrl'
  './edit/importCtrl'
  './edit/cookingtype/recipeCtrl'
], (app, config) ->
  app.registerController 'wm.ctrl.uhkklp.recipe', [
    'restService'
    '$location'
    '$http'
    'localStorageService'
    'notificationService'
    '$scope'
    'exportService'
    '$filter'
    '$upload'
    '$timeout'
    '$modal'
    '$rootScope'
    (restService, $location, $http, localStorageService, notificationService, $scope, exportService, $filter, $upload, $timeout, $modal, $rootScope) ->
      vm = this
      if $rootScope.uhkklp_recipe_tip
        notificationService.success $rootScope.uhkklp_recipe_tip, false
        delete $rootScope.uhkklp_recipe_tip
      scrollTo 0, 0
      EDIT_URL = '/uhkklp/edit/recipe'
      EDIT_SAMPLE_URL = '/uhkklp/edit/sample/recipe'
      EDIT_COOKINGTYPE_URL = '/uhkklp/edit/cookingtype/recipe'

      vm.breadcrumb = [
        'uhkklp_recipe'
      ]

      #tabs
      vm.tabs = [
        {
          name: 'recipe_list_tab_active_list'
          value: 0
        }
        {
          name: 'recipe_list_tab_inactive_list'
          value: 1
        }
        {
          name: 'recipe_list_tab_Third_list'
          value: 2
        }
        {
          name: 'recipe_list_sample'
          value: 3
        }
        {
          name: 'recipe_list_cookingtype'
          value: 4
        }
        {
          name: 'mt_product_list'
          value: 5
        }
      ]
      tabVal = $location.search().active
      $scope.showFirstTab = false
      $scope.showSecondTab = false
      $scope.showThirdTab = false
      $scope.showForthTab = false

      _showPage = (tabVal) ->
        if tabVal is undefined or tabVal is 'undefined' or tabVal is '0' or tabVal is '1' or tabVal is 0 or tabVal is 1
          $scope.showFirstTab = true
          $scope.showSecondTab = false
          $scope.showThirdTab = false
          $scope.showForthTab = false
          $scope.showFifthTab = false
        if tabVal is '2' or tabVal is 2
          $scope.showFirstTab = false
          $scope.showSecondTab = true
          $scope.showThirdTab = false
          $scope.showForthTab = false
          $scope.showFifthTab = false
        if tabVal is '3' or tabVal is 3
          $scope.showFirstTab = false
          $scope.showSecondTab = false
          $scope.showThirdTab = true
          $scope.showForthTab = false
          $scope.showFifthTab = false
        if tabVal is '4' or tabVal is 4
          $scope.showFirstTab = false
          $scope.showSecondTab = false
          $scope.showThirdTab = false
          $scope.showForthTab = true
          $scope.showFifthTab = false
        if tabVal is '5' or tabVal is 5
          $scope.showFirstTab = false
          $scope.showSecondTab = false
          $scope.showThirdTab = false
          $scope.showForthTab = false
          $scope.showFifthTab = true
      _showPage(tabVal)

      vm.curTab = if tabVal then vm.tabs[tabVal] else vm.tabs[0]

      #pagination
      vm.pageSize = 10
      vm.page = 1
      vm.enableExport = true

      vm.showOnShelvesModal = false
      vm.cacheCheckRows = []
      #table
      vm.list =
        columnDefs: [
          {
            field: 'cookbookId'
            label: 'recipe_list_cooknook_id'
          }
          {
            field: 'title'
            label: 'recipe_list_cookbook_name'
            sortable: true
          }
          {
            field: 'isSampleOpen'
            label: 'recipe_list_sample_open_state'
            sortable: true
          }
          {
            field: 'startDate'
            label: 'recipe_list_start_date'
            sortable: true
          }
          {
            field: 'endDate'
            label: 'recipe_list_end_date'
            sortable: true
          }
          {
            field: 'updatedDate'
            label: 'recipe_list_update_date'
            sortable: true
          }
          {
            field: 'operations'
            label: 'operations'
            type: 'operation'
          }
        ]
        data: []
        selectable: true
        hasLoading: true

        sortHandler: (colDef) ->
          if colDef.field?
            $scope.sortName = colDef.field
            if colDef.desc
              $scope.sortDesc = 'ASC'
            else
              $scope.sortDesc = 'DESC'
          _getList()

        editHandler: (item) ->
          tabVal = $location.search().active
          $location.url EDIT_URL + '/' + vm.list.data[item].cookbookId + "?active=" + tabVal

        deleteHandler: (item) ->
          $http
            method: 'POST'
            url: '/api/uhkklp/cookbook/delete?id=' + localStorageService.getItem(config.keys.currentUser).id
            data: $.param cookbookId: vm.list.data[item].cookbookId
            headers:
              'Content-Type': 'application/x-www-form-urlencoded'
          .success (data) ->
            if data['message'] is "not login"
              $location.url '/site/login'
              notificationService.warning 'recipe_go_to_login_tip', false
            else if data['message'] is "success"
              notificationService.success 'recipe_list_delete_success_tip', false
              _getList()
            else
              notificationService.error 'recipe_list_delete_failed_tip',false
          .error (data) ->
            notificationService.error 'recipe_list_delete_failed_tip',false

        downloadHandler: (item) ->
          if not vm.enableExport
            notificationService.warning 'recipe_list_downloading', false
            return

          params = {
            cookbookId: vm.list.data[item].cookbookId
          }

          exportService.export 'recipe_list_download_title', config.resources.exportSample, params, false
          vm.enableExport = false

        selectHandler: (checked, item) ->
          if item? and item > -1
            recipe = vm.list.data[item]
            if checked
              if ($.inArray recipe.cookbookId, vm.cacheCheckRows) is -1
                vm.cacheCheckRows.push recipe.cookbookId
            else
              position = $.inArray recipe.cookbookId, vm.cacheCheckRows
              if position > -1
                vm.cacheCheckRows.splice position, 1
          else
            vm.cacheCheckRows = []
            if checked
              for recipe in vm.list.data
                vm.cacheCheckRows.push recipe.cookbookId
          return

      _clear = ->
        vm.cacheCheckRows = []

      vm.showShelveModal = ->
        vm.showOnShelvesModal = not vm.showOnShelvesModal
        if vm.showOnShelvesModal
          vm.shelveType = 'now'
          return vm.onSaleTime = null

      vm.changeShelveType = ->
        if vm.shelveType is 'schedule'
          return vm.onSaleTime = moment().valueOf()
        else
          return vm.onSaleTime = null

      _recipeBatch = (update) ->
        $http
          method: 'POST'
          url: '/api/uhkklp/cookbook/cookbook-batch-handle'
          data: update
          headers:
            'Content-Type': 'application/json'
        .success (data) ->
          vm.page = 1
          _getList()
        .error (data) ->
          notificationService.error 'recipe_list_cookbook_batch_handle_fall', false

      vm.onShelves = ->
        update = {}
        update.operation = 'on'
        if vm.shelveType is 'schedule'
          if vm.onSaleTime and vm.onSaleTime <= moment().valueOf()
            notificationService.warning 'recipe_list_on_sale_time_less', false
            return
          else
            update.onSaleTime = vm.onSaleTime
        else
          update.onSaleTime = ''
        update.cookbookId = vm.cacheCheckRows
        vm.showOnShelvesModal = false
        _recipeBatch update

      vm.offShelves = ($event) ->
        update = {}
        update.operation = 'off'
        update.cookbookId = vm.cacheCheckRows
        notificationService.confirm $event, {
          title: 'recipe_list_off_shelves_tip'
          submitCallback: _recipeBatch
          params: [update]
        }

      vm.deleteGoods = ($event) ->
        update = {}
        update.operation = 'del'
        update.cookbookId = vm.cacheCheckRows
        notificationService.confirm $event, {
          title: 'recipe_edit_delete_tip'
          submitCallback: _recipeBatch
          params: [update]
        }

      $scope.$on 'exportDataPrepared', (event, type) ->
        if type is 'recipe-list-download-title'
          vm.enableExport = true
        else if type is 'recipe-list-download-all-sample'
          vm.enableExport = true

      vm.categories = []
      _getCategiriesDate = ->
        categoriesDate = []
        for category,j in vm.categories
          data = "{'name': '" + category.name + "','items': "
          i = 0
          items = []
          for item in category.items
            if item.check
              items.push "'" + item.name + "'"
              i++
          data = data + "[" + items.toString() + "]}"
          categoriesDate.push eval('(' + data + ')')
        return categoriesDate

      _getCount = ->
        #count
        tabVal = $location.search().active
        active = 'Y'
        if tabVal is undefined or tabVal is 'undefined' or tabVal is '0' or tabVal is 0
          active = 'Y'
        else
          active = 'N'
        $http
          method: 'POST'
          url: '/api/uhkklp/cookbook/count-list-manage-by-categories'
          data: {
              "keyword": vm.keyword
              "active": active
              "categories": _getCategiriesDate()
            }
          headers:
            'Content-Type': 'application/json'
        .success (data) ->
          tabVal = $location.search().active
          vm.totalCount = data['result']
          if vm.totalCount > 10
            vm.showPagination = true
          else
            vm.showPagination = false
        .error (data) ->
          notificationService.error 'recipe_list_get_count_error', false

      $scope.sortName = null
      $scope.sortDesc = null
      _getList = ->
        _getCount()
         #list
        recipes = []
        tabVal = $location.search().active
        active = 'Y'
        if tabVal is undefined or tabVal is 'undefined' or tabVal is '0' or tabVal is 0
          active = 'Y'
        else
          active = 'N'
        _clear()
        $http
          method: 'POST'
          url: '/api/uhkklp/cookbook/list-manage-by-categories'
          data: {
              "page": vm.page
              "pageSize": vm.pageSize
              "keyword": vm.keyword
              "active": active
              "categories": _getCategiriesDate()
              "sortName": $scope.sortName
              "sortDesc": $scope.sortDesc
            }
          headers:
            'Content-Type': 'application/json'
        .success (data) ->
          for cookbook,i in data['result']
            if cookbook['isSampleOpen'] is 'Y'
              data['result'][i]['isSampleOpen'] = $filter('translate')('recipe_list_sample_open_message')
            else if cookbook['isSampleOpen'] is 'N'
              data['result'][i]['isSampleOpen'] = $filter('translate')('recipe_list_sample_close_message')
            data['result'][i]['checked'] = false
          recipes = data['result']
          for recipe in recipes
            recipe.operations = [
              {
                text: 'recipe_list_operations_edit'
                title: 'edit'
                name: 'edit'
              }
              {
                text: 'recipe_list_operations_download'
                name: 'download'
                title: 'download'
              }
              {
                text: 'recipe_list_operations_delete'
                title: 'delete'
                name: 'delete'
              }
            ]
          vm.list.data = recipes
          vm.list.checkAll = false
          vm.list.hasLoading = false
        .error (data) ->
          notificationService.error 'recipe_list_get_count_error', false

      _getList()

      vm.activeList = true
      _activeList = (tabVal) ->
        if tabVal is 'undefined' or tabVal is undefined or tabVal is '0' or tabVal is 0
          vm.activeList = true
        else if tabVal is '1' or tabVal is 1
          vm.activeList = false
      _activeList($location.search().active)

      #clean search conditions
      _cleanSearchConditions = ->
        vm.isShow = false
        vm.keyword = ''
        for category in vm.categories
          for item in category.items
            item.check = false
        vm.conditions = []

      vm.changeTab = ->
        tabVal = $location.search().active
        _activeList(tabVal)
        _showPage(tabVal)
        vm.keyword = undefined
        vm.page = 1
        switch tabVal
          when '0', 0, '1', 1
            _cleanSearchConditions()
            _getList()
            _getCookingType()
            break
          when '2', 2
            _mtGetList()
            _cleanSearchConditions()
            break
          when '3', 3 then _getSampleList()
          when '4', 4
            _getTagList()
            _getCookingtypeList()
          when '5', 5 then _getProductList()

      vm.changePage = (currentPage) ->
        vm.page = currentPage
        _getList()

      vm.changeSize = (pageSize) ->
        vm.pageSize = pageSize
        vm.page = 1
        _getList()

      vm.searchKey = ->
        vm.page = 1
        _getList()
        vm.list.emptyMessage = 'search_no_data'

      $scope.import = ->
        modalInstance = $modal.open(
          templateUrl: '/build/modules/uhkklp/partials/edit/import.html'
          controller: 'wm.ctrl.uhkklp.edit.import'
          windowClass: 'setting-dialog'
        ).result.then( (data) ->
          if data is 'success'
            $location.url '/uhkklp/recipe?active=2'
            _showPage(2)
            angular.forEach vm.tabs, (tab) ->
              tab.active = false
              return
            vm.tabs[2].active = true
            _mtGetList()
        )

      vm.showConditions = ->
        vm.isShow = not vm.isShow

      _getCookingType = ->
        vm.tags = []
        vm.categories = []
        $http
          url: '/api/uhkklp/cooking-type/list'
          method: 'GET'
          headers:
            'Content-Type': 'application/x-www-form-urlencoded'
        .success (data) ->
          if (data['code'] is 200)
            for $data,i in data['result']
              if not ($data.category is '大類')
                vm.tags.push({"name": $data.name, "check": false})
              for category,j in vm.categories
                if category.name is $data.category
                  category.items.push({"name": $data.name, "check": false})
                  break
              if (j >= vm.categories.length or vm.categories.length is 0) and not ($data.category is '大類')
                vm.categories.push({
                  "id": vm.categories.length
                  "name": $data.category
                  "check": false
                  "items": [{
                    "name": $data.name
                    "check": false
                    }]
                  })
          else
            notificationService.error 'recipe_list_get_cook_type_error', false
        .error ->
          notificationService.error 'recipe_list_get_cook_type_error', false

      _getCookingType()

      vm.deleteCategory = (index, id) ->
        vm.conditions.splice index,1
        vm.categories[id].check = false
        for item in vm.categories[id].items
          item.check = false
        vm.page = 1
        _getList()
        vm.list.emptyMessage = 'search_no_data'

      _setConditions = ->
        vm.conditions = []
        for category,i in vm.categories
          conditionDate = []
          conditionDate.items = []
          for item in category.items
            if item.check
              conditionDate.name = category.name
              conditionDate.id = category.id
              conditionDate.items.push item.name
          if conditionDate.items.length > 0
            vm.conditions.push conditionDate

      vm.selectAllCatogories = (id) ->
        vm.categories[id].check = not vm.categories[id].check
        for item,i in vm.categories[id].items
          vm.categories[id].items[i].check = vm.categories[id].check

      vm.selectCategory = (id) ->
        for item,i in vm.categories[id].items
          if not item.check
            vm.categories[id].check = false
            break
        if i is vm.categories[id].items.length
          vm.categories[id].check = true

      vm.searchByCatogories = ->
        _setConditions()
        vm.page = 1
        _getList()
        vm.list.emptyMessage = 'search_no_data'

      vm.clear = ->
        _getCategiriesDate()
        for category in vm.categories
          category.check = false
          for item in category.items
            item.check = false

      ####↓↓↓ upload image page  ↓↓↓####

      _mtInit = ->
        $scope.mtPageSize = 10
        $scope.mtPage = 1
        $scope.mtTotalCount = 0
        $scope.mtSort = {}
        $scope.mtSearchKey = ''
        $scope.mtList =
          columnDefs: [
            {
              field: 'id'
              label: 'mt_recipe_group_id'
              sortable: true
              desc: true
            }
            {
              field: 'names'
              label: 'mt_recipe_group_names'
              type: 'html'
            }
            {
              field: 'count'
              label: 'mt_recipe_group_count'
            }
            {
              field: 'createdTime'
              label: 'mt_recipe_group_createdTime'
              sortable: true
              desc: true
            }
            {
              field: 'operator'
              label: 'mt_recipe_group_operator'
              sortable: true
              desc: true
            }
            {
              field: 'hasImages'
              label: 'mt_recipe_group_hasImages'
              sortable: true
              desc: true
            }
            {
              field: 'operations'
              label: 'operations'
              type: 'operation'
            }
          ]
          data: []
          hasLoading: true
          sortHandler: (colDef) ->
            if colDef.field is 'id'
              $scope.mtSort = {'_id': colDef.desc}
            else if colDef.field is 'createdTime'
              $scope.mtSort = {'createdTime': colDef.desc}
            else if colDef.field is 'operator'
              $scope.mtSort = {'operator': colDef.desc}
            else if colDef.field is 'hasImages'
              $scope.mtSort = {'hasImages': colDef.desc}
            _mtGetList()
            return
          exportHandler: (item) ->
            $scope.mtReqImgs = $scope.mtList.data[item].reqImgs
            $scope.mtBatchId = $scope.mtList.data[item].id
            $('#chooseImages').click()
            return
          deleteHandler: (item) ->
            url = '/api/uhkklp/cookbook-batch/delete'
            params =
              id: $scope.mtList.data[item].id
            $http
              url: url
              method: 'POST'
              headers:
                'Content-Type': 'application/json'
              data: params
            .success (data) ->
              notificationService.success 'mt_fm_delete_succ', false
              _mtGetList()
              return
            return
        _mtGetList()

      $scope.mtShowPagination = false
      _mtGetList = ->
        params =
          currentPage: $scope.mtPage
          pageSize: $scope.mtPageSize
          sort: $scope.mtSort
          searchKey: $scope.mtSearchKey
        $http
          url: '/api/uhkklp/cookbook-batch/get-list'
          method: 'POST'
          headers:
            'Content-Type': 'application/json'
          data: params
        .success (data) ->
          $scope.mtTotalCount = data.dataCount
          if $scope.mtTotalCount > 10
            $scope.mtShowPagination = true
          else
            $scope.mtShowPagination = false
          results = _mtFormatData data.result
          $scope.mtList.data = results
          $scope.mtList.hasLoading = false
          return
        return

      $scope.mtSearch = ->
        $scope.mtPage = 1
        _mtGetList()
        return

      $scope.mtChangePageSize = (pageSize) ->
        $scope.mtPage = 1
        $scope.mtPageSize = pageSize
        _mtGetList()
        return

      $scope.mtChangePage = (currentPage) ->
        $scope.mtPage = currentPage
        _mtGetList()
        return

      _mtFormatData = (datas) ->
        newDatas = []
        (
          tmp = {}
          tmp.id = data._id.$id
          createdTime = new Date data.createdTime.sec * 1000
          tmp.createdTime = $filter('date')(createdTime, 'yyyy-MM-dd HH:mm')
          tmp.operator = data.operator
          tmp.operations = [
            {
              name: 'export'
              title: 'mt_cookbook_img_import'
            }
            {
              name: 'delete'
              title: 'mt_delete'
            }
          ]
          if data.hasImages
            tmp.hasImages = $filter('translate')('mt_yes')
          else
            tmp.hasImages = $filter('translate')('mt_no')
          tmp.names = '<ul class = "mt_cookbook_namelist">'
          tmp.reqImgs = []
          (
            imgName = (cookbook.image.split '/').pop()
            tmp.names += '<li>' + cookbook.name + '&nbsp;-&nbsp;' + _getImgName(imgName) + '</li>'
            tmp.reqImgs.push _getImgName(imgName)
          ) for cookbook, j in data.cookbooks
          tmp.names += '</ul>'
          tmp.count = data.cookbooks.length
          newDatas.push tmp
        ) for data, i in datas
        newDatas

      _S4 = ->
        (((1 + Math.random()) * 0x10000) | 0).toString(16).substring 1
      _guid = ->
        _S4() + _S4() + _S4() + _S4() + _S4() + _S4()
      _getImgName = (name) ->
        name = name.replace('.jpg','')
        name = name.replace('.png','')
        name = name.replace('.gif','')
        return name
      $scope.imageTypes = ['image/jpeg', 'image/png', 'image/gif']
      $scope.onImagesSelect = (images) ->
        if not images or not images.length
          return
        $scope.loading = true

        $scope.mtActImgs = []
        $scope.mtQiniu = []
        for image in images
          $scope.mtActImgs.push _getImgName image.name
          if $.inArray(image.type, $scope.imageTypes) is -1
            $scope.loading = false
            if $('.message')
              ($ '.message') .hide()
            notificationService.error 'mt_tp_image_format_error', false
            return

        for reqName in $scope.mtReqImgs
          if $.inArray(reqName, $scope.mtActImgs) is -1
            $scope.loading = false
            if $('.message')
              ($ '.message') .hide()
            notificationService.error 'mt_tp_image_require_error', false
            return

        notificationService.info 'mt_fm_image_uploading', false
        $scope.mtImgMp = {}
        $scope.mtFaild = false
        $scope.imgKeys = []
        for image in images
          imageKey = {}
          imageKey.name = image.name
          name = _guid() + image.name.slice(image.name.lastIndexOf('.'))
          imageKey.key = name
          $scope.imgKeys.push imageKey
          $scope.mtImgMp[name] = image
          $http.get('/api/uhkklp/qiniu-token/generate?key=' + name).success (data) ->
            name = data.name
            token = data.token
            domain = data.domain
            uploadDomain = data.uploadDomain
            file = $scope.mtImgMp[name]

            $upload.upload(
              url: uploadDomain
              headers:
                'Content-Type': 'multipart/form-data'
              data:
                key: name
                token: token
              method: "POST"
              file: file
            ).progress((evt) ->
              return
            ).success((data, status, headers, config) ->
              return
            ).error ->
              $scope.mtFaild = true
              return
            return
        millisecond = 2000
        if images.length * 50 > 2000
          millisecond = images.length * 50
        $timeout(
          ->
            if $('.message')
              ($ '.message') .hide()
            if $scope.mtFaild
              notificationService.success 'mt_tp_image_upload_failed', false
            else
              $http.post('/api/uhkklp/cookbook-batch/image', {id: $scope.mtBatchId, imgKeys: $scope.imgKeys}).success((data) ->
                notificationService.success 'mt_tp_image_upload_succ', false
                _mtGetList()
                return
              )
            $scope.loading = false
            return
          , millisecond
        )
        return

      _mtInit()
      ####↑↑↑ upload image page  ↑↑↑####

      vm.isShowBatchBindTagDropdown = false
      vm.showBatchBindTag = ->
        for tag in vm.tags
          tag.check = false
        vm.isShowBatchBindTagDropdown = not vm.isShowBatchBindTagDropdown

      vm.tags = []

      vm.setModel = ->
        modalInstance = $modal.open(
          templateUrl: '/build/modules/uhkklp/partials/edit/tags.html'
          controller: 'wm.ctrl.uhkklp.edit.tags'
          windowClass: 'setting-dialog'
          resolve:
            modalData: ->
              vm.tags or []
        ).result.then( (data) ->
          _getCookingType()
        )

      vm.checkExistTag = (name) ->
        formTip = ''
        vm.tags = [] if not vm.tags?
        if name
          for tag in vm.tags
            if tag.name is name
              formTip = 'exist_tag'
              break
          if name.length > 20
            formTip = 'tag_character_tip'
        else
          formTip = 'required_tip'
        return formTip

      vm.createTag = ->
        if vm.checkExistTag vm.newTag
          return
        vm.tags.push ({"name": vm.newTag,"check": false})
        for category,i in vm.categories
          if category.name is "標簽"
            category.items.push ({"name": vm.newTag,"check": false})
            break
        if i >= vm.categories.length
          vm.categories.push ({
              "id": i
              "check": false
              "name": "標簽"
              "items": [{"name": vm.newTag,"check": false}]
            })
        $http
          url: '/api/uhkklp/cooking-type/save'
          method: 'POST'
          headers:
            'Content-Type': 'application/json'
          data: {
              "name": vm.newTag
              "id": localStorageService.getItem(config.keys.currentUser).id
              "category": "標簽"
            }
        .success (data) ->
          if data['code'] is 200
            vm.newTag = ''
          else if data['msg'] is "not login"
            $location.url '/site/login'
            notificationService.error 'recipe_go_to_login_tip', false
          else if data['msg'] is "Name is required"
            notificationService.error 'recipe_edit_save_error', false
        .error ->
          notificationService.error 'recipe_edit_save_error', false

      vm.saveTag = ->
        update = {}
        update.operation = 'tags'
        update.cookbookId = vm.cacheCheckRows
        update.tags = []
        for tag in vm.tags
          if tag.check
            update.tags.push tag.name
        _recipeBatch update
        vm.isShowBatchBindTagDropdown = false

      #       sample
      #pagination
      vm.samplePageSize = 10
      vm.samplePage = 1
      #table
      vm.sampleList =
        columnDefs: [
          {
            field: 'id'
            label: 'sample_list_sample_id'
          }
          {
            field: 'name'
            label: 'sample_list_sample_name'
          }
          {
            field: 'updatedAt'
            label: 'recipe_list_update_date'
            sortable: true
          }
          {
            field: 'operator'
            label: 'recipe_list_operator'
            sortable: true
          }
          {
            field: 'operations'
            label: 'operations'
            type: 'operation'
          }
        ]
        data: []
        hasLoading: true

        sortHandler: (colDef) ->
          if colDef.field?
            $scope.sortName = colDef.field
            if colDef.desc
              $scope.sortDesc = 'ASC'
            else
              $scope.sortDesc = 'DESC'
          _getSampleList()

        editHandler: (item) ->
          $location.url EDIT_SAMPLE_URL + '/' + vm.sampleList.data[item].id

        deleteHandler: (item) ->
          $http
            method: 'POST'
            url: '/api/uhkklp/sample/delete'
            data: {
                "sampleId": vm.sampleList.data[item].id
              }
            headers:
              'Content-Type': 'application/json'
          .success (data) ->
            if data['code'] is 200
              notificationService.success 'recipe_list_delete_success_tip', false
              _getSampleList()
            else
              notificationService.error 'recipe_list_delete_failed_tip',false
          .error (data) ->
            notificationService.error 'recipe_list_delete_failed_tip',false

      vm.showSamplePagination = false
      _getSampleCount = ->
        $http
          method: 'GET'
          url: '/api/uhkklp/sample/count-list?keyword=' + vm.sampleKeyword
          headers:
            'Content-Type': 'application/x-www-form-urlencoded'
        .success (data) ->
          vm.sampleTotalCount = data['result']
          if vm.sampleTotalCount > 10
            vm.showSamplePagination = true
          else
            vm.showSamplePagination = false

      $scope.sortName = null
      $scope.sortDesc = null
      _getSampleList = ->
        _getSampleCount()
        samples = []
        $url = '/api/uhkklp/sample/list?page=' + vm.samplePage + '&pageSize=' + vm.samplePageSize + '&keyword=' + vm.sampleKeyword
        if $scope.sortName?
          $url = $url + '&sortName=' + $scope.sortName + '&sortDesc=' + $scope.sortDesc
        $http
          method: 'GET'
          url: $url
          headers:
            'Content-Type': 'application/x-www-form-urlencoded'
        .success (data) ->
          sampleList = data['result']
          for sample in sampleList
            operations = [
              {
                name: 'edit'
              }, {
                name: 'delete'
              }
            ]
            operations[0].disable = false
            operations[1].disable = false
            sample.operations = operations
            if sample.usedNumber > 0
              operations[1].disable = true
          vm.sampleList.data = data['result']
          vm.sampleList.hasLoading = false

      _getSampleList()

      vm.changeSamplePage = (currentPage) ->
        vm.samplePage = currentPage
        _getSampleList()

      vm.changeSampleSize = (pageSize) ->
        vm.samplePageSize = pageSize
        vm.samplePage = 1
        _getSampleList()

      vm.searchSampleKey = ->
        vm.samplePage = 1
        _getSampleList()
        vm.sampleList.emptyMessage = 'search_no_data'

      vm.downloadSampleRecord = ->
        if not vm.enableExport
          notificationService.warning 'recipe_list_downloading', false
          return
        params = {}
        exportService.export 'recipe_list_download_all_sample', config.resources.exportAllSample, params, false
        vm.enableExport = false

      #       cookingtype
      #pagination
      vm.cookingtypePageSize = 10
      vm.cookingtypePage = 1
      #table
      vm.cookingtypeList =
        columnDefs: [
          {
            field: 'id'
            label: 'recipe_list_cookingtype_id'
          }
          {
            field: 'name'
            label: 'recipe_list_cookingtype_name'
          }
          {
            field: 'updatedAt'
            label: 'recipe_list_update_date'
            sortable: true
          }
          {
            field: 'operator'
            label: 'recipe_list_operator'
            sortable: true
          }
          {
            field: 'operations'
            label: 'operations'
            type: 'operation'
          }
        ]
        data: []
        hasLoading: true

        sortHandler: (colDef) ->
          if colDef.field?
            $scope.sortName = colDef.field
            if colDef.desc
              $scope.sortDesc = 'ASC'
            else
              $scope.sortDesc = 'DESC'
          _getCookingtypeList()

        editHandler: (item) ->
          vm.addCookingtype(vm.cookingtypeList.data[item].id)

        deleteHandler: (item) ->
          $http
            method: 'POST'
            url: '/api/uhkklp/cooking-type/delete'
            data: {
              id: vm.cookingtypeList.data[item].id
            }
            headers:
              'Content-Type': 'application/x-www-form-urlencoded'
          .success (data) ->
            if data['code'] is 1309
              $location.url '/site/login'
              notificationService.warning 'recipe_go_to_login_tip', false
            else if data['code'] is 200
              notificationService.success 'recipe_list_delete_success_tip', false
              _getCookingtypeList()
            else
              notificationService.error 'recipe_list_delete_failed_tip',false
          .error (data) ->
            notificationService.error 'recipe_list_delete_failed_tip',false

        tagHandler: (item, $event) ->
          _getTagList(vm.cookingtypeList.data[item].name)
          vm.isUpdateTags = true
          $scope.cookingtypeId = vm.cookingtypeList.data[item].id
          vm.tagStyle =
            top: $($event.target).offset().top - 17

      $scope.cookingtypeId = ''
      vm.updateTag = ->
        updatedDate = {
            "categoryId": $scope.cookingtypeId
            "tags": $scope.tags
          }
        $http
          url: '/api/uhkklp/cooking-type/update'
          method: 'POST'
          headers:
            'Content-Type': 'application/json'
          data: updatedDate
        .success (data) ->
          if data['code'] is 200
            notificationService.success 'cookingtype_update_success_tip', false
          else
            notificationService.error 'cookingtype_update_error_tip', false
        .error ->
          notificationService.error 'cookingtype_update_error_tip', false
        vm.isUpdateTags = false

      vm.cancelTag = ->
        vm.isUpdateTags = false

      $scope.tags = []
      $scope.loadingTag = false
      _getTagList = (categoryName) ->
        $scope.tags = []
        $scope.loadingTag = false
        $http
          url: '/api/uhkklp/cooking-type/list'
          method: 'GET'
          headers:
            'Content-Type': 'application/x-www-form-urlencoded'
        .success (data) ->
          if (data['code'] is 200)
            for $data,i in data['result']
              if not ($data.category is '大類' or $data.category is '固定分類')
                check = false
                if $data.category is categoryName
                  check = true
                else
                  check = false
                $scope.tags.push({
                    "name": $data.name
                    "check": check
                    "id": $data.id
                    "category": $data.category
                  })
            $scope.loadingTag = true
          else
            notificationService.error 'recipe_list_get_cook_type_error', false
        .error ->
          notificationService.error 'recipe_list_get_cook_type_error', false

      vm.showCookingtypePagination = false
      _getCookingtypeCount = ->
        $http
          method: 'GET'
          url: '/api/uhkklp/cooking-type/count-list?keyword=' + vm.cookingtypeKeyword
          headers:
            'Content-Type': 'application/x-www-form-urlencoded'
        .success (data) ->
          vm.cookingtypeTotalCount = data['result']
          if vm.cookingtypeTotalCount > 10
            vm.showCookingtypePagination = true
          else
            vm.showCookingtypePagination = false

      $scope.sortName = null
      $scope.sortDesc = null
      _getCookingtypeList = ->
        _getCookingtypeCount()
        cookingtypes = []
        $url = '/api/uhkklp/cooking-type/list-category?page=' + vm.cookingtypePage + '&pageSize=' + vm.cookingtypePageSize + '&keyword=' + vm.cookingtypeKeyword
        if $scope.sortName?
          $url = $url + '&sortName=' + $scope.sortName + '&sortDesc=' + $scope.sortDesc
        $http
          url: $url
          method: 'GET'
          headers:
            'Content-Type': 'application/x-www-form-urlencoded'
        .success (data) ->
          if data['code'] is 200
            for one in data['result']
              one.operations = [
                {
                  text: 'recipe_list_operations_edit'
                  title: 'edit'
                  name: 'edit'
                }
                {
                  text: 'recipe_list_operations_edit'
                  title: 'tag'
                  name: 'tag'
                }
                {
                  text: 'recipe_list_operations_delete'
                  title: 'delete'
                  name: 'delete'
                }
              ]
            vm.cookingtypeList.data = data['result']
          else
            notificationService.error 'recipe_list_get_cook_type_error', false
          vm.cookingtypeList.hasLoading = false
        .error ->
          notificationService.error 'recipe_list_get_cook_type_error', false
          vm.cookingtypeList.hasLoading = false

      _getCookingtypeList()

      vm.changeCookingtypePage = (currentPage) ->
        vm.cookingtypePage = currentPage
        _getCookingtypeList()

      vm.changeCookingtypeSize = (pageSize) ->
        vm.cookingtypePageSize = pageSize
        vm.cookingtypePage = 1
        _getCookingtypeList()

      vm.searchCookingtypeKey = ->
        vm.cookingtypePage = 1
        _getCookingtypeList()
        vm.cookingtypeList.emptyMessage = 'search_no_data'

      vm.addCookingtype = (id) ->
        modalInstance = $modal.open(
          templateUrl: '/build/modules/uhkklp/partials/edit/cookingtype/recipe.html'
          controller: 'wm.ctrl.uhkklp.edit.cookingtype.recipe'
          windowClass: 'setting-dialog'
          resolve:
            modalData: ->
              "id": id
        ).result.then( (data) ->
          if data is 'success'
            notificationService.success 'recipe_edit_save_success', false
            $location.url '/uhkklp/recipe?active=4'
            _showPage(4)
            angular.forEach vm.tabs, (tab) ->
              tab.active = false
              return
            vm.tabs[4].active = true
            _getCookingtypeList()
          else if data is 'login'
            notificationService.warning 'recipe_go_to_login_tip', false
            $location.url 'site/login'
          else if data is 'close'
            return
        )

      # FifthTab: product list
      $scope.product = {}
      $scope.product.pageSize = 10
      $scope.product.page = 1
      $scope.product.totalCount = 0
      $scope.product.list = {
        columnDefs: [
          {
            field: 'name'
            label: 'mt_producttb_name'
          }
          {
            field: 'tag'
            label: 'mt_producttb_tag'
          }
          {
            field: 'url'
            label: 'mt_producttb_url'
          }
        ]
        data: []
        hasLoading: true
      }

      _getProductList = ->
        params =
          currentPage: $scope.product.page
          pageSize: $scope.product.pageSize
        $http
          url: '/api/uhkklp/product/get-list'
          method: 'POST'
          headers:
            'Content-Type': 'application/json'
          data: params
        .success (data) ->
          $scope.product.totalCount = data.dataCount
          # results = _formatResult data.product
          $scope.product.list.data = data.product
          $scope.product.list.hasLoading = false

          return
        return
        return

      $scope.product.changePage = (currentPage) ->
        $scope.product.page = currentPage
        _getProductList()
        return

      $scope.product.changeSize = (pageSize) ->
        $scope.product.page = 1
        $scope.product.pageSize = pageSize
        _getProductList()
        return

      _getProductList()

      vm

  ]
