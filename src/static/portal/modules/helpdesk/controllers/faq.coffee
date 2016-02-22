angular.module 'faq', ['pasvaz.bindonce']
.controller 'FaqCtrl', [
  '$scope'
  '$http'
  ($scope, $http) ->
    vm = $scope

    vm.openAnswer = (faq, $event) ->
      if faq.status is 'open'
        faq.status = 'hide'
        $("#answer_#{faq.id}").hide()
      else
        faq.status = 'open'
        $("#answer_#{faq.id}").show()
      return

    vm.selectMenu = (menuName) ->
      location.hash = menuName
      for menu in vm.data
        if menu.name is menuName then menu.active = true else menu.active = false

    _getFaqs = (category, accountId) ->
      queryData =
        accountId: accountId
        category: if category then category else ''

      request =
        method: 'GET'
        url: '/api/helpdesk/faq/get-faqs'
        params: queryData

      $http(request)
        .success( (faqs) ->
          if not category then vm.data = faqs else vm.data = [faqs]
          _initActive()
          _initDefaultCategory()
        )

    _initActive = ->
      for key, menu of vm.data
        if key is '0' then menu.active = true else menu.active = false

    _initDefaultCategory = ->
      for menu, index in vm.data
        menu.name = "默认分类" if menu.isDefault

    _init = ->
      category = $('#category').val()
      accountId = $('#accountId').val()
      _getFaqs category, accountId

    _init()
]
.filter 'textareaBr', ->
  (input) ->
    input.replace(/\n/ig, "<br />")
