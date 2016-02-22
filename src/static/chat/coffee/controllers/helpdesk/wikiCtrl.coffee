define [
  'core/coreModule'
  'chat/config'
], (app, config) ->
  app.controller 'wm.ctrl.helpdesk.tabs.wiki', [
    '$scope'
    'debounceService'
    'userService'
    'restService'
    '$filter'
    ($scope, debounceService, userService, restService, $filter) ->
      vm = $scope
      basicLink = 'http://' + location.hostname + '/faq?'
      vm.enableWiki = true

      vm.chooseCategory = (idx) ->
        if idx is -1
          vm.selectedCategory = 'helpdesk_wiki_all'
          vm.currentLink = encodeURI(basicLink + "accountId=#{vm.accountId}")
        else
          vm.selectedCategory = vm.categories[idx].name
          categoryText = encodeURIComponent vm.categories[idx].category
          vm.currentLink = encodeURI(basicLink + "category=#{categoryText}&accountId=#{vm.accountId}")
        vm.pageSize = 100
        vm.currentPage = 0
        vm.categoryIndex = idx
        vm.FAQs = []
        getFaqList()

      vm.putMessage = (message) ->
        vm.$emit 'useKnowledge', message

      initWiki = ->
        vm.keyword = ""
        vm.categories = []
        vm.FAQs = []
        vm.categoryIndex = -1
        vm.selectedCategory = 'helpdesk_wiki_all'
        userInfo = userService.getInfo()
        vm.accountId = userInfo.accountId
        vm.currentLink = basicLink + "accountId=#{vm.accountId}"
        initPagination()
        getCategoryList()
        getFaqList()

      initFAQs = (items) ->
        if items.length > 0
          for item in items
            item.isShowAnswer = false

      initPagination = ->
        vm.pagination =
          currentPage: 1
          pageSize: 8
          totalItems: 0
          pageCount: 0
          changePage: (currentPage) ->
            vm.pagination.currentPage = currentPage
            getFaqList()

      getCategoryList = ->
        restService.get config.resources.faqCategory, (categories) ->
          if categories
            for value, i in categories
              vm.categories[i] = {}
              vm.categories[i].id = value.id
              vm.categories[i].name = if value.isDefault then $filter('translate')(value.name) else value.name
              vm.categories[i].isDefault = value.isDefault
              vm.categories[i].category = value.name # this is used in baseLink

      getFaqList = ->
        if vm.pagination.currentPage <= vm.pagination.pageCount or vm.pagination.pageCount is 0
          condition = {
            'per-page': vm.pagination.pageSize
            'page': vm.pagination.currentPage
          }

          if vm.categoryIndex isnt -1
            condition.faqCategoryId = vm.categories[vm.categoryIndex].id

          restService.get config.resources.faqs, condition, (data) ->
            if data.faqs
              vm.pagination.currentPage = data.currentPage
              vm.pagination.totalItems = data.totalCount
              vm.pagination.pageCount = data.pageCount
              initFAQs data.faqs
              vm.FAQs = data.faqs

      $('.faq-detail-body').scroll debounceService.callback( (elem) ->
        bodyHeight = $(elem).find('.faq-detail-body').height()
        listHeight = $(elem).find('.faq-list-scroll').outerHeight()
        scrollHeight = $(elem).find('.faq-detail-body').scrollTop()
        result = listHeight - bodyHeight - scrollHeight

        if result <= 2
          $timeout( ->
            getFaqList vm.categoryIndex
          , 400)

      , 200)

      initWiki()

      vm.$on 'changeCurrentClientOnlineStatus', (event, status) ->
        vm.enableWiki = status

      vm.$emit 'needOnlineStatus'

      vm
  ]
