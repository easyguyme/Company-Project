define [
  'wm/app'
  'wm/config'
], (app, config) ->
  app.registerController 'wm.ctrl.helpdesk.wiki', [
    'restService'
    '$modal'
    '$scope'
    'notificationService'
    'userService'
    '$location'
    '$filter'
    (restService, $modal, $scope, notificationService, userService, $location, $filter) ->
      vm = this
      vm.pageSize = $location.search().pageSize or 10
      vm.currentPage = $location.search().currentPage or 1
      vm.totalCount = 0
      vm.isEditCategory = false
      basicLink = 'http://' + location.hostname + '/faq?'

      _init = ->
        vm.FAQs = []
        vm.currentCategoryId = ''
        userInfo = userService.getInfo()
        vm.accountId = userInfo.accountId
        vm.pageLink = basicLink + "accountId=#{vm.accountId}"
        _loadPage()

        vm.breadcrumb = [
          'wiki'
        ]

        vm.FAQList =
        {
          columnDefs: [
            {
              field: 'question'
              label: 'helpdesk_wiki_question'
            }, {
              field: 'answer'
              label: 'helpdesk_wiki_answer'
              cellClass: 'default-td'
            }, {
              field: 'operations'
              label: 'operations'
              type: 'operation'
            }
          ],
          data: vm.FAQs
          deleteTitle: 'helpdesk_wiki_delete_confirm'

          deleteHandler: (index) ->
            restService.del config.resources.wikiFaq + '/' + vm.FAQs[index].id, (data) ->
              _loadPage()
              notificationService.success 'helpdesk_faq_del_successfully'

          editHandler: (index) ->
            data = vm.FAQs[index]
            param =
              categories: vm.categories
              data: data
              isEdit: true
            modalInstance = $modal.open(
              templateUrl: 'wikiDetail.html'
              controller: 'wm.ctrl.helpdesk.wikiDetail'
              windowClass: 'wiki-dialog'
              resolve:
                modalData: -> param
            ).result.then( (data) ->
              if data.isUpdated
                _loadPage()
            )
        }

        vm.operation = [
          {
            name: 'edit'
          }
          {
            name: 'delete'
          }
        ]

      vm.create = ->
        param =
          isEdit: false
          categories: vm.categories
          isCategoriesChanged: vm.isCategoriesChanged
        modalInstance = $modal.open(
          templateUrl: 'wikiDetail.html'
          controller: 'wm.ctrl.helpdesk.wikiDetail'
          windowClass: 'wiki-dialog'
          resolve:
            modalData: -> param
        ).result.then( (data) ->
          if data.isUpdated
            _loadPage()
        )

      vm.changePage = (currentPage) ->
        vm.currentPage = currentPage
        _loadPage()

      vm.changeSize = (pageSize) ->
        vm.pageSize = pageSize
        vm.currentPage = 1
        _loadPage()

      vm.editCategory = ->
        vm.isEditCategory = not vm.isEditCategory

      _deleteCategory = (idx) ->
        restService.del config.resources.wikiRemoveCategory + '/' + vm.categories[idx].id, (data) ->
          if vm.currentCategoryId is vm.categories[idx].id
            vm.currentCategoryId = ''
            vm.pageLink = encodeURI(basicLink + "accountId=#{vm.accountId}")
            _loadPage()
          vm.categories.splice(idx, 1)
          notificationService.success 'helpdesk_category_delete_successfully'

      vm.deleteCategory = (index, $event) ->
        notificationService.confirm $event,{
          submitCallback: _deleteCategory
          params: [index]
        }

      vm.showCategoryFaqs = (idx) ->
        if idx is -1
          vm.currentCategoryId = ''
          vm.pageLink = encodeURI(basicLink + "accountId=#{vm.accountId}")
        else
          vm.currentCategoryId = vm.categories[idx].id
          vm.pageLink = encodeURI(basicLink + "category=#{encodeURIComponent(vm.categories[idx].category)}" + "&accountId=#{vm.accountId}")
        _loadPage()

      _translateToTableParam = (data) ->
        vm.FAQs.length = 0
        if data.length > 0
          for item, i in data
            vm.FAQs[i] = {}
            vm.FAQs[i] = item
            vm.FAQs[i].operations = vm.operation

      _loadPage = ->
        condition = {
          'per-page': vm.pageSize
          'page': vm.currentPage
          'orderBy': {'updatedAt': 'desc'}
          'where': {'isDeleted': false}
        }

        if vm.currentCategoryId isnt ''
          condition.where.faqCategoryId = vm.currentCategoryId
        restService.get config.resources.wikiFaqs, condition, (faqList) ->
          if faqList.items
            vm.currentPage = faqList._meta.currentPage
            vm.totalCount = faqList._meta.totalCount
            vm.pageCount = faqList._meta.pageCount
            _translateToTableParam faqList.items
        _loadCategories()

      _loadCategories = ->
        restService.get config.resources.wikiCategory, (categories) ->
          vm.categories = []
          if categories.length > 0
            for value, i in categories
              vm.categories[i] = {}
              vm.categories[i].id = value.id
              vm.categories[i].name = if value.isDefault then $filter('translate')(value.name) else value.name
              vm.categories[i].isDefault = value.isDefault
              vm.categories[i].category = value.name # this is used in baseLink
          else
            return

      _init()

      $scope.$on 'faq-categories-updated', ->
        _loadCategories()

      vm
  ]


  .registerController 'wm.ctrl.helpdesk.wikiDetail', [
    '$scope'
    '$rootScope'
    'restService'
    'modalData'
    '$modalInstance'
    'notificationService'
    ($scope, $rootScope, restService, modalData, $modalInstance, notificationService) ->
      vm = $scope
      vm.isEdit = modalData.isEdit
      vm.categories = modalData.categories
      vm.addedCategory = ''
      vm.faq =
        optionalCategories: []
        data: {}

      _init = ->
        _initCategories()
        if not vm.isEdit
          vm.detailTitle = 'helpdesk_wiki_new'
        else
          vm.detailTitle = 'helpdesk_wiki_edit'
          vm.oldFaq = modalData.data
          vm.faq.data.question = vm.oldFaq.question
          vm.faq.data.answer = vm.oldFaq.answer
          vm.faq.data.selectedCategory = vm.oldFaq.faqCategoryId
        vm.faq.data.isUpdated = false

      vm.hideModal = ->
        $modalInstance.close vm.faq.data

      vm.operate = ->
        param =
          question: vm.faq.data.question
          answer: vm.faq.data.answer
          faqCategoryId: vm.faq.data.selectedCategory
        if vm.isEdit
          if _isEqual param, vm.oldFaq
            vm.hideModal()
          else
            restService.put config.resources.wikiFaq + '/' + vm.oldFaq.id, param, (data) ->
              vm.faq.data.isUpdated = true
              notificationService.success 'helpdesk_faq_edit_successfully'
              vm.hideModal()
        else
          restService.post config.resources.wikiFaqs, param, (data) ->
            vm.faq.data.isUpdated = true
            notificationService.success 'helpdesk_faq_add_successfully'
            vm.hideModal()

      vm.addCategory = (value) ->
        vm.faq.isAddCategory = false
        if not _isUnique value
          notificationService.error 'helpdesk_cannot_add_same_category', false
          return
        param =
          category: value
        restService.post config.resources.wikiAddCategory, param, (faqCategory) ->
          newCategory =
            id: faqCategory.id
            name: value
          vm.faq.optionalCategories.push newCategory
          vm.faq.data.selectedCategory = faqCategory.id
          vm.addedCategory = ''
          $rootScope.$broadcast 'faq-categories-updated'
          notificationService.success 'helpdesk_category_add_successfully'

      _initCategories = ->
        for value, i in vm.categories
          vm.faq.optionalCategories[i] = {}
          vm.faq.optionalCategories[i].id = value.id
          vm.faq.optionalCategories[i].name = value.name
          if i is 0
            vm.faq.data.selectedCategory = value.id

      _isEqual = (newValue, oldFaq) ->
        flag = false
        oldValue =
          question: vm.oldFaq.question
          answer: vm.oldFaq.answer
          faqCategoryId: vm.oldFaq.faqCategoryId
        if angular.equals newValue, oldValue
          flag = true
        return flag

      _isUnique = (newValue) ->
        flag = true
        for value in vm.categories
          if value.name is newValue
            flag = false
            return flag
        return flag

      _init()

      vm
  ]
